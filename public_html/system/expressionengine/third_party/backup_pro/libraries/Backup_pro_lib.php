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

if(!class_exists('PclZip62'))
{
	/**
	 * Setup M62_Pclzip
	 */
	include_once 'pclzip.lib.php';
}

 /**
 * Backup Pro - Base Library
 *
 * Base Library class
 *
 * @package 	mithra62\BackupPro
 * @author		Eric Lamb
 * @filesource 	./system/expressionengine/third_party/backup_pro/libraries/Backup_pro_lib.php
 */
class Backup_pro_lib
{
	/**
	 * Preceeds URLs 
	 * @var mixed
	 */
	private $url_base = FALSE;
	
	/**
	 * String to seperate database filenames between parts
	 * @var string
	 */
	public $name_sep = '@@';
	
	/**
	 * The full path to the main backup directory
	 * @var string
	 */
	public $backup_dir;
	
	/**
	 * The full path to the database backup directory
	 * @var string
	 */
	public $backup_db_dir;
	
	/**
	 * The full path to the database files directory
	 * @var string
	 */
	public $backup_files_dir;
	
	/**
	 * The full path to the log file for the progress bar
	 * @var string
	 */
	public $progress_log_file;
	
	/**
	 * The meta details for the existing backups on the system
	 * @var array
	 */
	public $backup_meta = false;

	/**
	 * The available email formatting options
	 * @var array
	 */
	public $email_format_options = array(
		'text' => 'Text',
		'html' => 'HTML'
	);	
	
	/**
	 * @ignore
	 */
	public function __construct()
	{
		$this->settings = $this->get_settings();
		ee()->load->helper('utilities');
		ee()->load->library('Backup_pro_backup_details', null, 'backup_details');
	}
	
	/**
	 * Returns the Backup Pro settings array
	 */
	public function get_settings()
	{
		if(!isset(ee()->session->cache[__CLASS__]['settings']))
		{
			ee()->session->cache[__CLASS__]['settings'] = ee()->backup_pro_settings->get_settings();
		}
	
		return ee()->session->cache[__CLASS__]['settings'];
	}	
	
	public function get_email_format_options()
	{
		return $this->email_format_options;
	}
	
	public function get_threshold_options()
	{
		return ee()->backup_pro_settings->auto_threshold_options;
	}
	
	/**
	 * Sets up the right menu options
	 * @return multitype:string
	 */
	public function get_right_menu()
	{
		$menu = array(
			'index'			=> $this->url_base.'index',
			'backup_db'		=> $this->url_base.'backup&type=backup_db',
			'backup_files'	=> $this->url_base.'backup&type=backup_files'
		);
		
		if(ee()->session->userdata('group_id') == '1' || (isset($this->settings['allowed_access_levels']) && is_array($this->settings['allowed_access_levels'])))
		{
			if(ee()->session->userdata('group_id') == '1' || in_array(ee()->session->userdata('group_id'), $this->settings['allowed_access_levels']))
			{
				$menu['settings'] = $this->url_base.'settings'.AMP.'section=general';
			}
		}
	
		if (ee()->extensions->active_hook('backup_pro_modify_right_menu') === TRUE)
		{
			$menu = ee()->extensions->call('backup_pro_modify_right_menu', $menu);
			if (ee()->extensions->end_script === TRUE) return $menu;
		}
		
		return $menu;
	}
	
	/**
	 * Creates the Settings menu for the view script
	 * @return multitype:multitype:string  multitype:string unknown
	 */
	public function get_settings_view_menu()
	{
		$menu = array(
			'general' => array('url' => 'general', 'target' => '', 'div_class' => ''),
			'db' => array('url' => 'db', 'target' => '', 'div_class' => ''),
			'files' => array('url' => 'files', 'target' => '_self', 'div_class' => ''),
			'cron' => array('url' => 'cron', 'target' => '', 'div_class' => ''),
			'integrity_agent' => array('url' => 'integrity_agent', 'target' => '', 'div_class' => ''),
			'cf' => array('url' => 'cf', 'target' => '', 'div_class' => ''),
			's3' => array('url' => 's3', 'target' => '', 'div_class' => ''),
			'gcs' => array('url' => 'gcs', 'target' => '', 'div_class' => ''),
			'ftp' => array('url' => 'ftp', 'target' => '', 'div_class' => ''),
		);
	
		if (ee()->extensions->active_hook('backup_pro_modify_settings_menu') === TRUE)
		{
			$menu = ee()->extensions->call('backup_pro_modify_settings_menu', $menu);
			if (ee()->extensions->end_script === TRUE) return $menu;
		}
	
		return $menu;
	}
	
