<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Author: Aaron Waldon (Causing Effect)
 * http://www.causingeffect.com
 *
 * License: MIT license.
 */

class In_sert
{
	/**
	 * Constructor
	 *
	 * @param string $settings array or empty string if none exist.
	 */
	public function __construct($settings = '')
	{
		$this->EE = get_instance();
		$this->settings = $settings;
	}

	/**
	 * Parses the given data for in:sert templates. Any found templates are saved as globals.
	 *
	 * @param $template_data
	 * @return string
	 */
	public function parse($template_data)
	{
		//make sure template files are enabled
		if ($this->EE->config->item('save_tmpl_files') !== 'y' && $this->EE->config->item('tmpl_file_basepath'))
		{
			return $template_data;
		}

		//parse embed variables
		if (@preg_match_all('/' . LD . 'in:sert:(.+?)' . RD . '/', $template_data, $matches))
		{
			foreach ($matches[1] as $template)
			{
				$global = '';

				if (!isset($this->EE->config->_global_vars['in:sert:' . $template]))
				{
					$pieces = explode('/', $template);
					$group_name = $pieces[0];
					$template_name = (isset($pieces[1])) ? $pieces[1] : 'index';

					//determine the path
					$path = rtrim($this->EE->config->item('tmpl_file_basepath'), '/') . '/';
					$path .= $this->EE->config->item('site_short_name') . '/' . $group_name . '.group/' . $template_name . '.html';

					//get the template contents
					if (file_exists($path))
					{
						$global = file_get_contents($path);

						//check if there was an error getting the file contents.
						if ($global === false)
						{
							$this->EE->config->_global_vars['in:sert:' . $template] = '';
						}
					}
					else
					{
						$this->EE->config->_global_vars['in:sert:' . $template] = '';
					}

					//strip comments and parse segment_x vars
					$global = preg_replace("/\{!--.*?--\}/s", '', $global);

					//swap config global vars
					if (strpos($global, '{') !== false) //if there are no curly brackets, no need to parse...
					{
						$global = $this->EE->TMPL->parse_variables_row($global, $this->EE->config->_global_vars);
					}

					//segment variables
					for ($i = 1; $i < 10; $i++)
					{
						$global = str_replace(LD . 'segment_' . $i . RD, $this->EE->uri->segment($i), $global);
					}

					//$global = $this->EE->TMPL->parse_globals($global);

					//save the variable
					$this->EE->config->_global_vars['in:sert:' . $template] = $global;
				}

				//replace the string
				$template_data = str_replace('in:sert:' . $template, $global, $template_data);
			}

			if (@preg_match('/' . LD . 'in:sert:(.+?)' . RD . '/', $template_data))
			{
				$template_data = $this->parse($template_data);
			}
		}

		return $template_data;
	}
}