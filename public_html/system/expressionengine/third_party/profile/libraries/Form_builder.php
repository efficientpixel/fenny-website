<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! class_exists('Form_builder')) :

/**
 * Form Builder
 *
 * Quickly build EE forms and manage the corresponding action
 */
class Form_builder
{
	protected $classname;
	protected $method;
	protected $action = '';
	protected $form_data = array(
		'error_handling',
		'return',
		'secure_return',
	);
	protected $values = array();
	protected $hidden = array();
	protected $attributes = array('id', 'class', 'name', 'onsubmit', 'enctype');
	protected $encoded_bools = array(
		//'show_errors' => array('ERR', TRUE),
	);
	protected $encoded_form_data = array(
		//'required' => 'REQ',
	);
	protected $encoded_numbers = array();
	protected $content = '';
	protected $array_form_data = array();
	protected $encoded_array_form_data = array();
	protected $secure_action = FALSE;
	
	protected $errors = array();
	protected $success_callback;
	protected $error_callback;
	protected $show_errors = TRUE;
	protected $error_header = FALSE;
	protected $return;
	protected $captcha = FALSE;
	
	protected $required = array();
	protected $rules;
	protected $options = array();
	
	protected $require_rules = TRUE;
	protected $require_form_hash = TRUE;
	protected $require_errors = TRUE;
	
	protected $global_errors;
	protected $global_form_variables;
	
	//keep this as the last property, please.
	protected $params = array();
	
	/**
	 * Constructor
	 * 
	 * @param array $params
	 * 
	 * @return void
	 */
	public function __construct($params = array())
	{
		$this->EE =& get_instance();
		$this->EE->load->library(array('encrypt', 'form_validation'));
		$this->reset($params);
	}
	
	public function set_errors(array $errors)
	{
		if ($this->EE->input->post('FRM'))
		{
			$this->global_errors[$this->EE->input->post('FRM')] = $errors;
		}

		return $this;
	}
	
	public function add_form_variable($key, $value = FALSE)
	{
		if ($hash = $this->EE->input->post('FRM'))
		{
			$variables = (is_array($key)) ? $key : array($key => $value);
			
			foreach ($variables as $key => $value)
			{
				$this->global_form_variables[$hash][$key] = (string) $value;
			}
		}
		
		return $this;
	}
	
	public function clear_errors()
	{
		return $this->set_errors(array());
	}
	
	public function set_success_callback($callback)
	{
		$this->success_callback = $callback;
		return $this;
	}
	
	public function set_error_callback($callback)
	{
		$this->error_callback = $callback;
		return $this;
	}
	
	public function set_rules($rules)
	{
		//@TODO
		return $this;
	}
	
	public function set_require_rules($require_rules = TRUE)
	{
		$this->require_rules = (bool) $require_rules;
		
		return $this;
	}
	
	public function set_require_form_hash($require_form_hash = TRUE)
	{
		$this->require_form_hash = (bool) $require_form_hash;
		
		return $this;
	}
	
	public function set_require_errors($require_errors = TRUE)
	{
		$this->require_errors = (bool) $require_errors;
		
		return $this;
	}
	
	public function set_required($required)
	{
		if (is_array($required))
		{
			$this->required = $required;
		}
		
		return $this;
	}
	
	public function set_return($return)
	{
		$this->return = $return;
		return $this;
	}
	
	public function set_show_errors($show_errors = TRUE)
	{
		$this->show_errors = $show_errors;
		return $this;
	}
	
	public function set_error_header($error_header)
	{
		$this->error_header = $error_header;
		return $this;
	}

	protected function set_global_error($value, $key = NULL)
	{
		$hash = $this->EE->input->post('FRM');

		if (is_null($key))
		{
			$this->global_errors[$hash][] = $value;
		}
		else
		{
			$this->global_errors[$hash][$key] = $value;
		}
	}

	public function add_error($key, $value = NULL)
	{
		if (is_array($key))
		{
			foreach ($key as $k => $v)
			{
				if (is_numeric($k))
				{
					$this->set_global_error($v);
				}
				else
				{
					$this->set_global_error($v, $k);
				}
			}
		}
		else if ($value !== NULL)
		{
			$this->set_global_error($value, $key);
		}
		else
		{
			$this->set_global_error($key);
		}
		
		return $this;
	}
	
