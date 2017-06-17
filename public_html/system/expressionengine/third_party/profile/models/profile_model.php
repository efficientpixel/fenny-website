<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Profile_model extends CI_Model
{
	protected $errors = array();
	
	protected $settings = NULL;

	protected $default_settings;
	
	public static $native_editable_fields = array(
		'url',
		'location',
		'occupation',
		'interests',
		'aol_im',
		'yahoo_im',
		'msn_im',
		'icq',
		'bio',
		'signature',
	);
	
	public static $native_fields = array(
		'avatar_height',
		'avatar_width',
		'join_date',
		'language',
		'last_activity',
		'last_comment_date',
		'last_entry_date',
		'last_forum_post_date',
		'last_visit',
		'photo_height',
		'photo_width',
		'timezone',
		'total_comments',
		'total_entries',
		'total_forum_posts',
		'total_forum_topics',
	);
	
	public static $native_fields_legacy = array(
		'avatar_height',
		'avatar_width',
		'daylight_savings',
		'join_date',
		'language',
		'last_activity',
		'last_comment_date',
		'last_entry_date',
		'last_forum_post_date',
		'last_visit',
		'photo_height',
		'photo_width',
		'timezone',
		'total_comments',
		'total_entries',
		'total_forum_posts',
		'total_forum_topics',
	);
	
	protected $fields; 
	
	public function __construct()
	{
		parent::__construct();
		
		include PATH_THIRD.'profile/config.php';
		
		$this->default_settings = $config['default_settings'];
		$this->fields = self::$native_fields; 
		
		if (! version_compare(APP_VER, '2.6', '>='))
		{
			$this->fields = self::$native_fields_legacy; 
		}
		
	}
	
	public function channel_id()
	{
		static $channel_id;
		
		if (is_null($channel_id))
		{
			$channel_id = $this->settings('channel_id');
		}
		
		return $channel_id;
	}
	
	public function site_id()
	{
		static $site_id;
		
		if (is_null($site_id))
		{
			$site_id = ($this->channel_id()) ? $this->get_channel_info('site_id') : FALSE;
		}
		
		return $site_id;
	}
	
	public function site_name()
	{
		static $site_name;
		
		if (is_null($site_name))
		{
			$site_name = ($this->channel_id()) ? $this->get_channel_info('site_name') : FALSE;
		}
		
		return $site_name;
	}
	
	public function channel_name()
	{
		static $channel_name;
		
		if (is_null($channel_name))
		{
			$channel_name = ($this->channel_id()) ? $this->get_channel_info('channel_name') : FALSE;
		}
		
		return $channel_name;
	}
	
	public function errors()
	{
		return $this->errors;
	}
	
	public function reset_errors()
	{
		$this->errors = array();
		
		return $this;
	}
	
	public function default_settings()
	{
		return $this->default_settings;
	}
	
	public function add_error($error, $value = NULL)
	{
		if (func_num_args() > 1)
		{
			$error = array($error => $value);
		}
		
		$error = (is_array($error)) ? $error : array($error);
		
		$this->errors = array_merge($this->errors, $error);
		
		return $this;
	}
	
	public function set_errors(array $errors)
	{
		$this->errors = $errors;
	}
	
	public function get_settings()
	{
		// this is not active record on purpose, leave it alone!
		$query = $this->db->query('SELECT `settings` FROM '.$this->db->dbprefix('extensions').' WHERE class = \'Profile_ext\' LIMIT 1');

		if ($query->row('settings') && $settings = @unserialize($query->row('settings')))
		{
			return array_merge($this->default_settings, $settings);
		}

		return $this->default_settings;
	}
	
	public function set_settings($settings)
	{
		if (is_array($settings))
		{
			$this->settings = array_merge($this->default_settings, $settings);
		}
		
		return $this;
	}
	
	public function settings($key = FALSE)
	{
		if (is_null($this->settings))
		{
			$this->settings = $this->get_settings();
		}
		
		if ($key === FALSE)
		{
			return $this->settings;
		}
		
		return (isset($this->settings[$key])) ? $this->settings[$key] : FALSE;
	}
	
	public function channel_entries($params)
	{
		require_once PATH_MOD.'channel/mod.channel.php';

		$channel = new Channel;
		
		//this is to kill channel:entries caching
		$tagproper = '';
		
		foreach ($params as $key => $value)
		{
			$tagproper .= $key.'="'.$value.'" ';
		}
		
		$this->TMPL->tagproper = substr($this->TMPL->tagproper, 0, strlen($this->TMPL->tagproper) - 1).$tagproper.substr($this->TMPL->tagproper, -1);
		
		$this->TMPL->tagparams = array_merge((array) $this->TMPL->tagparams, $params);

		if (version_compare(APP_VER, '2.6', '<'))
		{
			$this->TMPL->tagdata = $this->TMPL->assign_relationship_data($this->TMPL->tagdata);
		}
		
		if (count($this->TMPL->related_markers) > 0)
		{
			foreach ($this->TMPL->related_markers as $marker)
			{
				if ( ! isset($this->TMPL->var_single[$marker]))
				{
					$this->TMPL->var_single[$marker] = $marker;
				}
			}
		}

		if ($this->TMPL->related_id)
		{
			$this->TMPL->var_single[$this->TMPL->related_id] = $this->TMPL->related_id;
			
			$this->TMPL->related_id = '';
		}
		
		return $channel->entries();
	}

	public function create_profile($member_data, $member_id, $use_oldest_superadmin = FALSE)
	{
		if ( ! $this->channel_id())
		{
			return FALSE;
		}
		
		$this->load->helper('url');
		
		//if author_id is set, that means it's a temporary author_id that will be changed later on
		$author_id = ($use_oldest_superadmin) ? $this->oldest_superadmin() : $member_id;

		$deft_status = $this->get_channel_info('deft_status');

		$channel_titles = array(
			'title' => $member_data['screen_name'],
			'url_title' => url_title($member_data['username']),
			'channel_id' => $this->channel_id(),
			'author_id' => $author_id,
			'site_id' => $this->site_id(),
			'ip_address' => $this->input->ip_address(),
			'entry_date' => $this->localize->now,
			'edit_date' => date("YmdHis"),
			'versioning_enabled' => 'y',
			'status' => $deft_status ? $deft_status : 'open',
			'forum_topic_id' => 0,
		);

		//@TODO check url_title

		$channel_titles['year'] = date('Y', $channel_titles['entry_date']);
		$channel_titles['month'] = date('m', $channel_titles['entry_date']);
		$channel_titles['day'] = date('d', $channel_titles['entry_date']);

		$this->db->insert('channel_titles', $channel_titles);

		$entry_id = $this->db->insert_id();

		$channel_data = array(
			'entry_id' => $entry_id,
			'channel_id' => $this->channel_id(),
			'site_id' => $this->site_id(),
		);
		
		foreach ($this->get_custom_fields() as $field)
		{
			$channel_data['field_ft_'.$field['field_id']] = $field['field_fmt'];
		}
		
		foreach ($member_data as $key => $value)
		{
			if (strncmp($key, 'field_id_', 9) === 0)
			{
				$channel_data[$key] = $value;
			}
		}

		$this->db->insert('channel_data', $channel_data);

		$this->db->set('total_entries', 'total_entries + 1', FALSE)
			     ->where('member_id', $member_id)
			     ->update('members');

		$this->stats->update_channel_stats($this->channel_id());

		if ($this->config->item('new_posts_clear_caches') == 'y')
		{
			$this->functions->clear_caching('all');
		}
		else
		{
			$this->functions->clear_caching('sql');
		}

		return $entry_id;
	}
	
	public function get_all_profile_ids($member_id)
	{
		if ( ! $this->channel_id())
		{
			return FALSE;
		}
		
		$query = $this->db->select('entry_id')
				  ->where('channel_id', $this->channel_id())
				  ->where('author_id', $member_id)
				  ->get('channel_titles');
		
		$entry_ids = array();
		
		foreach ($query->result() as $row)
		{
			$entry_ids[] = $row->entry_id;
		}
		
		return $entry_ids;
	}
	
	public function get_member_id_by_username($username)
	{
		$member_id = FALSE;
		
		$query = $this->db->select('member_id, username, screen_name, email, email AS email_confirm')
				  ->where('username', $username)
				  ->get('members');
		
		if ($query->num_rows() > 0)
		{
			$member_id = $query->row('member_id');
			
			if ($this->session->userdata('member_id') != $member_id)
			{
				$this->set_member_var($query->row_array());
			}
		}
		
		$query->free_result();
		
		return $member_id;
	}
	
	public function get_member_id_by_email($email)
	{
		$member_id = FALSE;
		
		$query = $this->db->select('member_id, username, screen_name, email, email AS email_confirm')
				  ->where('email', $email)
				  ->get('members');
		
		if ($query->num_rows() > 0)
		{
			$member_id = $query->row('member_id');
			
			if ($this->session->userdata('member_id') != $member_id)
			{
				$this->set_member_var($query->row_array());
			}
		}
		
		$query->free_result();
		
		return $member_id;
	}
	
	public function get_member_id_by_entry_id($entry_id)
	{
		static $cache;
		
		if ( ! isset($cache[$entry_id]))
		{
			$query = $this->db->select('author_id')
					  ->where('entry_id', $entry_id)
					  ->get('channel_titles');
			
			$cache[$entry_id] = $query->row('author_id');
			
			$query->free_result();
		}
		
		return $cache[$entry_id];
	}
	
	public function get_member_id_by_url_title($url_title)
	{
		static $cache;
		
		if ( ! isset($cache[$url_title]))
		{
			$query = $this->db->select('author_id')
					  ->where('url_title', $url_title)
					  ->get('channel_titles');
			
			$cache[$url_title] = $query->row('author_id');
			
			$query->free_result();
		}
		
		return $cache[$url_title];
	}
	
	public function get_native_custom_fields()
	{
		static $member_fields;
		
		if (is_null($member_fields))
		{
			$member_fields = array();
			
			$this->load->model('member_model');
			
			$query = $this->member_model->get_all_member_fields();
			
			foreach ($query->result() as $row)
			{
				$member_fields[$row->m_field_id] = $row->m_field_name;
			}
			
			$query->free_result();
		}
		
		return $member_fields;
	}
	
	public function get_native_fields()
	{
		return array_merge(
			$this->get_native_custom_fields(),
			self::$native_fields,
			self::$native_editable_fields
		);
	}
	
	public function get_member_data($member_id)
	{
		static $cache;
		
		if (isset($cache[$member_id]))
		{
			return $cache[$member_id];
		}
		
		$cache[$member_id] = FALSE;
		
		if ( ! $member_id || ! is_numeric($member_id))
		{
			return $cache[$member_id];
		}
		
		foreach ($this->get_native_custom_fields() as $field_id => $field_name)
		{
			$this->db->select("`m_field_id_$field_id` AS `$field_name`", FALSE);
		}
		
		$query = $this->db->select('username, screen_name, email, email AS email_confirm, group_id')
				  ->select(self::$native_editable_fields)
				  ->select(self::$native_fields)
				  ->where('members.member_id', $member_id)
				  ->join('member_data', 'member_data.member_id = members.member_id')
				  ->get('members');
		
		if ($query->num_rows() > 0)
		{
			$cache[$member_id] = $query->row_array();
		}
		
		$query->free_result();
		
		return $cache[$member_id];
	}
	
	public function validate_entry_id($entry_id, &$member_id = NULL)
	{
		static $cache;
		
		if (isset($cache[$entry_id]))
		{
			return $cache[$entry_id];
		}
		
		if ( ! $this->channel_id())
		{
			return $cache[$entry_id] = FALSE;
		}
		
		// need to get author id if you're not a super admin, AND you're not allowed to admin members. 
		$admin_groups = (array) $this->settings('can_admin_members'); 
		
		if ($this->session->userdata('group_id') != 1 && ( !in_array($this->session->userdata('group_id'), $admin_groups)  && $this->session->userdata('can_admin_members') !== 'y'))
		{
			$this->db->where('author_id', $this->session->userdata('member_id'));
		}
		
		$query = $this->db->select('channel_titles.entry_id, channel_titles.author_id, members.username, members.screen_name, members.email, members.email AS email_confirm')
				  ->where('channel_id', $this->channel_id())
				  ->where('entry_id', $entry_id)
				  ->join('members', 'channel_titles.author_id = members.member_id')
				  ->get('channel_titles');
		
		if ($query->row('author_id'))
		{
			$member_id = $query->row('author_id');
			
			if ($this->session->userdata('member_id') != $query->row('author_id'))
			{
				$this->set_member_var($query->row_array());
			}
		}
		
		return $cache[$entry_id] = (bool) $query->num_rows();
	}
	
	public function member_vars()
	{
		if ( ! isset($this->session->cache['profile']['member_vars']))
		{
			$this->session->cache['profile']['member_vars'] = array(
				'username' => '',
				'screen_name' => '',
				'email' => '',
				'email_confirm' => '',
			);
			
			foreach ($this->get_native_fields() as $field)
			{
				$this->session->cache['profile']['member_vars'][$field] = '';
			}
		}
		
		return $this->session->cache['profile']['member_vars'];
	}
	
	public function set_member_var($key, $value = FALSE)
	{
		if (is_array($key))
		{
			$vars = $key;
			
			foreach ($this->member_vars() as $key => $value)
			{
				if (isset($vars[$key]))
				{
					$this->set_member_var($key, $vars[$key]);
				}
			}
		}
		else
		{
			$this->session->cache['profile']['member_vars'][$key] = $value;
		}
		
		return $this;
	}
	
	public function member_action($action, $class = NULL)
	{
		//take over output class so member module doesn't throw any errors
		$output_class = get_instance()->output;

		//trick the superglobal to use this class as the output class
		get_instance()->output =& $this;
		
		//load the member module class
		require_once PATH_MOD.'member/mod.member.php';
		
		if (is_null($class))
		{
			$class = 'Member';
		}
		else
		{
			require_once PATH_MOD.'member/mod.'.strtolower($class).'.php';
			
			$class = ucwords($class);
		}

		$member = new $class;
		
		$return = $member->$action();
		
		get_instance()->output =& $output_class;
		
		unset($output_class, $member);
		
		return $return;
	}
	
	public function validate_url_title($url_title, &$member_id = NULL)
	{
		static $cache;
		
		if (isset($cache[$url_title]))
		{
			return $cache[$url_title];
		}
		
		if ( ! $this->channel_id())
		{
			return $cache[$url_title] = FALSE;
		}
		
		if ($this->session->userdata('group_id') != 1)
		{
			$this->db->where('author_id', $this->session->userdata('member_id'));
		}
		
		$query = $this->db->select('channel_titles.entry_id, channel_titles.author_id, members.username, members.screen_name, members.email, members.email AS email_confirm')
				  ->where('channel_id', $this->channel_id())
				  ->where('url_title', $url_title)
				  ->join('members', 'channel_titles.author_id = members.member_id')
				  ->get('channel_titles');
		
		if ($query->row('author_id'))
		{
			$member_id = $query->row('author_id');
			
			if ($this->session->userdata('member_id') != $query->row('author_id'))
			{
				$this->set_member_var($query->row_array());
			}
		}
		
		return $cache[$entry_id] = (bool) $query->num_rows();
	}
	
	public function validate_group_id($group_id)
	{
		static $cache;
		
		if (isset($cache[$group_id]))
		{
			return $cache[$group_id];
		}
		
		if ( ! $group_id || ! is_numeric($group_id) && in_array($group_id, array(2, 3, 4)))
		{
			return $cache[$group_id] = FALSE;
		}
		
		if ($this->session->userdata('group_id') != 1)
		{
			$this->db->where('is_locked', 'n');
		}
		
		$this->db->where('group_id', $group_id)
			 ->where('site_id', $this->config->item('site_id'));
		
		return $cache[$group_id] = $this->db->count_all_results('member_groups') > 0;
	}
	
	public function change_author_id($member_id, $entry_id)
	{
		$this->db->update('channel_titles', array('author_id' => $member_id), array('entry_id' => $entry_id));
	}
	public function change_member_group($member_id, $group_id)
	{
		$this->db->update('members', array('group_id' => $group_id), array('member_id' => $member_id));
	}
	
	public function parse_profile_field($field_name, $member_id, $tagdata = '', $params = array(), $method = 'replace_tag')
	{
		static $once;
		
		if (is_null($once))
		{
			$this->load->library('api');
			
			$this->load->library('typography');
		
			$this->load->helper('custom_field');
			
			$this->api->instantiate('channel_fields');
			
			foreach ($this->get_custom_fields() as $field)
			{
				if ( ! isset($this->api_channel_fields->settings[$field['field_id']]))
				{
					$settings = (array) @unserialize(base64_decode($field['field_settings']));
					
					$this->api_channel_fields->set_settings(
						$field['field_id'],
						array_merge(
							$field,
							$settings
						)
					);
				}
			}
			
			$once = TRUE;
		}
		
		$data = $this->get_all_member_data($member_id);
		
		if ( ! $data)
		{
			return '';
		}
		
		$field_id = $this->get_field_id($field_name);
		
		if ($field_id && ! isset($data['field_id_'.$field_id]))
		{
			return '';
		}
		else if ( ! $field_id)
		{
			$result = isset($data[$field_name]) ? $data[$field_name] : '';
			
			if ($result && in_array($field_name, array('entry_date', 'expiration_date', 'comment_expiration_date', 'last_visit')) && isset($params['format']))
			{
				$result = $this->localize->decode_date($params['format'], $result); 
			}
			
			return $result;
		}
		else if ($handler = $this->api_channel_fields->setup_handler($field_id, TRUE))
		{
			$handler->field_name = $field_name;
			
			$this->api_channel_fields->apply('_init', array(array('row' => $data)));
			
			$var_data = $this->api_channel_fields->apply('pre_process', array($data['field_id_'.$field_id]));
			
			return $this->api_channel_fields->apply($method, array($var_data, $params, $tagdata));
		}
		
		return $data['field_id_'.$field_id];
	}
	
	public function get_channel_info($key = FALSE)
	{
		static $cache;
		
		if (is_null($cache))
		{
			$cache = FALSE;
			
			if ($this->channel_id())
			{
				$query = $this->db->select('channels.channel_name, channels.site_id, sites.site_name, channels.deft_status')
						  ->join('sites', 'channels.site_id = sites.site_id')
						  ->where('channels.channel_id', $this->channel_id())
						  ->get('channels');
				
				$cache = $query->row_array();
				
				$query->free_result();
			}
		}
		
		if ($key === FALSE)
		{
			return $cache;
		}
		
		return (isset($cache[$key])) ? $cache[$key] : FALSE;
	}

	public function get_profile_id($member_id)
	{
		static $cache;
		
		if (isset($cache[$member_id]))
		{
			return $cache[$member_id];
		}
		
		if ( ! $this->channel_id())
		{
			return FALSE;
		}
		
		if ($member_id === $this->session->userdata('member_id') && $this->input->cookie('active_profile') && $this->settings('allow_multiple_profiles'))
		{
			if ($this->validate_entry_id($this->input->cookie('active_profile')))
			{
				$this->set_active_profile($this->input->cookie('active_profile'));
				
				if ( ! $this->input->cookie('active_profile'))
				{
					return FALSE;
				}
				
				return $cache[$member_id] = $this->input->cookie('active_profile');
			}
			else
			{
				$this->unset_active_profile();
			}
		}
		
		// not active record on purpose
		$query = $this->db->query("SELECT `entry_id`
					  FROM ".$this->db->dbprefix('channel_titles')."
					  WHERE `author_id` = ".$this->db->escape($member_id)."
					  AND `channel_id` = ".$this->db->escape($this->channel_id())."
					  LIMIT 1");
		
		if ($query->num_rows() > 0)
		{
			$entry_id = $query->row('entry_id');
			
			$query->free_result();
			
			return $cache[$member_id] = $entry_id;
		}

		//create the entry if it doesn't exist
		$this->load->model('member_model');

		$query = $this->member_model->get_member_data($member_id, array('username', 'screen_name', 'email'));
		
		$cache[$member_id] = ($query->num_rows() > 0) ? $this->create_profile($query->row_array(), $member_id) : FALSE;
		
		$query->free_result();
		
		return $cache[$member_id];
	}
	
	public function member_has_entry($member_id = FALSE)
	{
		if ( ! $this->channel_id())
		{
			return FALSE;
		}
		
		if ($member_id === FALSE)
		{
			$member_id = $this->session->userdata('member_id');
		}
		
		$this->db->where('author_id', $member_id)
			 ->where('channel_id', $this->channel_id());
		
		return (bool) $this->db->count_all_results('channel_titles');
	}
	
	protected function load_validate($data)
	{
		if (isset(get_instance()->validate))
		{
			$this->validate->errors = array();
			
			$this->validate->log_msg = array();
			
			$this->validate->__construct($data);
		}
		else
		{
			$this->load->library('validate', $data);
		}
	}
	
	/**
	 * Sets the active_profile cookie, which is the entry_id of the selected active profile
	 *
	 * Only necessary when allow_multiple_profiles is on
	 * 
	 * @param string|int $entry_id
	 * 
	 * @return bool
	 */
	public function set_active_profile($entry_id)
	{
		if ( ! $this->channel_id() || ! $this->settings('allow_multiple_profiles'))
		{
			return FALSE;
		}
		
		//check that this entry_id is legit
		$this->db->where('entry_id', $entry_id)
			 ->where('channel_id', $this->channel_id())
			 ->where('author_id', $this->session->userdata('member_id'));
		
		if ($this->db->count_all_results('channel_titles') === 0)
		{
			return FALSE;
		}
		
		$this->functions->set_cookie('active_profile', $entry_id, PHP_INT_MAX);
		
		return TRUE;
	}
	
	public function unset_active_profile()
	{
		$key = ($this->config->item('cookie_prefix')) ? $this->config->item('cookie_prefix').'_'.'active_profile' : 'active_profile';
		
		unset($_COOKIE[$key]);
		
		$this->functions->set_cookie('active_profile', NULL, -100);
	}
	
	public function get_active_profile()
	{
		return $this->input->cookie('active_profile');
	}
	
	public function cancel_registration($member_id, $entry_id = FALSE)
	{
		$this->load->model('member_model');
		
		$this->member_model->delete_member($member_id);
		
		if ($entry_id)
		{
			$this->db->delete('channel_titles', array('entry_id' => $entry_id));
			$this->db->delete('channel_data', array('entry_id' => $entry_id));
		}
		
		$this->db->delete('online_users', array('member_id' => $member_id));
	}
	
	public function get_custom_fields()
	{
		static $custom_fields;
		
		if ( ! $channel_id = $this->channel_id())
		{
			return array();
		}
		
		if (is_null($custom_fields))
		{
			$query = $this->db->where('channel_id', $channel_id)
					  ->join('field_groups', 'field_groups.group_id = channel_fields.group_id')
					  ->join('channels', 'channels.field_group = field_groups.group_id')
					  ->select('channel_fields.*')
					  ->get('channel_fields');
			
			$custom_fields = array();
			
			foreach ($query->result_array() as $row)
			{
				$custom_fields[$row['field_id']] = $row;
			}
		}
		
		return $custom_fields;
	}
	
	public function get_field_id($field_name)
	{
		static $map;
		
		if (isset($map[$field_name]))
		{
			return $map[$field_name];
		}
		
		$custom_fields = $this->get_custom_fields();
		
		foreach ($custom_fields as $field_id => $field)
		{
			if ($field_name === $field['field_name'])
			{
				return $map[$field_name] = $field_id;
			}
		}
		
		return FALSE;
	}
	
	public function get_all_member_data($member_id)
	{
		$member_data = $this->get_member_data($member_id);

		if ($member_data === FALSE)
		{
			return array();
		}

		$profile_data = $this->get_profile_data($member_id);

		if ($profile_data === FALSE)
		{
			return $member_data;
		}

		return array_merge($member_data, $profile_data);
	}
	
	public function get_profile_data($member_id, $key = FALSE)
	{
		static $cache;
		
		if ( ! $member_id || ! $channel_id = $this->channel_id())
		{
			return FALSE;
		}
		
		if ( ! isset($cache[$member_id]))
		{
			$custom_fields = $this->get_custom_fields();
			
			foreach ($custom_fields as $field_id => $field)
			{
				$this->db->select('channel_data.field_id_'.$field_id);
				$this->db->select('channel_data.field_ft_'.$field_id);
				$this->db->select('channel_data.field_id_'.$field_id.' AS `'.$field['field_name'].'`');
			}
			
			$this->db->select('channel_titles.*, channels.*');
			
			$query = $this->db->join('channel_data', 'channel_data.entry_id = channel_titles.entry_id')
					  ->join('channels', 'channel_titles.channel_id = channels.channel_id')
					  ->where('author_id', $member_id)
					  ->where('channel_titles.channel_id', $channel_id)
					  ->order_by('entry_date', 'asc')
					  ->limit(1)
					  ->get('channel_titles');
			
			$cache[$member_id] = ($query->num_rows() === 0) ? FALSE : $query->row_array();
			
			$query->free_result();
		}
		
		if ($key !== FALSE)
		{
			return $cache[$member_id][$key] ? $cache[$member_id][$key] : FALSE;
		}
		
		return $cache[$member_id];
	}
	
	/**
	 * Only use after you've validated
	 *
	 * @param int|string $member_id
	 * @param array $validated_data data to update: username, screen name, email and password
	 * 
	 * @return void
	 */
	public function update_member($member_id, $validated_data)
	{
		if (isset($validated_data['screen_name']))
		{
			if ($this->config->item('forum_is_installed') === 'y')
			{
				$this->db->update('forums', array('forum_last_post_author' => $validated_data['screen_name']), array('forum_last_post_author_id' => $member_id));
				$this->db->update('forum_moderators', array('mod_member_name' => $validated_data['screen_name']), array('mod_member_id' => $member_id));
			}
			
			if ($this->db->table_exists('comments'))
			{
				$this->db->update('comments', array('name' => $validated_data['screen_name']), array('author_id' => $member_id));
			}

			$this->session->userdata['screen_name'] = stripslashes($validated_data['screen_name']);
		}

		if (isset($validated_data['email']))
		{
			if ($this->db->table_exists('comments'))
			{
				$this->db->update('comments', array('email' => $validated_data['email']), array('author_id' => $member_id));
			}

		}

		if (isset($validated_data['password']))
		{
			$this->load->library('auth');
			
			$this->auth->update_password($member_id, $validated_data['password']);
			
			unset($validated_data['password']);
		}
		
		if ($validated_data)
		{
			$this->db->update('members', $validated_data, array('member_id' => $member_id));
		}
	}
	
	/**
	 * Update a member's core data: username, screen name, email and password
	 * 
	 * @param unknown $data Description
	 * 
	 * @return Type    Description
	 */
	public function validate_member_data($member_id, $data)
	{
		if ($this->blacklist->blacklisted === 'y' && $this->blacklist->whitelisted === 'n')
		{
			$this->add_error(lang('not_authorized'));
			
			return FALSE;
		}
		
		$update = array();
		
		$member_data = $this->get_member_data($member_id);

		if ( ! $member_data)
		{
			$this->add_error('Invalid member');
			
			return FALSE;
		}
		
		$admin_groups = (array) $this->settings('can_admin_members'); 
		
		if ($member_id != $this->session->userdata('member_id') && (! in_array($this->session->userdata('group_id'), $admin_groups)  && $this->session->userdata('can_admin_members') !== 'y'))
		{
			$this->add_error(lang('not_authorized'));
			
			return FALSE;
		}
		
		$validate = array(
			'val_type' => 'update',
			'member_id' => $member_id,
			'fetch_lang' => TRUE,
			'require_cpw' => FALSE,
			'enable_log' => FALSE,
			'username' => $member_data['username'],
			'screen_name' => $member_data['screen_name'],
			'email' => $member_data['email'],
			'cur_username' => $member_data['username'] ,
			'cur_screen_name' => $member_data['screen_name'],
			'cur_email' => $member_data['email'],
			'password' => '',
			'password_confirm' => '',
			'cur_password' => '',
		);

		if (isset($data['username']) && $this->config->item('allow_username_change') === 'y' && $data['username'] !== $member_data['username'])
		{
			$validate['username'] = $update['username'] = $data['username'];
		}
		
		if (isset($data['screen_name']))
		{
			//this means it's blank, use the username
			if ($data['screen_name'])
			{
				$update['screen_name'] = $data['screen_name'];
			}
			else if (isset($update['username']))
			{
				$validate['screen_name'] = $update['screen_name'] = $update['username'];
			}
			
			if ($update['screen_name'] === $member_data['screen_name'])
			{
				unset($update['screen_name']);
			}
		}
		
		if ( ! empty($data['password']))
		{
			$validate['password'] = $update['password'] = $data['password'];
			
			$validate['password_confirm'] = isset($data['password_confirm']) ? $data['password_confirm'] : '';
		}

		if (isset($data['current_password']))
		{
			$data['cur_password'] = $data['current_password'];
		}
		
		if (isset($data['email']) && $data['email'] !== $member_data['email'])
		{
			$validate['email'] = $update['email'] = $data['email'];
		}
		
		if (isset($data['current_password']))
		{
			$validate['cur_password'] = $data['current_password'];
		}

		$this->load_validate($validate);

		if (isset($update['screen_name']))
		{
			$this->validate->validate_screen_name();
		}

		if (isset($update['username']))
		{
			$this->validate->validate_username();
		}

		if (isset($update['password']))
		{
			$this->validate->validate_password();
		}

		if (isset($update['email']))
		{
			$this->validate->validate_email();
		}
		
		if ( ! empty($update) && $this->settings('require_current_password'))
		{
			$this->validate->password_safety_check();
		}

		if (count($this->validate->errors) > 0)
		{
			$this->add_error($this->validate->errors);
			
			return FALSE;
		}
		
		return $update;
	}
	
	public function update_native_profile($member_id, $data, $xss_clean = FALSE)
	{
		$update = array();
		
		foreach (self::$native_editable_fields as $field)
		{
			if (isset($data[$field]))
			{
				$update[$field] = ($xss_clean) ? $this->security->xss_clean($data[$field]) : $data[$field];
			}
		}
		
		if ($update)
		{
			$this->db->update('members', $update, array('member_id' => $member_id));
		}
		
		$update = array();
		
		$this->load->model('member_model');
		
		$query = $this->member_model->get_all_member_fields(array(), FALSE);
		
		if ($query->num_rows() > 0)
		{
			$update = array();
			
			foreach ($query->result() as $row)
			{
				if (isset($data['m_field_id_'.$row->m_field_id]))
				{
					$update['m_field_id_'.$row->m_field_id] = ($xss_clean) ? $this->security->xss_clean($data['m_field_id_'.$row->m_field_id]) : $data['m_field_id_'.$row->m_field_id];
				}
				else if (isset($data[$row->m_field_name]))
				{
					$update['m_field_id_'.$row->m_field_id] = ($xss_clean) ? $this->security->xss_clean($data[$row->m_field_name]) : $data[$row->m_field_name];
				}
			}
			
			if ($update)
			{
				$this->db->update('member_data', $update, array('member_id' => $member_id));
			}
		}
		
		$query->free_result();
	}
	
	public function oldest_superadmin()
	{
		$query = $this->db->select('member_id')
				  ->where('group_id', 1)
				  ->order_by('member_id', 'asc')
				  ->limit(1)
				  ->get('members');
		
		$member_id = $query->row('member_id');
		
		$query->free_result();
		
		return $member_id;
	}
	
	public function send_email($email, $template, $variables = array())
	{
		$this->load->library('template', NULL, 'TMPL');
		
		$this->load->helper('text');
		
		$this->load->library('email');
		
		$variables['site_name'] = $this->config->item('site_name');
		
		$variables['site_url'] = $this->config->item('site_url');
		
		$template_data = $this->functions->fetch_email_template($template);
		
		$subject = $this->TMPL->parse_variables_row($template_data['title'], $variables);
		
		$message = entities_to_ascii($this->TMPL->parse_variables_row($template_data['data'], $variables));
		
		$this->email->clear(); 
		$this->email->initialize(array('mailtype' => "text", 'validate' => TRUE, 'wordwrap' => TRUE));

		$this->email->from($this->config->item('webmaster_email'), $this->config->item('webmaster_name'))
				   ->to($email)
				   ->subject($subject)
				   ->message($message);

		$send = $this->email->send();
		$this->email->clear(); 
 
		return $send;
	}
	
	/**
	 * Build an action URL
	 * 
	 * @param string $action            the name of the module method, without the _action suffix
	 * @param array $params            array to be added to the url query string
	 * 
	 * @return string
	 */
	public function build_action_url($action, $params = array())
	{	
		$params = array('ACT' => $this->functions->insert_action_ids($this->functions->fetch_action_id('Profile', $action.'_action'))) + $params;
		
		if ( ! isset($params['return']) && isset(get_instance()->TMPL))
		{
			$params['return'] = $this->TMPL->fetch_param('return', $this->uri->uri_string());
		}
		if ( $this->config->item('secure_forms') === 'y')
		{
			$params['XID'] = $this->functions->add_form_security_hash('{XID_HASH}');
		}
		
		return $this->functions->fetch_site_index(0, 0).QUERY_MARKER.http_build_query($params, NULL, '&amp;');
	}
	
	/**
	 * show user error
	 *
	 * hijacked so we can store errors from Member module,
	 * rather than showing them immediately
	 * 
	 * @param string $data
	 * 
	 * @return void
	 */
	public function show_user_error($type, $errors)
	{
		//this is used to convert member errors into inline errors
		$error_langs = array(
			'current_password' => array(
				lang('missing_current_password'),
				lang('invalid_password'),
			),
			'username' => array(
				lang('missing_username'),
				lang('invalid_characters_in_username'),
				lang('username_too_short'),// %x
				//lang('username_password_too_long'),
				lang('username_taken'),
			),
			'screen_name' => array(
				lang('disallowed_screen_chars'),
				lang('screen_name_taken'),
			),
			'password_confirm' => array(
				lang('missmatched_passwords'),
			),
			'password' => array(
				lang('missing_password'),
				lang('password_too_short'),
				//lang('username_password_too_long'),
				lang('password_based_on_username'),
				lang('not_secure_password'),
				lang('password_in_dictionary'),
			),
			'email_confirm' => array(
				lang('mbr_emails_not_match'),
			),
			'email' => array(
				lang('missing_email'),
				lang('invalid_email_address'),
				lang('email_taken'),
			),
			'captcha' => array(
				lang('captcha_required'),
				lang('captcha_incorrect'),
			),
			'accept_terms' => array(
				lang('mbr_terms_of_service_required'),
			),
		);
		
		$ignore = array(
			lang('username_password_too_long'),//ignored because we inject more specific errors earlier on
		);
		
		if ( ! is_array($errors))
		{
			$errors = array($errors);
		}
		
		foreach ($errors as $i => $error)
		{
			if (in_array($error, $ignore))
			{
				continue;
			}
			
			foreach ($error_langs as $key => $langs)
			{
				foreach ($langs as $lang)
				{
					if (strpos($lang, '%x') !== FALSE)
					{
						if (preg_match('#^'.str_replace('%x', '\d+', preg_quote($lang)).'$#', $error))
						{
							$this->add_error($key, $error);
							
							continue 3;
						}
					}
					else
					{
						if ($error === $lang)
						{
							$this->add_error($key, $error);
							
							continue 3;
						}
					}
				}
			}
			
			$this->add_error($error);
		}
	}

	/**
	 * show message
	 *
	 * hijacked so we can suppress messages from Member module,
	 * rather than showing them immediately
	 * 
	 * @param string $data
	 * 
	 * @return void
	 */
	public function show_message($data)
	{
		if (isset($data['heading']) && isset($data['content']) && $data['heading'] === lang('error'))
		{
			$this->add_error($data['content']);
		}
	}
}

/* End of file profile_model.php */
/* Location: ./system/expressionengine/third_party/profile/models/profile_model.php */