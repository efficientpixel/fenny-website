<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @property CI_Controller $EE
 */
class Profile_ext
{
	public $settings = array();
	public $name = 'Profile:Edit';
	public $version;
	public $description = 'Facilitates the use of channel entries as member profiles using the Channel Form.';
	public $settings_exist = 'y';
	public $docs_url = 'http://mightybigrobot.com/docs/profile_edit';
	public $required_by = array('module');

	private $errors = array();
	private $message;
	
	protected $EE;

	/**
	 * Extension_ext
	 *
	 * @access	public
	 * @param	mixed $settings = ''
	 * @return	void
	 */
	public function __construct($settings = '')
	{
		$this->EE =& get_instance();
		
		include PATH_THIRD.'profile/config.php';
		
		$this->version = $config['version'];
		
		$this->EE->load->library('encrypt');
		
		if ( ! in_array(PATH_THIRD.'profile/', $this->EE->load->get_package_paths()))
		{
			$this->EE->load->add_package_path(PATH_THIRD.'profile/');
		}
		
		$this->EE->load->model('profile_model');
		
		$this->EE->profile_model->set_settings($settings);
	}

	/**
	 * activate_extension
	 *
	 * @access	public
	 * @return	void
	 */
	public function activate_extension()
	{
		return TRUE;
	}

	/**
	 * update_extension
	 *
	 * @access	public
	 * @param	mixed $current = ''
	 * @return	void
	 */
	public function update_extension($current = '')
	{
		if ($current == '' || $current == $this->version)
		{
			return FALSE;
		}
		
		return TRUE;
	}

	/**
	 * disable_extension
	 *
	 * @access	public
	 * @return	void
	 */
	public function disable_extension()
	{
		return TRUE;
	}
	
	public function settings_form()
	{
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=profile');
	}

