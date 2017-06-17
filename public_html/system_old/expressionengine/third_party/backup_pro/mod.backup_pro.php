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
 * Backup Pro - Mod
 *
 * Module class
 *
 * @package 	mithra62\BackupPro
 * @author		Eric Lamb
 * @filesource 	./system/expressionengine/third_party/backup_pro/mod.backup_pro.php
 */
class Backup_pro {

	public $return_data	= '';
	
	public function __construct()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->db_conf = array(
			 'user' => ee()->db->username, 
			 'pass' => ee()->db->password,
			 'db_name' => ee()->db->database, 
			 'host' => ee()->db->hostname
		);
		
		ee()->load->model('backup_pro_settings_model', 'backup_pro_settings', TRUE);
		ee()->load->library('backup_pro_lib', null, 'backup_pro');
		ee()->load->library('backup_pro_sql_backup');	
		ee()->load->library('logger');
		ee()->load->library('email');	
		ee()->load->helper('file');
		ee()->load->library('encrypt');
		ee()->load->library('javascript');
			
		$this->settings = ee()->backup_pro->get_settings();
		ee()->backup_pro->set_backup_dir($this->settings['backup_store_location']);

		if($this->settings['max_db_backups'] > '0')
		{
			ee()->backup_pro->cleanup_backup_count('database', $this->settings['max_db_backups']);
		}
		
		if($this->settings['max_file_backups'] > '0')
		{
			ee()->backup_pro->cleanup_backup_count('files', $this->settings['max_file_backups']);
		}
				
		$this->total_space_used = ee()->backup_pro->get_space_used();
		if($this->total_space_used > $this->settings['auto_threshold'])
		{
			ee()->backup_pro->cleanup_auto_threshold_backups($this->settings['auto_threshold'], $this->total_space_used);
		}	
	}
	
	public function void()
	{
		
	}
	
	public function cron()
	{
		ini_set('memory_limit', -1);
		set_time_limit(0); //limit the time to 1 hours

		$type = ee()->input->get_post('type');
		ee()->backup_pro->set_db_info($this->db_conf);
		$path = ee()->backup_pro->make_db_filename();	
		$backup_paths = array();
		switch($type)
		{
			case 'db':
			default:
				$backup_paths['database'] = ee()->backup_pro_sql_backup->backup($path, $this->db_conf);
				$backup_type = 'database';
			break;
			
			case 'files':
				$backup_paths['files'] = ee()->backup_pro->backup_files();
				$backup_type = 'files';
			break;
			
			case 'combined':
				$backup_paths['database'] = ee()->backup_pro_sql_backup->backup($path, $this->db_conf);
				$backup_paths['files'] = ee()->backup_pro->backup_files();
				$backup_type = 'combined';
			break;
		}
		
		if(count($backup_paths) >= 1 && count($this->settings['cron_notify_member_ids']) >= 1)
		{
			ee()->load->library('Backup_pro_notify', null, 'notify');
			foreach($backup_paths As $type => $path)
			{
				$cron = array($type => $path);
				ee()->notify->send_cron_notification($cron, $type);
			}
		}
	}
}