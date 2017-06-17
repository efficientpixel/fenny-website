<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Evopost_upd {


	var $settings       = array();

	var $name           = "";
	var $version        = '1.0.0';
	var $description    = "";
	var $docs_url       = 'http://expressionengine.com';

	// -------------------------------
	//   Constructor - Extensions use this for settings
	// -------------------------------

	function Evopost_upd(){
		$this->EE =& get_instance();
		$this->name           = $this->EE->lang->line('evopost_module_name');
	 	$this->description    = $this->EE->lang->line('evopost_module_description');
	}

	function install()
	{
		$this->EE->load->dbforge();

		$data = array(
			'module_name' => $this->EE->lang->line('evopost_module_class_name') ,
			'module_version' => $this->version,
			'has_cp_backend' => 'n',
		);

		$this->EE->db->insert('modules', $data);

		$data = array(
			'class'		=> $this->EE->lang->line('evopost_module_class_name') ,
			'method'	=> 'getpostdata'
			);

			$this->EE->db->insert('actions', $data);


			return TRUE;

	}

	function update($current = '')
	{
		return FALSE;
	}


	function uninstall()
	{
		$this->EE->load->dbforge();

		$this->EE->db->select('module_id');
		$query = $this->EE->db->get_where('modules', array('module_name' => $this->EE->lang->line('evopost_module_class_name')));

		$this->EE->db->where('module_id', $query->row('module_id'));
		$this->EE->db->delete('module_member_groups');

		$this->EE->db->where('module_name', $this->EE->lang->line('evopost_module_class_name'));
		$this->EE->db->delete('modules');

		$this->EE->db->where('class', $this->EE->lang->line('evopost_module_class_name'));
		$this->EE->db->delete('actions');

		return TRUE;
	}


}