	public function set_form_data($data)
	{
		if (is_array($data))
		{
			$this->form_data = array_merge($this->form_data, $data);
		}
		else
		{
			$this->form_data[] = $data;
		}
		
		return $this;
	}
	
	public function set_value($data)
	{
		if ( ! is_array($data))
		{
			$data = array($data);
		}
		
		foreach ($data as $key)
		{
			if (is_array($this->EE->input->post($key)))
			{
				foreach ($this->EE->input->post($key) as $k => $v)
				{
					$_key = "$key[$k]";
					
					if ( ! isset($this->EE->form_validation->_field_data[$_key]))
					{
						$this->EE->form_validation->set_rules($_key, '', '');
					}
					
					$this->EE->form_validation->_field_data[$_key]['postdata'] = $v;
					
					$this->add_form_variable("$key:$k", $this->EE->form_validation->set_value($_key));
				}
			}
			else
			{
				if ( ! isset($this->EE->form_validation->_field_data[$key]))
				{
					$this->EE->form_validation->set_rules($key, '', '');
				}
				
				$this->EE->form_validation->_field_data[$key]['postdata'] = $this->EE->input->post($key);
				
				$this->add_form_variable($key, $this->EE->form_validation->set_value($key));
			}
		}
		/*
		if (is_array($data))
		{
			$this->values = array_merge($this->values, $data);
		}
		else
		{
			$this->values[] = $data;
		}
		*/
		
		return $this;
	}
	
	public function set_array_form_data($data)
	{
		if (is_array($data))
		{
			$this->array_form_data = $data;
		}
		else
		{
			$this->array_form_data[] = $data;
		}
		
		return $this;
	}
	public function set_encoded_array_form_data($data)
	{
		if (is_array($data))
		{
			$this->encoded_array_form_data = $data;
		}
		else
		{
			$this->encoded_array_form_data[] = $data;
		}
		
		return $this;
	}
	
	public function set_encoded_form_data($key, $value = FALSE)
	{
		if (is_array($key))
		{
			$this->encoded_form_data = array_merge($this->encoded_form_data, $key);
		}
		else
		{
			$this->encoded_form_data[$key] = $value;
		}
		
		return $this;
	}
	
	public function set_encoded_bools($key, $value = FALSE)
	{
		if (is_array($key))
		{
			$this->encoded_bools = array_merge($this->encoded_bools, $key);
		}
		else
		{
			$this->encoded_bools[$key] = $value;
		}
		
		return $this;
	}
	
	public function set_encoded_numbers($key, $value = FALSE)
	{
		if (is_array($key))
		{
			$this->encoded_numbers = $key;
		}
		else
		{
			$this->encoded_numbers[$key] = $value;
		}
		
		return $this;
	}
	
	public function set_options($key, $options = array())
	{
		$this->options[$key] = $options;
		
		return $this;
	}
	
	public function set_classname($classname)
	{
		if ($classname)
		{
			$this->classname = $classname;
		}
		
		return $this;
	}
	
	public function set_method($method)
	{
		if ($method)
		{
			$this->method = $method;
		}
		
		return $this;
	}
	
	public function set_action($action)
	{
		$this->action = $action;
		
		return $this;
	}
	
	public function set_attributes($key, $value = NULL)
	{
		if (is_array($key))
		{
			foreach ($key as $k => $v)
			{
				$this->set_attributes($k, $v);
			}
		}
		else
		{
			$this->attributes[$key] = $value;
		}
		
		return $this;
	}
	
	public function set_hidden($key, $value = NULL)
	{
		if (is_array($key))
		{
			foreach ($key as $k => $v)
			{
				$this->set_hidden($k, $v);
			}
		}
		else
		{
			$this->hidden[$key] = $value;
		}
		
		return $this;
	}
	
	public function initialize($params = array())
	{
		$this->reset();
		
		foreach (get_class_vars(__CLASS__) as $key => $value)
		{
			if (isset($params[$key]))
			{
				if (method_exists($this, "set_$key"))
				{
					$this->{"set_$key"}($params[$key]);
				}
				else
				{
					$this->{$key} = $params[$key];
				}
			}
		}
		
		return $this;
	}
	
