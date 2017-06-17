<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');



/**
 * Snippet files directly to your templates without having to load them manually 
 *
 * @package		Libraree
 * @subpackage	ThirdParty
 * @category	Modules
 * @link		
 */
class Libraree_upd {
		
	var $version        = '1.0.7'; 
	var $module_name = "Libraree";
	
    function Libraree_upd( $switch = TRUE ) 
    { 
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
    } 

    /**
     * Installer for the Libraree module
     */
    function install() 
	{				
						
		$data = array(
			'module_name' 	 => $this->module_name,
			'module_version' => $this->version,
			'has_cp_backend' => 'y',
			'has_publish_fields' => 'n'
		);

		$this->EE->db->insert('modules', $data);		
		
		$data = array(
			'class'		=> 'Libraree' ,
			'method'	=> 'save_to_file'
		);
		
		$this->EE->db->insert('actions', $data);
																									
		return TRUE;
	}

	
	/**
	 * Uninstall the Libraree module
	 */
	function uninstall() 
	{ 				
		
		$this->EE->db->select('module_id');
		$query = $this->EE->db->get_where('modules', array('module_name' => $this->module_name));
		
		$this->EE->db->where('module_id', $query->row('module_id'));
		$this->EE->db->delete('module_member_groups');
		
		$this->EE->db->where('module_name', $this->module_name);
		$this->EE->db->delete('modules');
		
		$this->EE->db->where('class', $this->module_name);
		$this->EE->db->delete('actions');
		
		$this->EE->db->where('class', $this->module_name.'_mcp');
		$this->EE->db->delete('actions');
										
		return TRUE;
	}
	
	/**
	 * Update the Libraree module
	 * 
	 * @param $current current version number
	 * @return boolean indicating whether or not the module was updated 
	 */
	
	function update($current = '')
	{
		return TRUE;
	}

    function EE() {if(!isset($this->EE)){$this->EE =& get_instance();}return $this->EE;}    
}

/* End of file upd.libraree.php */ 
/* Location: ./system/expressionengine/third_party/libraree/upd.libraree.php */
