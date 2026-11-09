<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Friend_model extends CI_Model
{
	private function pair($a, $b)
	{
		$a = (int) $a;
		$b = (int) $b;
		return array(min($a, $b), max($a, $b));
	}

	public function get_relationship($user_a, $user_b)
	{
		list($one, $two) = $this->pair($user_a, $user_b);
		return $this->db->get_where('friendships', array(
			'user_one' => $one,
			'user_two' => $two,
		))->row_array();
	}

	public function are_friends($user_a, $user_b)
	{
		$rel = $this->get_relationship($user_a, $user_b);
		return $rel && $rel['status'] === 'accepted';
	}

	public function get_friends($user_id)
	{
		$uid = (int) $user_id;
		$sql = "
			SELECT
				u.id, u.username, u.email, u.country_code, u.phone, u.gender_id, u.display_name, u.avatar_color, u.avatar_path, u.last_seen, u.user_type, u.created_at,
				g.name AS gender_name,
				f.id AS friendship_id, f.created_at AS friends_since
			FROM friendships f
			INNER JOIN users u ON u.id = IF(f.user_one = ?, f.user_two, f.user_one)
			LEFT JOIN genders g ON g.id = u.gender_id
			WHERE (f.user_one = ? OR f.user_two = ?)
			  AND f.status = 'accepted'
			ORDER BY u.display_name ASC
		";
		return $this->db->query($sql, array($uid, $uid, $uid))->result_array();
	}

	public function get_incoming_requests($user_id)
	{
		$uid = (int) $user_id;
		$sql = "
			SELECT
				u.id, u.username, u.display_name, u.avatar_color, u.avatar_path, u.last_seen,
				f.id AS friendship_id, f.created_at AS requested_at
			FROM friendships f
			INNER JOIN users u ON u.id = f.requested_by
			WHERE (f.user_one = ? OR f.user_two = ?)
			  AND f.status = 'pending'
			  AND f.requested_by <> ?
			ORDER BY f.created_at DESC
		";
		return $this->db->query($sql, array($uid, $uid, $uid))->result_array();
	}

	public function get_outgoing_requests($user_id)
	{
		$uid = (int) $user_id;
		$sql = "
			SELECT
				u.id, u.username, u.display_name, u.avatar_color, u.avatar_path, u.last_seen,
				f.id AS friendship_id, f.created_at AS requested_at
			FROM friendships f
			INNER JOIN users u ON u.id = IF(f.user_one = ?, f.user_two, f.user_one)
			WHERE f.requested_by = ?
			  AND f.status = 'pending'
			ORDER BY f.created_at DESC
		";
		return $this->db->query($sql, array($uid, $uid))->result_array();
	}

	public function get_suggestable_users($user_id, $query = '', $limit = 50, $allow_username_search = FALSE)
	{
		$uid = (int) $user_id;
		$limit = max(1, min(100, (int) $limit));
		$params = array($uid, $uid, $uid, $uid);
		$search_sql = '';
		$query = trim((string) $query);
		if ($query !== '') {
			$like = '%' . $this->db->escape_like_str($query) . '%';
			if ($allow_username_search) {
				$search_sql = ' AND (u.display_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)';
				$params[] = $like;
				$params[] = $like;
				$params[] = $like;
			} else {
				$search_sql = ' AND (u.display_name LIKE ? OR u.email LIKE ?)';
				$params[] = $like;
				$params[] = $like;
			}
		}
		$sql = "
			SELECT u.id, u.username, u.display_name, u.avatar_color, u.avatar_path, u.last_seen, u.email
			FROM users u
			WHERE u.id <> ?
			  AND u.id NOT IN (
				SELECT IF(f.user_one = ?, f.user_two, f.user_one)
				FROM friendships f
				WHERE f.user_one = ? OR f.user_two = ?
			  )
			  {$search_sql}
			ORDER BY u.display_name ASC
			LIMIT {$limit}
		";
		return $this->db->query($sql, $params)->result_array();
	}

	public function send_request($from_id, $to_id)
	{
		$from_id = (int) $from_id;
		$to_id = (int) $to_id;
		if ($from_id <= 0 || $to_id <= 0 || $from_id === $to_id) {
			return array('ok' => FALSE, 'error' => 'Invalid user');
		}

		$existing = $this->get_relationship($from_id, $to_id);
		if ($existing) {
			if ($existing['status'] === 'accepted') {
				return array('ok' => FALSE, 'error' => 'Already friends');
			}
			return array('ok' => FALSE, 'error' => 'Friend request already exists');
		}

		list($one, $two) = $this->pair($from_id, $to_id);
		$this->db->insert('friendships', array(
			'user_one' => $one,
			'user_two' => $two,
			'requested_by' => $from_id,
			'status' => 'pending',
		));

		return array('ok' => TRUE, 'friendship_id' => (int) $this->db->insert_id());
	}

	public function accept_request($friendship_id, $user_id)
	{
		$row = $this->db->get_where('friendships', array('id' => (int) $friendship_id))->row_array();
		if (!$row || $row['status'] !== 'pending') {
			return array('ok' => FALSE, 'error' => 'Request not found');
		}

		$user_id = (int) $user_id;
		$is_member = ((int) $row['user_one'] === $user_id || (int) $row['user_two'] === $user_id);
		if (!$is_member || (int) $row['requested_by'] === $user_id) {
			return array('ok' => FALSE, 'error' => 'Not allowed to accept this request');
		}

		$this->db->where('id', (int) $friendship_id);
		$this->db->update('friendships', array('status' => 'accepted'));
		return array('ok' => TRUE);
	}

	public function reject_or_cancel($friendship_id, $user_id)
	{
		$row = $this->db->get_where('friendships', array('id' => (int) $friendship_id))->row_array();
		if (!$row) {
			return array('ok' => FALSE, 'error' => 'Request not found');
		}

		$user_id = (int) $user_id;
		$is_member = ((int) $row['user_one'] === $user_id || (int) $row['user_two'] === $user_id);
		if (!$is_member) {
			return array('ok' => FALSE, 'error' => 'Not allowed');
		}

		$this->db->where('id', (int) $friendship_id)->delete('friendships');
		return array('ok' => TRUE);
	}

	public function unfriend($user_id, $friend_id)
	{
		$rel = $this->get_relationship($user_id, $friend_id);
		if (!$rel || $rel['status'] !== 'accepted') {
			return array('ok' => FALSE, 'error' => 'Friendship not found');
		}

		$this->db->where('id', (int) $rel['id'])->delete('friendships');
		return array('ok' => TRUE);
	}
}