	public function reset($reset = array())
	{
		if (empty($reset))
		{
			$reset = array_keys(get_class_vars(__CLASS__));
		}
		
		foreach (get_class_vars(__CLASS__) as $key => $value)
		{
			if (substr($key, 0, 7) !== 'global_' && in_array($key, $reset))
			{
				$this->{$key} = $value;
			}
		}
		
		return $this;
	}
	
	public function set_secure_action($secure_action = TRUE)
	{
		$this->secure_action = $secure_action;
		
		return $this;
	}
	
	public function set_params($params)
	{
		if ( ! is_array($params))
		{
			$params = array();
		}
		
		//set ALL encoded bools
		foreach ($this->encoded_bools as $key => $value)
		{
			$default = FALSE;
			
			if (is_array($value))
			{
				$default = (bool) @$value[1];
			}
			
			if ( ! isset($params[$key]))
			{
				$params[$key] = $default;
			}
		}
		
		$form_params = array(
			'required' => '',
			'rules' => array(),
			'show_errors' => 'yes',
		);
		
		foreach ($params as $param => $value)
		{
			switch($param)
			{
				case 'required':
					$form_params['required'] = $value;
					break;
				case 'show_errors':
					$form_params['show_errors'] = $this->create_bool_string($this->bool_string($value));
					break;
				case (strncmp($param, 'rules:', 6) === 0):
					$form_params['rules'][substr($param, 6)] = $value;
					break;
				case (in_array($param, $this->attributes)):
					$this->set_attributes($param, $value);
					break;
				case (in_array($param, $this->form_data)):
					$this->set_hidden($param, $value);
					break;
				case (array_key_exists($param, $this->encoded_form_data)):
					$this->set_hidden($this->encoded_form_data[$param], $this->EE->encrypt->encode($value));
					break;
				case (array_key_exists($param, $this->encoded_bools)):
					$key = (is_array($this->encoded_bools[$param])) ? $this->encoded_bools[$param][0] : $this->encoded_bools[$param];
					$this->set_hidden($key, $this->EE->encrypt->encode($this->create_bool_string($this->bool_string($value))));
					break;
				case (array_key_exists($param, $this->encoded_numbers)):
					$this->set_hidden($this->encoded_numbers[$param], $this->EE->encrypt->encode($this->sanitize_number($value)));
					break;
				case (strncmp($param, 'options:', 8) === 0):
					$this->set_options(substr($param, 8), $this->param_string_to_array($value));
				case (strpos($param, ':') !== FALSE):
					foreach ($this->array_form_data as $name)
					{
						if (preg_match("/^$name:(.+)$/", $param, $match))
						{
							$this->set_hidden($name.'['.$match[1].']', $value);
						}
					}
					foreach ($this->encoded_array_form_data as $k => $name)
					{
						if (!isset($enc_arr))
						{
							$enc_arr = array(); 
							$enc_name = $name; 
						}
						if (preg_match("/^$k:(.+)$/", $param, $match))
						{
							$enc_arr[$match[1]]= $value; 
						}
					}
					if (isset($enc_arr) && isset($enc_name))
					{
						$this->set_hidden($enc_name, $this->EE->encrypt->encode(base64_encode(serialize($enc_arr))));
					}
					break;
				case 'action':
					$this->set_action($value);
					break;
				case 'secure_action':
					$this->set_secure_action($this->bool_string($value));					
					break;
			}
		}
		
		//process required into rules
		if ($form_params['required'])
		{
			$this->required = array_merge($this->required, explode('|', $form_params['required']));
			
			foreach ($this->required as $key)
			{
				if (isset($form_params['rules'][$key]))
				{
					if (strpos($form_params['rules'][$key], 'required') === FALSE)
					{
						$form_params['rules'][$key] = 'required|'.$form_params['rules'][$key];
					}
				}
				else
				{
					$form_params['rules'][$key] = 'required';
				}
			}
		}
		
		$this->set_hidden('ERR', $this->EE->encrypt->encode($form_params['show_errors']));
		$this->set_hidden('RLS', $this->EE->encrypt->encode(serialize($form_params['rules'])));
		
		return $this;
	}
	
	public function required_keys()
	{
		$required_keys = array();
		
		if ($this->require_rules)
		{
			$required_keys[] = 'RLS';
		}
		
		if ($this->require_form_hash)
		{
			$required_keys[] = 'FRM';
		}
		
		if ($this->require_errors)
		{
			$required_keys[] = 'ERR';
		}
		
		return $required_keys;
	}
	
