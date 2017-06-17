<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Simplee_search_upd {

	var $module_name = 'Simplee_search';
    var $version = '1.0.1';
    
    function install(){
	    ee()->load->dbforge();
	    
	    $data = array(
	        'module_name' => $this->module_name,
	        'module_version' => $this->version,
	        'has_cp_backend' => 'n',
	        'has_publish_fields' => 'n'
	    );
	    ee()->db->insert('modules', $data);
	    
		return TRUE;
	}
	
	function uninstall(){
	    ee()->load->dbforge();
	    
		ee()->db->where('module_name', $this->module_name);
	    ee()->db->delete('modules');
	
	    return TRUE;
	}
	
	function update($current = ''){
	    return FALSE;
	}
}

?>