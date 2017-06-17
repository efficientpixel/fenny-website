<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine Snaptcha Extension
 *
 * @package		Snaptcha
 * @category	Extension
 * @description	Simple Non-obtrusive Automated Public Turing test to tell Computers and Humans Apart
 * @author		Ben Croker
 * @link		http://www.putyourlightson.net/snaptcha
 */


// get config
require_once PATH_THIRD.'snaptcha/config'.EXT;


class Snaptcha_ext
{
	var $name			= SNAPTCHA_NAME;
	var $version		= SNAPTCHA_VERSION;
	var $description	= SNAPTCHA_DESCRIPTION;
	var $settings_exist	= SNAPTCHA_SETTINGS_EXIST;
	var $docs_url		= SNAPTCHA_URL;

	var $settings		= array();

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 */
	public function __construct($settings = '')
	{
		$this->EE =& get_instance();

		$this->settings = $settings;

		// get settings in case they are not set
		$this->_get_settings();
	}

	// --------------------------------------------------------------------

	/**
	 * Field
	 */
	public function snaptcha_field($security_level='')
	{
		$snaptcha_field = $this->settings['field_name'];

		// allow override of security level
		$security_level = $security_level ? $security_level : $this->settings['security_level'];


		// append random characters to field name
		$name = $snaptcha_field.'_'.$this->EE->functions->random('alpha', 9);

		// set value for high security level
		$value = ($security_level > 1) ? $this->EE->functions->random('alpha', 13) : '';

		// get field html
		$field = '<div class="'.$snaptcha_field.'" style="position: absolute !important; height: 0 !important;  overflow: hidden !important;"><input type="text" id="'.$name.'" name="'.$name.'" value="'.$value.'" /></div>'.NL;


		// add javascript for medium and high security levels
		if ($security_level > 1)
		{
			$secret = $this->settings['unique_secret'];

			// create secret for high security level
			if ($security_level == 3)
			{
				$secret = $this->EE->functions->random('alpha', 13);

				// add secret to database
				$data = array(
					'name' => $name,
					'secret' => $secret,
					'timestamp' => time(),
					'ip_address' => $this->EE->input->ip_address()
				);

				$this->EE->db->insert('snaptcha', $data);
			}

			$field .= '<script type="text/javascript">document.getElementById("'.$name.'").value = "'.$secret.'";</script>'.NL;
		}


		return $field;
	}

	// --------------------------------------------------------------------

	/**
	 * Validate
	 */
	public function snaptcha_validate($security_level='')
	{
		// check if already validated
		if ($this->_get_session_cache('snaptcha', 'validated'))
		{
			return TRUE;
		}


		$snaptcha_field = $this->settings['field_name'];

		// allow override of security level
		$security_level = $security_level ? $security_level : $this->settings['security_level'];


		// search for field in posted values
		$field = '';

		foreach ($_POST as $key => $value)
		{
			if (strpos($key, $snaptcha_field.'_') === 0)
			{
				$field = $key;
			}
		}


		// if field not found
		if (!$field)
		{
			$this->_log('Snaptcha field not found');
			return FALSE;
		}


		// low security level
		if ($security_level == 1)
		{
			// check if field is blank
			if ($this->EE->input->post($field) != '')
			{
				$this->_log('Snaptcha field not blank');
				return FALSE;
			}
		}

		// medium security level
		else if ($security_level == 2)
		{
			// check if secret is correct
			if ($this->EE->input->post($field) != $this->settings['unique_secret'])
			{
				$this->_log('Snaptcha field not equal to unique secret');
				return FALSE;
			}
		}

		// high security level
		else
		{
			// set expiry time to 1 hour
			$expiry = time() - 3600;

			// check if secret exists in database
			$this->EE->db->where('name', $field);
			$this->EE->db->where('secret', $this->EE->input->post($field));
			$this->EE->db->where('timestamp > '.$expiry);
			$this->EE->db->where('ip_address', $this->EE->input->ip_address());
			$query = $this->EE->db->get('snaptcha');

			if (!$query OR !$query->num_rows())
			{
				$this->_log('Snaptcha field not found in database');
				return FALSE;
			}

			// delete and clean out old secrets from database
			$this->EE->db->where('name', $field);
			$this->EE->db->or_where('timestamp < '.$expiry);
			$this->EE->db->delete('snaptcha');
		}


		// mark as validated in session
		$this->_set_session_cache('snaptcha', 'validated', TRUE);

		return TRUE;
	}