	public function set_content($content)
	{
		$this->content = $content;
		
		return $this;
	}
	
	public function set_captcha($captcha = FALSE)
	{
		$this->captcha = (bool) $captcha;
		
		return $this;
	}

	public function errors($hash = NULL)
	{
		if (is_null($hash))
		{
			$hash = $this->EE->input->post('FRM');
		}
		
		//return $this->errors;
		return (isset($this->global_errors[$hash])) ? $this->global_errors[$hash] : array();
	}

	protected function build_form_hash()
	{
		return md5($this->EE->TMPL->tagproper);
	}

	public function form()
	{
		/**
		 * ex.
		 *
		 * function form_builder_form_start($module, $method)
		 * {
		 * 	if ($module === 'cartthrob' && $method === 'add_to_cart_form')
		 * 	{
		 * 		$this->EE->form_builder->set_hidden('ABC', '123');
		 * 	}
		 * }
		 */
		if ($this->EE->extensions->active_hook('form_builder_form_start'))
		{
			$tagparts = $this->EE->TMPL->tagparts;
			
			$module = array_shift($tagparts);
			
			$method = array_shift($tagparts);
			
			$this->EE->extensions->call('form_builder_form_start', $module, $method);
		}
		
		if ( ! $this->action)
		{
			// .283 Changed from using config->site_url because it uses CI's base url, making
			// it impossible to change the site's url from the CP
			$this->action = $this->EE->functions->create_url($this->EE->uri->uri_string());
		}
		
		$this->EE->load->helper('form');
		
		if ($this->is_secure())
		{
			$this->secure_action = TRUE;
		}
		
		if ($this->secure_action)
		{
			$this->action = $this->secure_url($this->action);
		}
		
		$data = $this->attributes;
		
		$data['action'] = $this->action;
		
		if ( ! empty($this->classname) && ! empty($this->method))
		{
			$data['hidden_fields']['ACT'] = $this->EE->functions->fetch_action_id($this->classname, $this->method);
		}
		
		$data['hidden_fields']['RET'] = $this->EE->functions->fetch_current_uri();
		$data['hidden_fields']['URI'] = $this->EE->uri->uri_string();
		$data['hidden_fields']['FRM'] = $this->build_form_hash();
		
		if ( ! isset($this->hidden['RLS']))
		{
			$this->set_hidden('RLS', $this->EE->encrypt->encode('a:0:{}'));
		}
		
		$data['hidden_fields'] = array_merge($data['hidden_fields'], $this->hidden);
		
		$return = $this->EE->functions->form_declaration($data).$this->content.form_close();
	
		$this->reset();
		
		return $return;
	}
	
