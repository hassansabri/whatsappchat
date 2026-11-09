<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('avatar_initials')) {
	function avatar_initials($name)
	{
		$name = trim((string) $name);
		if ($name === '') {
			return '?';
		}
		if (function_exists('mb_substr')) {
			return mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8');
		}
		return strtoupper(substr($name, 0, 1));
	}
}

if (!function_exists('user_avatar_url')) {
	function user_avatar_url($user)
	{
		if (empty($user['avatar_path']) || empty($user['id'])) {
			return NULL;
		}
		$path = FCPATH . $user['avatar_path'];
		if (!is_file($path)) {
			return NULL;
		}
		return site_url('files/avatar/'.(int) $user['id']);
	}
}

if (!function_exists('render_user_avatar')) {
	function render_user_avatar($user, $extra_class = '')
	{
		$online = !empty($user['online']) ? ' online' : '';
		$class = trim('avatar '.$extra_class.$online);
		$url = user_avatar_url($user);
		$color = !empty($user['avatar_color']) ? $user['avatar_color'] : '#0d6efd';
		$letter = avatar_initials(isset($user['display_name']) ? $user['display_name'] : '');
		if ($url) {
			return '<div class="'.html_escape($class).' has-image"><img src="'.html_escape($url).'" alt=""></div>';
		}
		return '<div class="'.html_escape($class).'" style="background: '.html_escape($color).'">'.html_escape($letter).'</div>';
	}
}

if (!function_exists('is_user_online')) {
	function is_user_online($last_seen)
	{
		if (empty($last_seen)) {
			return FALSE;
		}
		return strtotime($last_seen) >= time() - 60;
	}
}

if (!function_exists('avatar_colors')) {
	function avatar_colors()
	{
		return array('#0d6efd', '#198754', '#dc3545', '#fd7e14', '#6f42c1', '#20c997', '#0dcaf0', '#6610f2');
	}
}

if (!function_exists('country_codes')) {
	function country_codes()
	{
		return array(
			'+92' => 'Pakistan (+92)',
			'+91' => 'India (+91)',
			'+971' => 'UAE (+971)',
			'+966' => 'Saudi Arabia (+966)',
			'+1' => 'USA / Canada (+1)',
			'+44' => 'United Kingdom (+44)',
			'+61' => 'Australia (+61)',
			'+49' => 'Germany (+49)',
			'+33' => 'France (+33)',
			'+39' => 'Italy (+39)',
			'+34' => 'Spain (+34)',
			'+81' => 'Japan (+81)',
			'+86' => 'China (+86)',
			'+82' => 'South Korea (+82)',
			'+65' => 'Singapore (+65)',
			'+60' => 'Malaysia (+60)',
			'+62' => 'Indonesia (+62)',
			'+63' => 'Philippines (+63)',
			'+880' => 'Bangladesh (+880)',
			'+94' => 'Sri Lanka (+94)',
			'+977' => 'Nepal (+977)',
			'+20' => 'Egypt (+20)',
			'+27' => 'South Africa (+27)',
			'+55' => 'Brazil (+55)',
			'+52' => 'Mexico (+52)',
			'+7' => 'Russia / Kazakhstan (+7)',
			'+90' => 'Turkey (+90)',
			'+31' => 'Netherlands (+31)',
			'+46' => 'Sweden (+46)',
			'+41' => 'Switzerland (+41)',
		);
	}
}

if (!function_exists('format_phone_display')) {
	function format_phone_display($country_code, $phone)
	{
		$code = trim((string) $country_code);
		$num = trim((string) $phone);
		if ($code === '' && $num === '') {
			return '—';
		}
		return trim($code.' '.$num);
	}
}

if (!function_exists('require_login')) {
	function require_login()
	{
		$CI =& get_instance();
		if (!$CI->session->userdata('user_id')) {
			redirect('login');
		}
	}
}

if (!function_exists('current_user_id')) {
	function current_user_id()
	{
		$CI =& get_instance();
		return (int) $CI->session->userdata('user_id');
	}
}

if (!function_exists('normalize_user_type')) {
	function normalize_user_type($type)
	{
		return $type === 'system' ? 'system' : 'normal';
	}
}

if (!function_exists('is_system_user')) {
	function is_system_user($user = NULL)
	{
		if (is_array($user)) {
			return normalize_user_type(isset($user['user_type']) ? $user['user_type'] : 'normal') === 'system';
		}
		$CI =& get_instance();
		$type = $CI->session->userdata('user_type');
		if ($type === NULL) {
			$CI->load->model('User_model');
			$row = $CI->User_model->get_by_id(current_user_id());
			$type = $row && isset($row['user_type']) ? $row['user_type'] : 'normal';
			$CI->session->set_userdata('user_type', normalize_user_type($type));
		}
		return normalize_user_type($type) === 'system';
	}
}

if (!function_exists('require_system_user')) {
	function require_system_user()
	{
		require_login();
		if (!is_system_user()) {
			show_error('Only system users can manage accounts.', 403);
		}
	}
}

if (!function_exists('can_message_user')) {
	function can_message_user($from_id, $to_id)
	{
		$CI =& get_instance();
		$CI->load->model('User_model');
		$CI->load->model('Friend_model');

		$from = $CI->User_model->get_by_id((int) $from_id);
		$to = $CI->User_model->get_by_id((int) $to_id);
		if (!$from || !$to || (int) $from_id === (int) $to_id) {
			return FALSE;
		}
		if (is_system_user($from)) {
			return TRUE;
		}
		return $CI->Friend_model->are_friends($from_id, $to_id);
	}
}
