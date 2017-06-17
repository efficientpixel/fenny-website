<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * mithra62 - Backup Pro
 *
 * @author		Eric Lamb
 * @copyright	Copyright (c) 2015, mithra62, Eric Lamb.
 * @link		http://mithra62.com/projects/view/backup-pro/
 * @version		2.0
 * @filesource 	./system/expressionengine/third_party/backup_pro/
 */
 
 /**
 * Backup Pro - Settings Model
 *
 * Settings Model class
 *
 * @package 	mithra62\BackupPro
 * @author		Eric Lamb
 * @filesource 	./system/expressionengine/third_party/backup_pro/models/Backup_pro_settings_model.php
 */
class Backup_pro_settings_model extends CI_Model
{
	/**
	 * The settings table name
	 * @var string
	 */
	private $_table = 'backup_pro_settings';
	
	/**
	 * The settings Backup Pro offers with a default value
	 * @var array
	 */
	public $_defaults = array(
		'allowed_access_levels' => '',
		'auto_threshold' => '0',
		'auto_threshold_custom' => '',
		'exclude_paths' => '',
		'enable_cron' => '0',
		'cron_notify_member_ids' => array(), //the member_ids we're going send notifications to on Cron success
		'cron_attach_backups' => '0',
		'cron_attach_threshold' => '0',
		'cron_notify_email_subject' => '',
		'cron_notify_email_message' => '',
		'cron_notify_email_mailtype' => 'html',
		'ftp_hostname' => '',
		'ftp_username' => '',
		'ftp_password' => '',
		'ftp_debug' => '0',
		'ftp_port' => '21',
		'ftp_passive' => '0',
		'ftp_store_location' => '',
		'ftp_prune_remote' => '1',
		'license_number' => '',
		'license_check' => 0,
		'license_status' => '',
		'backup_store_location' => '',
		'backup_file_location' => '',
		's3_access_key' => '',
		's3_secret_key' => '',
		's3_bucket' => '',
		's3_prune_remote' => '1',
		'cf_username' => '',
		'cf_api' => '',
		'cf_bucket' => '',
		'cf_location' => 'us',
		'cf_prune_remote' => '1',
		'gcs_access_key' => '',
		'gcs_secret_key' => '',
		'gcs_bucket' => '',
		'gcs_prune_remote' => '1',
		'max_file_backups' => '0',
		'max_db_backups' => '0',
		'date_format' => '%M %d, %Y, %h:%i:%s%A',
		'relative_time' => '1',
		'db_backup_method' => 'php', //mysqldump
		'db_restore_method' => 'php', //mysql
		'db_backup_execute_pre_sql' => '', //these get executed against MySQL before a backup starts
		'db_backup_execute_post_sql' => '', //these get executed against MySQL after a backup finishes
		'db_backup_archive_pre_sql' => '', //these get written in the backup SQL dump at the top
		'db_backup_archive_post_sql' => '', //these get written in the backup SQL dump at the bottom
		'db_backup_ignore_tables' => array(), //what MySQL tables to ignore from the backup?
		'db_backup_ignore_table_data' => array(), //which tables should we not bother grabbing the data for?
		'mysqldump_command' => 'mysqldump',
		'mysqlcli_command' => 'mysql',
		'dashboard_recent_total' => '5',
		'db_backup_alert_threshold' => '1',
		'file_backup_alert_threshold' => '7',

		'backup_state_notify_member_ids' => array(), //the member_ids we're going send notifications to on backup state issues
		'backup_state_notify_email_subject' => '',
		'backup_state_notify_email_message' => '',
		'backup_state_notify_email_mailtype' => 'html',
		
		'backup_missed_schedule_notify_member_ids' => array(),
		'backup_missed_schedule_notify_email_mailtype' => 'html',
		'backup_missed_schedule_notify_email_subject' => '',
		'backup_missed_schedule_notify_email_message' => '',
		'backup_missed_schedule_notify_email_last_sent' => '0', //unix timestamp for determining whether to send an email
		'backup_missed_schedule_notify_email_interval' => 8, //hours between when backup state emails should be sent
		
		'disable_accordions' => TRUE
	);
	
