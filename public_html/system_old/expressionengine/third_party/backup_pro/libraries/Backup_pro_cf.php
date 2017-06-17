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
 * Backup Pro - Cloud Files Library
 *
 * Cloud Files Library class
 *
 * @package 	mithra62\BackupPro\CloudFiles
 * @author		Eric Lamb
 * @filesource 	./system/expressionengine/third_party/backup_pro/libraries/Backup_pro_cf.php
 */
class Backup_pro_cf
{
	public $config = array();
	
	public $settings = array();
	
	public function __construct()
	{
		$this->settings = ee()->backup_pro->get_settings();
		ee()->load->library('cf/cfiles');
		$this->settings['cf_username'] = ee()->encrypt->decode($this->settings['cf_username']);
		$this->settings['cf_api'] = ee()->encrypt->decode($this->settings['cf_api']);
		$this->settings['cf_location'] = $this->settings['cf_location'];
	}	
	
	public function connect()
	{
		return ee()->cfiles->initialize(array('cf_username' => $this->settings['cf_username'], 'cf_api' => $this->settings['cf_api']));
	}
	
	public function test_connection(array $config)
	{
		ee()->cfiles->api_location = $config['cf_location'];
		try
		{
			ee()->cfiles->initialize(array('cf_username' => $config['cf_username'], 'cf_api' => $config['cf_api']));
		}
		catch (Exception $e)
		{
			show_error(ee()->lang->line('cf_connect_fail'));
		}
	}
	
	public function make_bucket_name()
	{
		return str_replace(' ', '_', strtolower(ee()->config->config['site_label'].' Backup'));
	}
	
	public function move_backup($local, $type)
	{
		ee()->cfiles->api_location = $this->settings['cf_location'];
		$this->connect();
		if($this->settings['cf_bucket'] == '')
		{
			$this->settings['cf_bucket'] = $this->make_bucket_name();
		}
		
		//check for bucket
		try 
		{
			ee()->cfiles->cf_container = $this->settings['cf_bucket'];
			$bucket_data = ee()->cfiles->container_info();
		}
		catch(Exception $e)
		{
			//add bucket
			$bucket_data = array();
			ee()->cfiles->cf_container = $this->settings['cf_bucket'];
			ee()->cfiles->do_container('a');
		}

		$file_name = basename($local);
		$file_location = dirname($local).'/';
		ee()->cfiles->cf_folder = $type.'/';
		ee()->cfiles->do_object('a', $file_name, $file_location);
	}
	
	public function remove_backups(array $files)
	{
		ee()->cfiles->api_location = $this->settings['cf_location'];
		$this->connect();
		if($this->settings['cf_bucket'] == '')
		{
			$this->settings['cf_bucket'] = $this->make_bucket_name();
		}

		try 
		{
			ee()->cfiles->cf_container = $this->settings['cf_bucket'];
			$bucket_data = ee()->cfiles->container_info();
		}
		catch(Exception $e)
		{
			//add bucket
			$bucket_data = array();
			ee()->cfiles->cf_container = $this->settings['cf_bucket'];
			ee()->cfiles->do_container('a');
		}
				
		foreach($files AS $file)
		{
			$parts = explode(DIRECTORY_SEPARATOR, $file);
			if(count($parts) >= '1')
			{
				$filename = end($parts);
				$type = prev($parts);
				$remove = $type.'/'.$filename;
				ee()->cfiles->do_object('d', $remove);			
			}
		}
	}
}