	/**
	 * Creates the Dashboard menu for the view script
	 * @return multitype:multitype:string  multitype:string unknown
	 */
	public function get_dashboard_view_menu()
	{
		$menu = array(
			'home' => array('url' => 'index', 'target' => '', 'div_class' => ''),
			'db' => array('url' => 'db_backups', 'target' => '', 'div_class' => ''),
			'files' => array('url' => 'file_backups', 'target' => '_self', 'div_class' => '')
		);
	
		if (ee()->extensions->active_hook('backup_pro_modify_settings_menu') === TRUE)
		{
			$menu = ee()->extensions->call('backup_pro_modify_settings_menu', $menu);
			if (ee()->extensions->end_script === TRUE) return $menu;
		}
	
		return $menu;
	}
	
	/**
	 * Parses the meta details on the backup system for view
	 * @param array $backups
	 * @return array
	 */
	public function get_backup_meta(array $backups = array())
	{
		if(!$this->backup_meta)
		{
			$options = array(
				'newest_backup_taken' => false,
				'newest_backup_taken_raw' => false,
				'oldest_backup_taken' => false,
				'oldest_backup_taken_raw' => false,
				'total_space_used' => $this->filesize_format(0),
				'total_space_used_raw' => 0,
				'total_backups' => 0
			);
			
			$return = array();
			$date_range = array('min' => '', 'max' => '');
			foreach($backups AS $type => $backup)
			{
				$return[$type] = $options;
				if(count($backup) == '0')
				{
					continue;
				}
				
				$temp = $backups;
				$newest_backup = reset($temp[$type]);
				$return[$type]['newest_backup_taken'] = $newest_backup['file_date'];
				$return[$type]['newest_backup_taken_raw'] = $newest_backup['file_date_raw'];
	
				$oldest_backup = end($temp[$type]);
				$return[$type]['oldest_backup_taken'] = $oldest_backup['file_date'];
				$return[$type]['oldest_backup_taken_raw'] = $oldest_backup['file_date_raw'];
				
				$return[$type]['total_backups'] = count($backup);
				$space_used = 0;
				foreach($backup As $file)
				{
					$space_used = $space_used+$file['file_size_raw'];
					$date_range['max'] = ($file['file_date_raw'] > $date_range['max'] ? $file['file_date_raw'] : $date_range['max']);
					$date_range['min'] = ($date_range['min'] == '' || $file['file_date_raw'] < $date_range['min'] ? $file['file_date_raw'] : $date_range['min']);
				}
				$return[$type]['total_space_used_raw'] = $space_used;
				$return[$type]['total_space_used'] = $this->filesize_format($space_used);
			}
			
			$return['global'] = $options;
			$return['global']['total_backups'] = (int)$return['database']['total_backups']+(int)$return['files']['total_backups'];
			$return['global']['total_space_used'] = $this->filesize_format( $return['database']['total_space_used_raw']+$return['files']['total_space_used_raw'] );
			$return['global']['total_space_used_raw'] = $return['database']['total_space_used_raw']+$return['files']['total_space_used_raw'];
			
			$return['global']['oldest_backup_taken'] = ($date_range['min'] != '' ? m62_format_date($date_range['min'], false, true) : '');
			$return['global']['oldest_backup_taken_raw'] = $date_range['min'];
			$return['global']['newest_backup_taken'] = ($date_range['max'] != '' ? m62_format_date($date_range['max'], false, true) : '');
			$return['global']['newest_backup_taken_raw'] = $date_range['max'];			
			
			$this->backup_meta = $return;
		}
		
		return $this->backup_meta;
	}
	
	/**
	 * Inspects the system and returns an array prototype that includes the details per configuration
	 * @return array
	 */
	public function get_available_space()
	{
		$options = array(
			'available_space' => 0,
			'available_space_raw' => 0,
			'available_percentage' => 0,
			'max_space' => 0,
		);
		
		$meta = $this->get_backup_meta();
		if($this->settings['auto_threshold'] != '0')
		{
			$auto_threshold = ($this->settings['auto_threshold'] == 'custom' ? $this->settings['auto_threshold_custom'] : $this->settings['auto_threshold']);
			$return = $options;
			$return['available_space_raw'] = $auto_threshold-$meta['global']['total_space_used_raw'];
			$return['available_space'] = $this->filesize_format($return['available_space_raw']);
			$percentage = ( $meta['global']['total_space_used_raw'] / $auto_threshold ) * 100;
			$return['available_percentage'] = round((100-$percentage), 2);
			$return['max_space'] = $this->filesize_format($auto_threshold);
		}
		else 
		{
			$return = $options;
		}
		
		return $return;
	}
	
	/**
	 * Sets the backup directories using $path as a seed
	 * @param string $path
	 */
	public function set_backup_dir($path)
	{
		$this->backup_dir = $path;
		$this->backup_db_dir = $path.DIRECTORY_SEPARATOR .'database';
		$this->backup_files_dir = $path.DIRECTORY_SEPARATOR .'files';
	}
	