	public function safecracker_submit_entry_start(&$channel_form)
	{
		return $this->channel_form_submit_entry_start($channel_form); 
	}
	public function channel_form_submit_entry_start(&$channel_form)
	{
		if ($this->EE->profile_model->errors())
		{
			//they're already going to error out, no need to trigger the no title error.
			if ( ! $this->EE->input->post('title'))
			{
				$_POST['title'] = uniqid(md5(rand()), TRUE);
			}
			
			foreach ($this->EE->profile_model->errors() as $key => $value)
			{
				if ($this->EE->profile_model->settings('use_email_as_username') && $value === lang('username_taken'))
				{
					continue;
				}

				if (is_numeric($key))
				{
					$channel_form->errors[] = $value;
				}
				else
				{
					$channel_form->field_errors[$key] = $value;
				}
			}

			//adds a custom file prefix to channel_form file fields
			//ie. <input name="your_file_field_prefix" value="{username}_" type="hidden">
			foreach ($this->EE->channel_form->custom_fields as $field)
			{
				if ($field['field_type'] === 'safecracker_file' && isset($_FILES[$field['field_name']]) && isset($_POST[$field['field_name'].'_prefix']))
				{
					$prefix = $this->EE->input->post($field['field_name'].'_prefix', TRUE);

					unset($_POST[$field['field_name'].'_prefix']);

					$_FILES[$field['field_name']]['name'] = $prefix.$_FILES[$field['field_name']]['name'];
				}
			}
		}
	}
	public function safecracker_submit_entry_end(&$channel_form)
	{
		return $this->channel_form_submit_entry_end($channel_form); 
	}
	public function channel_form_submit_entry_end(&$channel_form)
	{
		$success = FALSE; 
		if (empty($channel_form->errors) && empty($channel_form->field_errors))
		{
			$success = TRUE; 
		}
		
		if ($this->is_register())
		{
			if (isset($this->EE->session->cache['profile']['member_id']))
			{
				if ($success)
				{
					//set in member member register
					//the reason this is here and not there is we only want to send the activation email
					//after we confirm that the channel_form/profile portion of the registration passes validation
					if (isset($this->EE->session->cache['profile']['send_email']))
					{
						$variables = $data = $this->EE->session->cache['profile']['send_email'];
						
						$variables['name'] = $data['screen_name'];
						
						$action_params = array(
							'authcode' => $data['authcode'],
							'return' => $this->EE->input->post('activation_return') ? $this->EE->input->post('activation_return') : '',
						);
						
						if ($this->EE->input->post('mailinglist_subscribe') &&
						    $this->EE->db->where('list_id', $this->EE->input->post('mailinglist_subscribe'))->count_all_results('mailing_lists') === 1 &&
						    $this->EE->db->where(array('list_id' => $this->EE->input->post('mailinglist_subscribe'), 'email' => $data['email']))->count_all_results('mailing_list') === 0
						   )
						{
							$action_params['mailinglist'] = $this->EE->input->post('mailinglist_subscribe');
						}
						
						$can_set_member_group = TRUE;

						if ($this->EE->session->userdata('member_id'))
						{
							//set this so channel_form_submit_entry_end knows not to log you out if failed
							$this->EE->session->cache['profile']['admin_register'] = TRUE;

							$this->EE->config->set_item('use_membership_captcha', 'n');

							$can_set_member_group = $this->EE->session->userdata('can_admin_mbr_groups') === 'y';
						}

						if ($this->EE->input->post('GID') && $can_set_member_group)
						{
							$this->EE->load->helper('security');
							
							$group_id = xss_clean($this->EE->encrypt->decode($this->EE->input->post('GID')));
							
							//validate group_id
							if ($this->EE->profile_model->validate_group_id($group_id))
							{
								$action_params['g'] = $this->EE->input->post('GID');
							}
						}
						
						$variables['activation_url'] = $this->EE->profile_model->build_action_url('activation', $action_params);
						
						$this->EE->profile_model->send_email($data['email'], 'mbr_activation_instructions', $variables);
						
					}
				}
				else
				{
					$this->EE->profile_model->cancel_registration($this->EE->session->cache['profile']['member_id'], $this->EE->session->cache['profile']['entry_id']);
					
					if ( ! isset($this->EE->session->cache['profile']['admin_register']))
					{
						$this->EE->session->destroy();
					}
				}
			}
			
			//update the author id; when using self activation or manual activiation by admin
			if (isset($this->EE->session->cache['profile']['member_id']) && $this->EE->config->item('req_mbr_activation') !== 'none')
			{
				$this->EE->db->update('channel_titles', array('author_id' => $this->EE->session->cache['profile']['member_id']), array('entry_id' => $this->EE->session->cache['profile']['entry_id']));
			}
		}
		
		if ($this->is_edit())
		{
			if ( ! empty($this->EE->session->cache['profile']['update_member']))
			{
				$this->EE->profile_model->update_member($this->EE->session->cache['profile']['member_id'], $this->EE->session->cache['profile']['update_member']);
			}
			
			if ($success && $this->EE->input->post('GID') && $this->EE->session->userdata('can_admin_mbr_groups') === 'y')
			{
				$this->EE->load->helper('security');
				
				$group_id = xss_clean($this->EE->encrypt->decode($this->EE->input->post('GID')));
				
				//validate group_id
				if ($this->EE->profile_model->validate_group_id($group_id))
				{
					$this->EE->profile_model->change_member_group($this->EE->session->cache['profile']['member_id'], $group_id);
				}
			}
			// the override_member_id should only be set if the person was an admin
 			if ($success && ! empty($this->EE->session->cache['profile']['override_member_id']))
			{
				$this->EE->profile_model->change_author_id($this->EE->session->cache['profile']['member_id'], $this->EE->input->post('entry_id'));
			}
		}
		
		//on register and edit profile
		if ($this->is_profile())
		{
			$member_id = isset($this->EE->session->cache['profile']['member_id']) ? $this->EE->session->cache['profile']['member_id'] : FALSE;
			
			if ($member_id && $success)
			{
				$this->EE->profile_model->update_native_profile($this->EE->session->cache['profile']['member_id'], $_POST, TRUE);
			}
			
			if ($member_id && $this->EE->input->post('return'))
			{
				$_POST['return'] = str_replace('MEMBER_ID', $this->EE->session->cache['profile']['member_id'], $this->EE->input->post('return'));
			}
			
			if (isset($this->EE->session->cache['profile']['userdata']))
			{
				foreach (array('can_assign_post_authors', 'can_edit_other_entries') as $key)
				{
					if (isset($this->EE->session->cache['profile']['userdata'][$key]))
					{
						$this->EE->session->userdata[$key] = $this->EE->session->cache['profile']['userdata'][$key];
					}
				}
			}
			
		
			if ($this->EE->profile_model->settings('use_email_as_username')
			    && isset($channel_form->field_errors['username'])
			    && $channel_form->field_errors['username'] == lang('missing_username')
			    && isset($channel_form->field_errors['email']))
			{
				unset($channel_form->field_errors['username']);
			}
			
			$member_data = isset($this->EE->session->cache['profile']['userdata']) ? $this->EE->session->cache['profile']['userdata'] : array();
			
			// Hooks
			if ($this->is_register())
			{
				if ($this->EE->extensions->active_hook('profile_register_end') === TRUE)
				{
					$this->EE->extensions->call('profile_register_end', $member_id, $member_data, $channel_form->entry, $success, $this->is_admin_register());
				}
			}
			else if ($this->is_edit())
			{
				if ($this->EE->extensions->active_hook('profile_edit_end') === TRUE)
				{
					$this->EE->extensions->call('profile_edit_end', $member_id, $member_data, $channel_form->entry, $success, $this->is_admin_edit());
				}
			}
		}
		
		//@TODO update native fields
		
		//@TODO
		//i wanted to pass error handling to form_builder because it does some stuff better, and also for consistency's sake
		//but it's not to feasible
		//will try to get some changes into channel_form to make it's inline errors/json more betterer
		return;
		
		/*
		if (
			//only proceed if this is a profile edit/register submission
			! isset($this->EE->session->cache['profile']['submit_entry']) ||
			//only proceed if there are errors to handle
			( ! $channel_form->errors && ! $channel_form->field_errors) ||
			//only proceed if we're doing json or inline errors
			($channel_form->error_handling !== 'inline' && ! $channel_form->json)
		)
		{
			return;
		}
		
		if (is_array($channel_form->errors))
		{
			//add the field name to custom_field_empty errors
			foreach ($channel_form->errors as $field_name => $error)
			{
				if ($error == $this->EE->lang->line('custom_field_empty'))
				{
					$channel_form->errors[$field_name] = $error.' '.$field_name;
				}
			}
		}
		
		if ( ! $channel_form->json && $channel_form->error_handling == 'inline')
		{
			$channel_form->entry = $_POST;
			
			$channel_form->form_error = TRUE;
			
			foreach($channel_form->post_error_callbacks as $field_type => $callbacks)
			{
				$callbacks = explode('|', $callbacks);
				
				foreach ($channel_form->custom_fields as $field)
				{
					if ($field['field_type'] == $field_type)
					{
						foreach ($callbacks as $callback)
						{
							if (in_array($callback, $channel_form->valid_callbacks))
							{
								$channel_form->entry[$field['field_name']] = $channel_form->entry['field_id_'.$field['field_id']] = call_user_func($callback, $channel_form->entry($field['field_name']));
							}
						}
					}
				}
			}
			
			foreach ($channel_form->date_fields as $field)
			{
				if ($channel_form->entry($field) && ! is_numeric($channel_form->entry($field)))
				{
					$channel_form->entry[$field] = $this->EE->localize->convert_human_date_to_gmt($channel_form->entry($field));
				}
			}
			
			if (version_compare(APP_VER, '2.1.3', '>'))
			{
				$this->EE->core->generate_page();
			}
			else
			{
				$this->EE->core->_generate_page();
			}
			
			return;
		}
		
		if ($channel_form->json)
		{
			return $channel_form->send_ajax_response(
				//json_encode(
					array(
						'success' => (empty($channel_form->errors) && empty($channel_form->field_errors)) ? 1 : 0,
						'errors' => (empty($channel_form->errors)) ? array() : $channel_form->errors,
						'field_errors' => (empty($channel_form->field_errors)) ? array() : $channel_form->field_errors,
						'entry_id' => $channel_form->entry('entry_id'),
						'url_title' => $channel_form->entry('url_title'),
						'channel_id' => $channel_form->entry('channel_id'),
					)
				//)
			);
		}
		*/
	}
	public function safecracker_entry_form_tagdata_start($tagdata, &$channel_form)
	{
		return $this->channel_form_entry_form_tagdata_start($tagdata, $channel_form); 
	}
	/**
	 * Changes the entry_form action
	 * 
	 * @return Type    Description
	 */
	public function channel_form_entry_form_tagdata_start($tagdata, &$channel_form)
	{
		if ($this->EE->extensions->last_call !== FALSE)
		{
			$tagdata = $this->EE->extensions->last_call;
		}
		
		if (isset($this->EE->session->cache['profile']['form_hidden']))
		{
			$channel_form->form_hidden($this->EE->session->cache['profile']['form_hidden']);
			
			unset($this->EE->session->cache['profile']['form_hidden']);
		}
		
		if ($this->is_edit())
		{
			//parse native member data for this entry
			$tagdata = $this->EE->TMPL->parse_variables_row($tagdata, $this->EE->profile_model->get_member_data($channel_form->entry('author_id')));
		}
		if ($this->EE->profile_model->errors())
		{
			if ( version_compare(APP_VER, '2.7', '>='))
			{
				$this->EE->load->add_package_path(PATH_MOD.'channel');
				$this->EE->load->library('channel_form/channel_form_lib', NULL, 'channel_form');
			}
			else
			{
				$this->EE->load->library('safecracker_lib', NULL, 'channel_form');
			}
			
			$channel_form->form_error = $this->EE->profile_model->errors(); 
 			foreach ($this->EE->profile_model->errors() as $key => $value)
			{
 				$channel_form->field_errors[$key] = $value; 
			}
		}
		
		return $tagdata;
	}

