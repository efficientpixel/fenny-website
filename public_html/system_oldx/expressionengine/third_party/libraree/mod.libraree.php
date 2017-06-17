<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Snippet files directly to your templates without havind to load them manually 
 *
 * @package		Libraree
 * @subpackage	ThirdParty
 * @category	Modules
 * @link		
 */
class Libraree {

	var $return_data;

    /**
     * @var Devkit_code_completion
     */
    private $EE;

	function Libraree()
	{		
		$this->EE =& get_instance();
		$this->EE->load->set_package_path(PATH_THIRD.'libraree/'); 
        
	}
	
	function save_to_file(){
		print_r($_POST);
		
		//include(APPPATH.'controllers/cp/design.php');
		
		//print_r($this->EE);
	}
		
	/**
     * Helper function for getting a parameter
	 */		 
	function _get_param($key, $default_value = '')
	{
		$val = $this->EE->TMPL->fetch_param($key);
		
		if($val == '') {
			return $default_value;
		}
		return $val;
	}

	/**
	 * Helper funciton for template logging
	 */	
	function _error_log($msg)
	{		
		$this->EE->TMPL->log_item("libraree ERROR: ".$msg);		
	}


    function EE() {if(!isset($this->EE)){$this->EE =& get_instance();}return $this->EE;}
}

/* End of file mod.libraree.php */ 
/* Location: ./system/expressionengine/third_party/libraree/mod.libraree.php */