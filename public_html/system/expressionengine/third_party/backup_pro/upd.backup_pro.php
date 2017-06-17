<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
 * Backup Pro - Update Class
 *
 * Update class
 *
 * @package 	mithra62\BackupPro
 * @author		Eric Lamb
 * @filesource 	./system/expressionengine/third_party/backup_pro/upd.backup_pro.php
 */
class Backup_pro_upd { 

    public $version = '2.0.1'; 
    
    public $name = 'Backup_pro';
    
    public $class = 'Backup_pro';
    
    public $settings_table = 'backup_pro_settings';
     
    public function __construct() 
    { 
    	
    } 
    
	public function install() 
	{
		ee()->load->dbforge();
	
		$data = array(
			'module_name' => $this->name,
			'module_version' => $this->version,
			'has_cp_backend' => 'y',
			'has_publish_fields' => 'n'
		);
	
		ee()->db->insert('modules', $data);
		
		$sql = "INSERT INTO exp_actions (class, method) VALUES ('".$this->name."', 'cron')";
		ee()->db->query($sql);

		$this->add_settings_table();
		$this->activate_extension();
		
		return TRUE;
	} 
	
	public function activate_extension()
	{	
		$data[] = array(
			'class'     => 'Backup_pro_ext',
			'method'    => 'cp_menu_array',
			'hook'      => 'cp_menu_array',
			'settings'  => serialize(array()),
			'priority'  => 500,
			'version'   => $this->version,
			'enabled'   => 'y'
		);	
		
		$data[] = array(
			'class'     => 'Backup_pro_ext',
			'method'    => 'check_backup_state',
			'hook'      => 'cp_member_login',
			'settings'  => serialize(array()),
			'priority'  => 500,
			'version'   => $this->version,
			'enabled'   => 'y'
		);	
	
		foreach($data AS $ex)
		{
			ee()->db->insert('extensions', $ex);	
		}		
	}
	
	
	private function add_settings_table()
	{
		ee()->load->dbforge();
		$fields = array(
			'id'	=> array(
				'type'			=> 'int',
				'constraint'	=> 10,
				'unsigned'		=> TRUE,
				'null'			=> FALSE,
				'auto_increment'=> TRUE
			),
			'setting_key'	=> array(
				'type' 			=> 'varchar',
				'constraint'	=> '60',
				'null'			=> FALSE,
				'default'		=> ''
			),
			'setting_value'  => array(
				'type' 			=> 'text',
				'null'			=> FALSE
			),
			'serialized' => array(
				'type' => 'int',
				'constraint' => 1,
				'null' => TRUE,
				'default' => '0'
			)										
		);

		ee()->dbforge->add_field($fields);
		ee()->dbforge->add_key('id', TRUE);
		ee()->dbforge->create_table($this->settings_table, TRUE);		
	}

	public function uninstall()
	{
		ee()->load->dbforge();
	
		ee()->db->select('module_id');
		$query = ee()->db->get_where('modules', array('module_name' => $this->class));
	
		ee()->db->where('module_id', $query->row('module_id'));
		ee()->db->delete('module_member_groups');
	
		ee()->db->where('module_name', $this->class);
		ee()->db->delete('modules');
	
		ee()->db->where('class', $this->class);
		ee()->db->delete('actions');
		
		ee()->dbforge->drop_table($this->settings_table);
		
		$this->disable_extension();
	
		return TRUE;
	}
	
	public function disable_extension()
	{
		ee()->db->where('class', 'Backup_pro_ext');
		ee()->db->delete('extensions');
	}	

	public function update($current = '')
	{
		if ($current == $this->version)
		{
			return FALSE;
		}

		/*
		if(version_compare($current, '2.1', '<'))
		{
			//$this->update_extension();
		}
		*/
		
		return TRUE;
	}

	public function update_extension()
	{
		$data = array();
		$data[] = array(
				'class'     => 'Backup_pro_ext',
				'method'    => 'cp_menu_array',
				'hook'      => 'cp_menu_array',
				'settings'  => serialize(array()),
				'priority'  => 500,
				'version'   => $this->version,
				'enabled'   => 'y'
		);
		
		foreach($data AS $ex)
		{
			ee()->db->insert('extensions', $ex);
		}		
	}
    
}