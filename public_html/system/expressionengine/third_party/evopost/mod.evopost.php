<?php

class Evopost {

	var $settings       = array();

	var $name           = "";
	var $version        = '1.0.0';
	var $description    = "";
	var $docs_url       = 'http://expressionengine.com';

	// -------------------------------
	//   Constructor - Extensions use this for settings
	// -------------------------------

	function Evopost(){
		$this->EE =& get_instance();
		$this->name           = $this->EE->lang->line('evopost_module_name');
		$this->description    = $this->EE->lang->line('evopost_module_description');
	}

	function getpostdata(){
		$tagdata = $this->EE->TMPL->tagdata;
		if(isset($_POST) && sizeof($_POST)>0){
			$tagdata = $this->EE->TMPL->tagdata;
			$tagdata = str_replace(LD.'ep_posted'.RD, 'yes', $tagdata);
			foreach($_POST as $key => $value){
				$tagdata = str_replace(LD.'ep_'.$key.RD, $value, $tagdata);
			}
			$tagdata = $this->wipeothertags($tagdata);
			return $tagdata;
		}
		else{
			$tagdata = str_replace(LD.'ep_posted'.RD, 'no', $tagdata);
			$tagdata = $this->wipeothertags($tagdata);
			return $tagdata;
		}
	}

	function wipeothertags($tagdata) {
		$p = $tagdata;
		$pattern = "/\{ep\_(.*?)\}/ims";
		$content = "";
		$tagdata = preg_replace($pattern, $content, $p);
		return $tagdata;
	}
}