	/**
	 * The settings keys that should be serialized for storage
	 * @var array
	 */
	private $_serialized = array(
		'cron_notify_member_ids',
		'backup_missed_schedule_notify_member_ids',
		'exclude_paths',
		'backup_file_location',
		'db_backup_execute_pre_sql',
		'db_backup_execute_post_sql',
		'db_backup_archive_pre_sql',
		'db_backup_archive_post_sql',
		'db_backup_ignore_tables',
		'db_backup_ignore_table_data'
	);
	
	/**
	 * The settings keys that should be encrypted for storage
	 * @var array
	 */
	private $_encrypted = array(
		'ftp_username',
		'ftp_password',
		's3_access_key',
		's3_secret_key',
		'cf_username',
		'cf_api',
		'gcs_access_key',
		'gcs_secret_key',
	);	
	
	/**
	 * The settings keys that are used for binary data
	 * @var array
	 */
	public $checkboxes = array(
		'cron_attach_backups',
		'ftp_passive',
		'cf_prune_remote',
		's3_prune_remote',
		'ftp_prune_remote',
		'relative_time'
	);
	
	/**
	 * The settings keys that have a custom option available
	 * @var array
	 */
	public $custom_options = array(
		'cp_reg_email_expire_ttl',
		'pw_ttl',
		'pw_expire_ttl',
		'member_expire_ttl'
	);
	
	/**
	 * The options available for scheduling
	 * @var array
	 */
	public $auto_threshold_options = array(
		'0' => 'Disabled',
		'104857600' => '100MB',
		'262144000' => '250MB',
		'524288000' => '500MB',
		'786432000' => '750MB',
		'1073741824' => '1GB',
		'5368709120' => '5GB',
		'10737418240' => '10GB',
		'custom' => 'Custom'
	);	
	
	public function __construct()
	{
		parent::__construct();
		$this->load->library('encrypt');
		
		//check for Apache
		if(isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] != '')
		{
			$this->_defaults['backup_file_location'] = realpath($_SERVER['DOCUMENT_ROOT']);
		}
		else //stupid IIS fucking things up for us again. 
		{
			if(isset($_SERVER['SCRIPT_FILENAME']))
			{
				$path = str_replace( '\\', '/', substr($_SERVER['SCRIPT_FILENAME'], 0, 0-strlen($_SERVER['PHP_SELF'])));
			}
			elseif(isset($_SERVER['PATH_TRANSLATED']))
			{
				$path = str_replace( '\\', '/', substr(str_replace('\\\\', '\\', $_SERVER['PATH_TRANSLATED']), 0, 0-strlen($_SERVER['PHP_SELF'])));
			}

			$this->_defaults['backup_file_location'] = realpath($path);
		}