	/**
	 * Wrapper that runs all the tests to ensure system stability
	 * @return array;
	 */
	public function error_check()
	{
		$errors = $this->check_backup_dirs();
		if($this->settings['license_number'] == '')
		{
			$errors['license_number'] = 'missing_license_number';
		}
		else
		{
			if(!$this->valid_license($this->settings['license_number']))
			{
				//$errors['license_number'] = 'invalid_license_number';
			}
			elseif($this->settings['license_status'] != '1')
			{
				//$errors['license_number'] = 'invalid_license_number';
			}
		}
		
		//now let's make sure we have at least 1 backup at all times
		$backups = $this->get_backups();
		if(count($backups['database']) == '0')
		{
			$errors['db_backup_state'] = sprintf(lang('no_db_backups_exist_yet'), $this->url_base.'backup'.AMP.'type=backup_db', false);
		}
		elseif($this->settings['db_backup_alert_threshold'] >= 1) 
		{
			//now let's check to see if we have a backup for each configured timeframe
			$backup_meta = $this->get_backup_meta($backups);
			if(isset($backup_meta['database']['newest_backup_taken_raw']) && $backup_meta['database']['newest_backup_taken_raw'] != '0')
			{
				$db_check_hours = mktime(0,0,0,date('m'), date('d')-$this->settings['db_backup_alert_threshold'], date('Y'));
				if($backup_meta['database']['newest_backup_taken_raw'] < $db_check_hours)
				{
					$errors['backup_state_db_backups'] = sprintf(lang('db_backup_past_expectation'), m62_relative_datetime($backup_meta['database']['newest_backup_taken_raw'], false), $this->url_base.'backup'.AMP.'type=backup_db');
				}
			}			
		}
		
		if(count($backups['files']) == '0')
		{
			$errors['file_backup_state'] = sprintf(lang('no_file_backups_exist_yet'), $this->url_base.'backup'.AMP.'type=backup_files', false);
		}
		elseif($this->settings['file_backup_alert_threshold'] >= 1)
		{
			if(isset($backup_meta['files']['newest_backup_taken_raw']) && $backup_meta['files']['newest_backup_taken_raw'] != '0')
			{
				$file_check_hours = mktime(0,0,0,date('m'), date('d')-$this->settings['file_backup_alert_threshold'], date('Y'));
				if($backup_meta['files']['newest_backup_taken_raw'] < $file_check_hours)
				{
					$errors['backup_state_files_backups'] = sprintf(lang('files_backup_past_expectation'), m62_relative_datetime($backup_meta['files']['newest_backup_taken_raw'], false), $this->url_base.'backup'.AMP.'type=backup_files');
				}
			}
		}
		
		return $errors;
	}
	
	/**
	 * Runs the tests to make sure the backup directories exist and are writable
	 * @return array;
	 */
	public function check_backup_dirs()
	{
		$errors = array();
		$index = dirname(__FILE__).'/../index.html';
		
		if(!is_writable($this->backup_dir))
		{
			$errors[] = 'db_dir_missing';
			$errors[] = 'files_dir_missing';
		}
		else
		{
			if(!file_exists($this->backup_db_dir))
			{
				if(!mkdir($this->backup_db_dir))
				{
					$errors[] = 'db_dir_missing';
				}
				else
				{
					@copy($index, $this->backup_db_dir.'/index.html');
				}			
			}
			elseif(!is_writable($this->backup_db_dir))
			{
				$errors[] = 'db_dir_not_writable';
			}
			
			if(!file_exists($this->backup_files_dir))
			{
				if(!mkdir($this->backup_files_dir))
				{
					$errors[] = 'files_dir_missing';
				}
				else
				{
					@copy($index, $this->backup_files_dir.'/index.html');
				}
			}
			elseif(!is_writable($this->backup_files_dir))
			{
				$errors[] = 'files_dir_not_writable';
			}
			
			if(!file_exists($this->backup_db_dir.'/.meta'))
			{
				if(!mkdir($this->backup_db_dir.'/.meta'))
				{
					$errors[] = 'db_dir_meta_missing';
				}
			}
			
			if(!file_exists($this->backup_files_dir.'/.meta'))
			{
				if(!mkdir($this->backup_files_dir.'/.meta'))
				{
					$errors[] = 'files_dir_meta_missing';
				}
			}			
			
		}
		return $errors;
	}	
	
