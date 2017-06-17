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
 * Backup Pro - Google Gloud Storage Library
 *
 * Amazon gcs Library class
 *
 * @package 	mithra62\BackupPro\GoogleCloudStorage
 * @author		Eric Lamb
 * @filesource 	./system/expressionengine/third_party/backup_pro/libraries/Backup_pro_gcs.php
 */
class Backup_pro_gcs
{
	
	public $config = array();
	
	public $settings = array();
	
	public function __construct()
	{
		$this->settings = ee()->backup_pro->get_settings();
		ee()->load->library('Gc', null, 'gcs');
		ee()->gcs->setExceptions();
		
		$this->settings['gcs_secret_key'] = ee()->encrypt->decode($this->settings['gcs_secret_key']);
		$this->settings['gcs_access_key'] = ee()->encrypt->decode($this->settings['gcs_access_key']);	
	}	
	
	public function connect()
	{
		ee()->gcs->setAuth($this->settings['gcs_access_key'] ,$this->settings['gcs_secret_key']);
	}
	
	public function test_connection($config)
	{
		ee()->gcs->setAuth($config['gcs_access_key'] ,$config['gcs_secret_key']);
		
		try {
			$buckets = ee()->gcs->listBuckets();
		}
		catch(GCException $e)
		{
			show_error($e->getMessage());
		}
	}
	
	public function make_bucket_name()
	{
		return str_replace(' ', '_', strtolower(ee()->config->config['site_label'].' Backup'));
	}
	
	public function move_backup($local, $type)
	{
		$this->connect();
		if($this->settings['gcs_bucket'] == '')
		{
			$this->settings['gcs_bucket'] = $this->make_bucket_name();
		}
		
		//check for bucket
		$buckets = ee()->gcs->listBuckets();
		if(!in_array($this->settings['gcs_bucket'], $buckets))
		{
			ee()->gcs->putBucket($this->settings['gcs_bucket']);
		}
		
		//now the sub folders
		$bucket = ee()->gcs->getBucket($this->settings['gcs_bucket']);
		$input = array('file' => $local);
		ee()->gcs->putObject(GC::inputFile($local), $this->settings['gcs_bucket'], $type.'/'.baseName($local), GC::ACL_PUBLIC_READ);	
	}
	
	public function remove_backups(array $files)
	{
		$this->connect();
		if($this->settings['gcs_bucket'] == '')
		{
			$this->settings['gcs_bucket'] = $this->make_bucket_name();
		}
				
		foreach($files AS $file)
		{
			$parts = explode(DIRECTORY_SEPARATOR, $file);
			if(count($parts) >= '1')
			{
				$filename = end($parts);
				$type = prev($parts);
				$remove = $type.'/'.$filename;
					
				try {
					ee()->gcs->deleteObject($this->settings['gcs_bucket'], $remove);
				}
				catch(GCException $e)
				{
					continue;
				}						
			}
		}
	}
}