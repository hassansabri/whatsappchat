<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Gender_model extends CI_Model
{
	public function get_active()
	{
		$this->db->where('is_active', 1);
		$this->db->order_by('sort_order', 'ASC');
		$this->db->order_by('name', 'ASC');
		return $this->db->get('genders')->result_array();
	}

	public function get_by_id($id)
	{
		return $this->db->get_where('genders', array('id' => (int) $id))->row_array();
	}

	public function is_valid_active($id)
	{
		$row = $this->db->get_where('genders', array(
			'id' => (int) $id,
			'is_active' => 1,
		))->row_array();
		return !empty($row);
	}
}
