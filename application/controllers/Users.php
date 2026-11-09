<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('User_model');
		$this->load->model('Friend_model');
		$this->load->model('Gender_model');
		require_login();
	}

	public function index()
	{
		$me = current_user_id();
		$this->User_model->touch_last_seen($me);
		$is_system = is_system_user();

		if ($is_system) {
			$rows = $this->User_model->get_all();
		} else {
			$rows = $this->Friend_model->get_friends($me);
		}

		$users = array();
		foreach ($rows as $u) {
			$u['id'] = (int) $u['id'];
			$u['user_type'] = normalize_user_type(isset($u['user_type']) ? $u['user_type'] : 'normal');
			$u['online'] = is_user_online($u['last_seen']);
			$u['initials'] = avatar_initials($u['display_name']);
			$u['avatar_url'] = user_avatar_url($u);
			$u['phone_display'] = format_phone_display(
				isset($u['country_code']) ? $u['country_code'] : '',
				isset($u['phone']) ? $u['phone'] : ''
			);
			$u['gender_name'] = isset($u['gender_name']) && $u['gender_name'] !== '' ? $u['gender_name'] : '—';
			$users[] = $u;
		}

		$this->load->view('users/index', array(
			'users' => $users,
			'currentUserId' => $me,
			'is_system' => $is_system,
			'success' => $this->input->get('success', TRUE),
		));
	}

	public function profile()
	{
		$this->edit(current_user_id());
	}

	public function add()
	{
		$codes = country_codes();
		$genders = $this->Gender_model->get_active();
		$can_choose_type = is_system_user();
		$error = '';
		$form = array(
			'display_name' => '',
			'username' => '',
			'email' => '',
			'country_code' => '',
			'phone' => '',
			'gender_id' => '',
			'avatar_path' => '',
			'avatar_color' => '#0d6efd',
			'user_type' => '',
		);

		if ($this->input->method() === 'post') {
			$form = $this->_read_form($can_choose_type, $codes, NULL);
			$password = (string) $this->input->post('password');
			$confirm = (string) $this->input->post('confirm_password');

			$error = $this->_validate_profile($form, $can_choose_type, $codes, FALSE);
			if ($error === '') {
				$error = $this->_validate_password($password, $confirm);
			}
			if ($error === '') {
				$error = $this->_unique_error($form);
			}

			$avatar_path = NULL;
			if ($error === '') {
				$upload = $this->_handle_avatar_upload();
				if ($upload['error'] !== '') {
					$error = $upload['error'];
				} else {
					$avatar_path = $upload['path'];
				}
			}

			if ($error === '') {
				$this->User_model->create(array(
					'username' => $form['username'],
					'email' => $form['email'],
					'country_code' => $form['country_code'],
					'phone' => $form['phone'],
					'gender_id' => (int) $form['gender_id'],
					'password' => password_hash($password, PASSWORD_DEFAULT),
					'display_name' => $form['display_name'],
					'avatar_color' => '#0d6efd',
					'avatar_path' => $avatar_path,
					'user_type' => normalize_user_type($form['user_type']),
				));
				redirect('users?success=added');
			}
		}

		$this->load->view('users/form', array(
			'mode' => 'add',
			'is_own_profile' => FALSE,
			'error' => $error,
			'password_error' => '',
			'password_success' => '',
			'form' => $form,
			'country_codes' => $codes,
			'genders' => $genders,
			'can_choose_type' => $can_choose_type,
			'can_change_password' => TRUE,
			'full_edit' => TRUE,
			'can_change_avatar' => TRUE,
		));
	}

	public function edit($id = 0)
	{
		$id = (int) $id;
		$user = $this->User_model->get_by_id($id);
		if (!$user) {
			show_404();
		}

		$is_system = is_system_user();
		$is_self = $id === current_user_id();

		if (!$is_system && !$is_self) {
			show_error('You can only view and edit your own profile.', 403);
		}

		$codes = country_codes();
		$genders = $this->Gender_model->get_active();
		$is_own_profile = $is_self;
		$full_edit = $is_system && !$is_self;
		$can_choose_type = $full_edit;
		$can_change_password = $is_self || $is_system;
		$can_change_avatar = TRUE;
		$error = '';
		$existing_type = normalize_user_type(isset($user['user_type']) ? $user['user_type'] : 'normal');
		$form = array(
			'id' => (int) $user['id'],
			'display_name' => $user['display_name'],
			'username' => $user['username'],
			'email' => $user['email'],
			'country_code' => isset($user['country_code']) ? $user['country_code'] : '',
			'phone' => isset($user['phone']) ? $user['phone'] : '',
			'gender_id' => isset($user['gender_id']) ? (string) (int) $user['gender_id'] : '',
			'gender_name' => isset($user['gender_name']) ? $user['gender_name'] : '',
			'avatar_path' => isset($user['avatar_path']) ? $user['avatar_path'] : '',
			'avatar_color' => !empty($user['avatar_color']) ? $user['avatar_color'] : '#0d6efd',
			'avatar_url' => user_avatar_url($user),
			'user_type' => $existing_type,
		);

		if ($this->input->method() === 'post') {
			if ($is_own_profile) {
				$display_name = trim((string) $this->input->post('display_name', TRUE));
				if ($display_name === '') {
					$error = 'Display name is required.';
				} else {
					$data = array('display_name' => $display_name);
					$avatar_result = $this->_apply_avatar_changes($user, $data);
					if ($avatar_result !== '') {
						$error = $avatar_result;
					} else {
						$this->User_model->update_user($id, $data);
						redirect('profile?success=updated');
					}
				}
				$form['display_name'] = $display_name;
			} else {
				$form = $this->_read_form($can_choose_type, $codes, $existing_type);
				$form['id'] = $id;
				$form['avatar_path'] = isset($user['avatar_path']) ? $user['avatar_path'] : '';
				$form['avatar_url'] = user_avatar_url($user);
				$form['gender_name'] = '';

				$error = $this->_validate_profile($form, $can_choose_type, $codes, FALSE);
				if ($error === '') {
					$error = $this->_unique_error($form, $id);
				}

				if ($error === '') {
					$data = array(
						'username' => $form['username'],
						'email' => $form['email'],
						'country_code' => $form['country_code'],
						'phone' => $form['phone'],
						'gender_id' => (int) $form['gender_id'],
						'display_name' => $form['display_name'],
						'user_type' => normalize_user_type($form['user_type']),
					);
					$avatar_result = $this->_apply_avatar_changes($user, $data);
					if ($avatar_result !== '') {
						$error = $avatar_result;
					} else {
						$this->User_model->update_user($id, $data);
						redirect('users?success=updated');
					}
				}
			}
		}

		$this->load->view('users/form', array(
			'mode' => 'edit',
			'is_own_profile' => $is_own_profile,
			'error' => $error,
			'password_error' => $this->input->get('password_error', TRUE),
			'password_success' => $this->input->get('password_success', TRUE) ?: '',
			'profile_success' => $this->input->get('success', TRUE),
			'form' => $form,
			'country_codes' => $codes,
			'genders' => $genders,
			'can_choose_type' => $can_choose_type,
			'can_change_password' => $can_change_password,
			'full_edit' => $full_edit,
			'can_change_avatar' => $can_change_avatar,
		));
	}

	public function password($id = 0)
	{
		$id = (int) $id;
		$user = $this->User_model->get_by_id($id);
		if (!$user) {
			show_404();
		}

		$is_system = is_system_user();
		$is_self = $id === current_user_id();
		if (!$is_system && !$is_self) {
			redirect('profile?password_error='.rawurlencode('You can only change your own password.'));
		}

		$back = $is_self ? 'profile' : 'users/'.$id.'/edit';

		if ($this->input->method() !== 'post') {
			redirect($back);
		}

		$password = (string) $this->input->post('password');
		$confirm = (string) $this->input->post('confirm_password');
		$error = $this->_validate_password($password, $confirm);

		if ($error !== '') {
			redirect($back.'?password_error='.rawurlencode($error).'#change-password');
		}

		$this->User_model->update_user($id, array(
			'password' => password_hash($password, PASSWORD_DEFAULT),
		));
		redirect($back.'?password_success=1#change-password');
	}

	private function _read_form($can_choose_type, $codes, $existing_type)
	{
		$form = array(
			'display_name' => trim((string) $this->input->post('display_name', TRUE)),
			'username' => trim((string) $this->input->post('username', TRUE)),
			'email' => trim((string) $this->input->post('email', TRUE)),
			'country_code' => trim((string) $this->input->post('country_code', TRUE)),
			'phone' => preg_replace('/\D+/', '', (string) $this->input->post('phone', TRUE)),
			'gender_id' => trim((string) $this->input->post('gender_id', TRUE)),
			'avatar_color' => '#0d6efd',
			'user_type' => trim((string) $this->input->post('user_type', TRUE)),
		);

		if ($form['country_code'] !== '' && !isset($codes[$form['country_code']])) {
			$form['country_code'] = '';
		}
		if ($form['gender_id'] !== '' && !$this->Gender_model->is_valid_active((int) $form['gender_id'])) {
			$form['gender_id'] = '';
		}

		if ($can_choose_type) {
			if ($form['user_type'] !== '' && !in_array($form['user_type'], array('normal', 'system'), TRUE)) {
				$form['user_type'] = '';
			}
		} else {
			$form['user_type'] = $existing_type === NULL ? 'normal' : $existing_type;
		}

		return $form;
	}

	private function _validate_profile($form, $require_user_type, $codes, $display_name_only)
	{
		if ($form['display_name'] === '') {
			return 'Display name is required.';
		}
		if ($display_name_only) {
			return '';
		}
		if ($form['username'] === '') {
			return 'Username is required.';
		}
		if ($form['email'] === '') {
			return 'Email is required.';
		}
		if ($form['country_code'] === '' || !isset($codes[$form['country_code']])) {
			return 'Country code is required.';
		}
		if ($form['phone'] === '') {
			return 'Phone number is required.';
		}
		if (!preg_match('/^[0-9]{6,15}$/', $form['phone'])) {
			return 'Phone number must be 6–15 digits.';
		}
		if ($form['gender_id'] === '' || !$this->Gender_model->is_valid_active((int) $form['gender_id'])) {
			return 'Gender is required.';
		}
		if ($require_user_type && ($form['user_type'] !== 'normal' && $form['user_type'] !== 'system')) {
			return 'User type is required.';
		}
		if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $form['username'])) {
			return 'Username must be 3–30 letters, numbers, or underscores.';
		}
		if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
			return 'Please enter a valid email address.';
		}
		return '';
	}

	private function _validate_password($password, $confirm)
	{
		if ($password === '') {
			return 'Password is required.';
		}
		if ($confirm === '') {
			return 'Confirm password is required.';
		}
		if (strlen($password) < 6) {
			return 'Password must be at least 6 characters.';
		}
		if ($password !== $confirm) {
			return 'Passwords do not match.';
		}
		return '';
	}

	private function _unique_error($form, $exclude_id = 0)
	{
		$conflicts = $this->User_model->uniqueness_conflicts(
			$form['username'],
			$form['email'],
			$form['country_code'],
			$form['phone'],
			$exclude_id
		);
		if (empty($conflicts)) {
			return '';
		}
		$labels = array(
			'username' => 'Username',
			'email' => 'Email',
			'phone' => 'Phone number',
		);
		$parts = array();
		foreach ($conflicts as $key) {
			$parts[] = isset($labels[$key]) ? $labels[$key] : $key;
		}
		return implode(', ', $parts) . ' already in use.';
	}

	public function check_unique()
	{
		if ($this->input->method() !== 'get') {
			$this->output->set_status_header(405)->set_content_type('application/json')
				->set_output(json_encode(array('ok' => FALSE, 'error' => 'Method not allowed')));
			return;
		}

		$field = trim((string) $this->input->get('field', TRUE));
		$value = trim((string) $this->input->get('value', TRUE));
		$country_code = trim((string) $this->input->get('country_code', TRUE));
		$exclude_id = (int) $this->input->get('exclude_id');
		$available = TRUE;
		$message = '';

		if ($field === 'username') {
			if ($value === '') {
				$available = FALSE;
				$message = 'Username is required.';
			} elseif ($this->User_model->username_exists($value, $exclude_id)) {
				$available = FALSE;
				$message = 'Username is already taken.';
			}
		} elseif ($field === 'email') {
			if ($value === '') {
				$available = FALSE;
				$message = 'Email is required.';
			} elseif ($this->User_model->email_exists($value, $exclude_id)) {
				$available = FALSE;
				$message = 'Email is already taken.';
			}
		} elseif ($field === 'phone') {
			$phone = preg_replace('/\D+/', '', $value);
			if ($country_code === '' || $phone === '') {
				$available = FALSE;
				$message = 'Country code and phone are required.';
			} elseif ($this->User_model->phone_exists($country_code, $phone, $exclude_id)) {
				$available = FALSE;
				$message = 'Phone number is already taken.';
			}
		} else {
			$this->output->set_status_header(422)->set_content_type('application/json')
				->set_output(json_encode(array('ok' => FALSE, 'error' => 'Invalid field')));
			return;
		}

		$this->output->set_content_type('application/json')->set_output(json_encode(array(
			'ok' => TRUE,
			'available' => $available,
			'message' => $message,
			'field' => $field,
		)));
	}

	private function _handle_avatar_upload()
	{
		if (empty($_FILES['avatar']) || !is_uploaded_file($_FILES['avatar']['tmp_name'])) {
			return array('path' => NULL, 'error' => '');
		}

		$file = $_FILES['avatar'];
		if (!empty($file['error']) && (int) $file['error'] !== UPLOAD_ERR_OK) {
			return array('path' => NULL, 'error' => 'Avatar upload failed.');
		}

		$max = 2 * 1024 * 1024;
		if ((int) $file['size'] > $max) {
			return array('path' => NULL, 'error' => 'Avatar must be 2MB or smaller.');
		}

		$finfo = new finfo(FILEINFO_MIME_TYPE);
		$mime = $finfo->file($file['tmp_name']);
		$allowed = array(
			'image/jpeg' => 'jpg',
			'image/png' => 'png',
			'image/gif' => 'gif',
			'image/webp' => 'webp',
		);
		if (!isset($allowed[$mime])) {
			return array('path' => NULL, 'error' => 'Avatar must be an image (JPG, PNG, GIF, or WEBP).');
		}

		$dir = FCPATH . 'uploads/avatars/';
		if (!is_dir($dir)) {
			mkdir($dir, 0755, TRUE);
		}

		$stored = date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
		$dest = $dir . $stored;
		if (!move_uploaded_file($file['tmp_name'], $dest)) {
			return array('path' => NULL, 'error' => 'Could not save avatar image.');
		}

		return array('path' => 'uploads/avatars/' . $stored, 'error' => '');
	}

	private function _apply_avatar_changes($user, &$data)
	{
		$remove = (string) $this->input->post('remove_avatar') === '1';
		$upload = $this->_handle_avatar_upload();
		if ($upload['error'] !== '') {
			return $upload['error'];
		}

		if ($upload['path']) {
			$this->_delete_avatar_file(isset($user['avatar_path']) ? $user['avatar_path'] : '');
			$data['avatar_path'] = $upload['path'];
			return '';
		}

		if ($remove) {
			$this->_delete_avatar_file(isset($user['avatar_path']) ? $user['avatar_path'] : '');
			$data['avatar_path'] = NULL;
		}

		return '';
	}

	private function _delete_avatar_file($relative)
	{
		if ($relative === '' || $relative === NULL) {
			return;
		}
		$path = FCPATH . $relative;
		if (is_file($path)) {
			@unlink($path);
		}
	}
}
