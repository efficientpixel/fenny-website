<?php if ( ! defined('EXT')) { exit('Invalid file request'); }
   /*
   ========================================================
   Plugin Utilities
   This serves for few php functions
   @Author : Abu Musa
   @Email: musa@codetrio.com
   @Website: http://www.codetrio.com/
   @Last Updated: April 11, 2014
   ========================================================
   */
  $plugin_info = array('pi_name' => 'Util', 
						 'pi_version' => '0.1', 
						 'pi_author' => 'Abu Musa', 
						 'pi_author_url' => 'http://www.codetrio.com/', 
						 'pi_description' => 'Utility Plugin. This allowes you to call few string processing php functions from your template. Has few other useful methods too.', 
						 'pi_usage' => Util::usage());
  
  class Util
  {
      var $return_data;
      public $sep;
      public $data;
      public $data_array = array();
      public $param_array = array();
      public $arguments = array();
      
      function __construct()
      {
          $this->EE =& get_instance();
          
          $this->tagdata = trim($this->EE->TMPL->tagdata);
          $this->data = $this->EE->TMPL->fetch_param('data');
	      $this->sep = $this->EE->TMPL->fetch_param('sep');
	      if($this->sep==''){
		      if(strstr($this->data, '|')) $this->sep = '|';
		      elseif(strstr($this->data, ',')) $this->sep = ',';
	      }

	      if($this->data=='') $this->data = $this->tagdata;	      
	                                    
          $this->return_data = $this->tagdata;
      }
      
      function escape(){
	      return htmlspecialchars($this->data, ENT_QUOTES);
      }

      /* will perform job like implode*/
      function join(){
	      $glue = $this->EE->TMPL->fetch_param('glue');	      
	      return implode($glue, explode($this->sep, $this->data));
      }
      
      function first(){
      	if(!$this->sep) return substr($this->data, 0,1);
      	
      	$this->data_array = explode($this->sep, $this->data);
      	return $this->data_array[0];
      }

      function last(){
      	if(!$this->sep) return substr($this->data, -1,1);
      	
      	$this->data_array = explode($this->sep, $this->data);
      	return array_pop($this->data_array);
      }
      
      function func(){
      
	      $function = $this->EE->TMPL->fetch_param('function');
	      $params  = $this->EE->TMPL->fetch_param('params');
	      if($params!='') $this->param_array = explode('|', $params);
	      

	      
	      $reflectedFunction = new ReflectionFunction($function);
	      $this->arguments[0] = $this->data;
	      
	      if(!empty($this->param_array)){
	      	foreach($this->param_array as $index=>$const){
	      		if(substr($const, 0,1)=='"' || substr($const, 0,1)=="'"){
	      			$this->param_array[$index] = substr($const,1,strlen($const)-2);

	      		}else $this->param_array[$index] = constant($const);
	      	}
	      	$this->arguments = array_merge($this->arguments, $this->param_array);
	      	
	      }	
	      
	      return $reflectedFunction->invokeArgs($this->arguments);
      }
      
      // ----------------------------------------
      //  Plugin Usage
      // ----------------------------------------
      // This function describes how the plugin is used.
      //  Make sure and use output buffering
      function usage()
      {
          ob_start();
?>

func
Execute any php function from template, You can pass function argument/constant too
Example:
{exp:util:func function="htmlentities" params="ENT_QUOTES|'UTF-8'"}A 'quote' is <b>bold</b>{/exp:util:func}
{exp:util:func function="strip_tags" params="'<p><a>'"}<p>Test paragraph.</p><!-- Comment --> <a href="#fragment">Other text</a>{/exp:util:func}
{exp:util:func function="strlen" data="Get string length"}

escape
Convert special characters to HTML entities
Example: {exp:util:escape data="<a href='test'>Test</a>"}


join
Join a string by a specific glue
Example: {exp:util:join data="1,2,3,4" glue="|"}

first
Get first character of a string
Example:
{exp:util:first data="1,2,3,4"}<br />
{exp:util:first data="1234"}

last
Get last character of a string
Example: {exp:util:last data="1,2,3,4"}
{exp:util:last data="1234"}


<?php
          $buffer = ob_get_contents();
          ob_end_clean();
          return $buffer;
      }
      /* END */
      
  }
  // END CLASS
/* End of file pi.util.php */
/* Location: ./system/expressionengine/third_party/util/pi.util.php */