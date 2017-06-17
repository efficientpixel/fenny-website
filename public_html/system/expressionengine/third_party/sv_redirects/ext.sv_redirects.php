<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
1.0.0 - Initial Release
1.0.1 - Add show_user_message to allow message display to be of a certain type
1.0.2 - Add override_return option from plugin/template
*/

//error_reporting(E_ALL);
class Sv_redirects_ext {

	/** -------------------------------------
	/** Settings
	/** -------------------------------------*/

	var $settings       = array();
	var $name           = 'SV Redirects';
	var $version        = '1.0.2';
	var $description    = 'Friendly redirects.';
	var $settings_exist = 'n';
	var $docs_url       = '';
	
	/** -------------------------------------
	/** Constructor
	/** -------------------------------------*/
	function Sv_redirects_ext($settings='') {
		
		$this->settings = $settings;
	    $this->EE =& get_instance();
		
	}

	/** -------------------------------------
	/** Activate
	/** -------------------------------------*/

	function activate_extension() {
	
 	// Add new extensions
        $ext_template = array(
            'class'    => __CLASS__,
            'settings' => '',
            'priority' => 10,
            'version'  => $this->version,
            'enabled'  => 'y'
        );
        
        $extensions = array(
			array('hook'=>'sessions_start', 'method'=>'sessions_start'),
            array('hook'=>'insert_comment_end', 'method'=>'insert_comment_end'),
        );
        
        foreach($extensions as $extension)
        {
            $ext = array_merge($ext_template, $extension);
            $this->EE->db->insert('exp_extensions', $ext);
        }

	}


	/** -------------------------------------
	/** Update Extension
	/** -------------------------------------*/
	
	function update_extension($current='') 
	{
		if ($current == '' OR $current == $this->version) {
			return FALSE;
		}
	}


	/** -------------------------------------
	/** Disable
	/** -------------------------------------*/
	
	function disable_extension() 
	{
	    $this->EE->db->where('class', __CLASS__);
	    $this->EE->db->delete('exp_extensions');
	}
	
	function insert_comment_end($data, $comment_moderate, $comment_id)
	{
		//$this->EE->extensions->end_script = TRUE;
		
		//$return_link = ( ! stristr($_POST['RET'],'http://') && ! stristr($_POST['RET'],'https://')) ? $this->EE->functions->create_url($_POST['RET']) : $_POST['RET'];
		
		//$this->EE->functions->redirect($return_link);
	}
	
	function sessions_start($sess)
	{
		if (REQ != 'CP')
		{
			$this->EE->output = new SV_Output();
			
		}
	}
	
}

class SV_Output extends EE_Output {
	function show_message($data, $xhtml = TRUE, $redirect = TRUE)
	{
		$EE =& get_instance();
		
		foreach (array('title', 'heading', 'content', 'redirect', 'rate', 'link') as $val)
		{
			if ( ! isset($data[$val]))
			{
				$data[$val] = '';
			}
		}
		
		if ( ! is_numeric($data['rate']) OR $data['rate'] == '')
		{
			$data['rate'] = $this->refresh_time;
		}
		
		$data['meta_refresh']	= ($data['redirect'] != '') ? "<meta http-equiv='refresh' content='".$data['rate']."; url=".$EE->security->xss_clean($data['redirect'])."'>" : '';
		$data['charset']		= $EE->config->item('output_charset');	
				
		if (is_array($data['link']) AND count($data['link']) > 0)
		{
			$refresh_msg = ($data['redirect'] != '' AND $this->refresh_msg == TRUE) ? $EE->lang->line('click_if_no_redirect') : '';
		
			$ltitle = ($refresh_msg == '') ? $data['link']['1'] : $refresh_msg;
			
			$url = (strtolower($data['link']['0']) == 'javascript:history.go(-1)') ? $data['link']['0'] : $EE->security->xss_clean($data['link']['0']);
		
			$data['link'] = "<a href='".$url."'>".$ltitle."</a>";
		}

		if ($xhtml == TRUE && isset($EE->session))
		{
			$EE->load->library('typography');
	
			$data['content'] = $EE->typography->parse_type(stripslashes($data['content']), array('text_format' => 'xhtml'));
		}		

		$EE->db->select('template_data');
		$EE->db->where('site_id', $EE->config->item('site_id'));
		$EE->db->where('template_name', 'message_template');		
		$query = $EE->db->get('specialty_templates');
		
		$row = $query->row_array();
		
		foreach ($data as $key => $val)
		{
			$row['template_data']  = str_replace('{'.$key.'}', $val, $row['template_data'] );
		}

		//echo  stripslashes($row['template_data'] );		
		
		@session_start();
		$_SESSION['Sv_redirects']['msg_data'] = $data;
		$_SESSION['Sv_redirects']['post_data'] = $_POST;
		session_write_close();
		
		// special case for password reset redirect
		if ($EE->input->post('ACT') !== FALSE)
		{
			if (isset($data['title']) && lang('mbr_passwd_email_sent') != '' && $data['title'] == lang('mbr_passwd_email_sent'))
			{
				$data['redirect'] = $EE->input->post('RET');
			}
		}
		
		if ($EE->input->post('sv_redirects_override') !== FALSE)
		{
			$data['redirect'] = $EE->input->post('sv_redirects_override');
		}
		
		if ($redirect)
		{
			if ($data['redirect'])
			{
				$EE->functions->redirect($EE->security->xss_clean($data['redirect']));
			}
			else
			{
				if (!empty($_SERVER['HTTP_REFERER']))
				{
					$EE->functions->redirect($EE->security->xss_clean($_SERVER['HTTP_REFERER']));
				}
				else
				{
					$EE->functions->redirect('/alert/');
				}
			}
		
			exit;
		}
	} 
	
	function show_user_message($type = 'error', $errors, $redirect = TRUE)
	{
		$EE =& get_instance();
		
		$this->set_header("Content-Type: text/html; charset=".$EE->config->item('charset'));
		
		if ($type != 'off')
		{	  
			switch($type)
			{
				case 'error' 	: $title = 'Error';
					break;
				case 'warning'	: $title = 'Warning';
					break;
				case 'success'	: $title = 'Success';
					break;
				default			: $title = 'Error';
					break;
			}
		}
		
		$content  = '<ul>';
		
		if ( ! is_array($errors))
		{
			$content.= "<li>".$errors."</li>\n";
		}
		else
		{
			foreach ($errors as $val)
			{
				$content.= "<li>".$val."</li>\n";
			}
		}
		
		$content .= "</ul>";
		
		$data = array(	'title' 	=> $title,
						'heading'	=> $heading,
						'content'	=> $content,
						'redirect'	=> '',
						'link'		=> array('JavaScript:history.go(-1)', $EE->lang->line('return_to_previous'))
					 );
				
		$this->show_message($data, 0, $redirect);
	} 
	
	function show_user_warning($msgs, $redirect = TRUE)
	{
		$this->show_user_message('warning', $msgs, $redirect);
	}
	
	function show_user_success($msgs, $redirect = TRUE)
	{
		$this->show_user_message('success', $msgs, $redirect);
	}
}