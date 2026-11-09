<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('User_model');
		$this->load->model('Conversation_model');
		$this->load->model('Message_model');
		$this->load->model('Typing_model');
		$this->load->model('Friend_model');

		if (!$this->session->userdata('user_id')) {
			$this->_json(array('ok' => FALSE, 'error' => 'Unauthorized'), 401);
		}
	}

	public function conversations()
	{
		$me = current_user_id();
		$this->User_model->touch_last_seen($me);

		if ($this->input->method() === 'get') {
			$me_user = $this->User_model->get_by_id($me);
			$is_system = is_system_user($me_user);

			$visible_ids = array();
			$people_rows = array();
			if ($is_system) {
				$people_rows = $this->User_model->get_all_except($me);
			} else {
				$people_rows = $this->Friend_model->get_friends($me);
			}
			foreach ($people_rows as $u) {
				$visible_ids[] = (int) $u['id'];
			}

			$rows = $this->Conversation_model->list_for_user($me);
			$conversations = array();
			$partner_ids = array();

			foreach ($rows as $row) {
				$row['conversation_id'] = (int) $row['conversation_id'];
				$row['user_id'] = (int) $row['user_id'];
				if (!$is_system && !in_array($row['user_id'], $visible_ids, TRUE)) {
					continue;
				}
				$row['unread_count'] = (int) $row['unread_count'];
				$row['online'] = is_user_online($row['last_seen']);
				$row['initials'] = avatar_initials($row['display_name']);
				$row['avatar_url'] = user_avatar_url($row);
				if (!$is_system) {
					unset($row['username']);
				}
				$conversations[] = $row;
				$partner_ids[] = $row['user_id'];
			}

			$users = array();
			foreach ($people_rows as $u) {
				$uid = (int) $u['id'];
				$item = array(
					'id' => $uid,
					'display_name' => $u['display_name'],
					'avatar_color' => $u['avatar_color'],
					'avatar_url' => user_avatar_url($u),
					'last_seen' => $u['last_seen'],
					'online' => is_user_online($u['last_seen']),
					'initials' => avatar_initials($u['display_name']),
					'has_conversation' => in_array($uid, $partner_ids, TRUE),
				);
				if ($is_system) {
					$item['username'] = $u['username'];
				}
				$users[] = $item;
			}

			$me_payload = array(
				'id' => (int) $me_user['id'],
				'display_name' => $me_user['display_name'],
				'avatar_color' => $me_user['avatar_color'],
				'avatar_url' => user_avatar_url($me_user),
				'user_type' => normalize_user_type(isset($me_user['user_type']) ? $me_user['user_type'] : 'normal'),
				'initials' => avatar_initials($me_user['display_name']),
			);
			if ($is_system) {
				$me_payload['username'] = $me_user['username'];
			}

			$this->_json(array(
				'ok' => TRUE,
				'conversations' => $conversations,
				'users' => $users,
				'me' => $me_payload,
			));
		}

		if ($this->input->method() === 'post') {
			$input = json_decode($this->input->raw_input_stream, TRUE);
			$other_id = isset($input['user_id']) ? (int) $input['user_id'] : 0;
			if ($other_id <= 0 || $other_id === $me) {
				$this->_json(array('ok' => FALSE, 'error' => 'Invalid user'), 422);
			}
			if (!$this->User_model->get_by_id($other_id)) {
				$this->_json(array('ok' => FALSE, 'error' => 'User not found'), 404);
			}
			if (!can_message_user($me, $other_id)) {
				$this->_json(array('ok' => FALSE, 'error' => 'You can only chat with friends'), 403);
			}
			$conversation_id = $this->Conversation_model->get_or_create($me, $other_id);
			$this->_json(array('ok' => TRUE, 'conversation_id' => $conversation_id));
		}

		$this->_json(array('ok' => FALSE, 'error' => 'Method not allowed'), 405);
	}

	public function messages()
	{
		$me = current_user_id();
		$this->User_model->touch_last_seen($me);

		if ($this->input->method() === 'get') {
			$conversation_id = (int) $this->input->get('conversation_id');
			$after_id = (int) $this->input->get('after_id');

			if ($conversation_id <= 0 || !$this->Conversation_model->user_in_conversation($conversation_id, $me)) {
				$this->_json(array('ok' => FALSE, 'error' => 'Conversation not found'), 404);
			}

			// Opening a deleted chat restores it to the list (WhatsApp-style).
			$this->Conversation_model->restore_chat($conversation_id, $me);

			$rows = $this->Message_model->list_messages($conversation_id, $me, $after_id);
			$messages = array();
			foreach ($rows as $msg) {
				$messages[] = $this->_format_message($msg, $me);
			}

			// Opening the chat = delivered + seen (WhatsApp-style).
			$this->Message_model->mark_delivered($conversation_id, $me);
			$this->Message_model->mark_read($conversation_id, $me);

			$receipts = array();
			foreach ($this->Message_model->list_receipts($conversation_id, $me) as $row) {
				$receipts[] = $this->_format_receipt($row);
			}

			$deleted = array();
			foreach ($this->Message_model->list_deleted_everyone($conversation_id, $me) as $row) {
				$deleted[] = array(
					'id' => (int) $row['id'],
					'deleted_everyone' => TRUE,
				);
			}

			$this->_json(array(
				'ok' => TRUE,
				'messages' => $messages,
				'receipts' => $receipts,
				'deleted' => $deleted,
			));
		}

		if ($this->input->method() === 'post') {
			$content_type = (string) $this->input->server('CONTENT_TYPE');
			$is_multipart = stripos($content_type, 'multipart/form-data') !== FALSE;

			$location_lat = NULL;
			$location_lng = NULL;

			if ($is_multipart) {
				$conversation_id = (int) $this->input->post('conversation_id');
				$body = trim((string) $this->input->post('body'));
				if ($this->input->post('location_lat') !== NULL && $this->input->post('location_lng') !== NULL) {
					$location_lat = (float) $this->input->post('location_lat');
					$location_lng = (float) $this->input->post('location_lng');
				}
			} else {
				$input = json_decode($this->input->raw_input_stream, TRUE);
				$conversation_id = isset($input['conversation_id']) ? (int) $input['conversation_id'] : 0;
				$body = isset($input['body']) ? trim((string) $input['body']) : '';
				if (isset($input['location_lat'], $input['location_lng'])) {
					$location_lat = (float) $input['location_lat'];
					$location_lng = (float) $input['location_lng'];
				}
			}

			if ($conversation_id <= 0 || !$this->Conversation_model->user_in_conversation($conversation_id, $me)) {
				$this->_json(array('ok' => FALSE, 'error' => 'Conversation not found'), 404);
			}
			if (mb_strlen($body) > 2000) {
				$this->_json(array('ok' => FALSE, 'error' => 'Message must be at most 2000 characters'), 422);
			}

			$has_location = FALSE;
			if ($location_lat !== NULL && $location_lng !== NULL) {
				if ($location_lat < -90 || $location_lat > 90 || $location_lng < -180 || $location_lng > 180) {
					$this->_json(array('ok' => FALSE, 'error' => 'Invalid location coordinates'), 422);
				}
				$has_location = TRUE;
			}

			$attachment = $this->_store_attachment($is_multipart);
			if ($attachment === FALSE) {
				return;
			}

			if ($body === '' && empty($attachment) && !$has_location) {
				$this->_json(array('ok' => FALSE, 'error' => 'Message, file, or location is required'), 422);
			}

			$duration_seconds = NULL;
			if ($is_multipart && $this->input->post('duration_seconds') !== NULL && $this->input->post('duration_seconds') !== '') {
				$duration_seconds = (float) $this->input->post('duration_seconds');
				if ($duration_seconds < 0 || $duration_seconds > 600) {
					$duration_seconds = NULL;
				} else {
					$duration_seconds = round($duration_seconds, 1);
				}
			}

			$data = array(
				'conversation_id' => $conversation_id,
				'sender_id' => $me,
				'body' => $body !== '' ? $body : ($has_location ? '📍 Shared current location' : ''),
			);
			if (!empty($attachment)) {
				$data = array_merge($data, $attachment);
				if ($duration_seconds !== NULL && strpos((string) $attachment['file_type'], 'audio/') === 0) {
					$data['duration_seconds'] = $duration_seconds;
				}
			}
			if ($has_location) {
				$data['location_lat'] = number_format($location_lat, 7, '.', '');
				$data['location_lng'] = number_format($location_lng, 7, '.', '');
			}

			$id = $this->Message_model->create($data);
			$this->Typing_model->set_status($conversation_id, $me, 'idle');
			$msg = $this->_format_message($this->Message_model->get_by_id($id), $me, TRUE);

			$this->_json(array('ok' => TRUE, 'message' => $msg));
		}

		$this->_json(array('ok' => FALSE, 'error' => 'Method not allowed'), 405);
	}

	public function message_played()
	{
		$me = current_user_id();
		$this->User_model->touch_last_seen($me);

		if ($this->input->method() !== 'post') {
			$this->_json(array('ok' => FALSE, 'error' => 'Method not allowed'), 405);
		}

		$input = json_decode($this->input->raw_input_stream, TRUE);
		$message_id = isset($input['message_id']) ? (int) $input['message_id'] : 0;
		if ($message_id <= 0) {
			$this->_json(array('ok' => FALSE, 'error' => 'Invalid message'), 422);
		}

		$row = $this->db->get_where('messages', array('id' => $message_id))->row_array();
		if (!$row || !$this->Conversation_model->user_in_conversation((int) $row['conversation_id'], $me)) {
			$this->_json(array('ok' => FALSE, 'error' => 'Message not found'), 404);
		}
		if ((int) $row['sender_id'] === (int) $me) {
			$this->_json(array('ok' => FALSE, 'error' => 'Cannot mark own voice as played'), 422);
		}
		if (strpos((string) $row['file_type'], 'audio/') !== 0) {
			$this->_json(array('ok' => FALSE, 'error' => 'Not a voice message'), 422);
		}

		$this->Message_model->mark_played($message_id, $me);
		$updated = $this->Message_model->get_by_id($message_id);
		$this->_json(array(
			'ok' => TRUE,
			'receipt' => $this->_format_receipt($updated),
		));
	}

	public function message_delete()
	{
		$me = current_user_id();
		$this->User_model->touch_last_seen($me);

		if ($this->input->method() !== 'post') {
			$this->_json(array('ok' => FALSE, 'error' => 'Method not allowed'), 405);
		}

		$input = json_decode($this->input->raw_input_stream, TRUE);
		$message_id = isset($input['message_id']) ? (int) $input['message_id'] : 0;
		$scope = isset($input['scope']) ? (string) $input['scope'] : 'me';
		if ($message_id <= 0) {
			$this->_json(array('ok' => FALSE, 'error' => 'Invalid message'), 422);
		}
		if (!in_array($scope, array('me', 'everyone'), TRUE)) {
			$this->_json(array('ok' => FALSE, 'error' => 'Invalid delete scope'), 422);
		}

		$row = $this->Message_model->get_by_id($message_id);
		if (!$row || !$this->Conversation_model->user_in_conversation((int) $row['conversation_id'], $me)) {
			$this->_json(array('ok' => FALSE, 'error' => 'Message not found'), 404);
		}
		if ($this->Message_model->is_hidden_for($message_id, $me)) {
			$this->_json(array('ok' => TRUE, 'scope' => 'me', 'message_id' => $message_id));
		}

		if ($scope === 'everyone') {
			$result = $this->Message_model->delete_for_everyone($message_id, $me);
			if (empty($result['ok'])) {
				$this->_json(array('ok' => FALSE, 'error' => $result['error']), 422);
			}
			$this->_json(array(
				'ok' => TRUE,
				'scope' => 'everyone',
				'message' => $this->_format_message($result['message'], $me, TRUE),
			));
		}

		$this->Message_model->hide_for_user($message_id, $me);
		$this->_json(array(
			'ok' => TRUE,
			'scope' => 'me',
			'message_id' => $message_id,
		));
	}

	public function conversation_clear()
	{
		$me = current_user_id();
		$this->User_model->touch_last_seen($me);

		if ($this->input->method() !== 'post') {
			$this->_json(array('ok' => FALSE, 'error' => 'Method not allowed'), 405);
		}

		$input = json_decode($this->input->raw_input_stream, TRUE);
		$conversation_id = isset($input['conversation_id']) ? (int) $input['conversation_id'] : 0;
		if ($conversation_id <= 0 || !$this->Conversation_model->user_in_conversation($conversation_id, $me)) {
			$this->_json(array('ok' => FALSE, 'error' => 'Conversation not found'), 404);
		}

		$this->Conversation_model->clear_chat($conversation_id, $me);
		$this->_json(array('ok' => TRUE, 'conversation_id' => $conversation_id, 'action' => 'clear'));
	}

	public function conversation_delete()
	{
		$me = current_user_id();
		$this->User_model->touch_last_seen($me);

		if ($this->input->method() !== 'post') {
			$this->_json(array('ok' => FALSE, 'error' => 'Method not allowed'), 405);
		}

		$input = json_decode($this->input->raw_input_stream, TRUE);
		$conversation_id = isset($input['conversation_id']) ? (int) $input['conversation_id'] : 0;
		if ($conversation_id <= 0 || !$this->Conversation_model->user_in_conversation($conversation_id, $me)) {
			$this->_json(array('ok' => FALSE, 'error' => 'Conversation not found'), 404);
		}

		$this->Conversation_model->delete_chat($conversation_id, $me);
		$this->_json(array('ok' => TRUE, 'conversation_id' => $conversation_id, 'action' => 'delete'));
	}

	public function typing()
	{
		$me = current_user_id();
		$this->User_model->touch_last_seen($me);

		if ($this->input->method() === 'get') {
			$conversation_id = (int) $this->input->get('conversation_id');
			if ($conversation_id <= 0 || !$this->Conversation_model->user_in_conversation($conversation_id, $me)) {
				$this->_json(array('ok' => FALSE, 'error' => 'Conversation not found'), 404);
			}

			$peer = $this->Typing_model->get_peer_status($conversation_id, $me);
			$this->_json(array('ok' => TRUE, 'typing' => $peer));
		}

		if ($this->input->method() === 'post') {
			$input = json_decode($this->input->raw_input_stream, TRUE);
			$conversation_id = isset($input['conversation_id']) ? (int) $input['conversation_id'] : 0;
			$status = isset($input['status']) ? (string) $input['status'] : 'idle';

			if ($conversation_id <= 0 || !$this->Conversation_model->user_in_conversation($conversation_id, $me)) {
				$this->_json(array('ok' => FALSE, 'error' => 'Conversation not found'), 404);
			}

			$this->Typing_model->set_status($conversation_id, $me, $status);
			$this->_json(array('ok' => TRUE));
		}

		$this->_json(array('ok' => FALSE, 'error' => 'Method not allowed'), 405);
	}

	private function _format_receipt($row)
	{
		$is_read = !empty($row['is_read']);
		$is_delivered = !empty($row['is_delivered']) || $is_read;
		$is_played = !empty($row['is_played']);
		$status = 'sent';
		if ($is_read) {
			$status = 'seen';
		} elseif ($is_delivered) {
			$status = 'delivered';
		}

		return array(
			'id' => (int) $row['id'],
			'is_delivered' => (bool) $is_delivered,
			'is_read' => (bool) $is_read,
			'is_played' => (bool) $is_played,
			'status' => $status,
			'is_audio' => isset($row['file_type']) && strpos((string) $row['file_type'], 'audio/') === 0,
		);
	}

	private function _format_message($msg, $me, $force_mine = FALSE)
	{
		$msg['id'] = (int) $msg['id'];
		$msg['sender_id'] = (int) $msg['sender_id'];
		$msg['is_mine'] = $force_mine ? TRUE : ($msg['sender_id'] === $me);
		$msg['is_read'] = (bool) $msg['is_read'];
		$msg['is_delivered'] = (bool) (!empty($msg['is_delivered']) || $msg['is_read']);
		$msg['is_played'] = (bool) (!empty($msg['is_played']));
		$msg['deleted_everyone'] = !empty($msg['deleted_everyone']);
		if ($msg['is_read']) {
			$msg['status'] = 'seen';
		} elseif ($msg['is_delivered']) {
			$msg['status'] = 'delivered';
		} else {
			$msg['status'] = 'sent';
		}
		$msg['can_delete_everyone'] = $msg['is_mine']
			&& !$msg['deleted_everyone']
			&& !empty($msg['created_at'])
			&& (time() - strtotime($msg['created_at'])) <= 86400;
		$msg['initials'] = avatar_initials($msg['display_name']);
		$msg['file_size'] = isset($msg['file_size']) ? (int) $msg['file_size'] : NULL;
		if ($msg['deleted_everyone']) {
			$msg['body'] = '';
			$msg['file_name'] = NULL;
			$msg['file_path'] = NULL;
			$msg['file_type'] = NULL;
			$msg['file_size'] = NULL;
			$msg['duration_seconds'] = NULL;
			$msg['has_file'] = FALSE;
			$msg['is_image'] = FALSE;
			$msg['is_audio'] = FALSE;
			$msg['file_url'] = NULL;
			$msg['download_url'] = NULL;
			$msg['has_location'] = FALSE;
			$msg['location_lat'] = NULL;
			$msg['location_lng'] = NULL;
			$msg['maps_url'] = NULL;
			return $msg;
		}
		$msg['has_file'] = !empty($msg['file_path']);
		$msg['is_image'] = $msg['has_file'] && strpos((string) $msg['file_type'], 'image/') === 0;
		$msg['is_audio'] = $msg['has_file'] && strpos((string) $msg['file_type'], 'audio/') === 0;
		$msg['duration_seconds'] = isset($msg['duration_seconds']) && $msg['duration_seconds'] !== NULL
			? (float) $msg['duration_seconds']
			: NULL;
		$msg['file_url'] = $msg['has_file'] ? site_url('files/view/'.$msg['id']) : NULL;
		$msg['download_url'] = $msg['has_file'] ? site_url('files/download/'.$msg['id']) : NULL;
		$msg['has_location'] = ($msg['location_lat'] !== NULL && $msg['location_lng'] !== NULL && $msg['location_lat'] !== '' && $msg['location_lng'] !== '');
		if ($msg['has_location']) {
			$lat = (float) $msg['location_lat'];
			$lng = (float) $msg['location_lng'];
			$msg['location_lat'] = $lat;
			$msg['location_lng'] = $lng;
			$msg['maps_url'] = 'https://www.openstreetmap.org/?mlat=' . rawurlencode((string) $lat)
				. '&mlon=' . rawurlencode((string) $lng)
				. '#map=16/' . rawurlencode((string) $lat) . '/' . rawurlencode((string) $lng);
		} else {
			$msg['location_lat'] = NULL;
			$msg['location_lng'] = NULL;
			$msg['maps_url'] = NULL;
		}
		return $msg;
	}

	private function _store_attachment($is_multipart)
	{
		if (!$is_multipart || empty($_FILES['attachment']) || !is_uploaded_file($_FILES['attachment']['tmp_name'])) {
			return NULL;
		}

		$file = $_FILES['attachment'];
		if (!empty($file['error']) && (int) $file['error'] !== UPLOAD_ERR_OK) {
			$this->_json(array('ok' => FALSE, 'error' => 'File upload failed'), 422);
			return FALSE;
		}

		$max_size = 10 * 1024 * 1024;
		if ((int) $file['size'] <= 0 || (int) $file['size'] > $max_size) {
			$this->_json(array('ok' => FALSE, 'error' => 'File must be 1 byte to 10 MB'), 422);
			return FALSE;
		}

		$original = basename((string) $file['name']);
		$ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
		$allowed = array(
			'jpg' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'png' => 'image/png',
			'gif' => 'image/gif',
			'webp' => 'image/webp',
			'pdf' => 'application/pdf',
			'webm' => 'audio/webm',
			'ogg' => 'audio/ogg',
			'oga' => 'audio/ogg',
			'mp3' => 'audio/mpeg',
			'm4a' => 'audio/mp4',
			'mp4' => 'audio/mp4',
			'wav' => 'audio/wav',
		);

		if ($ext === '' || !isset($allowed[$ext])) {
			$this->_json(array('ok' => FALSE, 'error' => 'Only images, PDF, and audio files are allowed'), 422);
			return FALSE;
		}

		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$detected = $finfo ? finfo_file($finfo, $file['tmp_name']) : '';
		if ($finfo) {
			finfo_close($finfo);
		}

		$audio_exts = array('webm', 'ogg', 'oga', 'mp3', 'm4a', 'mp4', 'wav');
		$audio_mimes = array(
			'audio/webm', 'video/webm', 'audio/ogg', 'application/ogg',
			'audio/mpeg', 'audio/mp3', 'audio/mp4', 'audio/x-m4a', 'audio/aac',
			'audio/wav', 'audio/x-wav', 'audio/wave', 'application/octet-stream',
		);

		$ok_mime = ($detected === $allowed[$ext])
			|| ($ext === 'jpg' && $detected === 'image/jpeg')
			|| ($ext === 'pdf' && in_array($detected, array('application/pdf', 'application/x-pdf'), TRUE))
			|| (in_array($ext, $audio_exts, TRUE) && in_array($detected, $audio_mimes, TRUE));

		if (!$ok_mime) {
			$this->_json(array('ok' => FALSE, 'error' => 'Invalid file content'), 422);
			return FALSE;
		}

		$dir = FCPATH . 'uploads/chat/';
		if (!is_dir($dir) && !mkdir($dir, 0755, TRUE)) {
			$this->_json(array('ok' => FALSE, 'error' => 'Upload folder missing'), 500);
			return FALSE;
		}

		$stored = date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
		$dest = $dir . $stored;
		if (!move_uploaded_file($file['tmp_name'], $dest)) {
			$this->_json(array('ok' => FALSE, 'error' => 'Could not save file'), 500);
			return FALSE;
		}

		return array(
			'file_name' => $original,
			'file_path' => 'uploads/chat/' . $stored,
			'file_type' => $allowed[$ext],
			'file_size' => (int) $file['size'],
		);
	}

	private function _json($payload, $status = 200)
	{
		http_response_code($status);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($payload);
		exit;
	}
}
