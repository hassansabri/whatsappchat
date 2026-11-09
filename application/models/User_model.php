<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model
{
	private function with_gender()
	{
		$this->db->select('users.*, genders.name AS gender_name');
		$this->db->join('genders', 'genders.id = users.gender_id', 'left');
	}

	public function get_by_id($id)
	{
		$this->with_gender();
		return $this->db->get_where('users', array('users.id' => (int) $id))->row_array();
	}

	public function get_by_username($username)
	{
		$this->with_gender();
		return $this->db->get_where('users', array('users.username' => $username))->row_array();
	}

	public function get_by_login($login)
	{
		return $this->get_by_username($login);
	}

	public function get_all()
	{
		$this->with_gender();
		$this->db->order_by('users.display_name', 'ASC');
		return $this->db->get('users')->result_array();
	}

	public function get_all_except($user_id)
	{
		$this->with_gender();
		$this->db->where('users.id !=', (int) $user_id);
		$this->db->order_by('users.display_name', 'ASC');
		return $this->db->get('users')->result_array();
	}

	public function username_or_email_exists($username, $email, $exclude_id = 0)
	{
		return $this->username_exists($username, $exclude_id) || $this->email_exists($email, $exclude_id);
	}

	public function username_exists($username, $exclude_id = 0)
	{
		$this->db->where('username', $username);
		if ($exclude_id) {
			$this->db->where('id !=', (int) $exclude_id);
		}
		return $this->db->count_all_results('users') > 0;
	}

	public function email_exists($email, $exclude_id = 0)
	{
		$this->db->where('email', $email);
		if ($exclude_id) {
			$this->db->where('id !=', (int) $exclude_id);
		}
		return $this->db->count_all_results('users') > 0;
	}

	public function phone_exists($country_code, $phone, $exclude_id = 0)
	{
		$this->db->where('country_code', $country_code);
		$this->db->where('phone', $phone);
		if ($exclude_id) {
			$this->db->where('id !=', (int) $exclude_id);
		}
		return $this->db->count_all_results('users') > 0;
	}

	public function uniqueness_conflicts($username, $email, $country_code, $phone, $exclude_id = 0)
	{
		$conflicts = array();
		if ($username !== '' && $this->username_exists($username, $exclude_id)) {
			$conflicts[] = 'username';
		}
		if ($email !== '' && $this->email_exists($email, $exclude_id)) {
			$conflicts[] = 'email';
		}
		if ($country_code !== '' && $phone !== '' && $this->phone_exists($country_code, $phone, $exclude_id)) {
			$conflicts[] = 'phone';
		}
		return $conflicts;
	}

	public function create($data)
	{
		$this->db->insert('users', $data);
		return (int) $this->db->insert_id();
	}

	public function update_user($id, $data)
	{
		$this->db->where('id', (int) $id);
		return $this->db->update('users', $data);
	}

	public function touch_last_seen($id)
	{
		$this->db->where('id', (int) $id);
		$this->db->update('users', array('last_seen' => date('Y-m-d H:i:s')));
	}
}
