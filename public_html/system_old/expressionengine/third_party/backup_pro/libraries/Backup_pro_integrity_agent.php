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
 * Backup Pro - Integrity Agent Library
 *
 * Integrity Agent Library class
 *
 * @package 	mithra62\BackupPro
 * @author		Eric Lamb
 * @filesource 	./system/expressionengine/third_party/backup_pro/libraries/Backup_pro_integrity_agent.php
 */
class Backup_pro_integrity_agent
{
	/**
	 * @ignore
	 */
	public function __construct()
	{
		$this->settings = ee()->backup_pro->get_settings();
	}
	
	/**
	 * Checks the existing backups on the system and ensure's things are kosher
	 */
	public function monitor_backup_state()
	{	
		$errors = ee()->backup_pro->error_check();
		if(isset($errors['backup_state_db_backups']) || isset($errors['backup_state_files_backups']))
		{
			//we have a winner! start the notification process
			ee()->load->library('Backup_pro_notify', null, 'notify');
			$last_notified = $this->settings['backup_missed_schedule_notify_email_last_sent'];
			$next_notified = mktime(date('G', $last_notified)+$this->settings['backup_missed_schedule_notify_email_interval'], date('i', $last_notified), 0, date('n', $last_notified), date('j', $last_notified), date('Y', $last_notified));
			
			if(time() > $next_notified)
			{
				ee()->notify->send_backup_state($errors);
				ee()->backup_pro_settings->update_setting('backup_missed_schedule_notify_email_last_sent', time());
			}
		}		
	}
}