	/**
	 * Returns all the existing backups on the file system
	 * @return array
	 */
	public function get_backups()
	{
		$data = array('database' => array(), 'files' => array());
		$ignore = array('.svn', 'index.html', 'tmp', '..', '.', '.git', '.meta');
		
		if(file_exists($this->backup_db_dir))
		{		
			$d = dir($this->backup_db_dir);
			while (false !== ($entry = $d->read())) 
			{
				if(!is_dir($this->backup_db_dir.'/'.$entry) && !in_array($entry, $ignore))
				{
					$file_data = $this->parse_filename($entry, 'database');
					$data['database'][$file_data['file_date_raw']] = $file_data;
				}
			}
			
			krsort($data['database'], SORT_NUMERIC);
		}
		
		if(file_exists($this->backup_files_dir))
		{			
			$d = dir($this->backup_files_dir);
			while (false !== ($entry = $d->read())) 
			{
				if(!is_dir($this->backup_files_dir.'/'.$entry) && !in_array($entry, $ignore))
				{
					$file_data = $this->parse_filename($entry, 'files');
					$data['files'][$file_data['file_date_raw']] = $file_data;
				}
			}
			krsort($data['files'], SORT_NUMERIC);
		}

		return $data;
	}
	
	/**
	 * Converts the backup files into a usable format
	 * @return multitype:string mixed
	 */
	public function get_ignore_files()
	{
		$paths = array($this->backup_files_dir);
		$total = count($paths);
		for($i = 0; $i < $total; $i++)
		{
			$paths[$i] = str_replace("\\", "/", $paths[$i]);
		}
		return $paths;
	}
	
	public function directory_to_array($directory, $recursive) 
	{
		$array_items = array();
		$directory = preg_replace("/\/\//si", "/", $directory);
		$ignore = $this->get_ignore_files();
		
		if ($handle = opendir($directory)) 
		{
			while (false !== ($file = readdir($handle))) 
			{
				if ($file != "." && $file != "..") 
				{
					if(in_array($file, $ignore) || in_array($directory. '/' . $file, $ignore)  || in_array($directory, $ignore))
					{
						continue;
					}
					if (is_dir($directory. '/' . $file)) 
					{
						if($recursive) 
						{
							$array_items = array_merge($array_items, $this->directory_to_array($directory. '/' . $file, $recursive));
						}
						$file = $directory . '/' . $file;
						$array_items[] = preg_replace("/\/\//si", "/", $file);
					} 
				}
			}
			closedir($handle);
		}
		return $array_items;
	}
	
	public function backup_files()
	{
		$path = $this->make_file_filename();
		$zip = new PclZip62($path);
		$total_items = 1;
		if($this->settings['s3_access_key'] != '' && $this->settings['s3_secret_key'] != '')
		{
			$total_items++;
		}

		if($this->settings['cf_api'] != '' && $this->settings['cf_username'] != '')
		{
			$total_items++;
		}		
		
		if($this->settings['ftp_hostname'] != '')
		{
			$total_items++;
		}			
		$this->write_progress_log(lang('backup_progress_bar_start'), $total_items, 0);
		
		$this->settings['exclude_paths'][] = $this->backup_files_dir;		
		$zip->set_exclude($this->settings['exclude_paths']);
		$zip->total_files = $total_items;
		
		if (ee()->extensions->active_hook('backup_pro_file_backup_start') === TRUE)
		{
			ee()->extensions->call('backup_pro_file_backup_start', $this->settings['backup_file_location']);
			if (ee()->extensions->end_script === TRUE) return;
		}
					
		if ($zip->create($this->settings['backup_file_location'], PCLZIP_OPT_REMOVE_PATH, realpath($_SERVER['DOCUMENT_ROOT'].'/../').'/') == 0) 
		{
			return FALSE;
		}
		
		$path_parts = pathinfo($path);
		$details_path = $path_parts['dirname'];
		$file_name = $path_parts['basename'];
		ee()->backup_details->create_details_file($file_name, $details_path);
		
		if (ee()->extensions->active_hook('backup_pro_file_backup_stop') === TRUE)
		{
			ee()->extensions->call('backup_pro_file_backup_stop', $path);
			if (ee()->extensions->end_script === TRUE) return;
		}		
		
		$total_items = $zip->total_files;
		ee()->backup_details->add_details($file_name, $details_path, array('item_count' => $total_items));
		if($zip->total_uncompressed_filesize)
		{
			ee()->backup_details->add_details($file_name, $details_path, array('uncompressed_size' => $zip->total_uncompressed_filesize));
		}
		
		if($this->settings['s3_access_key'] != '' && $this->settings['s3_secret_key'] != '')
		{
			$zip->total_files++;
			$this->write_progress_log(lang('backup_progress_bar_start_s3'), $zip->total_files, $total_items);
			ee()->load->library('backup_pro_s3');
			ee()->backup_pro_s3->move_backup($path, 'files');
			$this->write_progress_log(lang('backup_progress_bar_stop_s3'), $zip->total_files, $total_items);
			ee()->backup_details->add_details($file_name, $details_path, array('S3' => '1'));
			$total_items++;
		}
		
	    if($this->settings['gcs_access_key'] != '' && $this->settings['gcs_secret_key'] != '')
		{
			$zip->total_files++;
			$this->write_progress_log(lang('backup_progress_bar_start_gcs'), $zip->total_files, $total_items);
			ee()->load->library('backup_pro_gcs');
			ee()->backup_pro_gcs->move_backup($path, 'files');
			$this->write_progress_log(lang('backup_progress_bar_stop_gcs'), $zip->total_files, $total_items);
			ee()->backup_details->add_details($file_name, $details_path, array('GCS' => '1'));
			$total_items++;
		}	
		
		if($this->settings['cf_api'] != '' && $this->settings['cf_username'] != '')
		{
			$zip->total_files++;
			$this->write_progress_log(lang('backup_progress_bar_start_cf'), $zip->total_files, $total_items);
			ee()->load->library('backup_pro_cf');
			ee()->backup_pro_cf->move_backup($path, 'files');
			$this->write_progress_log(lang('backup_progress_bar_stop_cf'), $zip->total_files, $total_items);
			ee()->backup_details->add_details($file_name, $details_path, array('CF' => '1'));
			$total_items++;
		}

		if($this->settings['ftp_hostname'] != '')
		{
			$zip->total_files++;
			//$total_items++;
			$this->write_progress_log(lang('backup_progress_bar_start_ftp'), $zip->total_files, $total_items);			
			ee()->load->library('backup_pro_ftp');
			ee()->backup_pro_ftp->move_backup($path, 'files');		
			$this->write_progress_log(lang('backup_progress_bar_stop_ftp'), $zip->total_files, $total_items);	
			ee()->backup_details->add_details($file_name, $details_path, array('FTP' => '1'));
			$total_items++;		
		}		
		
		$this->write_progress_log(lang('backup_progress_bar_stop'), $zip->total_files, $zip->total_files);
		return $path;	
	}
	