	/**
	 * Hook into entry_submission_start
	 *
	 * This is used to show an error if the user is trying to create a new entry
	 * in the member channel when they already have an entry in that channel
	 * 
	 * @param string|int $channel_id
	 * @param bool $autosave
	 * 
	 * @return void
	 */
	public function entry_submission_start($channel_id, $autosave)
	{
		if ($autosave || $this->EE->input->get_post('entry_id'))
		{
			return;
		}

		if ($channel_id == $this->EE->profile_model->channel_id()//ignore if not the profile channel
		    && ($this->EE->profile_model->oldest_superadmin() != $this->EE->session->userdata('member_id'))//this means we're using the logged_out_member_id parameter
		    && ! $this->EE->profile_model->settings('allow_multiple_profiles')
		    && $this->EE->profile_model->member_has_entry())
		{
			$this->EE->lang->loadfile('profile', 'profile');

			show_error(lang('one_entry_per_member'), $status_code = 500 );
		}
	}

	/**
	 * Hook into member_member_register
	 *
	 * Creates an entry in the member channel for the new registrant,
	 * Saves entry_id and member_id to cache
	 * 
	 * @param array $data      the submitted member data
	 * @param string|int $member_id the nely created member_id
	 * 
	 * @return void
	 */
	public function member_member_register($data, $member_id)
	{
		if ( ! $this->EE->profile_model->channel_id())
		{
			return;
		}
		
		$this->EE->session->cache['profile']['userdata'] = $data;
		
		$entry_id = $this->EE->profile_model->create_profile($data, $member_id, isset($this->EE->session->cache['profile']['oldest_superadmin']));
		
		if ($this->is_register())
		{
			//we don't want the member module to log them into the new account if they're already logged in
			if ($this->EE->session->userdata('member_id') && $this->EE->config->item('req_mbr_activation') === 'none')
			{
				$this->EE->extensions->end_script = TRUE;
			}
			
			if ($this->EE->config->item('req_mbr_activation') === 'email')
			{
				$this->EE->session->cache['profile']['send_email'] = $data;
				
				$this->EE->extensions->end_script = TRUE;
			}
			
			if ( ! $this->EE->profile_model->settings('auto_login_after_register') && $this->EE->config->item('req_mbr_activation') === 'none')
			{
				$this->EE->extensions->end_script = TRUE;
			}
				
			//we'll ignore these prefs momentarily until after the profile entry is edited in channel_form_submit_entry_end
			$this->EE->session->userdata['can_edit_other_entries'] = $this->EE->session->userdata['can_assign_post_authors'] = 'y';
		
			if ( ! $this->is_admin_register())
			{
				$this->EE->load->model('member_model');
		
				$query = $this->EE->member_model->get_member_groups(array('can_assign_post_authors', 'can_edit_other_entries'), array('group_id' => $data['group_id']));
		
				if ($query->num_rows() > 0)
				{
					$this->EE->session->cache['profile']['userdata']['can_assign_post_authors'] = $query->row('can_assign_post_authors');
					$this->EE->session->cache['profile']['userdata']['can_edit_other_entries'] = $query->row('can_edit_other_entries');
				}
				
				$query->free_result();
		
				$this->EE->session->userdata['group_id'] = $data['group_id'];
			}
			else
			{
				$this->EE->session->cache['profile']['userdata']['can_assign_post_authors'] = $this->EE->session->userdata('can_assign_post_authors');
				$this->EE->session->cache['profile']['userdata']['can_edit_other_entries'] = $this->EE->session->userdata('can_edit_other_entries');
			}
		
			$this->EE->session->cache['profile']['member_id'] = $member_id;
	
			$this->EE->session->cache['profile']['entry_id'] = $entry_id;
		}
	}
	