	public function action_complete($validate = FALSE, $secure_forms = TRUE)
	{
		$this->EE->load->library('javascript');
		
		//dumb stuff for ee2.1.3
		if ( ! isset($this->EE->security) || get_class($this->EE->security) !== 'EE_Security')
		{
			require_once APPPATH.'core/EE_Security.php';
			
			$this->EE->security = new EE_Security;
		}
		
		if ( ! $this->return)
		{
			$this->return = ($this->EE->input->get_post('return')) ? $this->EE->input->get_post('return', TRUE) : $this->EE->uri->uri_string();
		}
		
		$url = $this->parse_path($this->return);
		
		if ($this->is_secure() || $this->bool_string($this->EE->input->post('secure_return')))
		{
			$url = $this->secure_url($url);
		}
		
		$flashdata = array(
			'success' => ! $this->errors(),
			'errors' => $this->errors(),
			'return' => $url,
		);
		
		if (AJAX_REQUEST && $this->EE->config->item('secure_forms') === 'y')
		{
			$flashdata['XID'] = $this->EE->functions->add_form_security_hash('{XID_HASH}');
		}
		
		//temp. store the current value of end_script, in case this call is nested inside another hook's call
		$end_script = $this->EE->extensions->end_script;
		
		foreach ($flashdata as $key => $value)
		{
			$this->EE->session->set_flashdata($key, $value);
		}
		
		if ($this->EE->input->post('ERR'))
		{
			$this->set_show_errors($this->bool_string($this->EE->encrypt->decode($this->EE->input->post('ERR')), TRUE));
		}
		
		if ($this->errors())
		{
			$this->callback($this->error_callback);
			
			if ($this->show_errors && ! AJAX_REQUEST)
			{
				if ($this->EE->input->post('error_handling') === 'inline')
				{
					foreach ($this->values as $key)
					{
						$value = $this->EE->input->post($key);
						
						//custom_data[foo] => custom_data:foo
						if (is_array($value))
						{
							foreach ($value as $k => $v)
							{
								$this->add_form_variable($key.':'.$k, $v);
							}
						}
						else
						{
							$this->add_form_variable($key, $value);
						}
					}
					
					$method = (version_compare(APP_VER, '2.1.3', '>')) ? 'generate_page' : '_generate_page';
					
					$this->EE->load->add_package_path(PATH_THIRD.'cartthrob/');
					
					$this->EE->core->$method();
					
					$this->EE->extensions->end_script = $end_script;
					
					return;
				}

				// if this is not loaded.... then the user_message template can not be output as part of show_error 2.6x
				// basically the exception class's show_error looks to see if TMPL is set... if not it outputs the general_error.php file... which we don't want. 
				if ( ! isset($this->EE->TMPL))
				{
					$this->EE->load->library('template', NULL, 'TMPL');
				}
				// since we'll be removing post in a minute, I'm creating temporary variables to store some stuff that would otherwise rely on post's existance
				$errors = $this->errors(); 
				$error_header = $this->error_header; 
				if (!empty($_POST))
				{
					unset($_POST); // we're unsetting post because show_error... a near useless function that is intended to replace show_user_error will otherwise insert a javascript back link which will then be replaced with [removed] link and will show some effed up code. show_message function of EE's output class basically has a bug. If that gets fixed, we can undo this so that the back link will be shown correctly. FOr now, removing $_POST will remove the bad back link. 
				}			
			  	return show_error( $errors, $status_code = 500,  $error_header);				

			}
		}
		/*
		if ($secure_forms &&  ! $this->EE->security->secure_forms_check($this->EE->input->post('XID')))
		{
			if($this->EE->input->post('RET'))
		{
			$this->EE->functions->redirect(stripslashes($this->EE->input->post('RET')));		
		}
		}
		*/
		if ( ! $this->errors())
		{
			$this->callback($this->success_callback);
		}
		
		//$url = $this->EE->functions->create_url($this->return);
		
		$this->EE->functions->redirect($url);
	}

