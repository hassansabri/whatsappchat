<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('User_model');
	}

	public function index()
	{
		if ($this->session->userdata('user_id')) {
			redirect('chat');
		}
		redirect('login');
	}

	public function login()
	{
		if ($this->session->userdata('user_id')) {
			redirect('chat');
		}

		$error = '';
		$username = '';

		if ($this->input->method() === 'post') {
			$username = trim((string) $this->input->post('username', TRUE));
			$password = (string) $this->input->post('password');

			if ($username === '' || $password === '') {
				$error = 'Please enter your username and password.';
			} else {
				$user = $this->User_model->get_by_username($username);
				if ($user && password_verify($password, $user['password'])) {
					$this->session->set_userdata(array(
						'user_id' => (int) $user['id'],
						'user_type' => normalize_user_type(isset($user['user_type']) ? $user['user_type'] : 'normal'),
					));
					$this->User_model->touch_last_seen((int) $user['id']);
					redirect('chat');
				}
				$error = 'Invalid username or password.';
			}
		}

		$this->load->view('auth/login', array(
			'error' => $error,
			'username' => $username,
		));
	}

	public function register()
	{
		if ($this->session->userdata('user_id')) {
			redirect('chat');
		}

		$error = '';
		$form = array(
			'display_name' => '',
			'username' => '',
			'email' => '',
		);

		if ($this->input->method() === 'post') {
			$form['display_name'] = trim((string) $this->input->post('display_name', TRUE));
			$form['username'] = trim((string) $this->input->post('username', TRUE));
			$form['email'] = trim((string) $this->input->post('email', TRUE));
			$password = (string) $this->input->post('password');
			$confirm = (string) $this->input->post('confirm_password');

			if ($form['display_name'] === '' || $form['username'] === '' || $form['email'] === '' || $password === '') {
				$error = 'All fields are required.';
			} elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $form['username'])) {
				$error = 'Username must be 3–30 letters, numbers, or underscores.';
			} elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
				$error = 'Please enter a valid email address.';
			} elseif (strlen($password) < 6) {
				$error = 'Password must be at least 6 characters.';
			} elseif ($password !== $confirm) {
				$error = 'Passwords do not match.';
			} elseif ($this->User_model->username_or_email_exists($form['username'], $form['email'])) {
				$error = 'Username or email is already taken.';
			} else {
				// Register still needs phone/gender in a later step if required by DB.
				// Keep a safe default gender when self-registering without those fields.
				$default_gender = $this->db->get_where('genders', array('name' => 'Prefer not to say'))->row_array();
				$gender_id = $default_gender ? (int) $default_gender['id'] : 4;
				$user_id = $this->User_model->create(array(
					'username' => $form['username'],
					'email' => $form['email'],
					'country_code' => '+92',
					'phone' => '0000000000',
					'gender_id' => $gender_id,
					'password' => password_hash($password, PASSWORD_DEFAULT),
					'display_name' => $form['display_name'],
					'avatar_color' => '#0d6efd',
					'avatar_path' => NULL,
					'user_type' => 'normal',
				));
				$this->session->set_userdata(array(
					'user_id' => $user_id,
					'user_type' => 'normal',
				));
				redirect('chat');
			}
		}

		$this->load->view('auth/register', array(
			'error' => $error,
			'form' => $form,
		));
	}

	public function logout()
	{
		$this->session->unset_userdata(array('user_id', 'user_type'));
		$this->session->sess_destroy();
		redirect('login');
	}
}