	public function cp_members_member_create($member_id, $data)
	{
		if ( ! $this->EE->profile_model->channel_id())
		{
			return;
		}
		
		$entry_id = $this->EE->profile_model->create_profile($data, $member_id);
		
		$this->EE->stats->update_member_stats();
		
		$this->EE->session->set_flashdata(array(
			'message_success' => lang('new_member_added').NBS.'<b>'.stripslashes($data['username']).'</b>',
		));
		
		$this->EE->functions->redirect(BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.$this->EE->profile_model->channel_id().AMP.'entry_id='.$entry_id);
	}
	
	//this is inactive and doesn't work--b/c by the time the hook is called, the entry's author has been changed to the "heir"
	public function cp_members_member_delete_end($member_ids = array())
	{
		if ( ! $this->EE->profile_model->settings('delete_profiles_when_deleting_members'))
		{
			return;
		}
		
		if (version_compare(APP_VER, '2.5', '<'))
		{
			$member_ids = (is_array($this->EE->input->post('delete'))) ? $this->EE->input->post('delete') : array();
		}
		
		$entry_ids = array();
		
		foreach ($member_ids as $member_id)
		{
			$entry_ids = array_merge($entry_ids, $this->EE->profile_model->get_all_profile_ids($member_id));
		}
		
		if ($entry_ids)
		{
			$this->EE->load->library('api');
			
			$this->EE->api->instantiate('channel_entries');
			
			$this->EE->api_channel_entries->delete_entry($entry_ids);
		}
	}
	