	public function get_space_used()
	{
		$amount = 0;
		$backups = $this->get_backups();		
		foreach($backups AS $type => $_backup)
		{
			if(is_array($_backup))
			{
				foreach($_backup AS $backup)
				{
					if(isset($backup['file_size_raw']))
					{
						$amount = ($amount+(int)$backup['file_size_raw']);
					}
				}
			}
		}
		
		if($amount > 0)
		{
			return $amount;
		}
	}
	
	public function parse_filename($name, $type)
	{
		if(substr($name, -3) != 'zip')
		{
			return;
		}
				
		$path = ($type == 'files' ? $this->backup_files_dir : $this->backup_db_dir).'/'.$name;
		if($type == 'files')
		{
			$data['file_date_raw'] = (int)$name;
			$data['file_date'] = m62_format_date($data['file_date_raw'], false, true);
			$data['details'] = ee()->backup_details->get_details($name, $this->backup_files_dir);
		}
		else
		{
			$parts = explode($this->name_sep, $name);
			if(!empty($parts['2']) && $parts['1'] == 'mysqldump')
			{
				$parts['1'] = $parts['2'];
				$parts['2'] = 'mysqldump';
			}

			$data = array();
			$data['file_date_raw'] = $parts['0'];
			$data['file_date'] = m62_format_date($data['file_date_raw'], false, true);
			$data['details'] = ee()->backup_details->get_details($name, $this->backup_db_dir);
		}
		$data['file_name'] = $name;
		$data['backup_type'] = $type;
		$data['file_size'] = $this->filesize_format(filesize($path));	
		$data['file_size_raw'] = filesize($path);
		return $data;
	}
	

	/**
	 * Creates the full path to store the backup for the database.
	 * @param mixed $filename
	 */
	public function make_db_filename($filename = FALSE)
	{
		if(!$filename)
		{
			$tail = '';
			if($this->settings['db_backup_method'] == 'mysqldump')
			{
				$tail = $this->settings['db_backup_method'].$this->name_sep;
			}
			
			return $this->backup_dir.'/database/'.mktime().$this->name_sep.$tail.$this->db_info['db_name'].'.sql';
		}
		else
		{
			return $this->backup_dir.'/database/'.$filename;
		}
	}
	
	/**
	 * Creates the full path to store the backup for the filesystem
	 * @param $filename
	 */
	public function make_file_filename($filename = FALSE)
	{
		if(!$filename)
		{
			return $this->backup_dir.'/files/'.mktime().'.zip';
		}
		else
		{
			return $this->backup_dir.'/files/'.$filename;
		}
	}
	
	public function make_pretty_filename($file, $type)
	{
		$name = utf8_decode(ee()->config->config['site_name']);
		$parts = $this->parse_filename($file, $type);
		switch($type)
		{
			case 'files':
				$name .= ' File Backup ';
			break;
			
			case 'db':
			default:
				$name .= ' Database Backup ';
			break;
		}
		
		$name .= date('YmdHis', $parts['file_date_raw']);
		return str_replace(' ', '_', strtolower($name)).'.zip';
	}		
	
