<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = 'auth';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

$route['login'] = 'auth/login';
$route['register'] = 'auth/register';
$route['logout'] = 'auth/logout';

$route['chat'] = 'chat/index';
$route['profile'] = 'users/profile';
$route['users'] = 'users/index';
$route['users/add'] = 'users/add';
$route['users/check-unique'] = 'users/check_unique';
$route['users/(:num)/edit'] = 'users/edit/$1';
$route['users/(:num)/password'] = 'users/password/$1';

$route['friends'] = 'friends/index';
$route['friends/search'] = 'friends/search';
$route['friends/add/(:num)'] = 'friends/add/$1';
$route['friends/accept/(:num)'] = 'friends/accept/$1';
$route['friends/remove/(:num)'] = 'friends/remove/$1';
$route['friends/unfriend/(:num)'] = 'friends/unfriend/$1';

$route['api/conversations'] = 'api/conversations';
$route['api/messages'] = 'api/messages';
$route['api/message-played'] = 'api/message_played';
$route['api/message-delete'] = 'api/message_delete';
$route['api/conversation-clear'] = 'api/conversation_clear';
$route['api/conversation-delete'] = 'api/conversation_delete';
$route['api/typing'] = 'api/typing';
$route['files/view/(:num)'] = 'files/view/$1';
$route['files/download/(:num)'] = 'files/download/$1';
$route['files/avatar/(:num)'] = 'files/avatar/$1';
