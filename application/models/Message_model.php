<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Message_model extends CI_Model
{
	private $select_fields = 'm.id, m.conversation_id, m.sender_id, m.body, m.file_name, m.file_path, m.file_type, m.file_size, m.duration_seconds, m.location_lat, m.location_lng, m.is_read, m.is_delivered, m.is_played, m.deleted_everyone, m.deleted_everyone_at, m.created_at, u.display_name, u.avatar_color';

	public function list_messages($conversation_id, $viewer_id, $after_id = 0)
	{
		$cleared_before = 0;
		if ($this->db->table_exists('conversation_user_state')) {
			$state = $this->db->get_where('conversation_user_state', array(
				'conversation_id' => (int) $conversation_id,
				'user_id' => (int) $viewer_id,
			))->row_array();
			if ($state) {
				$cleared_before = (int) $state['cleared_before_id'];
			}
		}

		$this->db->select($this->select_fields);
		$this->db->from('messages m');
		$this->db->join('users u', 'u.id = m.sender_id');
		$this->db->where('m.conversation_id', (int) $conversation_id);
		if ($cleared_before > 0) {
			$this->db->where('m.id >', $cleared_before);
		}
		$this->db->where(
			'NOT EXISTS (
				SELECT 1 FROM message_hides h
				WHERE h.message_id = m.id AND h.user_id = '.$this->db->escape((int) $viewer_id).'
			)',
			NULL,
			FALSE
		);
		if ((int) $after_id > 0) {
			$this->db->where('m.id >', (int) $after_id);
		}
		$this->db->order_by('m.id', 'ASC');
		$this->db->limit(200);
		return $this->db->get()->result_array();
	}

	public function create($data)
	{
		if (!isset($data['is_delivered'])) {
			$data['is_delivered'] = 0;
		}
		if (!isset($data['is_read'])) {
			$data['is_read'] = 0;
		}
		if (!isset($data['is_played'])) {
			$data['is_played'] = 0;
		}
		if (!isset($data['deleted_everyone'])) {
			$data['deleted_everyone'] = 0;
		}
		$this->db->insert('messages', $data);
		return (int) $this->db->insert_id();
	}

	public function get_by_id($id)
	{
		$this->db->select($this->select_fields);
		$this->db->from('messages m');
		$this->db->join('users u', 'u.id = m.sender_id');
		$this->db->where('m.id', (int) $id);
		return $this->db->get()->row_array();
	}

	public function is_hidden_for($message_id, $user_id)
	{
		return $this->db
			->where('message_id', (int) $message_id)
			->where('user_id', (int) $user_id)
			->count_all_results('message_hides') > 0;
	}

	public function hide_for_user($message_id, $user_id)
	{
		if ($this->is_hidden_for($message_id, $user_id)) {
			return TRUE;
		}
		return (bool) $this->db->insert('message_hides', array(
			'message_id' => (int) $message_id,
			'user_id' => (int) $user_id,
		));
	}

	public function delete_for_everyone($message_id, $sender_id)
	{
		$row = $this->db->get_where('messages', array('id' => (int) $message_id))->row_array();
		if (!$row) {
			return array('ok' => FALSE, 'error' => 'Message not found');
		}
		if ((int) $row['sender_id'] !== (int) $sender_id) {
			return array('ok' => FALSE, 'error' => 'Only the sender can delete for everyone');
		}
		if (!empty($row['deleted_everyone'])) {
			return array('ok' => TRUE, 'message' => $this->get_by_id($message_id));
		}

		// WhatsApp-like window: 24 hours.
		$created = strtotime($row['created_at']);
		if ($created === FALSE || (time() - $created) > 86400) {
			return array('ok' => FALSE, 'error' => 'Delete for everyone is only available within 24 hours');
		}

		$this->db->where('id', (int) $message_id);
		$this->db->update('messages', array(
			'deleted_everyone' => 1,
			'deleted_everyone_at' => date('Y-m-d H:i:s'),
			'body' => '',
			'file_name' => NULL,
			'file_path' => NULL,
			'file_type' => NULL,
			'file_size' => NULL,
			'duration_seconds' => NULL,
			'location_lat' => NULL,
			'location_lng' => NULL,
		));

		// Best-effort remove attachment file.
		if (!empty($row['file_path'])) {
			$path = FCPATH . $row['file_path'];
			if (is_file($path)) {
				@unlink($path);
			}
		}

		return array('ok' => TRUE, 'message' => $this->get_by_id($message_id));
	}

	public function list_deleted_everyone($conversation_id, $viewer_id = 0)
	{
		$cleared_before = 0;
		if ((int) $viewer_id > 0 && $this->db->table_exists('conversation_user_state')) {
			$state = $this->db->get_where('conversation_user_state', array(
				'conversation_id' => (int) $conversation_id,
				'user_id' => (int) $viewer_id,
			))->row_array();
			if ($state) {
				$cleared_before = (int) $state['cleared_before_id'];
			}
		}

		$this->db->select('id, deleted_everyone, deleted_everyone_at');
		$this->db->from('messages');
		$this->db->where('conversation_id', (int) $conversation_id);
		$this->db->where('deleted_everyone', 1);
		if ($cleared_before > 0) {
			$this->db->where('id >', $cleared_before);
		}
		$this->db->order_by('id', 'DESC');
		$this->db->limit(100);
		return $this->db->get()->result_array();
	}

	public function mark_delivered($conversation_id, $reader_id)
	{
		$this->db->where('conversation_id', (int) $conversation_id);
		$this->db->where('sender_id !=', (int) $reader_id);
		$this->db->where('is_delivered', 0);
		$this->db->where('deleted_everyone', 0);
		$this->db->update('messages', array('is_delivered' => 1));
	}

	public function mark_read($conversation_id, $reader_id)
	{
		$this->db->where('conversation_id', (int) $conversation_id);
		$this->db->where('sender_id !=', (int) $reader_id);
		$this->db->where('deleted_everyone', 0);
		$this->db->group_start();
		$this->db->where('is_read', 0);
		$this->db->or_where('is_delivered', 0);
		$this->db->group_end();
		$this->db->update('messages', array(
			'is_delivered' => 1,
			'is_read' => 1,
		));
	}

	public function mark_played($message_id, $reader_id)
	{
		$row = $this->db->get_where('messages', array('id' => (int) $message_id))->row_array();
		if (!$row || !empty($row['deleted_everyone'])) {
			return FALSE;
		}
		if ((int) $row['sender_id'] === (int) $reader_id) {
			return FALSE;
		}
		if (strpos((string) $row['file_type'], 'audio/') !== 0) {
			return FALSE;
		}

		$this->db->where('id', (int) $message_id);
		$this->db->update('messages', array(
			'is_played' => 1,
			'is_delivered' => 1,
			'is_read' => 1,
		));
		return TRUE;
	}

	public function list_receipts($conversation_id, $sender_id)
	{
		$this->db->select('id, is_delivered, is_read, is_played, file_type, deleted_everyone');
		$this->db->from('messages');
		$this->db->where('conversation_id', (int) $conversation_id);
		$this->db->where('sender_id', (int) $sender_id);
		$this->db->order_by('id', 'DESC');
		$this->db->limit(100);
		return $this->db->get()->result_array();
	}
}