		$this->_defaults['backup_store_location'] = realpath(dirname(realpath(__FILE__)).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'backups');
		$this->_defaults['cron_notify_email_message'] = lang('default_cron_message');
		$this->_defaults['cron_notify_email_subject'] = lang('default_cron_subject');
		$this->_defaults['backup_missed_schedule_notify_email_subject'] = lang('default_backup_missed_schedule_notify_email_subject');
		$this->_defaults['backup_missed_schedule_notify_email_message'] = lang('default_backup_missed_schedule_notify_email_message');
	}
	
	/**
	 * Adds a setting to the databse
	 * @param string $setting
	 */
	public function add_setting($setting)
	{
		$data = array(
		   'setting_key' => $setting,
		   'setting_value' => ''
		);
		
		return $this->db->insert($this->_table, $data); 
	}	
	
	public function get_settings()
	{
		$this->db->select('setting_key, setting_value, `serialized`');
		$query = $this->db->get($this->_table);	
		$_settings = $query->result_array();
		$settings = array();	
		foreach($_settings AS $setting)
		{
			$settings[$setting['setting_key']] = ($setting['serialized'] == '1' ? unserialize($setting['setting_value']) : $setting['setting_value']);
		}
		
		//now check to make sure they're all there and set default values if not
		foreach ($this->_defaults as $key => $value)
		{	
			//setup the override check
			if(isset($this->config->config['backup_pro'][$key]))
			{
				$settings[$key] = $this->config->config['backup_pro'][$key];
				if(in_array($key, $this->_encrypted) && $settings[$key] != '')
				{
					$settings[$key] = $this->encrypt->encode($settings[$key]);
				}				
			}
						
			if(!isset($settings[$key]))
			{
				$settings[$key] = $value;
			}
		}		

		if($settings['backup_file_location'] == '')
		{
			$settings['backup_file_location'] = $this->_defaults['backup_file_location'];
		}
		
		if($settings['backup_store_location'] == '')
		{
			$settings['backup_store_location'] = $this->_defaults['backup_store_location'];
		}
		
		//little sanity check to ensure we can use the `system()` function for SQL backups and set to PHP accordingly
		if(!function_exists('system'))
		{
			$settings['db_backup_method'] = $settings['db_restore_method'] = 'php';
		}

		$settings['max_file_backups'] = (int)$settings['max_file_backups'];
		$settings['max_db_backups'] = (int)$settings['max_db_backups'];

		return $settings;
	}
	
	/**
	 * Returns the value straigt from the database
	 * @param string $setting
	 */
	public function get_setting($setting)
	{
		return $this->db->get_where($this->_table, array('setting_key' => $setting))->result_array();
	}	
	
	public function update_settings(array $data)
	{
		$this->load->library('encrypt');
		
		foreach($this->checkboxes As $key => $value)
		{
			if(!isset($data[$value]))
			{
				$data[$value] = '0';	
			}
		}	
		
		foreach($this->custom_options As $key => $value)
		{
			if(isset($data[$value]) && $data[$value] == 'custom' && $data[$value.'_custom'] != '')
			{
				$data[$value] = $data[$value.'_custom'];
			}
		}
		
		foreach($data AS $key => $value)
		{
			
			if(in_array($key, $this->_serialized))
			{
				$value = (!is_array($value) ? explode("\n", $value) : $value);			
			}
			
			if(in_array($key, $this->_encrypted) && $value != '')
			{
				$value = $this->encrypt->encode($value);
			}
			
			$this->update_setting($key, $value);
		}
		
		return TRUE;
	}
	
	/**
	 * Updates the value of a setting
	 * @param string $key
	 * @param string $value
	 */
	public function update_setting($key, $value)
	{
		if(!$this->_check_setting($key))
		{
			return FALSE;
		}

		$data = array();
		if(is_array($value))
		{
			$value = serialize($value);
			$data['serialized '] = '1';
		}
		
		$data['setting_value'] = $value;
		$this->db->where('setting_key', $key);
		$this->db->update($this->_table, $data);
	}

	/**
	 * Verifies that a submitted setting is valid and exists. If it's valid but doesn't exist it is created.
	 * @param string $setting
	 */
	private function _check_setting($setting)
	{
		if(array_key_exists($setting, $this->_defaults))
		{
			if(!$this->get_setting($setting))
			{
				$this->add_setting($setting);
			}
			
			return TRUE;
		}		
	}	
	
	public function get_member_groups()
	{
		$this->db->select('group_title , group_id')->where('group_id != 1');
		$query = $this->db->get('member_groups');	
		$_groups = $query->result_array();	
		$groups = array();
		$groups[''] = '';
		foreach($_groups AS $group)
		{
			$groups[$group['group_id']] = $group['group_title'];
		}
		return $groups;
	}
	
	/**
	 * Returns a settings friendly array for the existing tables
	 * @return multitype:array
	 */
	public function get_db_tables()
	{
		$return = array();
		$tables = $this->db->query('SHOW TABLES')->result_array();
		foreach($tables AS $table)
		{
			foreach($table AS $key => $value)
			{
				$return[$value] = $value;
			}
		}
		
		return $return;	
	}
}