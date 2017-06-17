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
 * Backup Pro - S3 Library
 *
 * Amazon S3 Library class
 *
 * @package 	mithra62\BackupPro\S3
 * @author		Eric Lamb
 * @filesource 	./system/expressionengine/third_party/backup_pro/libraries/Backup_pro_s3.php
 */
class Backup_pro_s3
{
	public $config = array();
	
	public $settings = array();
	
	public function __construct()
	{
		$this->settings = ee()->backup_pro->get_settings();
		ee()->load->library('S3');
		
		$this->settings['s3_secret_key'] = ee()->encrypt->decode($this->settings['s3_secret_key']);
		$this->settings['s3_access_key'] = ee()->encrypt->decode($this->settings['s3_access_key']);	
	}	
	
	public function connect()
	{
		ee()->s3->setAuth($this->settings['s3_access_key'] ,$this->settings['s3_secret_key']);
	}
	
	public function test_connection($config)
	{
		ee()->s3->setAuth($config['s3_access_key'] ,$config['s3_secret_key']);
		$buckets = ee()->s3->listBuckets();
	}
	
	public function make_bucket_name()
	{
		return str_replace(' ', '_', strtolower(ee()->config->config['site_label'].' Backup'));
	}
	
	public function move_backup($local, $type)
	{
		$this->connect();
		if($this->settings['s3_bucket'] == '')
		{
			$this->settings['s3_bucket'] = $this->make_bucket_name();
		}
		
		//check for bucket
		$buckets = ee()->s3->listBuckets();
		if(!in_array($this->settings['s3_bucket'], $buckets))
		{
			ee()->s3->putBucket($this->settings['s3_bucket']);
		}
		
		//now the sub folders
		$bucket = ee()->s3->getBucket($this->settings['s3_bucket']);
		$input = array('file' => $local);
		ee()->s3->putObject(S3::inputFile($local), $this->settings['s3_bucket'], $type.'/'.baseName($local), S3::ACL_PUBLIC_READ);	
	}
	
	public function remove_backups(array $files)
	{
		$this->connect();
		if($this->settings['s3_bucket'] == '')
		{
			$this->settings['s3_bucket'] = $this->make_bucket_name();
		}
				
		foreach($files AS $file)
		{
			$parts = explode(DIRECTORY_SEPARATOR, $file);
			if(count($parts) >= '1')
			{
				$filename = end($parts);
				$type = prev($parts);
				$remove = $type.'/'.$filename;
				ee()->s3->deleteObject($this->settings['s3_bucket'], $remove);			
			}
		}
	}
}