	public function get_cron_commands($module_name)
	{
		$action_id = $this->get_cron_action($module_name);
		$url = ee()->config->config['site_url'].'?ACT='.$action_id;
		return array(
			 'file_backup' => array('url' => $url.AMP.'type=files', 'cmd' => 'curl "'.$url.AMP.'type=files"'),
			 'db_backup' => array('url' => $url.AMP.'type=db', 'cmd' => 'curl "'.$url.AMP.'type=db"'),
			 'combined' => array('url' => $url.AMP.'type=combined', 'cmd' => 'curl "'.$url.AMP.'type=combined"'),
		);
	}
	
	public function get_cron_action($module_name)
	{
		ee()->load->dbforge();
		ee()->db->select('action_id');
		$query = ee()->db->get_where('actions', array('class' => $module_name, 'method' => 'cron'));		
		return $query->row('action_id');
	}
	
	/**
	 * Wrapper to handle CP URL creation
	 * @param string $method
	 */
	public function _create_url($method)
	{
		return $this->url_base.$method;
	}

	/**
	 * Creates the value for $url_base
	 * @param string $url_base
	 */
	public function set_url_base($url_base)
	{
		$this->url_base = $url_base;
	}
	
	/**
	 * Permananetly removes the backups from the system
	 * @param array $backups
	 * @param string $remove_ftp
	 * @param string $remove_s3
	 * @param string $remove_cf
	 * @param string $remove_gcs
	 * @return boolean
	 */
	public function delete_backups(array $backups, $remove_ftp = TRUE, $remove_s3 = TRUE, $remove_cf = TRUE, $remove_gcs = TRUE)
	{
		$removed = array();
		foreach($backups AS $backup)
		{
			if(substr($backup, -3) != 'zip')
			{
				continue;
			}
			
			$file = $this->backup_dir.'/'.$backup;
			if(file_exists($file))
			{				
				$fileinfo = pathinfo($file);
				ee()->backup_details->remove_details_file($fileinfo['basename'], $fileinfo['dirname']);
				$removed[] = realpath($file);
				unlink($file);
			}
		}	
		
		if(count($removed) >= 1 && $this->settings['cf_api'] != '' && $this->settings['cf_username'] != '' && $remove_cf)
		{
			ee()->load->library('backup_pro_cf');
			ee()->backup_pro_cf->remove_backups($removed);				
		}

		if(count($removed) >= 1 && $this->settings['s3_access_key'] != '' && $this->settings['s3_secret_key'] != '' && $remove_s3)
		{
			ee()->load->library('backup_pro_s3');
			ee()->backup_pro_s3->remove_backups($removed);				
		}
		
		if(count($removed) >= 1 && $this->settings['gcs_access_key'] != '' && $this->settings['gcs_secret_key'] != '' && $remove_gcs)
		{
			ee()->load->library('backup_pro_gcs');
			ee()->backup_pro_gcs->remove_backups($removed);
		}		
				
		if(count($removed) >= 1 && $this->settings['ftp_hostname'] != '' && $remove_ftp)
		{
			ee()->load->library('backup_pro_ftp');
			ee()->backup_pro_ftp->remove_backups($removed);				
		}
		
		return TRUE;
	}

	/**
	 * Validates a license number is valid
	 * @param string $license
	 * @return number
	 */
	public function valid_license($license)
	{
		return preg_match("/^([a-z0-9]{8})-([a-z0-9]{4})-([a-z0-9]{4})-([a-z0-9]{4})-([a-z0-9]{12})$/", $license);
	}
	
	/**
	 * Performs the license check
	 * 
	 * Yes, if you wanted to disable license checks in Backup Pro 2, you'd mess with this. 
	 * But.. c'mon... I've worked hard and it's just me...
	 * 
	 * @param string $force
	 */
	 /*
	public function l($force = false)
	{
		$valid = false;
		if( $this->settings['license_number'] && $this->valid_license($this->settings['license_number']) )
		{
			$license_check = $this->settings['license_check'];
			$next_notified = mktime(date('G', $license_check)+24, date('i', $license_check), 0, date('n', $license_check), date('j', $license_check), date('Y', $license_check));
				
			if(time() > $next_notified || $force)
			{
				//license_check
				$get = array(
					'ip' => (ee()->input->ip_address()),
					'key' => ($this->settings['license_number']),
					'site_url' => (ee()->config->config['site_url']),
					'webmaster_email' => (ee()->config->config['webmaster_email']),
					'add_on' => ('backup-pro'), 
					'version' => ('2.0.1')
				);
				
				$url = 'https://mithra62.com/license-check/'.base64_encode(json_encode($get));
				$ch = curl_init($url);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1");
				$response = urldecode(curl_exec($ch));
				
				$json = json_decode($response, true);
				if($json && isset($json['valid']))
				{
					ee()->backup_pro_settings->update_setting('license_status', $json['valid']);
				}
				else 
				{
					ee()->backup_pro_settings->update_setting('license_status', '0');
				}

				ee()->backup_pro_settings->update_setting('license_check', time());
			}
		}
	}
	*/
	/**
	 * Wrapper to update the settings
	 * @param array $settings
	 * @return bool
	 */
	public function update_settings(array $settings = array())
	{
		/*
		if(isset($settings['license_number']) && $this->valid_license($settings['license_number']) && $this->settings['license_number'] != $settings['license_number'])
		{
			$settings['license_status'] = 1;
			$settings['license_check'] = 0;
		}
		*/
		return ee()->backup_pro_settings->update_settings($settings);
	}
	
