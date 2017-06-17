<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Mefu_upd {

    var $settings       = array();
    
    var $name           = 'MEFU';
    var $version        = '1.0.0';
    var $description    = 'Multiple Entry Field Updater';
    var $docs_url       = 'http://expressionengine.com';
	    
    // -------------------------------
    //   Constructor - Extensions use this for settings
    // -------------------------------
        
    function Mefu_upd(){
        $this->EE =& get_instance();
    }

    function install() 
	{
		$this->EE->load->dbforge();
	
		$data = array(
			'module_name' => 'Mefu' ,
			'module_version' => $this->version,
			'has_cp_backend' => 'n',
		);
		
		$this->EE->db->insert('modules', $data);
		
		$data = array(
			'class'		=> 'Mefu' ,
			'method'	=> 'getField'
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
		$query = $this->EE->db->get_where('modules', array('module_name' => "Mefu"));
	
		$this->EE->db->where('module_id', $query->row('module_id'));
		$this->EE->db->delete('module_member_groups');
	
		$this->EE->db->where('module_name', "Mefu");
		$this->EE->db->delete('modules');
	
		$this->EE->db->where('class', 'Mefu');
		$this->EE->db->delete('actions');
		
		return TRUE;
	}
 
    
}
