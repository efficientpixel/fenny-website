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
 * Backup Pro - FTP Library
 *
 * FTP Library class
 *
 * @package 	mithra62\BackupPro\Ftp
 * @author		Eric Lamb
 * @filesource 	./system/expressionengine/third_party/backup_pro/libraries/Backup_pro_ftp.php
 */
class Backup_pro_ftp
{
	public $config = array();
	
	public $settings = array();
	
	public function __construct()
	{
		$this->settings = ee()->backup_pro->get_settings();
		ee()->load->library('ftp');
		$this->settings['ftp_username'] = ee()->encrypt->decode($this->settings['ftp_username']);
		$this->settings['ftp_password'] = ee()->encrypt->decode($this->settings['ftp_password']);
				
		$this->config['hostname'] = $this->settings['ftp_hostname'];
		$this->config['username'] = trim($this->settings['ftp_username']);
		$this->config['password'] = trim($this->settings['ftp_password']);
		$this->config['port']     = $this->settings['ftp_port'];
		$this->config['passive']  = $this->settings['ftp_passive'];
		$this->config['debug']    = TRUE;		
	}	
	
	public function connect()
	{
		ee()->ftp->connect($this->config);	
	}
	
	public function test_connection(array $config)
	{
		$config['hostname'] = (isset($config['ftp_hostname']) ? $config['ftp_hostname'] : '');
		$config['username'] = (isset($config['ftp_username']) ? trim($config['ftp_username']) : '');
		$config['password'] = (isset($config['ftp_password']) ? trim($config['ftp_password']) : '');
		$config['port'] 	= (isset($config['ftp_port']) ? $config['ftp_port'] : '');
		$config['passive']  = (isset($config['ftp_passive']) ? $config['ftp_passive'] : '');
		$config['debug'] = TRUE;

		ee()->ftp->connect($config);
			
		$paths = ee()->ftp->list_files($config['ftp_store_location']);
		if(count($paths) == '0')
		{
			show_error(ee()->lang->line('ftp_directory_missing'));
		}
		$this->close();	
	}
	
	public function move_backup($local, $type)
	{
		if($this->settings['ftp_store_location'] == '')
		{
			return FALSE;
		}
		
		$this->connect();
		$paths = ee()->ftp->list_files($this->settings['ftp_store_location']);

		$store_path = rtrim($this->settings['ftp_store_location'], '/').'/'.$type;
		
		if(!is_array($paths) || (!in_array($type, $paths) && !in_array($store_path, $paths)))
		{
			ee()->ftp->mkdir($store_path);
		}
		
		$remote = $store_path. '/'.basename($local);
		ee()->ftp->upload($local, $remote); 
		$this->close();	
	}
	
	public function remove_backups(array $files)
	{
		$this->connect();
		foreach($files AS $file)
		{
			$parts = explode(DIRECTORY_SEPARATOR, $file);
			if(count($parts) >= '1')
			{
				$filename = end($parts);
				$type = prev($parts);
				$remove = $this->settings['ftp_store_location'].'/'.$type.'/'.$filename;
				$paths = ee()->ftp->list_files($this->settings['ftp_store_location'].'/'.$type.'/');
				if(in_array($filename, $paths))
				{
					ee()->ftp->delete_file($remove);	
				}				
			}
		}
		$this->close();
	}
	
	public function close()
	{
		ee()->ftp->close(); 
	}
}