	/**
	 * Creates the value for $db_info
	 * @param string $db_info
	 */
	public function set_db_info($info)
	{
		$this->db_info = $info;
	}
	
	/**
	 * Unzips the database backup for restoring
	 * @param string $path
	 * @param string $save
	 * @return string
	 */
	public function unzip_db_backup($path, $save)
	{
		$zip = zip_open($path);
		if ($zip) {
		  while ($zip_entry = zip_read($zip)) 
		  {
		  	$name = zip_entry_name($zip_entry);
		    $fp = fopen($save."/".zip_entry_name($zip_entry), "w");
		    if (zip_entry_open($zip, $zip_entry, "r")) 
		    {
		      $buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
		      fwrite($fp,"$buf");
		      zip_entry_close($zip_entry);
		      fclose($fp);
		    }
		  }
		  zip_close($zip);
		  return $save.'/'.$name;
		}	
	}
	
	/**
	 * Removes the oldest backups to keep the space under $max_size
	 * @param int $max_size
	 * @param int $used_size
	 */
	public function cleanup_auto_threshold_backups($max_size, $used_size)
	{
		if($max_size == '0')
		{
			return FALSE;
		}
		
		if($max_size == 'custom')
		{
			$max_size = $this->settings['auto_threshold_custom'];
		}
		
		$backups = $this->get_backups();
		$arr = array();
		if(count($backups) >= '1')
		{
			foreach($backups AS $type => $items)
			{
				$path = ($type == 'database' ? $this->backup_db_dir : $this->backup_files_dir);
				foreach($items AS $backup)
				{
					$arr[$backup['file_date_raw']] = $type.'/'.$backup['file_name'];
				}
			}
		}

		ksort($arr);
		$i = 0;
		$remove = array();
		while($this->get_space_used() > $max_size)
		{
			$file = array_shift($arr);
			if($file != '')
			{
				$remove = array();
				$remove[] = $file;
				$this->delete_backups($remove, $this->settings['ftp_prune_remote'], $this->settings['s3_prune_remote'], $this->settings['cf_prune_remote']);
			}
			//unlink($remove);
			$i++;
			if($i > 10) //just a little sanity check :)
			{
				break;
			}
		}	
	}
	
	/**
	 * Removes the backups on the system so the total == $total for backup $type
	 * @param string $type
	 * @param string $total
	 * @return void|boolean
	 */
	public function cleanup_backup_count($type = 'database', $total = '0')
	{
		$total = (int)$total;
		if($total == '0')
		{
			return FALSE;
		}
		
		//get the backups and clean things up for processing
		$backups = $this->get_backups();
		$arr = array();
		if(count($backups[$type]) >= '1')
		{
			$path = ($type == 'database' ? $this->backup_db_dir : $this->backup_files_dir);
			foreach($backups[$type] AS $backup)
			{
				$arr[$backup['file_date_raw']] = $type.'/'.$backup['file_name'];
			}			
		}

		
		if(count($arr) < $total)
		{
			return;
		}
		
		//check if we need to remove any for 
		$count = (count($arr)-$total);
		$i = 1;
		ksort($arr);
		$remove = array();
		foreach($arr AS $backup)
		{
			if($count >= $i)
			{
				$remove[] = $backup;
			}
			else
			{
				break;
			}
			$i++;
		}
		
		if(count($remove) >= '1')
		{
			$this->delete_backups($remove, $this->settings['ftp_prune_remote'], $this->settings['s3_prune_remote'], $this->settings['cf_prune_remote']);
		}
	}
	
	/**
	 * Writes out the progress log for the progress bar status updates
	 * @param string $msg
	 * @param int $total_items
	 * @param int $item_number
	 */
	public function write_progress_log($msg, $total_items = 0, $item_number = 0)
	{
		if($item_number > $total_items)
		{
			$item_number = $total_items;
		}
		
		$log = array('total_items' => $total_items, 'item_number' => $item_number, 'msg' => $msg);
		if(function_exists('json_encode'))
		{
			$log = json_encode($log);
		}
		else
		{
			$log = ee()->javascript->generate_json($log);
		}
		
		write_file($this->progress_log_file, $log);
	}
	
