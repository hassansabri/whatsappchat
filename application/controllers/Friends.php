<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Friends extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('User_model');
		$this->load->model('Friend_model');
		require_login();
	}

	public function index()
	{
		$me = current_user_id();
		$this->User_model->touch_last_seen($me);

		$friends = array();
		foreach ($this->Friend_model->get_friends($me) as $u) {
			$u['id'] = (int) $u['id'];
			$u['online'] = is_user_online($u['last_seen']);
			$u['initials'] = avatar_initials($u['display_name']);
			$friends[] = $u;
		}

		$incoming = array();
		foreach ($this->Friend_model->get_incoming_requests($me) as $u) {
			$u['id'] = (int) $u['id'];
			$u['online'] = is_user_online($u['last_seen']);
			$u['initials'] = avatar_initials($u['display_name']);
			$incoming[] = $u;
		}

		$outgoing = array();
		foreach ($this->Friend_model->get_outgoing_requests($me) as $u) {
			$u['id'] = (int) $u['id'];
			$u['online'] = is_user_online($u['last_seen']);
			$u['initials'] = avatar_initials($u['display_name']);
			$outgoing[] = $u;
		}

		$this->load->view('friends/index', array(
			'friends' => $friends,
			'incoming' => $incoming,
			'outgoing' => $outgoing,
			'is_system' => is_system_user(),
			'success' => $this->input->get('success', TRUE),
			'error' => $this->input->get('error', TRUE),
		));
	}

	public function search()
	{
		$me = current_user_id();
		$q = trim((string) $this->input->get('q', TRUE));
		$is_system = is_system_user();
		$rows = array();
		foreach ($this->Friend_model->get_suggestable_users($me, $q, 50, $is_system) as $u) {
			$item = array(
				'id' => (int) $u['id'],
				'display_name' => $u['display_name'],
				'avatar_color' => $u['avatar_color'],
				'avatar_url' => user_avatar_url($u),
				'online' => is_user_online($u['last_seen']),
				'initials' => avatar_initials($u['display_name']),
			);
			if ($is_system) {
				$item['username'] = $u['username'];
			}
			$rows[] = $item;
		}

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode(array(
				'ok' => TRUE,
				'users' => $rows,
				'show_username' => $is_system,
			)));
	}

	public function add($user_id = 0)
	{
		$result = $this->Friend_model->send_request(current_user_id(), (int) $user_id);
		$accept = (string) $this->input->server('HTTP_ACCEPT');
		$wants_json = (stripos($accept, 'application/json') !== FALSE)
			|| $this->input->method() === 'post';

		if ($wants_json) {
			$status = $result['ok'] ? 200 : 400;
			$this->output
				->set_status_header($status)
				->set_content_type('application/json')
				->set_output(json_encode($result));
			return;
		}

		if (!$result['ok']) {
			redirect('friends?error=' . rawurlencode($result['error']));
		}
		redirect('friends?success=sent');
	}

	public function accept($friendship_id = 0)
	{
		$result = $this->Friend_model->accept_request((int) $friendship_id, current_user_id());
		if (!$result['ok']) {
			redirect('friends?error=' . rawurlencode($result['error']));
		}
		redirect('friends?success=accepted');
	}

	public function remove($friendship_id = 0)
	{
		$result = $this->Friend_model->reject_or_cancel((int) $friendship_id, current_user_id());
		if (!$result['ok']) {
			redirect('friends?error=' . rawurlencode($result['error']));
		}
		redirect('friends?success=removed');
	}

	public function unfriend($user_id = 0)
	{
		$result = $this->Friend_model->unfriend(current_user_id(), (int) $user_id);
		if (!$result['ok']) {
			redirect('friends?error=' . rawurlencode($result['error']));
		}
		redirect('friends?success=unfriended');
	}
}
