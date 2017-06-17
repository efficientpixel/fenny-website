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
 * Backup Pro - Notification Library
 *
 * Base Library class
 *
 * @package 	mithra62\BackupPro
 * @author		Eric Lamb
 * @filesource 	./system/expressionengine/third_party/backup_pro/libraries/Backup_pro_notify.php
 */
class Backup_pro_notify
{	
	/**
	 * @ignore
	 */
	public function __construct()
	{
		$this->settings = ee()->backup_pro->get_settings();
		if(!isset(ee()->TMPL))
		{
			ee()->load->library('Template', null, 'TMPL');
		}	
		ee()->lang->loadfile('backup_pro');	
	}
	
	/**
	 * Sends the notification for a bad backup state
	 * @param array $errors
	 */
	public function send_backup_state(array $errors)
	{
		$to = array();
		if(!is_array($this->settings['backup_missed_schedule_notify_member_ids']) || count($this->settings['backup_missed_schedule_notify_member_ids']) == '0')
		{
			return;
		}
		
		$members = ee()->db->select('email')->from('members')->where_in('member_id', $this->settings['backup_missed_schedule_notify_member_ids'])->get()->result_array();
		foreach($members AS $email)
		{
			if($this->check_email($email['email']))
			{
				$to[] = $email['email'];
			}
		}
		
		if(count($to) == '0')
		{
			return FALSE;
		}
		
		$type = 'files';
		$frequency = $this->settings['file_backup_alert_threshold'];
		if(isset($errors['backup_state_db_backups']))
		{
			$type = 'database';
			$frequency = $this->settings['db_backup_alert_threshold'];
		}
		
		$backups = ee()->backup_pro->get_backups();
		$meta = ee()->backup_pro->get_backup_meta($backups);
		
		$vars = array('last_backup_date' => $meta[$type]['newest_backup_taken'], 'backup_frequency' => $frequency, 'backup_type' => $type);
		
		$vars = array_merge($vars, ee()->config->config);
		$subject = ee()->TMPL->parse_variables($this->settings['backup_missed_schedule_notify_email_subject'], array($vars));
		$message = ee()->TMPL->parse_variables($this->settings['backup_missed_schedule_notify_email_message'], array($vars));
		
		ee()->email->from(ee()->config->config['webmaster_email'], ee()->config->config['site_name']);
		ee()->email->to($to);
		ee()->email->mailtype = $this->settings['backup_missed_schedule_notify_email_mailtype'];
		ee()->email->subject($subject);
		ee()->email->message($message);
		
		ee()->email->send();
		ee()->email->clear();
	}
	
	/**
	 * Sends the Cron notification
	 * @param array $backup_paths
	 * @param string $backup_type
	 * @return void|boolean
	 */
	public function send_cron_notification(array $backup_paths, $backup_type = 'database')
	{
		$to = array();
		if(!is_array($this->settings['cron_notify_member_ids']) || count($this->settings['cron_notify_member_ids']) == '0')
		{
			return;
		}
		
		$members = ee()->db->select('email')->from('members')->where_in('member_id', $this->settings['cron_notify_member_ids'])->get()->result_array();
		foreach($members AS $email)
		{
			if($this->check_email($email['email']))
			{
				$to[] = $email['email'];
			}
		}
		
		if(count($to) == '0')
		{
			return FALSE;
		}
		
		$backup_details = array();
		foreach($backup_paths As $type => $path)
		{
			$path_parts = pathinfo($path);
			if($path_parts)
			{
				$details_path = $path_parts['dirname'];
				$file_name = $path_parts['basename'];
				$details = ee()->backup_pro->parse_filename($file_name, $type);
				unset($details['details']);
				$backup_details[] = $details;
			}
		}
		
		$vars = array_merge($backup_paths, array('backup_type' => $backup_type),  ee()->config->config);
		$subject = ee()->TMPL->parse_variables($this->settings['cron_notify_email_subject'], array($vars));
		$message = ee()->TMPL->parse_variables($this->settings['cron_notify_email_message'], array($vars));
		$message = ee()->TMPL->parse_variables($message, $backup_details);
		
		ee()->email->from(ee()->config->config['webmaster_email'], ee()->config->config['site_name']);
		ee()->email->to($to);
		ee()->email->mailtype = $this->settings['cron_notify_email_mailtype'];
		ee()->email->subject($subject);
		ee()->email->message($message);
		
		$attachments = array();
		if($this->settings['cron_attach_backups'] == '1')
		{
			foreach($backup_paths AS $key => $path)
			{
				if(file_exists($path) && ((filesize($path) < $this->settings['cron_attach_threshold']) || $this->settings['cron_attach_threshold'] == '0'))
				{
					//make pretty files
					if($key == 'db_backup')
					{
						$type = 'db';
					}
					else
					{
						$type = 'files';
					}
					
					$new_name = dirname($path).'/../'.$this->make_pretty_filename(basename($path), $type);
					if(copy($path, $new_name))
					{
						ee()->email->attach($new_name);
						$attachments[] = $new_name;
					}
				}
			}
		}
		
		ee()->email->send();
		ee()->email->clear();
		if(count($attachments) >= '1')
		{
			foreach($attachments AS $file)
			{
				if(file_exists($file))
				{
					@unlink($file);
				}
			}
		}		
	}
	
	/**
	 * Returns an array of members who can be notified by BP
	 * @param string $module_name
	 * @return multitype:unknown
	 */
	public function get_allowed_notify_members($module_name)
	{
		$member_group_ids = ee()->db->select('group_id')->from('module_member_groups')
				   ->where(array('module_name' => $module_name))
				   ->join('modules', 'modules.module_id = module_member_groups.module_id')
				   ->get()->result_array();
		
		$group_ids = array();
		foreach($member_group_ids AS $group)
		{
			$group_ids[] = $group['group_id'];
		}
		
		$group_ids[] = 1;
		$members = ee()->db->select(array('member_id', 'email'))->from('members')
				->where_in('group_id', $group_ids)
				->get()->result_array();

		$return = array('0' => '');
		foreach($members AS $member)
		{
			$return[$member['member_id']] = $member['email'];
		}
		
		return $return;
	}
	
	/**
	 * Validates an email address
	 * @param string $email
	 * @return mixed
	 */
	public function check_email($email)
	{
		if(function_exists('filter_var'))
		{
			return filter_var($email, FILTER_VALIDATE_EMAIL);
		}
		else
		{
			return $this->valid_email($email);
		}
	}
	
	/**
	 * Simpler validation email method
	 * @param string $email
	 * @return boolean
	 */
	public function valid_email($email)
	{
		ee()->load->helper('email');
		return valid_email($email);
	}
}