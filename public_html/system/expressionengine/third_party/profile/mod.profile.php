<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Profile
{
	/**
	 * Profile
	 *
	 * @access	public
	 * @return	void
	 */
	public function __construct()
	{
		$this->EE =& get_instance();

		$this->EE->load->model('profile_model');
		
		$this->EE->lang->loadfile('profile', 'profile');
		
		$this->EE->load->library('typography');
		
		$this->EE->load->library('encrypt');
		
		if ( version_compare(APP_VER, '2.7', '>='))
		{
			// checking to see if the channel_form_lib already exists before loading it.
			// If you already have a channel:form on the page it'll throw errors if you try to load the library
			if (!class_exists('channel_form_lib')) 
		    {
            	$this->EE->load->add_package_path(PATH_MOD.'channel');

            	$this->EE->load->library('channel_form/channel_form_lib');
        	}
		}
		else
		{
			$this->EE->load->add_package_path(PATH_MOD.'safecracker/');

			$this->EE->load->library('safecracker_lib', NULL, 'channel_form');
		}

		$this->EE->channel_form->profile =& $this;
	}
	
	/**
	 * A list of the current member's profiles
	 * 
	 * @return string
	 */
	public function profiles()
	{
		if ( ! $this->EE->profile_model->channel_id() || ! $this->EE->profile_model->channel_name())
		{
			return  show_error(lang('no_channel'));
		}
		
		if ($this->EE->TMPL->fetch_param('username'))
		{
			$member_id = $this->EE->profile_model->get_member_id_by_username($this->EE->TMPL->fetch_param('username'));
		}
		else if ($this->EE->TMPL->fetch_param('member_id'))
		{
			$member_id = $this->EE->TMPL->fetch_param('member_id');
		}
		else
		{
			$member_id = $this->EE->session->userdata('member_id');
		}
		
		if ( ! $member_id || ! $entry_ids = $this->EE->profile_model->get_all_profile_ids($member_id))
		{
			return $this->EE->TMPL->no_results();
		}
		
		if ($this->EE->profile_model->site_id() != $this->EE->config->item('site_id'))
		{
			$this->EE->TMPL->site_ids = array($this->EE->profile_model->site_id());
		}
		
		return $this->EE->profile_model->channel_entries(array(
			'dynamic' => 'no',
			'channel' => $this->EE->profile_model->channel_name(),
			'entry_id' => implode('|', $entry_ids),
		));
	}
	
	public function __call($name, $args)
	{
		$member_id = $this->EE->TMPL->fetch_param('member_id', $this->EE->session->userdata('member_id'));
		
		$method = isset($this->EE->TMPL->tagparts[2]) ? 'replace_'.$this->EE->TMPL->tagparts[2] : 'replace_tag';
		
		return $this->EE->profile_model->parse_profile_field($name, $member_id, $this->EE->TMPL->tagdata, $this->EE->TMPL->tagparams, $method);
	}
	
	public function login()
	{
		$this->EE->load->library('form_builder');
		
		$variables = array_merge(
			$this->EE->profile_model->member_vars(),
			$this->EE->form_builder->form_variables()
		);

		$variables['auto_login'] = ($this->EE->config->item('user_session_type') === 'c') ? 1 : 0;
		
		$this->EE->form_builder->initialize(array(
			'classname' => 'Profile',
			'method' => 'login_action',
			'params' => $this->EE->TMPL->tagparams,
			'content' => $this->EE->TMPL->parse_variables($this->EE->TMPL->tagdata, array($variables)),
		));

		return $this->EE->form_builder->form();
	}
	
	public function logout_link()
	{
		return $this->EE->profile_model->build_action_url('logout');
	}
	
	public function logout_action()
	{
		$this->EE->load->library('form_builder');
		
		$this->EE->profile_model->member_action('member_logout');
		
		$this->EE->functions->redirect($this->EE->functions->create_url($this->EE->input->get_post('return')));
	}
	
	public function login_action()
	{
		$this->EE->load->library('form_builder');
		
		if ($this->EE->extensions->active_hook('profile_login_start') === TRUE)
		{
			$this->EE->extensions->call('profile_login_start');
		}
		
		if ( ! $this->EE->profile_model->errors())
		{
			$this->EE->profile_model->member_action('member_login');
		}
		
		if ($this->EE->extensions->active_hook('profile_login_end') === TRUE)
		{
			$this->EE->extensions->call('profile_login_end');
		}
		
		$this->EE->form_builder->add_error($this->EE->profile_model->errors());
		
		foreach ($this->EE->profile_model->member_vars() as $key => $value)
		{
			if (FALSE !== ($value = $this->EE->input->post($key)))
			{
				$this->EE->profile_model->set_member_var($key, $this->EE->security->xss_clean($value));
			}
		}
		
		return $this->EE->form_builder->action_complete();
	}
	
	public function forgot_password()
	{
		$this->EE->load->library('form_builder');

		$variables = array_merge(
			$this->EE->profile_model->member_vars(),
			$this->EE->form_builder->form_variables()
		);
		
		$this->EE->form_builder->initialize(array(
			'classname' => 'Profile',
			'method' => 'forgot_password_action',
			'form_data' => array(
				'forgot_password_return',
			),
			'params' => $this->EE->TMPL->tagparams,
			'content' => $this->EE->TMPL->parse_variables($this->EE->TMPL->tagdata, array($variables)),
		));

		return $this->EE->form_builder->form();
	}
	
	public function forgot_password_action()
	{
		$this->EE->load->library('form_builder');
		
		$this->EE->load->helper(array('string', 'email'));
		
		$this->EE->load->model('member_model');
		
		$this->EE->lang->loadfile('member');
		
		if ($this->EE->session->userdata('is_banned'))
		{
			return $this->EE->form_builder->add_error(lang('not_authorized'))
					       ->action_complete();
		}
		
		$email = $this->EE->input->post('email');
		
		if ( ! valid_email($email))
		{
			return $this->EE->form_builder->add_error('email', lang('invalid_email_address'))
					       ->action_complete();
		}
		
		$query = $this->EE->member_model->get_member_emails(array(), array('email' => $email));
		
		if ($query->num_rows() === 0)
		{
			return $this->EE->form_builder->add_error(lang('no_email_found'))
					       ->action_complete();
		}
		
		$member_data = $query->row_array();
		
		$resetcode = random_string();
		
		$query->free_result();
		
		$this->EE->db->delete('reset_password', array('member_id' => $member_data['member_id']));
		
		$this->EE->db->delete('reset_password', array('date <' => $this->EE->localize->now - (60*60*24)));
		
		$insert = $this->EE->db->insert('reset_password', array(
			'resetcode' => $resetcode,
			'date' => $this->EE->localize->now,
			'member_id' => $member_data['member_id'],
		));
		
		$variables = $member_data;
		
		$variables['name'] = $member_data['screen_name'];
		
		$variables['reset_url'] = $this->EE->profile_model->build_action_url('reset_password', array(
			'resetcode' => $resetcode,
			'return' => $this->EE->input->post('forgot_password_return'),
		));
		
		if ( ! $this->EE->profile_model->send_email($email, 'forgot_password_instructions', $variables))
		{
			return $this->EE->form_builder->add_error(lang('error_sending_email'))
					       ->action_complete();
		}
		
		foreach ($this->EE->profile_model->member_vars() as $key => $value)
		{
			if (FALSE !== ($value = $this->EE->input->post($key)))
			{
				$this->EE->profile_model->set_member_var($key, $this->EE->security->xss_clean($value));
			}
		}
		
		return $this->EE->form_builder->action_complete();
	}
	public function profile_id()
	{
		return (int) ($this->EE->profile_model->get_profile_id($this->EE->session->userdata('member_id')));
	}
	public function is_active_profile()
	{
		if ( ! $this->EE->TMPL->fetch_param('entry_id'))
		{
			return 0;
		}
		
		return (int) ($this->EE->profile_model->get_profile_id($this->EE->session->userdata('member_id')) == $this->EE->TMPL->fetch_param('entry_id'));
	}
	
	/**
	 * Get a link to the action which sets the active profile
	 * 
	 * @return Type    Description
	 */
	public function set_active_profile_link()
	{
		if ( ! $this->EE->TMPL->fetch_param('entry_id'))
		{
			return '';
		}
		
		return $this->EE->profile_model->build_action_url('set_active_profile', array('entry_id' => $this->EE->TMPL->fetch_param('entry_id')));
	}
	
	public function set_active_profile_action()
	{
		$this->EE->load->library('form_builder');
		
		$this->EE->profile_model->set_active_profile($this->EE->input->get_post('entry_id'));
		
		$this->EE->functions->redirect($this->EE->functions->create_url($this->EE->input->get_post('return')));
	}
	
	public function member_group_select($params = NULL, $tagdata = '')
	{
		if (func_num_args() === 0)
		{
			$params = $this->EE->TMPL->tagparams;
			$tagdata = $this->EE->TMPL->tagdata;
		}

		$this->EE->load->helper('form');
 
		if ( ! empty($params['id']))
		{
			$attrs['id'] = $params['id'];
		}

		if ( ! empty($params['class']))
		{
			$attrs['class'] = $params['class'];
		}

		if ( ! empty($params['onchange']))
		{
			$attrs['onchange'] = $params['onchange'];
		}

		$extra = '';

		if (isset($attrs))
		{
			$extra .= _attributes_to_string($attrs);
		}

		if ( ! empty($params['extra']))
		{
			if (strncmp(' ', $params['extra'], 1) !== 0)
			{
				$extra .= ' ';
			}

			$extra .= $params['extra'];
		}
		
		$name = 'GID'; 
		
		$selected = isset($params['selected']) ? $params['selected'] : $this->EE->config->item('default_member_group');
		
		if ( ! empty($params['member_id']))
		{
			$member_data = $this->EE->profile_model->get_member_data($params['member_id']);

			if (isset($member_data['group_id']))
			{
				$selected = $member_data['group_id'];
			}
		}

		$site_id = $this->EE->profile_model->site_id();

		if ( ! empty($params['member_groups']))
		{
			$this->EE->db->where_in('group_id', explode('|', $params['member_groups']));
		}

		if ($this->EE->session->userdata('group_id') != 1)
		{
			$this->EE->db->where('is_locked', 'n');
		}
		
		$query = $this->EE->db->select('group_title, group_id, group_description')
						->where('group_id >', 4)
						->where('site_id', $site_id)
						->order_by('group_id', 'asc')
						->get('member_groups');
		
		$member_groups = array();
		
		foreach ($query->result_array() as $row)
		{
			$member_groups[$row['group_id']] = $row;
		}
		
		$query->free_result();

		if (!$member_groups)
		{
			$default_member_group = $this->EE->config->item('default_member_group');
			
			$query = $this->EE->db->select('group_title, group_id, group_description')
							->where('group_id', $default_member_group)
							->where('site_id', $site_id)
							->order_by('group_id', 'asc')
							->get('member_groups');
		
			foreach ($query->result_array() as $row)
			{
				$member_groups[$row['group_id']] = $row;
			}

			$query->free_result();
		}
		
		if ($tagdata)
		{
			$variables = array();

			if ( ! empty($params['add_blank']))
			{
				$variables[] = array(
					'option_value' => '',
					'option_name' => $params['add_blank'],
					'selected' => '',
					'group_title' => '',
					'group_id' => '',
					'group_description' => '',
				);
			}
			
			foreach ($member_groups as $group_id => $row)
			{
				$variables[] = array_merge(
					$row,
					array(
						'option_value' => $this->EE->encrypt->encode($group_id),
						'option_name' => $row['group_title'],
						'selected' => $group_id == $selected ? '1' : 0,
					)
				);
			}

			if ( ! $variables)
			{
				$variables[] = array();
			}
			
			return '<select name="'.$name.'"'.$extra.'>'.$this->EE->TMPL->parse_variables($tagdata, $variables).'</select>';
		}
		
		$options = empty($params['add_blank']) ? array() : array('' => $params['add_blank']);

		foreach ($member_groups as $group_id => $row)
		{
			$options[$this->EE->encrypt->encode($group_id)] = $row['group_title'];
		}
		
 		return form_dropdown(
			$name, 
			$options,
			$selected,
			$extra
		);
	}

	public function view()
	{
		if ( ! $this->EE->profile_model->channel_id() || ! $this->EE->profile_model->channel_name())
		{
			return  show_error(lang('no_channel'));
		}
		
		if ($this->EE->profile_model->site_id() != $this->EE->config->item('site_id'))
		{
			$this->EE->TMPL->site_ids = array($this->EE->profile_model->site_id());
		}
		
		$entry_id = FALSE;
		
		$url_title = FALSE;
		
		$member_id = FALSE;
		
		if ($this->EE->TMPL->fetch_param('entry_id'))
		{
			$entry_id = $this->EE->TMPL->fetch_param('entry_id');
			
			$member_id = $this->EE->profile_model->get_member_id_by_entry_id($entry_id);
		}
		else if ($this->EE->TMPL->fetch_param('url_title'))
		{
			$url_title = $this->EE->TMPL->fetch_param('url_title');
			
			$member_id = $this->EE->profile_model->get_member_id_by_url_title($url_title);
		}
		else if ($this->EE->TMPL->fetch_param('username'))
		{
			$member_id = $this->EE->profile_model->get_member_id_by_username($this->EE->TMPL->fetch_param('username'));
		}
		else if ($this->EE->TMPL->fetch_param('member_id'))
		{
			$member_id = $this->EE->TMPL->fetch_param('member_id');
		}
		else
		{
			$member_id = $this->EE->session->userdata('member_id');
		}
		
		if ( ! $entry_id && ! $url_title)
		{
			if ( ! $member_id)
			{
				return $this->EE->TMPL->no_results();
			}
			
			if ( ! $entry_id = $this->EE->profile_model->get_profile_id($member_id))
			{
				return $this->EE->TMPL->no_results();
			}
		}
		
		if ($member_id)
		{
			if ($member_data = $this->EE->profile_model->get_member_data($member_id))
			{
				$this->EE->TMPL->tagdata = $this->EE->TMPL->parse_variables_row($this->EE->TMPL->tagdata, $member_data);
			}
		}
		
		//@TODO missing from custom_profile_data
		/*
  array (
  0 => 'birthday',
  1 => 'local_time',
  2 => 'member_group',
  3 => 'search_path',
  4 => 'send_private_message',
)
		*/
		
		$params = array(
			'dynamic' => 'no',
			'channel' => $this->EE->profile_model->channel_name(),
			'limit' => '1',
		);
		
		if ($url_title)
		{
			$params['url_title'] = $url_title;
		}
		else
		{
			$params['entry_id'] = $entry_id;
		}
		$data = $this->EE->profile_model->channel_entries($params);
		
		$this->EE->load->model('member_model'); 
		$member_data = $this->EE->member_model->get_member_data($member_id); 
		if ($member_data->result() && $member_data->num_rows() > 0)
		{
			$data = $this->EE->TMPL->parse_variables_row($data, $member_data->row_array() );
 
		}
		return $data;
	}

	public function edit()
	{
		if ( ! $this->EE->profile_model->channel_id())
		{
			return  show_error(lang('no_channel'));
		}
		
		$entry_id = FALSE;
		$member_id = FALSE; 
		$url_title = FALSE;
		
		if ($this->EE->TMPL->fetch_param('entry_id'))
		{
			$entry_id = $this->EE->TMPL->fetch_param('entry_id');
		}
		else if ($this->EE->TMPL->fetch_param('url_title'))
		{
			$url_title = $this->EE->TMPL->fetch_param('url_title');
		}
		else if ($this->EE->TMPL->fetch_param('username'))
		{
			$member_id = $this->EE->profile_model->get_member_id_by_username($this->EE->TMPL->fetch_param('username'));
		}
		else if ($this->EE->TMPL->fetch_param('member_id'))
		{
			$member_id = $this->EE->TMPL->fetch_param('member_id');
		}
		else
		{
			$member_id = $this->EE->session->userdata('member_id');
		}
		
		if ($entry_id && ! $this->EE->profile_model->validate_entry_id($entry_id, $member_id))
		{
			$entry_id = FALSE;
		}
		else if ($url_title && ! $this->EE->profile_model->validate_url_title($url_title, $member_id))
		{
			$url_title = FALSE;
		}
		
		if ( ! $entry_id && ! $url_title)
		{
			if ( ! $member_id)
			{
				return $this->EE->TMPL->no_results();
			}
			
			if ( ! $this->EE->channel_form->bool_string($this->EE->TMPL->fetch_param('new')))
			{
				if ($entry_id = $this->EE->profile_model->get_profile_id($member_id))
				{
					$this->EE->TMPL->tagparams['entry_id'] = $entry_id;
				}
				else
				{
					return $this->EE->TMPL->no_results();
				}
			}
		}
		
		$this->EE->TMPL->tagparams['channel'] = $this->EE->profile_model->channel_name();
		
		$this->EE->session->cache['profile']['form_hidden'] = array(
			'ACT' => $this->EE->functions->fetch_action_id('Profile', 'edit_action'),
			'member_id' => $member_id,
		);
		
		$group_id = $this->EE->TMPL->fetch_param('group_id');
		
		if ($group_id && $this->EE->profile_model->validate_group_id($group_id))
		{
			$this->EE->session->cache['profile']['form_hidden']['GID'] = $this->EE->encrypt->encode($group_id);
		}
		
		if ($this->EE->TMPL->fetch_param('dynamic_screen_name'))
		{
			$this->EE->session->cache['profile']['form_hidden']['dynamic_screen_name'] = base64_encode($this->EE->TMPL->fetch_param('dynamic_screen_name'));
		}
		
		if ($this->EE->profile_model->site_id() != $this->EE->config->item('site_id'))
		{
			$this->EE->TMPL->site_ids = array($this->EE->profile_model->site_id());
			
			$this->EE->TMPL->tagparams['site'] = $this->EE->profile_model->site_name();
		}
		
		if ( ! $this->EE->TMPL->fetch_param('return'))
		{
			$this->EE->TMPL->tagparams['return'] = $this->EE->uri->uri_string();
		}
		
		if ($this->EE->channel_form->errors || $this->EE->channel_form->field_errors || $this->EE->profile_model->errors())
		{
			$member_vars = $this->EE->profile_model->member_vars();
			
			//we only want to parse those vars which have been posted
			foreach ($member_vars as $key => $value)
			{
				if ( ! isset($_POST[$key]))
				{
					unset($member_vars[$key]);
				}
			}
			
			$this->EE->TMPL->tagdata = $this->EE->TMPL->parse_variables_row($this->EE->TMPL->tagdata, $member_vars);
		}

		foreach ($this->EE->TMPL->var_single as $var)
		{
			if (strncmp($var, 'field:member_group_select', 25) === 0)
			{
				$this->EE->TMPL->tagdata = str_replace(
					LD.$var.RD,
					$this->EE->channel_form->profile->member_group_select(
						array_merge(
							array('member_id' => $member_id),
							$this->EE->functions->assign_parameters(substr($var, 25))
						)
					),
					$this->EE->TMPL->tagdata
				);
			}
		}
		
		//tell channel_form that this is an edit form, not a register form
		$this->EE->session->cache['profile']['edit'] = TRUE;
		
		return $this->EE->channel_form->entry_form();
	}
	
	public function edit_action($update_member_settings = TRUE)
	{
		$this->EE->session->cache['profile']['submit_entry'] = TRUE;
		
		$member_id = $this->EE->input->post('member_id');
		
		if ( ! $member_id)
		{
			$this->EE->functions->redirect($this->EE->input->post('RET'));
		}
		
		if ($this->EE->extensions->active_hook('profile_edit_start') === TRUE)
		{
			$this->EE->extensions->call('profile_edit_start', $member_id, isset($this->EE->session->cache['profile']['admin_edit']));
		}
		
		$member_data = $this->EE->profile_model->get_member_data($member_id);
		
		if (isset($_POST['username']) && $_POST['username'] !== $member_data['username'])
		{
			if (isset($_POST['screen_name']) && $_POST['screen_name'] === '')
			{
				$_POST['screen_name'] = $_POST['username'];
			}
			else if ( ! isset($_POST['screen_name']) && $member_data['username'] === $member_data['screen_name'])
			{
				$_POST['screen_name'] = $_POST['username'];
			}
		}
		
		if ($this->EE->input->post('dynamic_screen_name'))
		{
			$_POST['screen_name'] = base64_decode($this->EE->input->post('dynamic_screen_name'));
			
			foreach ($_POST as $key => $value)
			{
				if (is_string($value) && strpos($_POST['screen_name'], '['.$key.']') !== FALSE)
				{
					$_POST['screen_name'] = str_replace('['.$key.']', $value, $_POST['screen_name']);
				}
			}
		}
		
		if ($this->EE->profile_model->settings('use_email_as_username'))
		{
			if (isset($_POST['email']) && ! isset($_POST['username']))
			{
				$_POST['username'] = $_POST['email'];
			}
			else if ( ! isset($_POST['email']) && isset($_POST['username']))
			{
				$_POST['email'] = $_POST['username'];
			}
		}
		
		if (isset($_POST['email']) && ! $this->EE->profile_model->settings('require_email_confirm'))
		{
			$_POST['email_confirm'] = $_POST['email'];
		}
		
		if (isset($_POST['password']) && ! $this->EE->profile_model->settings('require_password_confirm'))
		{
			$_POST['password_confirm'] = $_POST['password'];
		}
		
		$this->EE->session->cache['profile']['edit'] = TRUE;
		
		$this->EE->session->cache['profile']['member_id'] = $member_id;
		
		if ($member_id != $this->EE->session->userdata('member_id'))
		{
			if ( in_array($this->EE->session->userdata('group_id'), $this->EE->profile_model->settings('can_admin_members')) ||  $this->EE->session->userdata('can_admin_members') === 'y')
			{
				// admins
				$this->EE->session->cache['profile']['override_member_id'] = $member_id;
				// ok.... teh way the channel forms API works, we HAVE to let EE use the original member id when creating / editing. Then we'll have to change it later in the extension. 
				$_POST['author_id'] = $this->EE->session->userdata('member_id');
 				$this->EE->session->cache['profile']['admin_edit'] = TRUE;
			}
			elseif ($this->EE->session->userdata('group_id') == 1)
			{
				// superadmins
				$this->EE->session->cache['profile']['admin_edit'] = TRUE;
			}
		}
 
		
		$this->EE->session->cache['profile']['update_member'] = $this->EE->session->cache['profile']['userdata'] = $this->EE->profile_model->validate_member_data($member_id, $_POST);
		
		// existing entry
		if ($this->EE->input->post('entry_id'))
		{
			if ($this->EE->profile_model->settings('auto_title_screen_name') && $this->EE->input->post('screen_name') !== FALSE && $this->EE->input->post('screen_name') != $member_data['screen_name'])
			{
				$_POST['title'] = $this->EE->input->post('screen_name');
			}

			if ($this->EE->profile_model->settings('auto_url_title_username') && $this->EE->input->post('username') !== FALSE && $this->EE->input->post('username') != $member_data['username'])
			{
				$_POST['url_title'] = $this->EE->input->post('username');
			}
		}
		// new entry... there's no title already set
		else
		{
			// if this setting is on, and there's no title, we'll just use the existing screen name. 
			if ($this->EE->profile_model->settings('auto_title_screen_name') && ! $this->EE->input->post('title'))
			{
				$_POST['title'] = $member_data['screen_name'];
			}
			// if this setting is on, and there's no title, we'll just use the existing username. 
			if ($this->EE->profile_model->settings('auto_url_title_username') && ! $this->EE->input->post('url_title') )
			{
				$_POST['url_title'] = $member_data['username'];
			}
		}
		
		foreach ($this->EE->profile_model->member_vars() as $key => $value)
		{
			if (FALSE !== ($value = $this->EE->input->post($key)))
			{
				$this->EE->profile_model->set_member_var($key, $this->EE->security->xss_clean($value));
			}
		}
		
		$_POST['json'] = $this->EE->input->is_ajax_request();
		
		$return_data = NULL; 
		
		
		
		if ( version_compare(APP_VER, '2.7', '>='))
		{
			try
			{
				$return_data = $this->EE->channel_form->submit_entry();
			}
			catch (Channel_form_exception $e)
			{
				return $e->show_user_error();
			}
			
			if ($override_member_id)
			{
				$this->EE->profile_model->change_author_id($member_id, $entry_id); 
			}
 		}
		else
		{
			$return_data =  $this->EE->channel_form->submit_entry();
		}
		
		if ($return_data)
		{
			return $return_data;
		}
	}

	public function register()
	{
		if ( ! $this->EE->profile_model->channel_id())
		{
			return  show_error(lang('no_channel'));
		}

		$this->EE->TMPL->tagparams['channel'] = $this->EE->profile_model->channel_name();
		
		$this->EE->TMPL->tagparams['logged_out_member_id'] = $this->EE->profile_model->oldest_superadmin();
		
		$this->EE->TMPL->tagdata = $this->EE->TMPL->parse_variables_row($this->EE->TMPL->tagdata, $this->EE->profile_model->member_vars());
		
		$this->EE->session->cache['profile']['register'] = TRUE;
		
		/*
		if (preg_match_all('#{if captcha}(.*?){/if}#s', $this->EE->TMPL->tagdata, $matches))
		{
			foreach ($matches[0] as $i => $full_match)
			{
				if ($this->EE->config->item('use_membership_captcha') === 'y' && ! $this->EE->session->userdata('member_id'))
				{
					$tagdata = $this->EE->TMPL->parse_variables_row($matches[1][$i], array(
						'captcha_word' => '',
						'captcha' => $this->EE->functions->create_captcha(),
					));
					
					$tagdata = $this->EE->TMPL->swap_var_single('captcha', $this->EE->functions->create_captcha(), $tagdata);
					
					$this->EE->TMPL->tagdata = str_replace($full_match, $tagdata, $this->EE->TMPL->tagdata);
				}
				else
				{
					$this->EE->TMPL->tagdata = str_replace($full_match, '', $this->EE->TMPL->tagdata);
				}
			}
		}
		*/ 
 		$cond['captcha'] = $this->EE->functions->create_captcha(); 

		if ($this->EE->config->item('use_membership_captcha') === 'y' && ! $this->EE->session->userdata('member_id') && $cond['captcha'] )
		{
			$this->EE->TMPL->tagdata = $this->EE->functions->prep_conditionals( $this->EE->TMPL->tagdata, array("captcha"=> TRUE));
			$this->EE->TMPL->tagdata = $this->EE->TMPL->swap_var_single('captcha', $cond['captcha'], $this->EE->TMPL->tagdata);
			$this->EE->TMPL->tagdata = $this->EE->TMPL->swap_var_single('captcha_word', '', $this->EE->TMPL->tagdata);
		}
		else
		{
			$this->EE->TMPL->tagdata = $this->EE->functions->prep_conditionals( $this->EE->TMPL->tagdata, array("captcha"=> FALSE));
			$this->EE->TMPL->tagdata = $this->EE->TMPL->swap_var_single('captcha', '', $this->EE->TMPL->tagdata);
			$this->EE->TMPL->tagdata = $this->EE->TMPL->swap_var_single('captcha_word', '', $this->EE->TMPL->tagdata);
		}
 		
		$this->EE->session->cache['profile']['form_hidden'] = array(
			'ACT' => $this->EE->functions->fetch_action_id('Profile', 'register_action'),
		);
		
		$group_id = $this->EE->TMPL->fetch_param('group_id');
		
		if ($this->EE->profile_model->validate_group_id($group_id))
		{
			$this->EE->session->cache['profile']['form_hidden']['GID'] = $this->EE->encrypt->encode($group_id);
		}
		
		if ($this->EE->TMPL->fetch_param('dynamic_screen_name'))
		{
			$this->EE->session->cache['profile']['form_hidden']['dynamic_screen_name'] = base64_encode($this->EE->TMPL->fetch_param('dynamic_screen_name'));
		}
		
		if ($this->EE->profile_model->site_id() != $this->EE->config->item('site_id'))
		{
			$this->EE->TMPL->site_ids = array($this->EE->profile_model->site_id());
			
			$this->EE->TMPL->tagparams['site'] = $this->EE->profile_model->site_name();
		}
		
		if ( ! $this->EE->TMPL->fetch_param('return'))
		{
			$this->EE->TMPL->tagparams['return'] = $this->EE->uri->uri_string();
		}
		
		if ($this->EE->TMPL->fetch_param('activation_return'))
		{
			$this->EE->session->cache['profile']['form_hidden']['activation_return'] = $this->EE->TMPL->fetch_param('activation_return');
		}
		
		
		if ($this->EE->profile_model->errors())
		{
			$this->EE->channel_form->form_error = $this->EE->profile_model->errors(); 
 			foreach ($this->EE->profile_model->errors() as $key => $value)
			{
 				$this->EE->channel_form->field_errors[$key] = $value; 
			}
		}
		
		return $this->EE->channel_form->entry_form();
	}
	
	public function register_action()
	{
		$this->EE->session->cache['profile']['submit_entry'] = TRUE;
		
		if ($this->EE->extensions->active_hook('profile_register_start') === TRUE)
		{
			$admin_register = (bool) $this->EE->session->userdata('member_id');
			
			$this->EE->extensions->call('profile_register_start', $admin_register);
		}
		
		//log them in if applicable
		$this->EE->load->library('auth');
		
		$username = $this->EE->input->post('username', TRUE);
		
		$password = $this->EE->input->post('password', TRUE);
		
		if ($this->EE->profile_model->settings('use_email_as_username'))
		{
			if (isset($_POST['email']) && ! isset($_POST['username']))
			{
				$_POST['username'] = $username = $_POST['email'];
			}
			else if ( ! isset($_POST['email']))
			{
				$_POST['email'] = $username;
			}
		}
		
		if ($this->EE->input->post('dynamic_screen_name'))
		{
			$screen_name = base64_decode($this->EE->input->post('dynamic_screen_name'));
			
			foreach ($_POST as $key => $value)
			{
				if (is_string($value) && strpos($screen_name, '['.$key.']') !== FALSE)
				{
					$screen_name = str_replace('['.$key.']', $value, $screen_name);
				}
			}
			
			if (strpos($screen_name, '[member_id]') !== FALSE)
			{
				// getting the highest member id. we'll add one to it. '
				$query = $this->EE->db->query('SELECT MAX(member_id) AS `member_id` FROM '.$this->EE->db->dbprefix('members'));

				$max_id = NULL; 
				if ($query->num_rows() > 0)
				{
					$max_id = $query->row('member_id');
					if ($max_id)
					{
						$max_id += 1; 
					}
				}

				$query->free_result();
				$screen_name = str_replace('[member_id]', $max_id, $screen_name); 
			}
			
			$_POST['screen_name'] = $screen_name;
		}
		
		//if they're already logged in and they don't have member admin privileges
		
		$admin_groups = (array) $this->EE->profile_model->settings('can_admin_members'); 
		
		if ($this->EE->session->userdata('member_id') && ( ! in_array($this->EE->session->userdata('group_id'),$admin_groups)  && $this->EE->session->userdata('can_admin_members') !== 'y'))
		{
			$this->EE->profile_model->add_error(lang('logged_in_no_register_privilege'));
		}
		else
		{
			//login the member if they already exist
			if ($username && $this->EE->db->where('username', $username)->count_all_results('members'))
			{
				if ($sess = $this->EE->auth->authenticate_username($username, $password))
				{
					if ($sess->is_banned())
					{
						return  show_error(lang('not_authorized'));
					}
					
					$sess->start_session();
	
					if ( ! isset($_POST['title']))
					{
						$_POST['title'] = $this->EE->session->userdata('screen_name');
					}
					
					if ( ! isset($_POST['url_title']))
					{
						$_POST['url_title'] = $this->EE->session->userdata('username');
					}
					
					$_POST['entry_id'] = $this->EE->profile_model->get_profile_id($this->EE->session->userdata('member_id'));
					// updating author_id for channel_form use
					$this->set_author_and_entry($this->EE->session->userdata('member_id')); 
					
					
					$return_data = NULL; 

					if ( version_compare(APP_VER, '2.7', '>='))
					{
						try
						{
							$return_data = $this->EE->channel_form->submit_entry();
						}
						catch (Channel_form_exception $e)
						{
							return $e->show_user_error();
						}
						return $return_data;
					}
					else
					{
						return $this->EE->channel_form->submit_entry();
					}
				}
			}
		}
		
		if ($this->EE->profile_model->settings('use_email_as_username'))
		{
			$username = $this->EE->input->post('email');
		}
		
		if (strlen($username) > 50)
		{
			$this->EE->profile_model->add_error('username', lang('username_too_long'));
		}
		
		if (strlen($password) > 40)
		{
			$this->EE->profile_model->add_error('password', lang('password_too_long'));
		}
		
		//we set this cache variable so that the member_member_register hook knows to use
		//the oldest superadmin as the temporary author_id of the entry, to get around
		//api_channel_entries restrictions
		if ($this->EE->config->item('req_mbr_activation') !== 'none')
		{
			$this->EE->session->cache['profile']['oldest_superadmin'] = TRUE;
		}
		
		$this->EE->session->cache['profile']['register'] = TRUE;
		
		//turn off captcha if already logged in
		$use_membership_captcha = $this->EE->config->item('use_membership_captcha');
		
		$can_set_member_group = TRUE;
		
		if ($this->EE->session->userdata('member_id'))
		{
			//set this so channel_form_submit_entry_end knows not to log you out if failed
			$this->EE->session->cache['profile']['admin_register'] = TRUE;
			
			$this->EE->config->set_item('use_membership_captcha', 'n');
			
			$can_set_member_group = $this->EE->session->userdata('can_admin_mbr_groups') === 'y';
		}
		
		$default_member_group = $this->EE->config->item('default_member_group');
		
		if ($this->EE->config->item('req_mbr_activation') === 'none' && $can_set_member_group && $this->EE->input->post('GID'))
		{
			$this->EE->load->helper('security');
			
			$group_id = xss_clean($this->EE->encrypt->decode($this->EE->input->post('GID')));
			
			//validate group_id
			if ($this->EE->profile_model->validate_group_id($group_id))
			{
				$this->EE->config->set_item('default_member_group', $group_id);
			}
		}
		
		if (isset($_POST['email']) && ! $this->EE->profile_model->settings('require_email_confirm'))
		{
			$_POST['email_confirm'] = $_POST['email'];
		}
		
		if (isset($_POST['password']) && ! $this->EE->profile_model->settings('require_password_confirm'))
		{
			$_POST['password_confirm'] = $_POST['password'];
		}
		
		$register = $this->EE->profile_model->member_action('register_member', 'member_register');
		
		$this->EE->config->set_item('default_member_group', $default_member_group);
		
		$this->EE->config->set_item('use_membership_captcha', $use_membership_captcha);
		
		//member_register returns FALSE if registrations are turned off
		if ($register === FALSE)
		{
			$this->EE->profile_model->add_error(lang('registration_off'));
		}
		
		foreach ($this->EE->profile_model->member_vars() as $key => $value)
		{
			if (FALSE !== ($value = $this->EE->input->post($key)))
			{
				$this->EE->profile_model->set_member_var($key, $this->EE->security->xss_clean($value));
			}
		}
		
		$this->set_author_and_entry(); 
		
		$_POST['json'] = $this->EE->input->is_ajax_request();

		if ($this->EE->input->post('title') === FALSE)
		{
			$_POST['title'] = ($this->EE->input->post('screen_name')) ? $this->EE->input->post('screen_name') : $this->EE->input->post('username');
		}
		
		if ($this->EE->input->post('url_title') === FALSE)
		{
			$_POST['url_title'] = $this->EE->input->post('username');
		}
		
		
		$return_data = NULL; 
		
		if ( version_compare(APP_VER, '2.7', '>='))
		{
			try
			{
				$return_data = $this->EE->channel_form->submit_entry();
			}
			catch (Channel_form_exception $e)
			{
				return $e->show_user_error();
			}
			return $return_data;
		}
		else
		{
			return $this->EE->channel_form->submit_entry();
			
		}
	}
	/**
	 * set_author_and_entry	
	 * 
	 * sets the author_id and entry_id in $_POST
	 *
	 * @param string $member_id 
	 * @param string $entry_id 
	 * @return void
	 * @author Chris Newton
	 */
	public function set_author_and_entry($member_id = NULL, $entry_id = NULL)
	{
		if ($member_id)
		{
			$this->EE->session->cache['profile']['member_id'] = $member_id; 
		}
		if ($entry_id)
		{
			$this->EE->session->cache['profile']['entry_id'] = $entry_id; 
		}
		//if this isn't set it means the register action failed early
		if (isset($this->EE->session->cache['profile']['member_id']))
		{
			//we can't set the author_id to the new member unless there's no account activation required
			//api_channel_entries will throw an error due to author_id/member_id mismatch
			//so we rectify this later on in the extension in the channel_form_submit_entry_end hook
			if ($this->EE->config->item('req_mbr_activation') === 'none')
			{
				$_POST['author_id'] = $this->EE->session->cache['profile']['member_id'];
			}
			
			//we have to do this because the XID hash was already cleared by Member::register_member and needs to be restored,
			//in order for SC to work. don't worry, Member class already validated it
			if ($this->EE->config->item('secure_forms') === 'y' && $this->EE->input->post('XID'))
			{
				if (version_compare(APP_VER, '2.5.5', '<'))
				{
					$this->EE->db->insert('security_hashes', array('date' => time() - 60, 'ip_address' => $this->EE->input->ip_address(), 'hash' => $this->EE->input->post('XID')));
				}
				else
				{
					$this->EE->db->insert('security_hashes', array('date' => time() - 60, 'session_id' => $this->EE->session->userdata('session_id'), 'hash' => $this->EE->input->post('XID')));
					
				}
			}

			if (isset($this->EE->session->cache['profile']['entry_id']))
			{
				$_POST['entry_id'] = $this->EE->session->cache['profile']['entry_id'];

				// we only need this on register for versions after 2.5.5
				if ($this->EE->session->cache['profile']['register'] && !version_compare(APP_VER, '2.5.5', '<='))
				{
					// there's this big pile of meta now that helps keep channel_form from using an entry id set in post to edit stuff
					// so we gotta get the meta and manipulate it to shove in the entry id, or we end up with TWO profiles for a new registration
					if (empty($_POST['meta']))
					{
						$this->EE->profile_model->add_error(lang('form_decryption_failed'));
					}
					else
					{
						$this->EE->load->library('encrypt');
						$meta = $this->EE->encrypt->decode($_POST['meta'], $this->EE->db->username.$this->EE->db->password);

						$new_meta = unserialize($meta);

						$new_meta['entry_id'] = $_POST['entry_id']; 
						$new_meta = serialize($new_meta);
						
						$_POST['meta'] = $this->EE->encrypt->encode($new_meta, $this->EE->db->username.$this->EE->db->password);
					}
				}
			}
		}
		
		$_POST['json'] = $this->EE->input->is_ajax_request();

		if ($this->EE->input->post('title') === FALSE)
		{
			$_POST['title'] = ($this->EE->input->post('screen_name')) ? $this->EE->input->post('screen_name') : $this->EE->input->post('username');
		}
		
		if ($this->EE->input->post('url_title') === FALSE)
		{
			$_POST['url_title'] = $this->EE->input->post('username');
		}
		// adds on the member_id to the return: http://example.com/template_group/template/123
		if (isset($this->EE->session->cache['profile']['member_id']) && strpos('MEMBER_ID', $_POST['return']) !== FALSE)
		{
			$_POST['return'] = str_replace("MEMBER_ID", $this->EE->session->cache['profile']['member_id'], $_POST['return']); 
		}
		// don't want to submit an entry yet... so this is commented out. 
		// why? because we need to hack the meta data needed by channel_form
		// to include the entry id. 
		#return $this->EE->channel_form->submit_entry();
	}
	
	public function activation_action()
	{
		$data = array(
			'group_id' => 4,
			'authcode' => $this->EE->input->get('authcode'),
		);
		
		if ( ! $data['authcode'])
		{
			return  show_error(lang('missing_authcode'));
		}
		
		$query = $this->EE->db->select('member_id')
				      ->where($data)
				      ->get('members');
		
		if ($query->num_rows() === 0)
		{
			return  show_error(lang('invalid_authcode'));
		}
		
		$member_id = $query->row('member_id');
		
		$query->free_result();
		
		$_GET['id'] = $data['authcode'];
		
		$default_member_group = $this->EE->config->item('default_member_group');
		
		if ($this->EE->input->get('g'))
		{
			$this->EE->load->helper('security');
			
			$group_id = xss_clean($this->EE->encrypt->decode($this->EE->input->get('g')));
			
			//validate group_id
			if ($this->EE->profile_model->validate_group_id($group_id))
			{
				$this->EE->config->set_item('default_member_group', $group_id);
			}
		}
		
		$this->EE->profile_model->member_action('activate_member', 'member_register');
		
		$this->EE->config->set_item('default_member_group', $default_member_group);
		
		if ( ! $this->EE->session->userdata('member_id') && $this->EE->profile_model->settings('login_after_email_activation'))
		{
			$this->EE->load->library('auth');
			
			$this->EE->load->model('member_model');
			
			$query = $this->EE->member_model->get_member_data($member_id);
			
			$auth = new Auth_result($query->row());
			
			$auth->remember_me(60*60*24*365);
			
			$auth->start_session();
		}
		
		$this->EE->functions->redirect($this->EE->functions->create_url($this->EE->input->get_post('return')));
	}
	
	public function reset_password()
	{
		// logged in user. ignore
		if ($this->EE->session->userdata('member_id') !== 0)
		{
			return $this->EE->functions->redirect($this->EE->functions->fetch_site_index());
		}
		// banned user
		if ($this->EE->session->userdata('is_banned') === TRUE)
		{
			return $this->EE->output->show_user_error('general', array(lang('not_authorized')));
		}
		// missing token
		if ( ! ($token = $this->EE->input->get('id')))
		{
			return $this->EE->output->show_user_error('submission', array(lang('mbr_no_reset_id')));
		}
		
		$this->EE->load->library('form_builder');

		$variables = array_merge(
			$this->EE->profile_model->member_vars(),
			$this->EE->form_builder->form_variables()
		);
		
		$this->EE->form_builder->initialize(array(
			'classname' => 'Profile',
			'method' => 'process_reset_password',
			'form_data' => array(
				'FROM',
				'resetcode' => $token
			),
			'params' => $this->EE->TMPL->tagparams,
			'content' => $this->EE->TMPL->parse_variables($this->EE->TMPL->tagdata, array($variables)),
		));

		$this->EE->form_builder->set_hidden('FROM','');
		$this->EE->form_builder->set_hidden('resetcode',$token);

		return $this->EE->form_builder->form();
	}
	
	public function process_reset_password()
	{
		$this->EE->load->library('form_builder');
		
		$_GET['id'] = $this->EE->input->get('resetcode');
		
		//false means the member_id from the reset-password table does not exist
		if (FALSE === $this->EE->profile_model->member_action('process_reset_password', 'member_auth'))
		{
			return  show_error(lang('mbr_id_not_found'));
		}
		
		if ($this->EE->profile_model->errors())
		{
			return  show_error( $this->EE->profile_model->errors());
		}
		
		$this->EE->functions->redirect($this->EE->functions->create_url($this->EE->input->get_post('return')));
	}
	
	public function reset_password_action()
	{
		$this->EE->load->library('form_builder');
		
		$_GET['id'] = $this->EE->input->get('resetcode');
		
		// logged in user. ignore
		if ($this->EE->session->userdata('member_id') !== 0)
		{
			return $this->EE->functions->redirect($this->EE->functions->fetch_site_index());
		}
		// banned user
		if (ee()->session->userdata('is_banned') === TRUE)
		{
			return $this->EE->output->show_user_error('general', array(lang('not_authorized')));
		}
		// missing token
		if ( ! ($token = $this->EE->input->get_post('id')))
		{
			return $this->EE->output->show_user_error('submission', array(lang('mbr_no_reset_id')));
		}
		
		$this->EE->functions->redirect($this->EE->functions->create_url($this->EE->input->get_post('return'))."?id=".$token);
	}
}

/* End of file mod.profile.php */
/* Location: ./system/expressionengine/third_party/profile/mod.profile.php */