	public function validate($action_complete_on_error = FALSE)
	{
		$this->EE->lang->loadfile('form_validation');
		
		foreach ($this->required_keys() as $key)
		{
			if ( ! $this->EE->input->post($key))
			{
				$this->add_error(sprintf(lang('required'), $key));
				
				return FALSE;
			}
		}
		
		if ( ! is_array($this->rules))//meaning, someone has already done this processing
		{
			//$this->rules = $this->process_rules($this->EE->input->post('rules'));
			$this->rules = $this->unserialize($this->EE->encrypt->decode($this->EE->input->post('RLS')), FALSE);
			
			//the unserialize failed, and we may be subject to tampering
			if ( ! is_array($this->rules) && in_array('RLS', $this->required_keys()))
			{
				$this->add_error(sprintf(lang('required'), 'RLS'));
				
				return FALSE;
			}
			
			foreach ($this->required as $field)
			{
				if ( ! isset($this->rules[$field]))
				{
					$this->rules[$field] = 'required';
				}
				else if (strpos($this->rules[$field], 'required') === FALSE)
				{
					$this->rules[$field] = 'required|'.$this->rules[$field];
				}
			}
		}
		
		if ( ! $this->rules && ! $this->captcha)
		{
			return TRUE;
		}
		
		foreach ($this->rules as $key => $rules)
		{
			// will convert item_options[item_option_1] to Item Options (Item Option 1) if validation_item_options_item_option_1 is not found in the language file. 
			if (preg_match('/^([a-z_]+)(\[\d+\])?\[(.*)\]$/', $key, $match))
			{
				$lang_key_base = 'validation_'. $match[1]; 
				$lang_key_full = $lang_key_base.'_'.$match[3];

				$main_language_line = $this->EE->lang->line($lang_key_base); 
				$full_language_line = $this->EE->lang->line($lang_key_full); 
				$sub_language_line = $this->EE->lang->line($match[3]); 
				
				// main language line does not exist
				// replace _ with spaces
				if ($main_language_line === $lang_key_base)
				{
					$main_language_line = ucwords(str_replace(array("validation_", "_"), " ", $main_language_line)); 
				}

				// sub language line does not exist
				// replace _ with spaces
				if ($sub_language_line === $match[3])
				{
					$sub_language_line = ucwords(str_replace(array("validation_", "_"), " ", $sub_language_line)); 
				}				
				// there is no full language line
				if ($label = $full_language_line === $lang_key_full)
				{
					$label = sprintf($main_language_line, $sub_language_line);
 				}
				// oh wow... there is a full language line. Let's use that
				else
				{
					$label = $full_language_line; 
				}
			}
			else if (preg_match('/^([a-z_]+)(:\d+)?:(.*)$/', $key ,$match))
			{
				$key = $match[1];
				
				if ($match[2])
				{
					$key .= '['.$match[2].']';
				}
				
				$key .= '['.$match[3].']';
				
				$lang_key = 'validation_'.$match[1].'_'.$match[3];
				
				if (($label = $this->EE->lang->line($lang_key)) === $lang_key)
				{
					$label = sprintf($this->EE->lang->line('validation_'.$match[1]), $match[3]);
				}
			}
			else
			{
				if (($label = $this->EE->lang->line('validation_'.$key)) === 'validation_'.$key)
				{
					$label = $key;
				}
			}
			
			$this->EE->form_validation->set_rules($key, $label, $rules);
		}
		
		if ($this->rules && ! $valid = $this->EE->form_validation->run())
		{
			$this->add_error($this->EE->form_validation->_error_array);
			
			return FALSE;
		}
		
		if ($this->captcha)
		{
			if ( ! $captcha = $this->EE->input->post('captcha', TRUE))
			{
				$this->add_error('captcha', lang('captcha_required'));
				
				return FALSE;
			}
			else
			{
				$this->EE->db->where('word', $captcha);
				$this->EE->db->where('ip_address', $this->EE->input->ip_address());
				$this->EE->db->where('date > ', '(UNIX_TIMESTAMP()-7200)', FALSE);
			    
				if ( ! $this->EE->db->count_all_results('captcha'))
				{
					$this->add_error('captcha', lang('captcha_incorrect'));
					
					return FALSE;
				}
				else
				{
					$this->EE->db->where('word', $captcha);
					$this->EE->db->where('ip_address', $this->EE->input->ip_address());
					$this->EE->db->where('date < ', '(UNIX_TIMESTAMP()-7200)', FALSE);
					
					$this->EE->db->delete('captcha');
				}
			}
		}
		
		return TRUE;
	}
	
	public function error_variables()
	{
		return $this->form_variables();
	}

