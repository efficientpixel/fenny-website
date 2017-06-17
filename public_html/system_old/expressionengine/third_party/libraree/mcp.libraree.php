<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 *
 * @package		Libraree
 * @subpackage	ThirdParty
 * @category	Modules
 * @link		
 */
class Libraree_mcp
{
	var $base;			// the base url for this module			
	var $form_base;		// base url for forms
	var $module_name = "libraree";	

    var $EE;

	function Libraree_mcp( $switch = TRUE )
	{
		
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance(); 
		$this->base	 	 = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.$this->module_name;
		$this->form_base = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.$this->module_name;
		$this->EE->load->add_package_path(PATH_THIRD.'libraree/'); 
		
	}

	function index() 
	{
		
		$settings = $this->get_settings();
		$settings["path"] = $this->EE->config->config['tmpl_file_basepath'];
		
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('libraree_name'));
		
		//check mailtype
		$this->EE->load->library("email");
		$this->EE->email->EE_initialize();
		$vars["mailtype"] = $this->EE->email->mailtype;
			
		$output = $this->EE->load->view('cp_index',array('settings' => $settings, 'vars' => $vars),TRUE);
		
		return $output;
	}

	
	function content_wrapper($content_view, $lang_key, $vars = array())
	{
		$vars['content_view'] = $content_view;
		$vars['_base'] = $this->base;
		$vars['_form_base'] = $this->form_base;
		$this->EE->cp->set_variable('cp_page_title', lang($lang_key));
		$this->EE->cp->set_breadcrumb($this->base, lang('libraree_module_name'));

		return $this->EE->load->view('_wrapper', $vars, TRUE);
	}
	
	function show_rendered_template()
	{
		$settings = $this->get_settings();
		
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('libraree_name'));
		
		$output = $this->EE->load->view('cp_view_template',array('settings' => $settings),TRUE);
		
		return $output;
	}
	
	function LibrarEE_cp_base(){
		return TRUE;
	}
	
	function get_settings($all_sites = FALSE)
	{
		$get_settings = $this->EE->db->query("SELECT settings FROM exp_extensions WHERE class = 'Libraree_ext' LIMIT 1");
		
		$this->EE->load->helper('string');
		
		if ($get_settings->num_rows() > 0 && $get_settings->row('settings') != '')
        {
        	$settings = strip_slashes(unserialize($get_settings->row('settings')));
        	$settings = ($all_sites == FALSE && isset($settings[$this->EE->config->item('site_id')])) ? 
        		$settings[$this->EE->config->item('site_id')] : 
        		$settings;
        }
        else
        {
        	$settings = array();
        }
        return $settings;
	}
}

/* End of file mcp.libraree.php */ 
/* Location: ./system/expressionengine/third_party/libraree/mcp.libraree.php */