	//for legacy purposes
	public function cp_menu_array($menu)
	{
		return ($this->EE->extensions->last_call !== FALSE) ? $this->EE->extensions->last_call : $menu;
	}
	
	public function template_fetch_template($row)
	{
		static $once;
		
		if (is_null($once))
		{
			$member_data = $this->EE->profile_model->get_all_member_data($this->EE->session->userdata('member_id'));
			
			foreach ($this->EE->profile_model->get_native_fields() as $field_name)
			{
				$this->EE->config->_global_vars['profile:'.$field_name] = isset($member_data[$field_name]) ? $member_data[$field_name] : '';
			}
			
			foreach ($this->EE->profile_model->get_custom_fields() as $field)
			{
				$this->EE->config->_global_vars['profile:'.$field['field_name']] = isset($member_data[$field['field_name']]) ? $member_data[$field['field_name']] : '';;
			}
		}
		
		$once = TRUE;
		
		$tags = array();
		
		//var pair
		if (preg_match_all('/{profile:(.+?)([\s\t\r\n].*?)?}(.*?){\/profile:\\1}/ms', $row['template_data'], $matches))
		{
			foreach ($matches[0] as $i => $full_match)
			{
				$params = ($matches[2][$i]) ? $this->EE->functions->assign_parameters($matches[2][$i]) : array();
				
				$field_name = $matches[1][$i];
				
				$method = 'replace_tag';
				
				$tagdata = $matches[3][$i];
				
				if (strpos($field_name, ':') !== FALSE)
				{
					$parts = explode(':', $field_name);
					
					$field_name = array_shift($parts);
					
					$method = 'replace_'.array_shift($parts);
					
					unset($parts);
				}
				
				$key = substr($full_match, 1, -1);
				
				$tags[] = array(
					'key' => $key,
					'params' => $params,
					'field_name' => $field_name,
					'method' => $method,
					'tagdata' => $tagdata,
				);
			}
		}
		
		if (preg_match_all('/{profile:(.+?)([\s\t\r\n].*?)?}/ms', $row['template_data'], $matches))
		{
			foreach ($matches[0] as $i => $full_match)
			{
				$params = ($matches[2][$i]) ? $this->EE->functions->assign_parameters($matches[2][$i]) : array();
				
				$field_name = $matches[1][$i];
				
				$method = 'replace_tag';
				
				if (strpos($field_name, ':') !== FALSE)
				{
					$parts = explode(':', $field_name);
					
					$field_name = array_shift($parts);
					
					$method = 'replace_'.array_shift($parts);
					
					unset($parts);
				}
				
				$key = substr($full_match, 1, -1);
				
				$tags[] = array(
					'key' => $key,
					'params' => $params,
					'field_name' => $field_name,
					'method' => $method,
					'tagdata' => '',
				);
			}
		}
		
		foreach ($tags as $tag)
		{
			$this->EE->config->_global_vars[$tag['key']] = $this->EE->profile_model->parse_profile_field(
				$tag['field_name'],
				$this->EE->session->userdata('member_id'),
				$tag['tagdata'],
				$tag['params'],
				$tag['method']
			);
		}
	}
	