	public function form_variables()
	{
		$hash = $this->build_form_hash();

		$variables = array(
			'errors_exist' => 0,
			'global_errors_exist' => 0,
			'field_errors_exist' => 0,
			'global_errors:count' => 0,
			'field_errors:count' => 0,
			'errors' => array(),
			'global_errors' => array(),
			'field_errors' => array(),
		);
		
		if (isset($this->global_form_variables[$hash]))
		{
			foreach ($this->global_form_variables[$hash] as $key => $value)
			{
				$variables[$key] = $this->EE->security->xss_clean($value);
			}
		}
		
		$total_results = count($this->errors($hash));
		
		if ($total_results > 0)
		{
			$count = 1;
			
			foreach ($this->errors($hash) as $key => $value)
			{
				$first_row = ($count === 1);
				
				$last_row = ($count === $total_results);
				
				$error = array(
					'error' => $value,
					'field' => $key,
					'global_error' => 0,
					'field_error' => 0,
					'error:count' => $count,
					'error:total_results' => $total_results,
					'first_row' => $first_row,
					'last_row' => $last_row,
					'first_error' => $first_row,
					'last_error' => $last_row,
				);
				
				if (is_int($key) || (function_exists('ctype_digit') && ctype_digit($key)))
				{
					$error['field'] = '';
					
					$error['global_error'] = '1';
					
					$variables['global_errors:count']++;
					
					$variables['global_errors_exist'] = 1;
					
					$variables['global_errors'][] = $error;
				}
				else
				{
					if (preg_match_all('/\[(.+?)\]/', $key, $matches))
					{
						$secondary_key = $key;
						
						foreach ($matches[0] as $i => $replace)
						{
							$secondary_key = str_replace($replace, ':'.$matches[1][$i], $key);
						}
						
						$variables['error:'.$secondary_key] = $value;
					}
					
					$error['field_error'] = '1';
					
					$variables['error:'.$key] = $value;
					
					$variables['field_errors_exist'] = 1;
					
					$variables['field_errors:count']++;
					
					$variables['field_errors'][] = $error;
				}
				
				$variables['errors'][] = $error;
				
				$count++;
			}
			
			$variables['errors_exist'] = '1';
		}
		else
		{
			if (preg_match_all('#{(global_|field_)?errors(.*?)}(.*){/\\1errors}#s', $this->EE->TMPL->tagdata, $matches))
			{
				foreach ($matches[0] as $i => $replace)
				{
					$variables[substr($replace, 1, -1)] = '';
				}
			}

			array_unshift($variables['errors'], array());
			array_unshift($variables['global_errors'], array());
			array_unshift($variables['field_errors'], array());
		}
		
		foreach ($this->EE->TMPL->var_single as $key)
		{
			if (strpos($key, 'error:') === 0 && ! isset($variables[$key]))
			{
				$variables[$key] = '';
			}
			else if (strncmp($key, 'encode ', 6) === 0)
			{
				$params = $this->EE->functions->assign_parameters(substr($key, 6));
				
				$variables[$key] = '';
				
				if (isset($params['name']))
				{
					//we just want the name
					if ( ! isset($params['value']))
					{
						$variables[$key] = $this->convert_input_name($params['name']);
					}
					else
					{
						$variables[$key] = $this->convert_input_value($params['name'], $params['value']);
					}
				}
			}
		}
		
		foreach ($this->options as $field_name => $options)
		{
			$field_name = 'options:'.$field_name;
			
			$variables[$field_name] = array();
			
			foreach ($options as $option_value => $option_name)
			{
				$variables[$field_name][] = array(
					'option_value' => $this->EE->encrypt->encode($option_value),
					'option_name' => $option_name,
				);
			}
		}
		
		if (preg_match_all('#{if captcha}(.*?){/if}#s', $this->EE->TMPL->tagdata, $matches))
		{
			foreach ($matches[0] as $i => $full_match)
			{
				if ($this->captcha)
				{
					$tagdata = $this->EE->TMPL->parse_variables_row($matches[1][$i], array(
						'captcha_word' => '',
						'captcha' => $this->EE->functions->create_captcha(),
					));
					
					$tagdata = $this->EE->TMPL->swap_var_single('captcha', $this->EE->functions->create_captcha(), $tagdata);
					
					$variables[substr($full_match, 1, -1)] = $tagdata;
				}
				else
				{
					$variables[substr($full_match, 1, -1)] = '';
				}
			}
		}
		
		return $variables;
	}
	
	protected function sanitize_number($number = NULL, $allow_negative = FALSE)
	{
		if (is_int($number) || is_float($number) || ctype_digit($number))
		{
			return $number;
		}

		if ( ! $number)
		{
			return 0;
		}

		$prefix = ($allow_negative && preg_match('/^-/', $number)) ? '-' : '';
		$number = preg_replace('/[^0-9\.]/', '', $number);

		// changed so that '' won't be returned
		if (is_numeric($number) || is_int($number) || is_float($number) || ctype_digit($number))
		{
			return $prefix.$number;
		}
		else
		{
			return 0; 
		}
	}
	
	protected function bool_string($string, $default = FALSE)
	{
		switch (strtolower($string))
		{
			case 'true':
			case 't':
			case 'yes':
			case 'y':
			case 'on':
			case '1':
				return TRUE;
				break;
			case 'false':
			case 'f':
			case 'no':
			case 'n':
			case 'off':
			case '0':
				return FALSE;
				break;
			default:
				return $default;
		}
	}
	
