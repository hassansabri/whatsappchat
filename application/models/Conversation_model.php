<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Conversation_model extends CI_Model
{
	public function find_between($user_one, $user_two)
	{
		$a = min((int) $user_one, (int) $user_two);
		$b = max((int) $user_one, (int) $user_two);
		return $this->db->get_where('conversations', array(
			'user_one' => $a,
			'user_two' => $b,
		))->row_array();
	}

	public function create_between($user_one, $user_two)
	{
		$a = min((int) $user_one, (int) $user_two);
		$b = max((int) $user_one, (int) $user_two);
		$this->db->insert('conversations', array(
			'user_one' => $a,
			'user_two' => $b,
		));
		return (int) $this->db->insert_id();
	}

	public function get_or_create($me, $other)
	{
		$existing = $this->find_between($me, $other);
		if ($existing) {
			$conversation_id = (int) $existing['id'];
			$this->restore_chat($conversation_id, $me);
			return $conversation_id;
		}
		return $this->create_between($me, $other);
	}

	public function user_in_conversation($conversation_id, $user_id)
	{
		$this->db->where('id', (int) $conversation_id);
		$this->db->group_start()
			->where('user_one', (int) $user_id)
			->or_where('user_two', (int) $user_id)
			->group_end();
		return $this->db->count_all_results('conversations') > 0;
	}

	public function get_user_state($conversation_id, $user_id)
	{
		return $this->db->get_where('conversation_user_state', array(
			'conversation_id' => (int) $conversation_id,
			'user_id' => (int) $user_id,
		))->row_array();
	}

	public function cleared_before_id($conversation_id, $user_id)
	{
		$row = $this->get_user_state($conversation_id, $user_id);
		return $row ? (int) $row['cleared_before_id'] : 0;
	}

	public function max_message_id($conversation_id)
	{
		$row = $this->db
			->select_max('id', 'max_id')
			->where('conversation_id', (int) $conversation_id)
			->get('messages')
			->row_array();
		return isset($row['max_id']) ? (int) $row['max_id'] : 0;
	}

	public function upsert_user_state($conversation_id, $user_id, $data)
	{
		$conversation_id = (int) $conversation_id;
		$user_id = (int) $user_id;
		$existing = $this->get_user_state($conversation_id, $user_id);
		$payload = array_merge(array(
			'cleared_before_id' => 0,
			'is_deleted' => 0,
		), $data);

		if ($existing) {
			$this->db->where('conversation_id', $conversation_id);
			$this->db->where('user_id', $user_id);
			return $this->db->update('conversation_user_state', $payload);
		}

		$payload['conversation_id'] = $conversation_id;
		$payload['user_id'] = $user_id;
		return $this->db->insert('conversation_user_state', $payload);
	}

	public function clear_chat($conversation_id, $user_id)
	{
		$max_id = $this->max_message_id($conversation_id);
		$existing = $this->get_user_state($conversation_id, $user_id);
		$cleared_before = $existing ? max((int) $existing['cleared_before_id'], $max_id) : $max_id;
		return $this->upsert_user_state($conversation_id, $user_id, array(
			'cleared_before_id' => $cleared_before,
			'is_deleted' => $existing ? (int) $existing['is_deleted'] : 0,
		));
	}

	public function delete_chat($conversation_id, $user_id)
	{
		$max_id = $this->max_message_id($conversation_id);
		$existing = $this->get_user_state($conversation_id, $user_id);
		$cleared_before = $existing ? max((int) $existing['cleared_before_id'], $max_id) : $max_id;
		return $this->upsert_user_state($conversation_id, $user_id, array(
			'cleared_before_id' => $cleared_before,
			'is_deleted' => 1,
		));
	}

	public function restore_chat($conversation_id, $user_id)
	{
		$existing = $this->get_user_state($conversation_id, $user_id);
		if (!$existing || empty($existing['is_deleted'])) {
			return TRUE;
		}
		$this->db->where('conversation_id', (int) $conversation_id);
		$this->db->where('user_id', (int) $user_id);
		return $this->db->update('conversation_user_state', array('is_deleted' => 0));
	}

	public function list_for_user($user_id)
	{
		$me = (int) $user_id;
		$sql = "
			SELECT
				c.id AS conversation_id,
				u.id AS user_id,
				u.username,
				u.display_name,
				u.avatar_color,
				u.avatar_path,
				u.last_seen,
				(
					SELECT CASE
						WHEN m.deleted_everyone = 1 THEN 'This message was deleted'
						WHEN m.body IS NOT NULL AND m.body <> '' THEN m.body
						WHEN m.file_type LIKE 'audio/%' THEN '🎤 Voice message'
						WHEN m.file_name IS NOT NULL THEN CONCAT('📎 ', m.file_name)
						WHEN m.location_lat IS NOT NULL AND m.location_lng IS NOT NULL THEN '📍 Location'
						ELSE 'Attachment'
					END
					FROM messages m
					WHERE m.conversation_id = c.id
					  AND m.id > COALESCE(cus.cleared_before_id, 0)
					  AND NOT EXISTS (
						SELECT 1 FROM message_hides h
						WHERE h.message_id = m.id AND h.user_id = ?
					  )
					ORDER BY m.id DESC
					LIMIT 1
				) AS last_message,
				(
					SELECT m.created_at
					FROM messages m
					WHERE m.conversation_id = c.id
					  AND m.id > COALESCE(cus.cleared_before_id, 0)
					  AND NOT EXISTS (
						SELECT 1 FROM message_hides h
						WHERE h.message_id = m.id AND h.user_id = ?
					  )
					ORDER BY m.id DESC
					LIMIT 1
				) AS last_message_at,
				(
					SELECT COUNT(*)
					FROM messages m
					WHERE m.conversation_id = c.id
					  AND m.id > COALESCE(cus.cleared_before_id, 0)
					  AND m.sender_id <> ?
					  AND m.is_read = 0
					  AND m.deleted_everyone = 0
					  AND NOT EXISTS (
						SELECT 1 FROM message_hides h
						WHERE h.message_id = m.id AND h.user_id = ?
					  )
				) AS unread_count
			FROM conversations c
			INNER JOIN users u ON u.id = IF(c.user_one = ?, c.user_two, c.user_one)
			LEFT JOIN conversation_user_state cus
				ON cus.conversation_id = c.id AND cus.user_id = ?
			WHERE (c.user_one = ? OR c.user_two = ?)
			  AND (
				COALESCE(cus.is_deleted, 0) = 0
				OR EXISTS (
					SELECT 1
					FROM messages m
					WHERE m.conversation_id = c.id
					  AND m.id > COALESCE(cus.cleared_before_id, 0)
					  AND NOT EXISTS (
						SELECT 1 FROM message_hides h
						WHERE h.message_id = m.id AND h.user_id = ?
					  )
				)
			)
			ORDER BY COALESCE(
				(
					SELECT m.created_at
					FROM messages m
					WHERE m.conversation_id = c.id
					  AND m.id > COALESCE(cus.cleared_before_id, 0)
					  AND NOT EXISTS (
						SELECT 1 FROM message_hides h
						WHERE h.message_id = m.id AND h.user_id = ?
					  )
					ORDER BY m.id DESC
					LIMIT 1
				),
				c.created_at
			) DESC
		";
		return $this->db->query($sql, array($me, $me, $me, $me, $me, $me, $me, $me, $me, $me))->result_array();
	}
}
