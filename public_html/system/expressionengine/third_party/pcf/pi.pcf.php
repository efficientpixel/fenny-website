<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * Utils Plugin
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Plugin
 * @link		
 */

$plugin_info = array(
	'pi_name'		=> 'Pcf',
	'pi_version'	=> '1.1.1',
	'pi_author'		=> 'Ryan Blenis',
	'pi_author_url'	=> '',
	'pi_description'=> 'PCF Parser',
	'pi_usage'		=> Pcf::usage()
);


class Pcf {

	public $return_data;
	
	private $_current_component;
	private $_top_data;
	private $_materials;
	private $_tmp_arr;
	private $_my_data;
    
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
		$this->_tmp_arr = array();
		error_reporting(E_ALL & ~E_NOTICE);ini_set('display_errors', 1);
		$this->_my_data = array('endpoint1', 
								'endpoint1:x', 
								'endpoint1:y', 
								'endpoint1:z', 
								'endpoint2', 
								'endpoint2:x', 
								'endpoint2:y', 
								'endpoint2:z',
								'centrepoint', 
								'branch1point',
								'itemcode',
								'piping-spec',
								'date-dmy',
								'itemdesc',
								'descmod',
								'skey',
								'spoolidentifier',
								'itemattribute0',
								'revision',
								'project-identifier',
								'area',
								'fabitem'
								);
	}
	
	public function parse()
	{
		$t = $this->EE->TMPL->tagdata;
		$file = $this->EE->TMPL->fetch_param('url');
		$clear_cache = ($this->EE->TMPL->fetch_param('clear_cache') == 'y') ? TRUE : FALSE;
		
		$tagdata = '';
		
		$data = $this->_get_file($file, $clear_cache);
		$array_data = $this->_parse_data($data);
		$array_data = $this->_filter_non_components($array_data);
		//echo '<pre>';print_r($array_data);
		foreach ($array_data as $component)
		{
			$tr = $t;
			$this->_current_component = $component;
			$vars = array(
				'type' => $component['text']
			);
			
			foreach ($component['children'] as $c)
			{
				if (is_array($c))
				{
					$vars[$c['type']] = $c[$c['type']]['text']; 
					//echo '<pre>'; print_r($c);echo $c['type'].'</pre>';
					foreach ($c[$c['type']] as $ci => $ct)
					{
						$vars[$c['type'].':'.$ci] = $ct;
					}
				}
				else
				{
					
				}
			}
			
			$row = $this->EE->TMPL->parse_variables_row($tr, $vars);
			
			//clean up leftover rows
			if (preg_match_all("#".LD."(.*?)".RD."#", $row, $matches))
			{
				foreach ($matches[1] as $nn)
				{
					if (in_array($nn, $this->_my_data))
					{
						$row = $this->EE->TMPL->_parse_var_single($nn, '', $row);
					}
				}
			}
			
			$row = $this->_process_descmods($row);
			
			$row = preg_replace('"(\s+\r?\n){2,}"', "\r\n", $row);
			$tagdata .= $row;
		}
		
		$tagdata .= $this->_materials;
		$tagdata = $this->_top_data.$tagdata;
		
		return $this->return_data = $tagdata;
	}
	
	public function _process_descmods($string)
	{
		$name = 'descmod';
		$curdesc = $this->_get_component_description();
		
		if ( ! $match_count = preg_match_all("|".LD.$name.'.*?'.RD.'(.*?)'.LD.'/'.$name.RD."|s", $string, $matches))
		{
			return $string;
		}

		foreach ($matches[1] as $k => $match)
		{
			if (preg_match_all("|".LD.$name.'(.*?)'.RD."|s", $matches[0][$k], $param_matches))
			{
				$parameters = ee()->functions->assign_parameters($param_matches[1][0]);
			}
			
			$findreplace = $parameters['findreplace'];
			
			$findreplace = explode('|', $findreplace);
			
			foreach ($findreplace as $fr)
			{
				$fr_arr = explode('=', $fr);
				$find = $fr_arr[0];
				$replace = $fr_arr[1];
				
				if (stripos($curdesc, $find) !== FALSE)
				{
					$info = preg_split('[\s+]', $match);
					
					if (count($info) == 1) //this is a category header "type"
					{
						$string = str_replace($matches[0][$k], $replace, $string);
					}
					elseif (count($info) > 1) // this is any other type (we need to keep the first identifier)
					{
						$string = str_replace($matches[0][$k], $info[0].' '.$replace, $string);
					}
				}
			}
		}
		
		// due to the multiple find/replace functionality we need to loop through again to clean up
		// "unfound" find/replace pairs
		foreach ($matches[1] as $k => $match)
		{
			if (preg_match_all("|".LD.$name.'(.*?)'.RD."|s", $matches[0][$k], $param_matches))
			{
				$parameters = ee()->functions->assign_parameters($param_matches[1][0]);
			}
			
			$findreplace = $parameters['findreplace'];
			
			$findreplace = explode('|', $findreplace);
			
			foreach ($findreplace as $fr)
			{
				$fr_arr = explode('=', $fr);
				$find = $fr_arr[0];
				$replace = $fr_arr[1];
				
				if (stripos($curdesc, $find) === FALSE)
				{
					$string = str_replace($matches[0][$k], $matches[1][$k], $string);
				}
			}
		}
		
		return $string;
	}
	
	public function _get_component_description()
	{
		foreach ($this->_current_component['children'] as $c)
		{
			if (is_array($c) && $c['type'] == 'itemdesc')
			{
				return $c[$c['type']]['text'];
			}
		}
	}
	
	public function _filter_non_components($data)
	{
		$td = array();
		foreach ($data as $d)
		{
			if (isset($d['children']) && is_array($d['children']))
			{
				$td[] = $d;
			}
		}
		
		return $td;
	}
	
	public function _parse_data($data)
	{
		$main_array = $this->_indent_helper($data);
		
		foreach ($main_array as $i => $elem)
		{
			if (is_array(@$elem['children']))
			{
				foreach ($elem['children'] as $id => $d)
				{
					$main_array[$i]['children'][$id] = $this->_parse_child($d);
				}
			}
		}
		
		return $main_array;
	}
	
	function _indent_helper($list, $indentation = '    ')
	{
  		$result = array();
  		$path = array();
		$mat_flag = FALSE;
		$top_flag = TRUE;
		$top_data = array();
		$mat_data = array();
		
		$x = 0;
		
		$list = preg_split("[\r\n]", $list);
		foreach ($list as $line)
		{
			if ($line == "MATERIALS")
			{
				$mat_flag = TRUE;
			}
			if (preg_match("/^$/", $line)) continue;
			if ($mat_flag)
			{
				$mat_data[] = $line;
				continue;
			}
			if (strpos($line, $indentation) === FALSE)
			{
				$result[$x]['text'] = trim($line);
				if ($this->_has_children())
				{
					$result[$x-1]['children'] = $this->_get_children();
				}
				$x++;
			}
			else
			{
				$this->_push_child($line);
				$top_flag = FALSE;
			}
			if ($top_flag === TRUE)
			{
				$top_data[] = $line;
			}
		}
		
		// last check because these may need to be popped off
		if ($this->_has_children())
		{
			$result[$x-1]['children'] = $this->_get_children();
		}
  		
  		$this->_top_data = implode("\r\n", $top_data);
  		$this->_materials = implode("\r\n", $mat_data);
  		
  		return $result;
	}

	public function _push_child($elem)
	{
		$elem = trim($elem);
		$this->_tmp_arr[] = $this->_parse_child($elem);
	}
	
	public function _get_children()
	{
		$tmp = $this->_tmp_arr;
		$this->_tmp_arr = array();
		
		return $tmp;
	}
	
	public function _has_children()
	{
		return (count($this->_tmp_arr) > 0) ? TRUE : FALSE;
	}
	
	public function _parse_child($elem)
	{
		if (is_array($elem)) return $elem;
		
		static $endpoint = 1;
		
		$firstval = preg_match('([^\s]+)', $elem, $match);
		
		switch ($match[0])
		{
			case 'END-POINT':
				$info = preg_split('[\s+]', $elem);
				
				$ci = array(
					'type' => 'endpoint'.$endpoint,
					'endpoint'.$endpoint => array(
						'text' => $elem,
						'z' => $info[1],
						'x' => $info[2],
						'y' => $info[3],
						'size' => $info[4]
					)
				);
				if ($endpoint++ > 1)
				{
					$endpoint = 1;
				}
			break;
			case 'CENTRE-POINT':
				$info = preg_split('[\s+]', $elem);
				$ci = array(
					'type' => 'centrepoint',
					'centrepoint' => array(
						'text' => $elem,
						'z' => $info[1],
						'x' => $info[2],
						'y' => $info[3]
					)
				);
			break;
			case 'BRANCH1-POINT':
				$info = preg_split('[\s+]', $elem);
				
				$ci = array(
					'type' => 'branch1point',
					'branch1point' => array(
						'text' => $elem,
						'z' => $info[1],
						'x' => $info[2],
						'y' => $info[3],
						'size' => $info[4]
					)
				);
			break;
			case 'SKEY':
				$ci = array(
					'type' => 'skey',
					'skey' => array(
						'text' => $elem
					)
				);
			break;
			case 'PIPING-SPEC':
				$ci = array(
					'type' => 'piping-spec',
					'piping-spec' => array(
						'text' => $elem
					)
				);
			break;
			case 'DATE-DMY':
				$ci = array(
					'type' => 'date-dmy',
					'date-dmy' => array(
						'text' => $elem
					)
				);
			break;
			case 'ITEM-CODE':
				$ci = array(
					'type' => 'itemcode',
					'itemcode' => array(
						'text' => $elem
					)
				);
			break;
			case 'ITEM-DESCRIPTION':
				$ci = array(
					'type' => 'itemdesc',
					'itemdesc' => array(
						'text' => $elem
					)
				);
			break;
			case 'PIPING-SPEC':
				$ci = array(
					'type' => 'pipingspec',
					'pipingspec' => array(
						'text' => $elem
					)
				);
			break;
			case 'SPOOL-IDENTIFIER':
				$ci = array(
					'type' => 'spoolidentifier',
					'spoolidentifier' => array(
						'text' => $elem
					)
				);
			break;
			case 'ITEM-ATTRIBUTE0':
				$ci = array(
					'type' => 'itemattribute0',
					'itemattribute0' => array(
						'text' => $elem
					)
				);
			break;
			case 'REVISION':
				$ci = array(
					'type' => 'revision',
					'revision' => array(
						'text' => $elem
					)
				);
			break;
			case 'PROJECT-IDENTIFIER':
				$ci = array(
					'type' => 'project-identifier',
					'project-identifier' => array(
						'text' => $elem
					)
				);
			break;
			case 'AREA':
				$ci = array(
					'type' => 'area',
					'area' => array(
						'text' => $elem
					)
				);
			break;
			case 'FABRICATION-ITEM':
				$ci = array(
					'type' => 'fabitem',
					'fabitem' => array(
						'text' => $elem
					)
				);
			break;
			default:
				$ci = $elem;
			break;
		}
		
		return $ci;
	}
	
	/**
	 * @file - Should be the path after the domain
	 *		 ex. http://www.example.com/my/files/file.txt would be "my/files/file.txt"
	 */
	public function _get_file($file, $clear_cache = FALSE)
	{
		if ($clear_cache === TRUE)
		{
			unset($_SESSION['pcf']['file']);
		}
		if (isset($_SESSION['pcf']['file']))
		{
			return $_SESSION['pcf']['file'];
		}
		$protocol = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") ? "http://" : "http://";
		$url = $protocol . $_SERVER['HTTP_HOST'] . '/' . $file;
		$port = $_SERVER['SERVER_PORT'];
		
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_PORT , (int)$port);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'Host: '.$_SERVER['HTTP_HOST'] ));
		curl_setopt($ch, CURLOPT_ENCODING , "gzip");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,20); 
		curl_setopt($ch, CURLOPT_TIMEOUT, 40);
		$resp = curl_exec($ch);
		
		if(curl_errno($ch) > 1) //should be 0 but for some reason my curl complains about error 1, protocol unrecognised but request still processes.
			$resp = curl_error($ch);
				
		curl_close ($ch);
		
		$_SESSION['pcf']['file'] = $resp;
		
		return $resp;
	}
	
	// ----------------------------------------------------------------
	
	/**
	 * Plugin Usage
	 */
	public static function usage()
	{
		ob_start();
?>

 Usage documentation goes here.
<?php
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}
}
