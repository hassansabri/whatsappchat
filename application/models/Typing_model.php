<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Typing_model extends CI_Model
{
	public function set_status($conversation_id, $user_id, $status)
	{
		$allowed = array('idle', 'typing', 'erasing');
		if (!in_array($status, $allowed, TRUE)) {
			$status = 'idle';
		}

		$row = array(
			'conversation_id' => (int) $conversation_id,
			'user_id' => (int) $user_id,
			'status' => $status,
			'updated_at' => date('Y-m-d H:i:s'),
		);

		$exists = $this->db->get_where('typing_status', array(
			'conversation_id' => (int) $conversation_id,
			'user_id' => (int) $user_id,
		))->row_array();

		if ($exists) {
			$this->db->where('conversation_id', (int) $conversation_id);
			$this->db->where('user_id', (int) $user_id);
			return $this->db->update('typing_status', array(
				'status' => $status,
				'updated_at' => $row['updated_at'],
			));
		}

		return $this->db->insert('typing_status', $row);
	}

	public function get_peer_status($conversation_id, $viewer_id)
	{
		$this->db->where('conversation_id', (int) $conversation_id);
		$this->db->where('user_id !=', (int) $viewer_id);
		$this->db->where('updated_at >=', date('Y-m-d H:i:s', time() - 4));
		$this->db->where_in('status', array('typing', 'erasing'));
		$row = $this->db->get('typing_status')->row_array();

		if (!$row) {
			return array('status' => 'idle');
		}

		return array(
			'status' => $row['status'],
			'user_id' => (int) $row['user_id'],
			'updated_at' => $row['updated_at'],
		);
	}
}
