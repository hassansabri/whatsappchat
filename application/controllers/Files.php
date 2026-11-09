<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Files extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Message_model');
		$this->load->model('Conversation_model');
		$this->load->model('User_model');
		require_login();
	}

	public function avatar($user_id = 0)
	{
		$user = $this->User_model->get_by_id((int) $user_id);
		if (!$user || empty($user['avatar_path'])) {
			show_404();
		}

		$path = FCPATH . $user['avatar_path'];
		if (!is_file($path)) {
			show_404();
		}

		$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
		$mime_map = array(
			'jpg' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'png' => 'image/png',
			'gif' => 'image/gif',
			'webp' => 'image/webp',
		);
		$mime = isset($mime_map[$ext]) ? $mime_map[$ext] : 'application/octet-stream';
		header('Content-Type: ' . $mime);
		header('Content-Length: ' . filesize($path));
		header('Cache-Control: private, max-age=86400');
		readfile($path);
		exit;
	}

	private function _authorized_message($message_id)
	{
		$message_id = (int) $message_id;
		$row = $this->db->get_where('messages', array('id' => $message_id))->row_array();
		if (!$row || empty($row['file_path'])) {
			return NULL;
		}

		if (!$this->Conversation_model->user_in_conversation((int) $row['conversation_id'], current_user_id())) {
			return NULL;
		}

		$path = FCPATH . $row['file_path'];
		if (!is_file($path)) {
			return NULL;
		}

		$row['abs_path'] = $path;
		return $row;
	}

	public function download($message_id = 0)
	{
		$row = $this->_authorized_message($message_id);
		if (!$row) {
			show_404();
		}

		$this->load->helper('download');
		force_download($row['file_name'], file_get_contents($row['abs_path']));
	}

	public function view($message_id = 0)
	{
		$row = $this->_authorized_message($message_id);
		if (!$row) {
			show_404();
		}

		$mime = $row['file_type'] ?: 'application/octet-stream';
		// MediaRecorder webm is usually Opus; help browsers pick the right decoder.
		if ($mime === 'audio/webm') {
			$mime = 'audio/webm; codecs=opus';
		} elseif ($mime === 'audio/ogg') {
			$mime = 'audio/ogg; codecs=opus';
		}

		$path = $row['abs_path'];
		$size = (int) filesize($path);
		$filename = str_replace(array('"', '\\'), '', (string) $row['file_name']);
		$is_audio = strpos((string) $row['file_type'], 'audio/') === 0;

		header('Content-Type: ' . $mime);
		header('Content-Disposition: inline; filename="'.$filename.'"');
		header('Cache-Control: private, max-age=0, must-revalidate');
		header('X-Content-Type-Options: nosniff');

		// Full-file responses are more reliable for short voice notes than Range seeks
		// on MediaRecorder WebM (often missing Cues / seek indexes).
		if ($is_audio && $size <= (2 * 1024 * 1024)) {
			header('Accept-Ranges: none');
			header('Content-Length: ' . $size);
			readfile($path);
			exit;
		}

		header('Accept-Ranges: bytes');
		$range = isset($_SERVER['HTTP_RANGE']) ? (string) $_SERVER['HTTP_RANGE'] : '';
		if (preg_match('/bytes=(\d+)-(\d*)/', $range, $m)) {
			$start = (int) $m[1];
			$end = ($m[2] !== '') ? (int) $m[2] : ($size - 1);
			if ($start > $end || $start >= $size) {
				http_response_code(416);
				header("Content-Range: bytes */{$size}");
				exit;
			}
			$end = min($end, $size - 1);
			$length = $end - $start + 1;
			http_response_code(206);
			header("Content-Range: bytes {$start}-{$end}/{$size}");
			header('Content-Length: ' . $length);
			$fp = fopen($path, 'rb');
			fseek($fp, $start);
			echo fread($fp, $length);
			fclose($fp);
			exit;
		}

		header('Content-Length: ' . $size);
		readfile($path);
		exit;
	}
}