	public function publish_form_entry_data($row)
	{
		if ($this->EE->input->get('myaccount'))
		{
			$this->EE->lang->loadfile('profile', 'profile');
			
			$url = BASE.AMP.'C=myaccount'.AMP.'id='.$this->EE->input->get('myaccount');

			if ($this->EE->input->get('original_site_id'))
			{
				$url .= AMP.'original_site_id='.$this->EE->input->get('original_site_id');
			}

			$this->EE->cp->set_right_nav(array(
				'back_to_member_settings' => $url,
			));
		}
		
		return ($this->EE->extensions->last_call !== FALSE) ? $this->EE->extensions->last_call : $row;
	}
	
	private function is_admin_register()
	{
		return isset($this->EE->session->cache['profile']['admin_register']);
	}
	
	//set in register_action
	private function is_register()
	{
		return isset($this->EE->session->cache['profile']['register']);
	}
	
	//set in edit_action
	private function is_admin_edit()
	{
		return isset($this->EE->session->cache['profile']['admin_edit']);
	}
	
	//set in edit_action
	private function is_edit()
	{
		return isset($this->EE->session->cache['profile']['edit']);
	}
	
	private function is_profile()
	{
		return $this->is_register() || $this->is_edit();
	}
	
	private function check_referer($controller, $method = FALSE, $more = array())
	{
		if ($this->referer_get('C') !== $controller)
		{
			return FALSE;
		}
		
		if ($method && $this->referer_get('M') !== $method)
		{
			return FALSE;
		}
		
		foreach ($more as $key => $value)
		{
			if ($this->referer_get($key) !== $value)
			{
				return FALSE;
			}
		}
		
		return TRUE;
	}
	
