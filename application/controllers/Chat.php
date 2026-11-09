<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Chat extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('User_model');
		require_login();
	}

	public function index()
	{
		$user = $this->User_model->get_by_id(current_user_id());
		if (!$user) {
			$this->session->sess_destroy();
			redirect('login');
		}

		$this->User_model->touch_last_seen((int) $user['id']);
		$user['initials'] = avatar_initials($user['display_name']);

		$this->load->view('chat/index', array(
			'user' => $user,
		));
	}
}