	// gives us a little more obscurity
	// for our encrypted boolean form values
	protected function create_bool_string($bool = FALSE)
	{
		switch(rand(1, 6))
		{
			case 1:
				$string = ($bool) ? 'true' : 'false';
				break;
			case 2:
				$string = ($bool) ? 't' : 'f';
				break;
			case 3:
				$string = ($bool) ? 'yes' : 'no';
				break;
			case 4:
				$string = ($bool) ? 'y' : 'n';
				break;
			case 5:
				$string = ($bool) ? 'on' : 'off';
				break;
			case 6:
				$string = ($bool) ? '1' : '0';
				break;
		}

		$output = '';

		foreach (str_split($string) as $char)
		{
			$output .= (rand(0,1)) ? $char : strtoupper($char);
		}

		return $output;
	}
	
	protected function unserialize($data, $force_array = TRUE)
	{
		if (is_array($data))
		{
			return $data;
		}
		
		if (FALSE === ($data = @unserialize($data)))
		{
			return ($force_array) ? array() : FALSE;
		}
		
		return $data;
	}
	
	protected function is_secure()
	{
		return (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on');
	}
	
	protected function secure_url($url, $domain = FALSE)
	{
		if ($domain)
		{
			$url = preg_replace('/(https?:\/\/)([^\/]+)(.*)/', '\\1'.$domain.'\\3', $url);
		}
		
		return str_replace('http://', 'https://', $url);
	}
	
	/**
	 * Callback caller
	 * 
	 * @param array|string|bool $callback    a function name, or an array($object, $method), or an array($object, $method, $arg1, $arg2, ...)
	 * 
	 * @return void
	 */
	protected function callback($callback)
	{
		if (is_array($callback) && ($count = count($callback)) > 1)
		{
			$args = NULL;
			
			if ($count > 2)
			{
				$args = $callback;
				
				$callback = array(array_shift($args), array_shift($args));
			}
			
			if (method_exists($callback[0], $callback[1]) && is_callable($callback))
			{
				if (is_null($args))
				{
					call_user_func($callback);
				}
				else
				{
					call_user_func_array($callback, $args);
				}
			}
		}
		else if (is_string($callback) && function_exists($callback))
		{
			$callback();
		}
	}
	
	private function convert_input_name($name)
	{
		foreach (array('form_data', 'encoded_form_data', 'encoded_numbers', 'encoded_bools') as $which)
		{
			foreach ($this->$which as $key => $alias)
			{
				if ($which === 'form_data')
				{
					$key = $alias;
				}
				
				if ($key === $name)
				{
					return $alias;
				}
			}
		}
		
		return '';
	}
	
	protected function convert_input_value($name, $value)
	{
		foreach (array('form_data', 'encoded_form_data', 'encoded_numbers', 'encoded_bools') as $which)
		{
			foreach ($this->$which as $key => $alias)
			{
				if ($key === $name)
				{
					switch($which)
					{
						case 'encoded_form_data':
							return $this->EE->encrypt->encode($value);
						case 'encoded_numbers':
							return $this->EE->encrypt->encode($this->sanitize_number($value, TRUE));
						case 'encoded_bools':
							return $this->EE->encrypt->encode($this->create_bool_string($this->bool_string($value)));
					}
					
					return $value;
				}
			}
		}
	}
	
	protected function parse_path($path)
	{
		if ( ! $path)
		{
			return '';
		}
		
		if (strpos($path, '{site_url}') !== FALSE)
		{
			$path = str_replace('{site_url}', $this->EE->functions->fetch_site_index(1), $path);
		}
		
		if (strpos($path, '{path=') !== FALSE)
		{
			$path = preg_replace_callback('/'.LD.'path=[\042\047]?(.*?)[\042\047]?'.RD.'/', array($this->EE->functions, 'create_url'), $path);
		}
		
		if ( ! preg_match("#^(http:\/\/|https:\/\/|www\.|[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})#i", $path))
		{
			if (strpos($path, '/') !== 0)
			{
				$path = $this->EE->functions->create_url($path);
			}
		}
		
		return $path;
	}
	
	protected function param_string_to_array($string)
	{
		$values = array();

		if ($string)
		{
			foreach (explode('|', $string) as $value)
			{
				if (strpos($value, ':') !== FALSE)
				{
					$value = explode(':', $value);

					$values[$value[0]] = $value[1];
				}
				else
				{
					$values[$value] = $value;
				}
			}
		}
		return $values;
	}
} // END class

endif; // class_exists close
