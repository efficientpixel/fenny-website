<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Profile_mcp
{
	private $native_settings = array(
		'allow_member_registration',
		'req_mbr_activation',
		'require_terms_of_service',
		'use_membership_captcha',
		'default_member_group',
		'new_member_notification',
		'mbr_notification_emails',
	);
	
	/**
	 * Profile_mcp
	 *
	 * @access	public
	 * @return	void
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
		
		$this->EE->load->model('profile_model');
	}

	/**
	 * index
	 *
	 * @access	public
	 * @return	void
	 */
	public function index()
	{

		$this->cp_setup();
		
		$this->EE->load->helper('form');
		
		$this->EE->load->library('table');
		
		$this->EE->lang->loadfile('admin');
		
		$output = form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=profile'.AMP.'method=save_settings');
		
		$this->EE->table->set_template(array(
			'table_open' => '<table class="mainTable" border="0" cellspacing="0" cellpadding="0">',
			'row_start' => '<tr class="even">',
			'row_alt_start' => '<tr class="odd">',
		));
		
		$this->EE->table->set_caption('Profile:Edit Preferences');
		
		foreach ($this->EE->profile_model->default_settings() as $key => $value)
		{
			$this->settings_row($key, $this->EE->profile_model->settings($key));
		}
		
		$output .= $this->EE->table->generate();
		
		$this->EE->table->set_caption(lang('member_preferences'));
		
		foreach ($this->native_settings as $key)
		{
			$this->settings_row($key, $this->EE->config->item($key));
		}
		
		$output .= $this->EE->table->generate();
		
		$output .= form_submit('', lang('submit'), 'class="submit"');
		
		$output .= form_close();
		
		return $output;
	}
	
	private function settings_row($key, $value)
	{
		$row = array(form_label(lang($key), $key));
		
		$notes = array(	
			'mbr_notification_emails' => lang('separate_emails'),
			'default_member_group' => lang('group_assignment_defaults_to_two'),
			'login_after_email_activation' => lang('login_after_email_activation_note'),
			'auto_login_after_register' => lang('auto_login_after_register_note'),
		);
		
		if (isset($notes[$key]))
		{
			$row[0] .= '<div class="subtext">'.$notes[$key].'</div>';
		}
		switch ($key)
		{
			case 'can_admin_members':
				
				$options = array();
				
				$query = $this->EE->db->select('group_id, group_title')
						      ->where('group_id >', 4)
						      ->get('member_groups');
		
				$options[] = "---";
		
				foreach ($query->result() as $group)
				{
					$options[$group->group_id] = $group->group_title;
				}
				
				$query->free_result();
				
				$row[] = form_multiselect($key."[]", $options, $value, 'id="'.$key.'"');
				
				break;
			case 'channel_id':
				
				$options = array('' => array('' => '---'));
				
				$query = $this->EE->db->select('channels.channel_id, channels.channel_title, sites.site_label')
							->join('sites', 'sites.site_id = channels.site_id')
						      ->get('channels');
		
				foreach ($query->result() as $channel)
				{
					if ( ! isset($options[$channel->site_label]))
					{
						$options[$channel->site_label] = array();
					}
					
					$options[$channel->site_label][$channel->channel_id] = $channel->channel_title;
				}
				
				$query->free_result();
				
				$row[] = form_dropdown($key, $options, $value, 'id="'.$key.'"');
				
				break;
			
			case 'default_member_group':
				
				$options = array();
				
				$query = $this->EE->db->select('group_id, group_title')
						      ->where('group_id >', 4)
						      ->get('member_groups');
		
				foreach ($query->result() as $group)
				{
					$options[$group->group_id] = $group->group_title;
				}
				
				$query->free_result();
				
				$row[] = form_dropdown($key, $options, $value, 'id="'.$key.'"');
				
				break;
			
			case 'req_mbr_activation':
				
				$row[] = form_dropdown(
					$key,
					array(
						'none' => lang('no_activation'),
						'email' => lang('email_activation'),
						'manual' => lang('manual_activation')
					),
					$value
				);
				
				break;
			
			case 'mbr_notification_emails':
				
				$row[] = form_input($key, $value, 'id="'.$key.'"');
				
				break;
			
			case 'allow_member_registration':
			case 'require_terms_of_service':
			case 'use_membership_captcha':										
			case 'default_member_group':
			case 'new_member_notification':
				
				$row[] = form_label(form_checkbox($key, 'y', $value === 'y', 'id="'.$key.'"').NBS.lang('yes'), $key);
				
				break;
			
			default:
				
				$row[] = form_label(form_checkbox($key, 1, (bool) $value, 'id="'.$key.'"').NBS.lang('yes'), $key);
		}
		
		$this->EE->table->add_row($row);
	}
	
	public function encryption_key()
	{
		$this->EE->cp->cp_page_title =  lang('profile_module_name');
		
		return $this->EE->load->view('encryption_key', array(), TRUE);
	}
	
	public function installation()
	{
		$this->cp_setup();
		
		$vars = array(
			'install_channels' => array(),
			'install_template_groups' => array(),
			'install_member_groups' => array(),
			'template_errors' => ($this->EE->session->flashdata('template_errors')) ? $this->EE->session->flashdata('template_errors') : array(),
			'templates_installed' => ($this->EE->session->flashdata('templates_installed')) ? $this->EE->session->flashdata('templates_installed') : array(),
		);
		
		$this->EE->load->library('package_installer', array('xml' => $this->EE->load->view('installation_xml', array(), TRUE)));
		
		foreach ($this->EE->package_installer->packages() as $index => $package)
		{
			switch($package->getName())
			{
				case 'channel':
					$vars['install_channels'][$index] = $package->attributes()->channel_title;
					if (isset($package->field_group) && isset($package->field_group->field))
					{
						foreach ($package->field_group->field as $field)
						{
							$vars['fields'][$index][] = $field->attributes()->field_label;
						}
					}
					break;
				case 'template_group':
					$vars['install_template_groups'][$index] = $package->attributes()->group_name;
					if (isset($package->template))
					{
						foreach ($package->template as $template)
						{
							$vars['templates'][$index][] = $template->attributes()->template_name;
						}
					}
					break;
				case 'member_group':
					$vars['install_member_groups'][$index] = $package->attributes()->group_name;
					break;
			}
		}
		
		return $this->EE->load->view('installation', $vars, TRUE);
	}
	
	public function do_installation()
	{
		$this->EE->load->add_package_path(PATH_THIRD.'profile/');
		
		$this->EE->load->library('package_installer', array('xml' => $this->EE->load->view('installation_xml', array(), TRUE)));
		
		if (is_array($templates_to_install = $this->EE->input->post('templates')))
		{
			foreach ($this->EE->package_installer->packages() as $row_id => $package)
			{
				if ( ! in_array($row_id, $templates_to_install))
				{
					$this->EE->package_installer->remove_package($row_id);
				}
			}
			
			$this->EE->package_installer->install();
			
			$this->EE->session->set_flashdata('template_errors', $this->EE->package_installer->errors());
			
			$this->EE->session->set_flashdata('templates_installed', $this->EE->package_installer->installed());
		}
		
		$query = $this->EE->db->select('channel_id')
				      ->where('channel_name', 'member_profiles')
				      ->get('channels');
		
		if ($channel_id = $query->row('channel_id'))
		{
			$query->free_result();
			
			//update settings
			$query = $this->EE->db->select('settings')
					      ->where('class', 'Profile_ext')
					      ->limit(1)
					      ->get('extensions');
			
			if ($query->num_rows() > 0)
			{
				$settings = unserialize($query->row('settings'));
				
				if (empty($settings['channel_id']))
				{
					$settings['channel_id'] = $channel_id;
				}
				
			}
			else
			{
				$settings = array('channel_id' => $channel_id);
			}
			
			$this->EE->db->update('extensions', array('settings' => serialize($settings)), array('class' => 'Profile_ext'));
		}
		
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=profile'.AMP.'method=installation');
	}
	
	public function set_encryption_key()
	{
		$this->EE->config->_update_config(array('encryption_key' => $this->EE->input->post('encryption_key')));
		
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=profile');
	}
	
	public function utilities()
	{
		if ( ! $channel_id = $this->EE->profile_model->channel_id())
		{
			show_error(sprintf(lang('no_channel_cp'), BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=profile'));
		}
		
		$this->cp_setup();
		
		$this->EE->cp->add_js_script(array(
			'ui' => array('core', 'widget', 'progressbar')
		));
		
		$this->EE->lang->loadfile('myaccount');
		$this->EE->lang->loadfile('member');
		
		$member_count = $this->EE->db->where_not_in('group_id', array(2, 3))
					     ->count_all_results('members');
		
		$this->EE->javascript->output('
		$.profile = {
			memberCount: '.$member_count.',
			syncMemberFields: {
				offset: 0,
				limit: 100,
				memberFields: null,
				sync: function(){
					if ($.profile.syncMemberFields.memberFields === null){
						$.profile.syncMemberFields.memberFields = {};
						$.each($("#member_fields").serializeArray(), function(i, v){
							if (v.value && v.value !== undefined && v.value !== "undefined"){
								$.profile.syncMemberFields.memberFields[v.name] = v.value;
							}
						});
						$("#member_fields select").attr("disabled", true);
					}
					$.post(
						EE.BASE+"&C=addons_modules&M=show_module_cp&module=profile&method=sync_member_fields",
						{
							XID: EE.XID,
							offset: $.profile.syncMemberFields.offset,
							limit: $.profile.syncMemberFields.limit,
							member_fields: $.profile.syncMemberFields.memberFields
						},
						function(data){
							var progress = ($.profile.syncMemberFields.offset / $.profile.memberCount) * 100;
							$("#sync_member_fields_progressbar").progressbar("value", progress);
							$.profile.syncMemberFields.offset += $.profile.syncMemberFields.limit;
							if ($.profile.syncMemberFields.offset < $.profile.memberCount){
								$.profile.syncMemberFields.sync();
							} else {
								$("#sync_member_fields").replaceWith("<h1>Finished!</h1>");
								$("#sync_member_fields_progressbar").progressbar("value", 100);
							}
						},
						"text"
					);
				}
			},
			associateExistingEntries: {
				offset: 0,
				limit: 100,
				memberField: null,
				profileField: null,
				sync: function(){
					var field;
					if ($.profile.associateExistingEntries.memberField === null){
						field = $("select[name=member_field]");
						$.profile.associateExistingEntries.memberField = field.val();
						field.attr("disabled", true);
					}
					if ($.profile.associateExistingEntries.profileField === null){
						field = $("select[name=profile_field]");
						$.profile.associateExistingEntries.profileField = field.val();
						field.attr("disabled", true);
					}
					$.post(
						EE.BASE+"&C=addons_modules&M=show_module_cp&module=profile&method=associate_existing_entries",
						{
							XID: EE.XID,
							offset: $.profile.associateExistingEntries.offset,
							limit: $.profile.associateExistingEntries.limit,
							member_field: $.profile.associateExistingEntries.memberField,
							profile_field: $.profile.associateExistingEntries.profileField
						},
						function(data){
							var progress = ($.profile.associateExistingEntries.offset / $.profile.memberCount) * 100;
							$("#associate_existing_entries_progressbar").progressbar("value", progress);
							$.profile.associateExistingEntries.offset += $.profile.associateExistingEntries.limit;
							if ($.profile.associateExistingEntries.offset < $.profile.memberCount){
								$.profile.associateExistingEntries.sync();
							} else {
								$("#associate_existing_entries").replaceWith("<h1>Finished!</h1>");
								$("#associate_existing_entries_progressbar").progressbar("value", 100);
							}
						},
						"text"
					);
				}
			},
			createProfiles: {
				offset: 0,
				limit: 100,
				memberCount: null,
				start: function(){
					$.post(
						EE.BASE+"&C=addons_modules&M=show_module_cp&module=profile&method=create_profiles",
						{
							XID: EE.XID,
							get_member_count: 1
						},
						function(data){
							console.log(data);
							// the XID is returned in JSON format
							obj = JSON.parse(data);
							console.log(obj.XID);
							// have to explicitly set the XID 
							EE.XID = obj.XID;
							
							$.profile.createProfiles.memberCount = Number(obj.member_count);

							if ($.profile.createProfiles.memberCount > 0){
								$.profile.createProfiles.sync();
							} else {
								$.profile.createProfiles.finish();
							}
						},
						"text"
					);
				},
				finish: function(){
					$("#create_profiles").replaceWith("<h1>Finished!</h1>");
					$("#create_profiles_progressbar").progressbar("value", 100);	
				},
				sync: function(){
					$.post(
						EE.BASE+"&C=addons_modules&M=show_module_cp&module=profile&method=create_profiles",
						{
							XID: EE.XID,
							offset: $.profile.createProfiles.offset,
							limit: $.profile.createProfiles.limit
						},
						function(data){
							console.log(data);
							obj = JSON.parse(data);
							console.log(obj.XID);
							EE.XID = obj.XID;
							var progress = ($.profile.createProfiles.offset / $.profile.createProfiles.memberCount) * 100;
							$("#create_profiles_progressbar").progressbar("value", progress);
							$.profile.createProfiles.offset += $.profile.createProfiles.limit;
							if ($.profile.createProfiles.offset < $.profile.createProfiles.memberCount){
								$.profile.createProfiles.sync();
							} else {
								$.profile.createProfiles.finish();
							}
						},
						"text"
					);
				}
			}
		};
		$(".progressbar").progressbar();
		$("#sync_member_fields").click(function(){
			$.profile.syncMemberFields.sync();
			return false;
		});
		$("#associate_existing_entries").click(function(){
			$.profile.associateExistingEntries.sync();
			return false;
		});
		$("#create_profiles").click(function(){
			$.profile.createProfiles.start();
			return false;
		});
		$("#member_fields select").each(function(){
			var name = $(this).parent().siblings().text();
			var select = this;
			$.each(this.options, function(i, v){
				if ($(v).text() === name){
					select.selectedIndex = i;
					return false;
				}
			});
		});
		');
		
		$vars = array(
			'member_fields' => array(),
			'member_fields_all' => array(
				lang('member_fields') => array(
					'username' => lang('username'),
					'email' => lang('email'),
					'screen_name' => lang('screen_name'),
					'url' => lang('url'),
					'location' => lang('location'),
					'occupation' => lang('occupation'),
					'interests' => lang('interests'),
					'aol_im' => lang('aol_im'),
					'yahoo_im' => lang('yahoo_im'),
					'msn_im' => lang('msn_im'),
					'icq' => lang('icq'),
					'bio' => lang('bio'),
					'signature' => lang('signature'),
				),
			),
			'profile_fields_with_blank' => array('' => '---'),
			'profile_fields' => array(),
		);
		
		$query = $this->EE->db->select('m_field_id, m_field_label')
				      ->order_by('m_field_order', 'asc')
				      ->get('member_fields');
		
		foreach ($query->result() as $row)
		{
			$vars['member_fields'][$row->m_field_id] = $row->m_field_label;
		}
		if(! empty($vars['member_fields']))
		{
			$vars['member_fields_all'][lang('profile_fields')] = $vars['member_fields'];
		}
		$query = $this->EE->db->select('field_id, field_label')
				      ->join('channels', 'channels.field_group = channel_fields.group_id')
				      ->where('channel_id', $channel_id)
				      ->order_by('field_order', 'asc')
				      ->get('channel_fields');
		
		foreach ($query->result() as $row)
		{
			$vars['profile_fields'][$row->field_id] = $row->field_label;
			$vars['profile_fields_with_blank'][$row->field_id] = $row->field_label;
		}
		
		$this->EE->db->_reset_select();
		
		return $this->EE->load->view('utilities', $vars, TRUE);
	}
	
	public function associate_existing_entries()
	{
		$offset = $this->EE->input->post('offset');
		
		$limit = $this->EE->input->post('limit');
		
		$profile_field = 'field_id_'.$this->EE->input->post('profile_field');
		
		$channel_id = $this->EE->profile_model->channel_id();
		
		if (is_numeric($this->EE->input->post('member_field')))
		{
			$member_field = 'm_field_id_'.$this->EE->input->post('member_field');
			
			$member_table = 'member_data';
		}
		else
		{
			$member_field = $this->EE->input->post('member_field');
			
			$member_table = 'members';
		}
		
		$query = $this->EE->db->select("channel_data.entry_id, {$member_table}.member_id, {$member_table}.{$member_field}")
				      ->join($member_table, "{$member_table}.{$member_field} = channel_data.{$profile_field}")
				      ->where("channel_data.{$profile_field} !=", '')
				      ->limit($limit, $offset)
				      ->get('channel_data');
		
		foreach ($query->result() as $row)
		{
			$this->EE->db->update('channel_titles', array('author_id' => $row->member_id), array('entry_id' => $row->entry_id));
		}
		
		exit('done');
	}
	
	public function sync_member_fields()
	{
		$offset = $this->EE->input->post('offset');
		
		$limit = $this->EE->input->post('limit');
		
		$channel_id = $this->EE->profile_model->channel_id();
		
		$member_fields = $this->EE->input->post('member_fields');
		
		$members = array();
		
		$member_ids = array();
		
		$query = $this->EE->db->select('member_data.*, members.screen_name, members.username')
				      ->join('members', 'members.member_id = member_data.member_id')
				      ->limit($limit, $offset)
				      ->get('member_data');
		
		foreach ($query->result_array() as $row)
		{
			$members[$row['member_id']] = $row;
			
			$member_ids[] = $row['member_id'];
		}
		
		$query->free_result();
		
		$query = $this->EE->db->select('entry_id, author_id')
				      ->where('channel_id', $channel_id)
				      ->where_in('author_id', $member_ids)
				      ->get('channel_titles');
		
		//update existing entries
		foreach ($query->result() as $row)
		{
			$data = array();
			
			foreach ($member_fields as $m_field_id => $field_id)
			{
				$data['field_id_'.$field_id] = $members[$row->author_id]['m_field_id_'.$m_field_id];
			}
			
			$this->EE->db->update('channel_data', $data, array('entry_id' => $row->entry_id));
			
			unset($members[$row->author_id]);
		}
		
		$query->free_result();
		
		//create new entries
		foreach ($members as $member_id => $member_data)
		{
			$data = array(
				'screen_name' => $member_data['screen_name'],
				'username' => $member_data['username'],
			);
			
			foreach ($member_fields as $m_field_id => $field_id)
			{
				$data['field_id_'.$field_id] = (string) $member_data['m_field_id_'.$m_field_id];
			}
			
			$this->EE->profile_model->create_profile($data, $member_id);
		}
		
		exit('done');
	}
	
	public function create_profiles()
	{
		$members_table = '`'.$this->EE->db->dbprefix('members').'`';
		$channel_table = '`'.$this->EE->db->dbprefix('channel_titles').'`';
		$channel_id = $this->EE->profile_model->channel_id();
		
		if ( ! $channel_id)
		{
			exit('food');
		}
		
		if ($this->EE->input->post('get_member_count'))
		{
			$sql = "SELECT COUNT(*) AS count
				  FROM {$members_table}
				  WHERE 0 = (
					SELECT COUNT(*)
					FROM {$channel_table}
					WHERE {$channel_table}.`author_id` = {$members_table}.`member_id`
					AND {$channel_table}.`channel_id` = {$channel_id}
				  )
				  AND group_id NOT IN (2, 3)";
		
			$query = $this->EE->db->query($sql);
 			$this->EE->output->send_ajax_response(array('member_count' => $query->row('count'), 'XID' => $this->EE->functions->add_form_security_hash('{XID_HASH}')));
			
// 	exit($query->row('count'));
		}
		
		$offset = $this->EE->input->post('offset');
		if (!$offset)
		{
			$offset = 0; 
		}
		
		$limit = $this->EE->input->post('limit');
		
		//get members who don't have an entry
		$sql = "SELECT `member_id`, `username`, `screen_name`
			  FROM {$members_table}
			  WHERE 0 = (
				SELECT COUNT(*)
				FROM {$channel_table}
				WHERE {$channel_table}.`author_id` = {$members_table}.`member_id`
				AND {$channel_table}.`channel_id` = {$channel_id}
			  )
			  AND group_id NOT IN (2, 3)"; 

		if ($limit)
		{
			$sql .= "  LIMIT {$offset}, {$limit}"; 
		}
		
		$query = $this->EE->db->query($sql);
		
		foreach ($query->result_array() as $row)
		{
			$entry_id = $this->EE->profile_model->create_profile($row, $row['member_id']);
		}
		
		$query->free_result();
		
		$this->EE->output->send_ajax_response(array('XID' => $this->EE->functions->add_form_security_hash('{XID_HASH}')));
	}
	
	public function save_settings()
	{
		$settings = array();
		
		foreach (array_keys($this->EE->profile_model->settings()) as $key)
		{
			if ($key == 'can_admin_members')
			{
				$data = $this->EE->input->post($key); 
				if (is_array($data))
				{
					$settings[$key]  = array_filter($data); 
				}
			}
			else
			{
				$settings[$key] = $this->EE->input->post($key);
			}
		}
		
		$this->EE->db->update('extensions', array('settings' => serialize($settings)), array('class' => 'Profile_ext'));
		
		require_once PATH_THIRD.'profile/upd.profile.php';
		
		$upd = new Profile_upd;
		
		$upd->update_hooks($settings);
		
		$native_settings = array();
		
		foreach ($this->native_settings as $key)
		{
			if ($this->EE->input->post($key) === FALSE)
			{
				$native_settings[$key] = 'n';
			}
			else
			{
				$native_settings[$key] = $this->EE->input->post($key);
			}
		}
		
		$this->EE->config->update_site_prefs($native_settings);
		
		$this->EE->session->set_flashdata('message_success', lang('settings_saved'));
		
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=profile');
	}
	
	protected function cp_setup($title = NULL)
	{
		if ( ! $this->EE->config->item('encryption_key'))
		{
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=profile'.AMP.'method=encryption_key');
		}
		
		if (is_null($title))
		{
			$title = lang('profile_module_name');
		}
		
		$this->EE->cp->set_right_nav(array(
			'settings' => BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=profile',
			'utilities' => BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=profile'.AMP.'method=utilities',
			'installation' => BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=profile'.AMP.'method=installation',
		));
		
		$this->EE->cp->cp_page_title = $title;
	}
}

/* End of file mcp.profile.php */
/* Location: ./system/expressionengine/third_party/profile/mcp.profile.php */