	// --------------------------------------------------------------------
	/**
	  *  Start integration with other modules
	  */
	// --------------------------------------------------------------------

	/**
	 * Comment Form Field
	 */
	public function comment_field($tagdata)
	{
		// get value returned from last extension call if it exists
		$tagdata = $this->EE->extensions->last_call ? $this->EE->extensions->last_call : $tagdata;

		// append field to tagdata
		$tagdata .= $this->snaptcha_field();

		return $tagdata;
	}

	// --------------------------------------------------------------------

	/**
	 * Comment Form Validation
	 */
	public function comment_validate()
	{
		if (!$this->snaptcha_validate())
		{
			$this->_show_error();
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Safecracker Field
	 */
	public function safecracker_field($return)
	{
		// get value returned from last extension call if it exists
		$return = $this->EE->extensions->last_call ? $this->EE->extensions->last_call : $return;

		// append field to form
		$return = str_replace('</form>', $this->snaptcha_field().NL.'</form>', $return);

		return $return;
	}

	// --------------------------------------------------------------------

	/**
	 * Safecracker Validation
	 */
	public function safecracker_validate($object)
	{
		if (!$this->snaptcha_validate())
		{
			$this->_show_error();
		}
	}

  // --------------------------------------------------------------------

  /**
   * Channel Form Field
   */
	public function channel_form_field($return)
  {
		// get value returned from last extension call if it exists
		$return = $this->EE->extensions->last_call ? $this->EE->extensions->last_call : $return;

	    // append field to form
	    $return = str_replace('</form>', $this->snaptcha_field().NL.'</form>', $return);

	    return $return;
  }

  // --------------------------------------------------------------------

  /**
   * Channel Form Validation
   */
  public function channel_form_validate($object)
  {
    if (!$this->snaptcha_validate())
    {
      $this->_show_error();
    }
  }

	// --------------------------------------------------------------------

	/**
	 * Freeform Field
	 */
	public function freeform_field($return)
	{
		// get value returned from last extension call if it exists
		$return = $this->EE->extensions->last_call ? $this->EE->extensions->last_call : $return;

		// append field to form
		$return = str_replace('</form>', $this->snaptcha_field().'</form>', $return);

		return $return;
	}

	// --------------------------------------------------------------------

	/**
	 * Freeform Validation
	 */
	public function freeform_validate($errors)
	{
		// skip if we are in the CP
		if (REQ == 'CP')
		{
			return $errors;
		}

		// if no errors exist
		if (count($errors) == 0)
		{
			if (!$this->snaptcha_validate())
			{
				$this->_show_error();
			}
		}

		return $errors;
	}

	// --------------------------------------------------------------------

	/**
	 * Member Register Validation
	 */
	public function member_register_validate($obj)
	{
		// if enabled and no errors exist in member object
		if ($this->settings['member_registration_validation'] AND count($obj->errors) == 0)
		{
			// member register security level must be less than 3
			$member_register_security_level = ($this->settings['security_level'] < 3) ? $this->settings['security_level'] : 2;

			if (!$this->snaptcha_validate($member_register_security_level))
			{
				$this->_show_error();
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Member Register Validation Deprecated (for versions earlier than EE2.5.0)
	 */
	public function member_register_validate_deprecated()
	{
		// check if version is 2.5.0 or later
		if (version_compare(APP_VER, '2.5.0', '>='))
		{
			return;
		}

		// if enabled
		if ($this->settings['member_registration_validation'])
		{
			// member register security level must be less than 3
			$member_register_security_level = ($this->settings['security_level'] < 3) ? $this->settings['security_level'] : 2;

			if (!$this->snaptcha_validate($member_register_security_level))
			{
				$this->_show_error();
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Forum Field
	 */
	public function forum_field($obj, $str)
	{
		// get value returned from last extension call if it exists
		$str = $this->EE->extensions->last_call ? $this->EE->extensions->last_call : $str;

		// if submission form is in string or we are on view thread page
		if (substr_count($str, '{form_declaration:forum:submit_post}') OR $this->EE->uri->segment(2) == 'viewthread')
		{
			// get field
			$field = $this->snaptcha_field();

			// extract input field name
			preg_match('/name="(.*?)"/', $field, $matches);
			$name = $matches[1];

			// append field
			$str .= $field;

			// append javascript that will move the field into the form
			$str .= '<script type="text/javascript">window.onload = function() { document.getElementById("submit_post").appendChild(document.getElementById("'.$name.'").parentNode); }</script>'.NL;
		}

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Forum Validation
	 */
	public function forum_validate()
	{
		if (!$this->snaptcha_validate())
		{
			$this->_show_error();
		}
	}

	// --------------------------------------------------------------------

	/**
	 * User Register Validation
	 */
	public function user_register_validate()
	{
		// if enabled
		if ($this->settings['member_registration_validation'])
		{
			if (!$this->snaptcha_validate())
			{
				$this->_show_error();
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Freemember Register Validation
	 */
	public function freemember_register_validate()
	{
		// if enabled and submitted form is validated
		if ($this->settings['member_registration_validation'] AND $this->EE->form_validation->run())
		{
			if (!$this->snaptcha_validate())
			{
				$this->_show_error();
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Proform Field
	 */
	public function proform_field()
	{
		// prepend field with required fake captcha input field
		$output = '<input type="hidden" name="captcha" value="xyz" />'.$this->snaptcha_field();

		return array($output, FALSE);
	}

	// --------------------------------------------------------------------

	/**
	 * Proform Process
	 */
	public function proform_process_captcha($obj, $form_obj, $form_session)
	{
		// return TRUE boolean to indicate that we are handling captcha
		return array(TRUE, $form_obj, $form_session);
	}

	// --------------------------------------------------------------------

	/**
	 * Proform Validation
	 */
	public function proform_validate($obj, $form_obj, &$form_session)
	{
		// if no errors
		if (count($form_session->errors) == 0)
		{
			if (!$this->snaptcha_validate())
			{
				$form_session->add_error('captcha', $this->settings['error_message']);
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Email Form Field
	 */
	public function email_form_field($return)
	{
		// get value returned from last extension call if it exists
		$return = $this->EE->extensions->last_call ? $this->EE->extensions->last_call : $return;

		// append field to form
		$return = str_replace('</form>', $this->snaptcha_field().NL.'</form>', $return);

		return $return;
	}

	// --------------------------------------------------------------------

	/**
	 * Email Form Validation
	 */
	public function email_form_validate($variables)
	{
		if (!$this->snaptcha_validate())
		{
			$this->_show_error();
		}

		return $variables;
	}

	// --------------------------------------------------------------------

	/**
	 * Rating Field
	 */
	public function rating_field($tagdata)
	{
		// get value returned from last extension call if it exists
		$tagdata = $this->EE->extensions->last_call ? $this->EE->extensions->last_call : $tagdata;

		// append field to tagdata
		$tagdata .= $this->snaptcha_field();

		return $tagdata;
	}

	// --------------------------------------------------------------------

	/**
	 * Rating Validation
	 */
	public function rating_validate()
	{
		if (!$this->snaptcha_validate())
		{
			$this->_show_error();
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Rating Comment Field
	 */
	public function rating_comment_field($tagdata)
	{
		// get value returned from last extension call if it exists
		$tagdata = $this->EE->extensions->last_call ? $this->EE->extensions->last_call : $tagdata;

		// append field to tagdata
		$tagdata .= $this->snaptcha_field();

		return $tagdata;
	}

	// --------------------------------------------------------------------

	/**
	 * Rating Comment Validation
	 */
	public function rating_comment_validate()
	{
		if (!$this->snaptcha_validate())
		{
			$this->_show_error();
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Email Module Field
	 */
	public function email_module_field($tagdata)
	{
		// get value returned from last extension call if it exists
		$tagdata = $this->EE->extensions->last_call ? $this->EE->extensions->last_call : $tagdata;

		// append field to tagdata
		$tagdata .= $this->snaptcha_field();

		return $tagdata;
	}

	// --------------------------------------------------------------------

	/**
	 * Email Module Validation
	 */
	public function email_module_validate()
	{
		if (!$this->snaptcha_validate())
		{
			$this->_show_error();
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Expresso Store Hook - prevents Snaptcha validation on member registration
	 */
	public function store_order_complete_start()
	{
		// mark as validated in session
		$this->_set_session_cache('snaptcha', 'validated', TRUE);
	}

	// --------------------------------------------------------------------
	/**
	  *  End integration with other modules
	  */
	// --------------------------------------------------------------------

	/**
	  *  Get settings
	  */
	private function _get_settings()
	{
		if (empty($this->settings))
		{
			$this->EE->db->where('class', __CLASS__);
			$this->EE->db->limit(1);
			$query = $this->EE->db->get('extensions');

			if ($row = $query->row())
			{
				if ($row->settings)
				{
					$this->settings = strip_slashes(unserialize($row->settings));
				}
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	  *  Show error message
	  */
	private function _show_error($message='')
	{
		$message = $message ? $message : $this->settings['error_message'];

		$this->EE->output->show_user_error('submission', $message);
	}

	// --------------------------------------------------------------------

	/**
	  *  Log a rejected submission
	  */
	private function _log($message)
	{
		// if logging is enabled
		if ($this->settings['logging'])
		{
			// create log string
			$log = date(DATE_ATOM).' Rejected form submission at '.$_SERVER['HTTP_REFERER'].' from '.$this->EE->input->ip_address().' ('.$message.')'.NL;

			// write to log file (open in append mode)
			$fh = fopen(PATH_THIRD.'snaptcha/log.txt', 'a');
			fwrite($fh, $log);
			fclose($fh);
		}
	}

	// --------------------------------------------------------------------

	/**
	  *  Checks if is valid license
	  */
	private function _valid_license($string)
	{
		return preg_match("/^([a-z0-9]{8})-([a-z0-9]{4})-([a-z0-9]{4})-([a-z0-9]{4})-([a-z0-9]{12})$/", trim($string));
	}

	// --------------------------------------------------------------------

	/**
	  *  Get session cache - adds support for EE < 2.2
	  */
	private function _get_session_cache($class_name, $variable_name)
	{
		if (method_exists($this->EE->session, 'cache'))
		{
			return $this->EE->session->cache($class_name, $variable_name);
		}

		else
		{
			return (isset($this->EE->session->cache[$class_name][$variable_name]) AND $this->EE->session->cache[$class_name][$variable_name]);
		}
	}

	// --------------------------------------------------------------------

	/**
	  *  Set session cache - adds support for EE < 2.2
	  */
	private function _set_session_cache($class_name, $variable_name, $value)
	{
		if (method_exists($this->EE->session, 'set_cache'))
		{
			$this->EE->session->set_cache($class_name, $variable_name, $value);
		}

		else
		{
			$this->EE->session->cache[$class_name][$variable_name] = $value;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Settings Form
	 */
	public function settings_form($settings)
	{
		$this->EE->load->helper('form');
		$this->EE->load->library('table');

		$this->EE->cp->load_package_js('script');

		$this->settings = $settings;

		// member register security level must be less than 3
		$member_register_security_level = ($this->settings['security_level'] < 3) ? $this->settings['security_level'] : 2;

		$vars = array();
		$vars['unique_secret'] = $settings['unique_secret'];
		$vars['valid_license'] = $this->_valid_license($settings['license_number']);
		$vars['member_registration_html_low'] = $this->snaptcha_field(1);
		$vars['member_registration_html_medium'] = $this->snaptcha_field(2);
		$vars['member_registration_html_high'] = $this->snaptcha_field(3);
		$vars['log_file_not_writable'] = !is_writable(PATH_THIRD.'snaptcha/log.txt');

		$vars['settings'] = array
		(
			'license_number' => form_input(array(
						'name' => 'license_number',
						'value' => $settings['license_number'],
						'style' => ($vars['valid_license'] ? '' : 'border-color: #CE0000; width: 70%;')
			)),
			'security_level' => form_dropdown(
						'security_level',
						array(3 => lang('high'), 2 => lang('medium'), 1 => lang('low')),
						$settings['security_level']
			),
			'field_name' => form_input(
						'field_name',
						$settings['field_name']
			),
			'logging' => form_dropdown(
						'logging',
						array(0 => lang('disabled'), 1 => lang('enabled')),
						$settings['logging']
			),
			'member_registration_validation' => form_dropdown(
						'member_registration_validation',
						array(0 => lang('disabled'), 1 => lang('enabled')),
						$settings['member_registration_validation']
			),
			'error_message' => form_textarea(array(
						'name' => 'error_message',
						'value' => $settings['error_message'],
						'rows' => '6'
			))
		);

		return $this->EE->load->view('settings', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Save Settings
	 */
	public function save_settings()
	{
		$this->EE->lang->loadfile('snaptcha');

		if (empty($_POST))
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}

		// check that field name prefix is at least four characters
		if (strlen($this->EE->input->post('field_name')) < 4)
		{
			show_error($this->EE->lang->line('field_name_prefix_too_short'));
		}

		unset($_POST['submit']);

		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->update('extensions', array('settings' => serialize($_POST)));

		$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('preferences_updated'));
	}

	// --------------------------------------------------------------------

	/**
	 * Update Extension
	 */
	public function update_extension($current = '')
	{
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}


		if (version_compare($current, '1.1', '<'))
		{
			// get current settings
			$this->EE->db->where('class', __CLASS__);
			$query = $this->EE->db->get('extensions');
			$settings = unserialize($query->row()->settings);

			// add unique_secret and member_registration_validation to settings
			$settings['unique_secret'] = $this->EE->functions->random('alpha', 13);
			$settings['member_registration_validation'] = 0;
			$this->EE->db->where('class', __CLASS__);
			$this->EE->db->update('extensions', array('settings' => serialize($settings)));

			// member register hooks
			$this->_insert_hooks(array(
				'member_member_register_start' => 'member_register_validate_deprecated'
			));
		}


		if (version_compare($current, '1.2', '<'))
		{
			// forum hooks
			$this->_insert_hooks(array(
				'forum_threads_template' => 'forum_field',
				'forum_submit_post_start' => 'forum_validate'
			));
		}


		if (version_compare($current, '1.2.1', '<'))
		{
			// add forum hook for submission form
			$this->_insert_hooks(array(
				'forum_submission_form_end' => 'forum_field'
			));
		}


		if (version_compare($current, '1.3', '<'))
		{
			// get current settings
			$this->EE->db->where('class', __CLASS__);
			$query = $this->EE->db->get('extensions');
			$settings = unserialize($query->row()->settings);

			// add logging to settings
			$settings['logging'] = 0;
			$this->EE->db->where('class', __CLASS__);
			$this->EE->db->update('extensions', array('settings' => serialize($settings)));
		}


		if (version_compare($current, '1.4', '<'))
		{
			// user module hooks
			$this->_insert_hooks(array(
				'user_register_start' => 'user_register_validate'
			));
		}


		if (version_compare($current, '1.4.1', '<'))
		{
			// update safecracker validation hook
			$this->EE->db->where(array(
				'class' => __CLASS__,
				'hook' => 'safecracker_submit_entry_end'
			));
			$this->EE->db->update('extensions', array('hook' => 'safecracker_submit_entry_start'));
		}


		if (version_compare($current, '1.5.1', '<'))
		{
			// freemember hooks
			$this->_insert_hooks(array(
				'freemember_register_validation' => 'freemember_register_validate'
			));
		}


		if (version_compare($current, '1.6', '<'))
		{
			// update member validation hook
			$this->EE->db->where(array(
				'class' => __CLASS__,
				'hook' => 'member_member_register_start'
			));
			$this->EE->db->update('extensions', array('hook' => 'member_member_register_errors'));


			// delete zoo visitor hook (safecracker will deal with zoo visitor)
			if (version_compare($current, '1.4', '>='))
			{
				$this->EE->db->where(array(
					'class' => __CLASS__,
					'hook' => 'zoo_visitor_register_validation_start'
				));
				$this->EE->db->delete('extensions');
			}

			// proform hooks
			$this->_insert_hooks(array(
				'proform_create_captcha' => 'proform_field',
				'proform_process_captcha' => 'proform_process_captcha',
				'proform_validation_end' => 'proform_validate'
			));
		}


		if (version_compare($current, '1.6.7', '<'))
		{
			// email form hooks
			$this->_insert_hooks(array(
				'email_form_form_end' => 'email_form_field',
				'email_form_send_form_start' => 'email_form_validate'
			));
		}


		if (version_compare($current, '1.7', '<'))
		{
			// rating hooks
			$this->_insert_hooks(array(
				'rating_form_tagdata' => 'rating_field',
				'insert_rating_start' => 'rating_validate',
				'rating_comment_form_tagdata' => 'rating_comment_field',
				'insert_rating_comment_start' => 'rating_comment_validate'
			));
		}


		if (version_compare($current, '1.7.1', '<'))
		{
			// email module hooks
			$this->_insert_hooks(array(
				'email_module_form_end' => 'email_module_field',
				'email_module_send_email_start' => 'email_module_validate'
			));
		}


	    if (version_compare($current, '1.7.3', '<'))
	    {
	      if (version_compare(APP_VER, '2.7.0', '>='))
	      {
	        // delete safecracker hooks
	        $this->EE->db->where(array(
	          'class' => __CLASS__,
	          'hook' => 'safecracker_entry_form_tagdata_end'
	        ));
	        $this->EE->db->delete('extensions');

	        $this->EE->db->where(array(
	          'class' => __CLASS__,
	          'hook' => 'safecracker_submit_entry_start'
	        ));
	        $this->EE->db->delete('extensions');
	      }

	      // channel form hooks
	      $this->_insert_hooks(array(
	        'channel_form_entry_form_tagdata_end' => 'channel_form_field',
	        'channel_form_submit_entry_start' => 'channel_form_validate'
	      ));
	    }


	    if (version_compare($current, '1.7.6', '<'))
	    {
			// expresso store hooks
			$this->_insert_hooks(array(
				'store_order_complete_start' => 'store_order_complete_start',
			));
		}


		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->update(
			'extensions',
			array('version' => $this->version)
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Activate Extension
	 */
	public function activate_extension()
	{
		$this->EE->lang->loadfile('snaptcha');


		// default settings
		$this->settings = array(
			'license_number' => '',
			'unique_secret' => $this->EE->functions->random('alpha', 13),
			'security_level' => 3,
			'field_name' => 'snap',
			'error_message' => lang('default_error_message'),
			'member_registration_validation' => 0,
			'logging' => 0
		);


		// comment form	hooks
		$this->_insert_hooks(array(
			'comment_form_tagdata' => 'comment_field',
			'insert_comment_start' => 'comment_validate'
		));

		// safecracker hooks (pre 2.7.0)
	    if (version_compare(APP_VER, '2.7.0', '<'))
	    {
	  		$this->_insert_hooks(array(
	  			'safecracker_entry_form_tagdata_end' => 'safecracker_field',
	  			'safecracker_submit_entry_start' => 'safecracker_validate'
	  		));
	    }

	    // channel form hooks
	    $this->_insert_hooks(array(
	      'channel_form_entry_form_tagdata_end' => 'channel_form_field',
	      'channel_form_submit_entry_start' => 'channel_form_validate'
	    ));

		// freeform hooks
		$this->_insert_hooks(array(
			'freeform_module_form_end' => 'freeform_field',
			'freeform_module_validate_end' => 'freeform_validate'
		));

		// member register hooks
		$this->_insert_hooks(array(
			'member_member_register_errors' => 'member_register_validate',
			'member_member_register_start' => 'member_register_validate_deprecated'
		));

		// forum hooks
		$this->_insert_hooks(array(
			'forum_threads_template' => 'forum_field',
			'forum_submission_form_end' => 'forum_field',
			'forum_submit_post_start' => 'forum_validate'
		));

		// user module hooks
		$this->_insert_hooks(array(
			'user_register_start' => 'user_register_validate'
		));

		// freemember hooks
		$this->_insert_hooks(array(
			'freemember_register_validation' => 'freemember_register_validate'
		));

		// proform hooks
		$this->_insert_hooks(array(
			'proform_create_captcha' => 'proform_field',
			'proform_process_captcha' => 'proform_process_captcha',
			'proform_validation_end' => 'proform_validate'
		));

		// email form hooks
		$this->_insert_hooks(array(
			'email_form_form_end' => 'email_form_field',
			'email_form_send_form_start' => 'email_form_validate'
		));

		// rating hooks
		$this->_insert_hooks(array(
			'rating_form_tagdata' => 'rating_field',
			'insert_rating_start' => 'rating_validate',
			'rating_comment_form_tagdata' => 'rating_comment_field',
			'insert_rating_comment_start' => 'rating_comment_validate'
		));

		// email module hooks
		$this->_insert_hooks(array(
			'email_module_form_end' => 'email_module_field',
			'email_module_send_email_start' => 'email_module_validate'
		));

		// expresso store hooks
		$this->_insert_hooks(array(
			'store_order_complete_start' => 'store_order_complete_start',
		));


		// create snaptcha table
		$this->EE->load->dbforge();

		$fields = array(
						'id' 			=> array(
											'type'			=> 'int',
											'constraint'	=> 11,
											'unsigned'		=> TRUE,
											'null'			=> FALSE,
											'auto_increment'=> TRUE
										),
						'name'			=> array(
											'type' 			=> 'varchar',
											'constraint'	=> '100',
											'null'			=> FALSE,
											'default'		=> ''
										),
						'secret'  		=> array(
											'type' 			=> 'varchar',
											'constraint'	=> '100',
											'null'			=> FALSE,
											'default'		=> ''
										),
						'timestamp'  	=> array(
											'type' 			=> 'varchar',
											'constraint'	=> '16',
											'null'			=> FALSE,
											'default'		=> ''
										),
						'ip_address' 			 => array(
											'type' 			=> 'varchar',
											'constraint'	=> '16',
											'null'			=> FALSE,
											'default'		=> ''
										)
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('id', TRUE);
		$this->EE->dbforge->create_table('snaptcha', TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Disable Extension
	 */
	public function disable_extension()
	{
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->delete('extensions');

		// delete snaptcha table
		$this->EE->load->dbforge();
		$this->EE->dbforge->drop_table('snaptcha');
	}

	// --------------------------------------------------------------------

	/**
	 * Insert Hooks
	 */
	private function _insert_hooks($hooks)
	{
		$data = array(
			'class'	 	=> __CLASS__,
			'settings'  => serialize($this->settings),
			'priority'  => 10,
			'version'   => $this->version,
			'enabled'   => 'y'
		);

		foreach ($hooks as $hook => $method)
		{
			$data['hook'] = $hook;
			$data['method'] = $method;

			$this->EE->db->insert('extensions', $data);
		}
	}

}
// END CLASS

/* End of file ext.snaptcha.php */
/* Location: ./system/expressionengine/third_party/snaptcha/ext.snaptcha.php */
