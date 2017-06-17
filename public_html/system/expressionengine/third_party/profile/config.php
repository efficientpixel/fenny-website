<?php
$config['name'] = 'Profile:Edit';
$config['version'] = '1.2.1';
$config['nsm_addon_updater']['versions_xml'] = 'http://mightybigrobot.com/products/version/profile-edit';
$config['index']['availability'] = '<a class="btn btn-primary" href="http://mightybigrobot.com/products/detail/profile-edit/">Buy Now</a>';
$config['index']['version'] = $config['version']; 
$config['index']['title'] = $config['name']; 

$config['docs_location'] = 'profile-edit';

$config['default_settings'] = array(
	'channel_id' => FALSE,
	'can_admin_members' => FALSE,
	'allow_multiple_profiles' => '0',
	'use_email_as_username' => '0',
	'login_after_email_activation' => '0',
	'global_profile_variables' => '0',
	'auto_title_screen_name' => '1',
	'auto_url_title_username' => '1',
	'require_current_password' => '1',
	'require_password_confirm' => '1',
	'require_email_confirm' => '1',
	'auto_login_after_register' => '1',
);