	/**
	 * Removes the progress log
	 */
	public function remove_progress_log()
	{	
		delete_files($this->progress_log_file);
	}	

	/**
	 * Format a number of bytes into a human readable format.
	 * Optionally choose the output format and/or force a particular unit
	 *
	 * @param   int     $bytes      The number of bytes to format. Must be positive
	 * @param   string  $format     Optional. The output format for the string
	 * @param   string  $force      Optional. Force a certain unit. B|KB|MB|GB|TB
	 * @return  string              The formatted file size
	 */
	public function filesize_format($val, $digits = 3, $mode = "IEC", $bB = "B"){ //$mode == "SI"|"IEC", $bB == "b"|"B"
	
		$si = array("", "k", "M", "G", "T", "P", "E", "Z", "Y");
		$iec = array("", "Ki", "Mi", "Gi", "Ti", "Pi", "Ei", "Zi", "Yi");
		switch(strtoupper($mode)) {
			case "SI" : 
				$factor = 1000; 
				$symbols = $si; 
			break;
			case "IEC" : 
				$factor = 1024; 
				$symbols = $iec; 
			break;
			default : 
				$factor = 1000; 
				$symbols = $si; 
			break;
		}
		switch($bB) {
			case "b" : 
				$val *= 8; 
			break;
			default : 
				$bB = "B"; 
			break;
		}
		for($i=0;$i<count($symbols)-1 && $val>=$factor;$i++) {
			$val /= $factor;
		}
		$p = strpos($val, ".");
		if($p !== false && $p > $digits) {
			$val = round($val);
		} elseif($p !== false) { 
			$val = round($val, $digits-$p);
		}
		
		return round($val, $digits) . " " . $symbols[$i] . $bB;
	}	

	/**
	 * Given the full system path to a file it will force the "Save As" dialogue of browsers
	 *
	 * @param   string  $filename	Path to file
	 */
	public function file_download($filename, $force_name = FALSE)
	{
		// required for IE, otherwise Content-disposition is ignored
		if(ini_get('zlib.output_compression'))
		{
			ini_set('zlib.output_compression', 'Off');
		}
	
		$file_extension = strtolower(substr(strrchr($filename,"."),1));
	
		if( $filename == "" )
		{
			echo "<html><body>ERROR: download file NOT SPECIFIED.</body></html>";
			exit;
		} elseif ( ! file_exists( $filename ) )
		{
			echo "<html><body>ERROR: File not found. </body></html>";
			exit;
		};
		switch( $file_extension )
		{
			case "pdf": $ctype="application/pdf"; break;
			case "exe": $ctype="application/octet-stream"; break;
			case "zip": $ctype="application/zip"; break;
			case "doc": $ctype="application/msword"; break;
			case "xls": $ctype="application/vnd.ms-excel"; break;
			case "ppt": $ctype="application/vnd.ms-powerpoint"; break;
			case "gif": $ctype="image/gif"; break;
			case "png": $ctype="image/png"; break;
			case "rtf": $ctype="text/rtf"; break;
			case "jpeg":
			case "jpg": $ctype="image/jpg"; break;
			default: $ctype="application/zip";
		}

		$filesize = filesize($filename);	
		header("Pragma: public"); // required
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private",false); // required for certain browsers
		header("Content-Type: $ctype");	
		// change, added quotes to allow spaces in filenames, by Rajkumar Singh
		header("Content-Disposition: attachment; filename=\"".($force_name ? $force_name : basename($filename))."\";" );
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: ".$filesize);

		if ($fd = fopen ($filename, "r")) 
		{
		    while(!feof($fd)) {
		        $buffer = fread($fd, 1024*8);
		        echo $buffer;
		    }
		}
		fclose ($fd);
		exit();
	}	

	/**
	 * Deletes a directory with all of its contents
	 * Works recursively to remove additional directories
	 *
	 * @param   string	$dirName	The directory to remove
	 * @return  bool
	 */
	function delete_dir($dirName) 
	{

		if(empty($dirName)) 
		{
			return FALSE;
		}

		
		if(file_exists($dirName)) 
		{
			$dir = dir($dirName);
			while($file = $dir->read()) 
			{
				if($file != '.' && $file != '..' ) 
				{
					if(is_dir($dirName.'/'.$file)) 
					{
						$this->delete_dir($dirName.'/'.$file);
					} 
					else 
					{
						unlink($dirName.'/'.$file);
					}
				}
			}
			rmdir($dirName.'/'.$file);
		} 
		else
		{
			return FALSE;
		}
		
		return TRUE;		
	}	
}