	private function referer_get($key = FALSE)
	{
		static $get;
		
		//get $_GET from the referring page
		if (is_null($get))
		{
			parse_str(parse_url(@$_SERVER['HTTP_REFERER'], PHP_URL_QUERY), $get);
		}
		
		if ($key === FALSE)
		{
			return $get;
		}
		
		$this->EE->load->helper('array');
		
		return element($key, $get);
	}
	
	public function entry_submission_redirect($entry_id, $meta, $data, $cp_call, $redirect)
	{
		if ($this->EE->extensions->last_call !== FALSE)
		{
			$redirect = $this->EE->extensions->last_call;
		}
		
		if ($cp_call && $channel_id = $this->EE->profile_model->channel_id())
		{
			if ($channel_id == $data['channel_id'])
			{
				if ($this->referer_get('myaccount'))
				{
					$redirect = BASE.AMP.'C=myaccount'.AMP.'id='.$this->referer_get('myaccount');
				}
				else
				{
					$redirect = BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.$data['channel_id'].AMP.'entry_id='.$entry_id;
					
					if ($this->referer_get('filter'))
					{
						$redirect .= AMP.'filter='.$this->referer_get('filter');
					}
				}

				if ($this->referer_get('original_site_id'))
				{
					$redirect .= AMP.'original_site_id='.$this->referer_get('original_site_id');
				}
			}
		}
		
		return $redirect;
	}
	
	public function cp_js_end()
	{
		$str = $this->EE->extensions->last_call;
		
		if ($this->check_referer('content_publish', 'entry_form') && $channel_id = $this->EE->profile_model->channel_id())
		{
			if ($channel_id == $this->referer_get('channel_id'))
			{
				$this->EE->lang->loadfile('profile', 'profile');
				
				$str .= '
					$(function(){
						var edited = false
						$(".rightNav a:first").click(function(){
							return (edited) ? confirm("'.lang('unsaved_changes').'") : true;
						});
						$("#mainContent").find(":input").one("change", function(){
							edited = true;
						});
					});
				';
			}
		}
		
		return $str;
	}
	
	public function myaccount_nav_setup()
	{
		if ($this->EE->input->get('original_site_id'))
		{
			$this->EE->config->site_prefs('', $this->EE->input->get('original_site_id'));

			$this->EE->functions->set_cookie('cp_last_site_id', $this->EE->input->get('original_site_id'), 0);

			$this->EE->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'id='.$this->EE->input->get('id'));
		}

		$additional_nav = $this->EE->extensions->last_call !== FALSE ? $this->EE->extensions->last_call : array();
		
		$additional_nav['personal_settings'] = array(
			lang('edit_profile_entry') => array(
				'extension' => 'profile',
				'method' => 'edit_profile_entry',
			),
		);
		
		return $additional_nav;
	}
	
	public function edit_profile_entry()
	{
		if ($channel_id = $this->EE->profile_model->channel_id())
		{
			if ( ! $member_id = $this->EE->input->get('id'))
			{
				$member_id = $this->EE->session->userdata('member_id');
			}
			
			if ($entry_id = $this->EE->profile_model->get_profile_id($member_id))
			{
				$redirect = BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.$channel_id.AMP.'entry_id='.$entry_id.AMP.'myaccount='.$member_id;

				if ($this->EE->config->item('site_id') != $this->EE->profile_model->site_id())
				{
					$redirect .= AMP.'original_site_id='.$this->EE->config->item('site_id');

					$this->EE->config->site_prefs('', $this->EE->profile_model->site_id());

					$this->EE->functions->set_cookie('cp_last_site_id', $this->EE->profile_model->site_id(), 0);
				}

				$this->EE->functions->redirect($redirect);
			}
		}
		
		$this->EE->lang->loadfile('profile', 'profile');
		
		return sprintf(lang('no_channel_cp'), BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=profile');
	}

    public function cartthrob_addon_register($valid_addons)
    {
        if ($this->EE->extensions->last_call !== FALSE)
        {
            $valid_addons = $this->EE->extensions->last_call;
        }

        $addon_name = "profile";

        $valid_addons[] = $addon_name;

        return $valid_addons;
    }
}

/* End of file ext.profile.php */
/* Location: ./system/expressionengine/third_party/profile/ext.profile.php */