<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * mithra62 - Backup Pro
 *
 * @package		BackupPro
 * @author		Eric Lamb
 * @copyright	Copyright (c) 2015, mithra62, Eric Lamb.
 * @link		http://mithra62.com/projects/view/backup-pro/
 * @version		2.0
 * @filesource 	./system/expressionengine/third_party/backup_pro/
 */
 
/**
 * Backup Pro - Extension
 *
 * Extension class
 *
 * @package 	mithra62\BackupPro
 * @author		Eric Lamb
 * @filesource 	./system/expressionengine/third_party/backup_pro/ext.backup_pro.php
 */
class Backup_pro_ext 
{

	public $settings = array();
	
	public $name = 'Backup Pro';
	
	public $version = '2.0.1';
	
	public $description	= 'Extensions for Backup Pro';
	
	public $settings_exist	= 'y';
	
	public $docs_url = ''; 
	
	public $required_by = array('module');	
		
	/**
	 * @ignore
	 */
	public function __construct()
	{
		ee()->lang->loadfile('backup_pro');

		$this->db_conf = array(
				'user' => ee()->db->username,
				'pass' => ee()->db->password,
				'db_name' => ee()->db->database,
				'host' => ee()->db->hostname
		);			
		
		$this->query_base = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=backup_pro'.AMP.'method=';	
		
		$this->url_base = BASE.AMP.$this->query_base;
	}
	
	public function settings_form()
	{
		ee()->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=backup_pro'.AMP.'method=settings');
	}
	
	public function get_backups($api, $method = FALSE)
	{
		//this is very important!! 
		//without the extension gets ran on every call
		if(__FUNCTION__ != $api->method)
		{
			return;
		}
		
		//ee()->load->add_package_path(PATH_THIRD.'export_it/'); 
		ee()->load->model('backup_pro_settings_model', 'backup_pro_settings', TRUE);
		
		ee()->load->library('backup_pro');
		$this->settings = ee()->backup_pro->get_settings();
		ee()->backup_pro->set_backup_dir($this->settings['backup_store_location']);
		
		echo json_encode(ee()->backup_pro->get_backups());
		ee()->extensions->end_script = TRUE;
	}
	
	public function cp_menu_array($menu)
	{
		$menu = (ee()->extensions->last_call != '' ? ee()->extensions->last_call : $menu);
		
		$this->url_base = BASE.AMP.$this->query_base;
		if(ee()->session->userdata('can_access_tools') == 'y')
		{
			$new_menu = array();
			if(!empty($menu['tools']['tools_communicate']))
			{
				$new_menu['tools_communicate'] = $menu['tools']['tools_communicate'];
				unset($menu['tools']['tools_communicate']);
			}
				
			if(!empty($menu['tools']['0']))
			{
				$new_menu['0'] = $menu['tools']['0'];
				unset($menu['tools']['0']);
			}
				
			if(!empty($menu['tools']['tools_utilities']))
			{
				$new_menu['tools_utilities'] = $menu['tools']['tools_utilities'];
				unset($menu['tools']['tools_utilities']);
			}
				
			if(!empty($menu['tools']['tools_data']))
			{
				$new_menu['tools_data'] = $menu['tools']['tools_data'];
				unset($menu['tools']['tools_data']);
			}
				
			if(ee()->session->userdata('can_access_modules') == 'y')
			{
				$new_menu['backup_pro'] = array(
						'view_backups' => $this->url_base.'index',
						'backup_db' => $this->url_base.'backup'.AMP.'type=backup_db',
						'backup_files' => $this->url_base.'backup'.AMP.'type=backup_files'
				);
		
				$new_menu['backup_pro']['0'] = '----';
				$new_menu['backup_pro']['backup_pro_settings'] = $this->url_base.'settings';
			}
				
			if(!empty($menu['tools']['tools_data']))
			{
				$new_menu['tools_logs'] = $menu['tools']['tools_logs'];
				unset($menu['tools']['tools_logs']);
			}
			
			$new_menu = array_merge($new_menu, $menu['tools']);
				
			if(!empty($menu['tools']['1']))
			{
				$new_menu['1'] = $menu['tools']['1'];
				unset($menu['tools']['1']);
			}
				
			if(!empty($menu['tools']['overview']))
			{
				$new_menu['overview'] = $menu['tools']['overview'];
				unset($menu['tools']['overview']);
			}
				
			$menu['tools'] = $new_menu;
		
		}
				
		return $menu;
	}
	
	/**
	 * Checks the backups available on the system and notifies the configured users if needed
	 */
	public function check_backup_state()
	{

		ee()->load->model('backup_pro_settings_model', 'backup_pro_settings', TRUE);
		ee()->load->library('backup_pro_lib', null, 'backup_pro');
		$this->settings = ee()->backup_pro->get_settings();
		ee()->backup_pro->set_backup_dir($this->settings['backup_store_location']);
		
		ee()->backup_pro->set_url_base($this->url_base);
		ee()->backup_pro->set_db_info($this->db_conf);
				
		ee()->load->library('Backup_pro_integrity_agent', null, 'integrity_agent');
		ee()->integrity_agent->monitor_backup_state();
	}

	public function activate_extension() 
	{
		return TRUE;
	}
	
	public function update_extension($current = '')
	{
		return TRUE;
	}

	public function disable_extension()
	{
		return TRUE;
	}
}