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
	'pi_version'	=> '1.0',
	'pi_author'		=> 'Ryan Blenis',
	'pi_author_url'	=> '',
	'pi_description'=> 'PCF Parser',
	'pi_usage'		=> Pcf::usage()
);


class Pcf {

	public $return_data;
    
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
		die('af');
	}
	
	public function pcf()
	{
		$t = $this->EE->TMPL->tagdata;
		$file = $this->EE->TMPL->fetch_param('url');
		
		$data = $this->_get_file($file);
		$parsed_data = $this->_parse_data($data);
		
		print_r($parsed_data);die;
		
	}
	
	public function _parse_data($data)
	{
		return $this->_helper($data);
	}
	
	function helper($list, $indentation = '    ') 
	{
  		$result = array();
  		$path = array();

  		foreach (explode("\n", $list) as $line) 
  		{
    		// get depth and label
   	 		$depth = 0;
    		while (substr($line, 0, strlen($indentation)) === $indentation) 
    		{
      			$depth += 1;
      			$line = substr($line, strlen($indentation));
    		}

    		// truncate path if needed
    		while ($depth < sizeof($path)) 
    		{
      			array_pop($path);
    		}

    		// keep label (at depth)
    		$path[$depth] = $line;

    		// traverse path and add label to result
    		$parent =& $result;
    		foreach ($path as $depth => $key) 
    		{
      			if (!isset($parent[$key])) 
      			{
        			$parent[$line] = array();
        			break;
      			}

      			$parent =& $parent[$key];
    		}
  		}
  		return $result;
	}

	
	/**
	 * @file - Should be the path after the domain
	 *		 ex. http://www.example.com/my/files/file.txt would be "my/files/file.txt"
	 */
	public function _get_file($file)
	{
		$protocol = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") ? "http://" : "http://";
		$url = $protocol . $_SERVER['HTTP_HOST'] . $file;
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
