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
 * Backup Pro - Backup Meta Details Library
 *
 * Backup Meta Details  Library class
 *
 * @package 	mithra62\BackupPro\Meta
 * @author		Eric Lamb
 * @filesource 	./system/expressionengine/third_party/backup_pro/libraries/Backup_pro_meta.php
 */
class Backup_pro_backup_details
{
	/**
	 * The name of the directory the details are all stored in
	 * @var string
	 */
	public $details_directory = '.meta';
	
	/**
	 * The file extension the backup details will contain is
	 * @var string
	 */
	public $details_ext = '.m62';
	
	/**
	 * The outline for how data should be stored
	 * @var array
	 */
	public $details_prototype = array(
		'note' => '',
		'hash' => '',
		'S3' => 0,
		'CF' => 0,
		'GCS' => 0,
		'FTP' => 0,
		'items' => array(),
		'item_count' => 0,
		'uncompressed_size' => 0,
		'created_by' => 0,
		'verified' => 0,
		'time_taken' => 0
	);
	
	/**
	 * Returns the details of a given backup from the .meta directory
	 * @param string $file_name
	 * @param string $path
	 * @return array
	 */
	public function get_details($file_name, $path)
	{
		$details_file = rtrim($path, '/').'/'.$this->details_directory.'/'. $file_name.$this->details_ext;
		if( !file_exists($details_file) )
		{
			$this->create_details_file($file_name, $path);
		}
		
		$data = file_get_contents($details_file);
		if( !$data )
		{
			$data = $this->create_details_file($file_name, $path);
		}
		
		return json_decode($data, true);
	}
	
	/**
	 * Creates the meta details file for the given backup
	 * @param string $file_name
	 * @param string $path
	 * @param array $data
	 */
	public function create_details_file($file_name, $path, array $data = array())
	{
		$file_path = rtrim($path, '/').'/'. $file_name;
		$save_path = rtrim($path, '/').'/'.$this->details_directory.'/'.$file_name.$this->details_ext;
		$data = array_merge($data, $this->details_prototype);
		$data['hash'] = md5_file($file_path);
		
		$data = json_encode($data);
		file_put_contents($save_path, $data );
		return $data;
	}
	
	/**
	 * Writes the updated details info for a backup
	 * @param string $file_name
	 * @param string $path
	 * @param array $data
	 */
	public function add_details($file_name, $path, array $data)
	{
		$details = $this->get_details($file_name, $path);
		$save_path = rtrim($path, '/').'/'.$this->details_directory.'/'.$file_name.$this->details_ext;
		$data = array_merge($details, $data);
		$data = json_encode($data);
		file_put_contents($save_path, $data);
	}
	
	/**
	 * Removes the meta file related to a backup
	 * @param string $file_name
	 * @param string $path
	 */
	public function remove_details_file($file_name, $path)
	{
		$remove_path = rtrim($path, '/').'/'.$this->details_directory.'/'.$file_name.$this->details_ext;
		if(file_exists($remove_path))
		{
			unlink($remove_path);
		}
	}
}