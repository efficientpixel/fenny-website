<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Libraree_ext {

	var $settings       = array();
	var $name           = 'Libraree';
	var $version        = '1.0.7';
	var $description    = 'Save your snippets as files and access them directly in your templates without logging into the control panel)';
	var $settings_exist = 'y';
	var $docs_url       = '';

	var $files;
	var $entries;
	var $comp_entries;

	var $subject_start;
	var $subject_end;
	var $delete_trigger;			
				
	var $snippet_paths = array();
	var $glob_paths = array();
	var $spec_paths = array();
	
	var $separator;
	
	var $ENABLED;
				
	function Libraree_ext($settings='') {
	
		
		$this->ENABLED = TRUE;
		
	    $this->EE =& get_instance();
	
		$this->EE->load->helper('path');
		
		$this->settings = $settings;
		
		$this->EE->lang->loadfile('libraree');		
			
		$results = $this->EE->db->query("SELECT module_id FROM ".$this->EE->db->dbprefix('modules')." WHERE module_name = 'Brilliant_retail'");

	    if ($results->num_rows > 0){$this->settings["br_installed"] = false;
	    }else{$this->settings["br_installed"] = false;}
	    
		//error_reporting(E_ALL ^ E_NOTICE);
		
		$this->EE->load->add_package_path(PATH_THIRD.'libraree/'); 
		
		$this->EE->load->helper('filefolder');
		
		if (isset($this->EE->config->config['libraree_basepath'])) {
			$this->settings['path'] = $this->EE->config->config['libraree_basepath'];
		}
		
		if(isset($this->settings['path']))
		{
			$realpath = set_realpath($_SERVER['DOCUMENT_ROOT'].$this->settings['path']);
		
			if(!directory_exists($realpath)){
				$realpath = set_realpath($this->settings['path']);
			}
				
			$this->settings['path'] = $realpath;
		
		}
	    
	    
		$this->files = array();
		$this->entries = array();
		$this->comp_entries = array();
		$this->snippet_paths = array();
		$this->glob_paths = array();
		$this->spec_paths = array();
		
		$this->subject_start = '<subject>';
		$this->subject_end = '</subject>';
		$this->delete_trigger = '--';
	
		$this->roundTime = 2;
		
		$this->DEBUG = FALSE;

		
	}
	
	
	/** -------------------------------------
	/** process files and db entries
	/** -------------------------------------*/	
	
	function process(&$OBJ){	
		//echo "LIBRAREE";
		if(isset($_SESSION["libraree_error"])){
			$this->ENABLED = FALSE;
		}
		
		if($this->ENABLED){
		
			//if($this->DEBUG){
			//	error_reporting(1);
			//	//echo $this->msg;
			//}else{
			//	error_reporting(0);
			//}
				
			//Before processing, re-check if the folder still exists and check permission.  
			//if there is a problem, mail the admin and set an error notification on the CP homepage
			
			$settings = $this->settings;
			
			if(isset($settings["path"])){
	
				//if MSM disabled, use the default site_id
				$site_id = $this->EE->config->item('site_id');
				
				//if MSM enabled, run through the settings to check which site syncing has been enabled
				
				$this->EE->db->select('site_id, site_name');				
				$this->EE->db->from($this->EE->db->dbprefix("sites"));
				$query = $this->EE->db->get();
				
				$enabled = true;
				if($enabled){
				// is syncing of snippets enabled?
				
					if(isset($settings["snippets_enabled"])){
						
						$snippets_path = $settings["path"]."snippets/";
						
						/** ----------------------------------------------
						/** If MSM is enabled, sync global snippet folder
						/** ---------------------------------------------*/	
						
						if($query->num_rows() > 1){	
				
							//get all file info 
						
							$this->files = $this->get_files($snippets_path."global/");
							
							$this->snippet_paths[0] = $snippets_path."global/";
							
							// get all db entries
							
							$this->entries = $this->get_from_db("snippets", 0, "snippet_id", "snippet_name", "snippet_contents");
							
							// compare
							
							$this->compare("snippets", 0, $this->files, $this->entries);
						}
						
						/** ----------------------------------------------
						/** Sync local snippet folders
						/** ---------------------------------------------*/	
						
						if($query->num_rows() >= 1){	
						
							foreach($query->result_array() as $row){
						
								$this->snippet_paths[$row["site_id"]] = $snippets_path.$row["site_name"]."/";
								
								//get all file info 
							
								$this->files = $this->get_files($snippets_path.$row["site_name"]."/");
								
								// get all db entries
								
								$this->entries = $this->get_from_db("snippets", $row["site_id"], "snippet_id", "snippet_name", "snippet_contents");
							
								// compare
								
								$this->compare("snippets", $row["site_id"], $this->files, $this->entries);
								
							}
						
						}
					}
					
				// is syncing of global variables enabled?
		
					if(isset($settings["global_variables_enabled"])){
						
						$glob_path = $settings["path"]."global_variables/";
						
						if($query->num_rows() >= 1){	
						
							foreach($query->result_array() as $row){
						
								$this->glob_paths[$row["site_id"]] = $glob_path.$row["site_name"]."/";
								
								//get all file info 
							
								$this->files = $this->get_files($glob_path.$row["site_name"]."/");
								
								// get all db entries
								
								$this->entries = $this->get_from_db("global_variables", $row["site_id"], "variable_id", "variable_name", "variable_data");
							
								// compare
								
								$this->compare("global_variables", $row["site_id"], $this->files, $this->entries);
							}
						
						}
						
					}
		
				// is syncing of message pages enabled?
				
					if(isset($settings["message_pages_enabled"])){
						
						$spec_path = $settings["path"]."specialty_templates/";
				
						if($query->num_rows() >= 1){	
						
							foreach($query->result_array() as $row){
						
								$this->spec_paths[$row["site_id"]] = $spec_path.$row["site_name"]."/";
								
								//get all file info 
							
								$this->files = $this->get_files($spec_path.$row["site_name"]."/");
								
								// get all db entries
								
								$this->entries = $this->get_from_db("specialty_templates", $row["site_id"], "template_id", "template_name", "template_data");
							
								// compare
								
								$this->compare("specialty_templates", $row["site_id"], $this->files, $this->entries);
							}
						
						}
						
					}
					
					// is syncing of Brilliant Retail notifications enabled?
		
					if(isset($settings["br_enabled"]) && $this->settings["br_installed"]){
						
						$br_path = $settings["path"]."brilliant_retail/";
						
						if($query->num_rows() >= 1){	
						
							foreach($query->result_array() as $row){
						
								$this->br_paths[$row["site_id"]] = $br_path.$row["site_name"]."/";
								
								//get all file info 
							
								$this->files = $this->get_files($br_path.$row["site_name"]."/");
								
								// get all db entries
								
								$this->entries = $this->get_from_db("br_email", $row["site_id"], "email_id", "title", "content");
							
								// compare
								
								$this->compare("br_email", $row["site_id"], $this->files, $this->entries);
							}
						
						}
						
					}
					
				}	
				
				//Check if user message parsing is enabled
				if(isset($settings['user_message_parsing_enabled'])){
				
					$this->EE->output 	 = new Libraree_Output;
					
				}
		
				//Check if user message parsing is enabled
				if(isset($settings['user_email_parsing_enabled'])){
				
					$this->EE->functions = new Libraree_Functions;
					
				}
				  		
			}
		}
	}
	
	/** ----------------------------------------------
	/** Compare files with db entries and vice versa
	/** ---------------------------------------------*/	
	
	function compare($table, $site_id, $files, $entries){

		$this->comp_entries = array();
		
		// move entries into a temp array for comparison
		$this->comp_entries = $entries;
		
		if(count($files) > 0){

			foreach($files as $filekey => $filevalue){
			
				if($filevalue["remove"] == true){
					
					$this->remove($table, $filekey, $site_id, true);
				
				}
				elseif(isset($this->comp_entries[$filekey]))
				{
					//file exists as db entry
					$this->msg .= "File exists :".$filekey;
					
					if($filevalue["sync_time_human"] > $this->comp_entries[$filekey]["sync_time_human"] || $filevalue["creation_time_human"] > $this->comp_entries[$filekey]["sync_time_human"])
					{
						//file has been modified, sync to DB
						$this->msg .= "File modified<br/>";
						
						$this->sync_to_db($table, $site_id, $filekey, false);
					}
					elseif($filevalue["sync_time"] < $this->comp_entries[$filekey]["sync_time"])
					{
						//DB has been modified, sync to file
						
						$this->sync_to_file($table, $site_id, $filekey, FALSE);
					}
					
					//unset from the db entries array, because we do not have to re-compare these with the file
					unset($this->comp_entries[$filekey]);
					
				}else{
					//file does not exist as db entry
					$this->sync_to_db($table, $site_id, $filekey, true);
					
				}
			}
			
		}
		//COMPARE ENTRIES WITH FILES
		
		if(count($this->comp_entries) > 0){
	
			foreach($this->comp_entries as $entrykey => $entryvalue){
				
				if($entryvalue["remove"] == true)
				{
					//Do not remove specialty templates
					if($table != "specialty_templates"){
						$this->remove($table, $entrykey, $site_id, false);
					}
				
				}
				elseif(!isset($files[$entrykey]))
				{
					//file does not exist
					$this->msg .= "File does not exist ".$entrykey;
					
					$this->sync_to_file($table, $site_id, $entrykey, TRUE);
				}
			}
		
		}
	}
	
		
	function remove($table, $filekey, $site_id, $fromFile){
		
		$cleankey = str_replace($this->delete_trigger, "", $filekey);
		
		if($fromFile)
		{
			unlink($this->files[$filekey]["server_path"]);
			unset($this->comp_entries[$cleankey]);
		}
		else
		{
			unlink($this->files[$cleankey]["server_path"]);		
			unset($this->comp_entries[$filekey]);
		}
		
		$fieldname = ($table == "snippets")?"snippet_name":"variable_name";
		$fieldname = ($table == "specialty_templates")?"template_name":$fieldname;
			
			$this->EE->db->where(array($fieldname => $cleankey, 'site_id' => $site_id));
			$this->EE->db->delete($this->EE->db->dbprefix($table)); 
			$this->EE->db->where(array($fieldname => $filekey, 'site_id' => $site_id));
			$this->EE->db->delete($this->EE->db->dbprefix($table)); 

	}
	/** -------------------------------------
	/** Sync content of db entry to file
	/** -------------------------------------*/	
	
	function sync_to_file($table, $site_id, $filekey, $new){

		if($new){
			//file does not exist, create it
			
			//get extension
			$ext = "html";
			$ext = ($table == "snippets")?"snip":$ext;
			$ext = ($table == "global_variables")?"glob":$ext;
			$ext = ($table == "specialty_templates")?"spec":$ext;
			
			//get path
			$path = ($table == "snippets")?$this->snippet_paths[$site_id]:"";
			$path = ($table == "global_variables")?$this->glob_paths[$site_id]:$path;
			$path = ($table == "specialty_templates")?$this->spec_paths[$site_id]:$path;
			$path = ($table == "br_email")?$this->br_paths[$site_id]:$path;
		
			//get data
			$file_data = $this->entries[$filekey]["data"];
						
			//if the file is a specialty template, we add the subject inside subect tags in the file
			$subject = $this->subject_start.$this->entries[$filekey]["subject"].$this->subject_end."\n";
			
			$file_data = ($table == "specialty_templates" && $filekey != "wrapper"  && $filekey != "message_template" && $filekey != "offline_template" ) ? $subject.$file_data : $file_data;
			
			$fullpath = $path.$filekey.".".$ext;
			
			if($this->write_to_file($fullpath, $file_data, $table)){
			
				$this->sync_times($table, $site_id, $fullpath, $filekey);
			
			}
		
		}else{
			//file exists, update the content and sync times
			
			$fullpath = $this->files[$filekey]["server_path"];
		
			$file_data = $this->entries[$filekey]["data"];
			
			//if the file is a specialty template, we add the subject inside subect tags in the file
			$subject = $this->subject_start.$this->entries[$filekey]["subject"].$this->subject_end."\n";
			
			$file_data = ($table == "specialty_templates" && $filekey != "wrapper" && $filekey != "message_template" && $filekey != "offline_template"  ) ? $subject.$file_data : $file_data;
			
			if($this->write_to_file($fullpath, $file_data, $table)){	
				
				$this->sync_times($table, $site_id, $fullpath, $filekey);
			
			}
		}
		
	}
	
	/** -------------------------------------
	/** Write data to file
	/** -------------------------------------*/	
	
	function write_to_file($path, $data, $table){
		error_reporting(0);
		//check if libraree folder exists, otherwise create it
		$this->createFolder($this->settings["path"]);//."libraree/");
		
		$hasGlobal = ($table == "snippets") ? TRUE : FALSE;
	
		$this->checkFolders($this->settings["path"].$table."/", $hasGlobal);
		
		if (write_file($path, $data))
		{
			chmod($path,0666);    
		    return TRUE;
		}
		else
		{   
		    return FALSE;
		}
	}
	
	/** ------------------------------------------
	/** Sync db entry sync_time to file mod time
	/** -----------------------------------------*/	
	
	function sync_times($table, $site_id, $file, $key){
		
		//set same value for file modification date and sync_time 
		$file_info = get_libraree_file_info($file);
		
		$human_date = date("Y-m-d H:i:s",$file_info["date"]);
		
		if($table == "snippets"){
			$this->EE->db->query("update exp_snippets set sync_time ='".$human_date."' where snippet_name='".$key."' and site_id = ".$site_id.";");
		}
		
		if($table == "global_variables"){
			$this->EE->db->query("update exp_global_variables set sync_time ='".$human_date."' where variable_name='".$key."' and site_id = ".$site_id.";");
		}
		
		if($table == "specialty_templates"){
			$this->EE->db->query("update exp_specialty_templates set sync_time ='".$human_date."' where template_name='".$key."' and site_id = ".$site_id.";");
		}
		
		if($table == "br_email"){
			$this->EE->db->query("update exp_br_email set sync_time ='".$human_date."' where title ='".$key."' and site_id = ".$site_id.";");
		}
	}
	
	/** ------------------------------------------
	/** Write file data to db entry
	/** no new specialty_templates can be created, only updated
	/** -----------------------------------------*/	
	
	function sync_to_db($table, $site_id, $filekey,$new){
		$this->EE->load->helper('librareestring');
		
		$file_data = read_file($this->files[$filekey]["server_path"]);
		
		$sync_time_human = ($this->files[$filekey]["creation_time"] > $this->files[$filekey]["sync_time"]) ? $this->files[$filekey]["creation_time_human"] : $this->files[$filekey]["sync_time_human"];
		
		$sync_time = ($this->files[$filekey]["creation_time"] > $this->files[$filekey]["sync_time"]) ? $this->files[$filekey]["creation_time"] : $this->files[$filekey]["sync_time"];
								
		if($file_data){
			error_reporting(0);
			//"TOUCH" the file to update the filemtime
			//touch($this->files[$filekey]["server_path"]);
			fclose(fopen($this->files[$filekey]["server_path"], 'a'));
			
			if($new){	
				//Create the db entry and set sync time to the file modification date
				if($table == "snippets"){
				
					$this->EE->db->query("INSERT INTO ".$this->EE->db->dbprefix($table)." (snippet_name, snippet_contents, site_id, sync_time) VALUES ('".$filekey."', '".addslashes($file_data)."', ".$site_id.", '".$sync_time_human."');");
				
				}
	
				if($table == "global_variables"){
				
					$this->EE->db->query("INSERT INTO ".$this->EE->db->dbprefix($table)." (variable_name, variable_data, site_id, sync_time) VALUES ('".$filekey."', '".addslashes($file_data)."', ".$site_id.", '".$sync_time_human."');");
				
				}
					
				if($table == "specialty_templates"){
					$content_start_pos = strpos($file_data, $this->subject_end);
			
					$subject = substring_between($file_data, $this->subject_start ,$this->subject_end);
					$doSubject = ($subject !== FALSE)? $subject : $filekey;
					$stripped_data = substr($file_data, $content_start_pos+strlen($this->subject_start)+1 );
			
					$mcontent = ($content_start_pos !== FALSE) ? $stripped_data : $file_data;
					$mcontent = addslashes($mcontent);
	
					$this->EE->db->query("INSERT INTO exp_specialty_templates (template_data, data_title, sync_time, template_name, site_id) VALUES ('".$mcontent."', '".$doSubject."', '".$sync_time_human."', '".$filekey."', '".$site_id."')");
					
					
				}
				
				if($table == "br_email"){
				
					$this->EE->db->query("INSERT INTO ".$this->EE->db->dbprefix($table)." (title, content, site_id, sync_time) VALUES ('".$filekey."', '".addslashes($file_data)."', ".$site_id.", '".$sync_time_human."');");
				
				}
				
						
			}else{
				//update the db entry and set sync time to the file modification date
				if($table == "snippets"){
					
					$this->EE->db->query("update ".$this->EE->db->dbprefix($table)." set snippet_contents='".addslashes($file_data)."', sync_time ='".$sync_time_human."' where snippet_name='".$filekey."' and site_id = ".$site_id.";");
				
				}
	
				if($table == "global_variables"){
					
					$this->EE->db->query("update ".$this->EE->db->dbprefix($table)." set variable_data='".addslashes($file_data)."', sync_time ='".$sync_time_human."' where variable_name='".$filekey."' and site_id = ".$site_id.";");
				
				}
				
				if($table == "specialty_templates"){
					$content_start_pos = strpos($file_data, $this->subject_end);
			
					$subject = substring_between($file_data, $this->subject_start ,$this->subject_end);
					$doSubject = ($subject !== FALSE)? ", data_title = '".$subject."' " : "";
					$stripped_data = substr($file_data, $content_start_pos+strlen($this->subject_start)+1 );
			
					$mcontent = ($content_start_pos !== FALSE) ? $stripped_data : $file_data;
					$mcontent = addslashes($mcontent);
	
					$this->EE->db->query("update exp_specialty_templates set template_data='".$mcontent."' ".$doSubject.", sync_time ='".$sync_time_human."' where template_name='".$filekey."' and site_id = '".$site_id."';");
					
				}	
				
				if($table == "br_email"){
					
					$this->EE->db->query("update ".$this->EE->db->dbprefix($table)." set content='".addslashes($file_data)."', sync_time ='".$sync_time_human."' where title='".$filekey."' and site_id = ".$site_id.";");
				
				}
					
			}
			
			$this->EE->config->_global_vars[$filekey] = $file_data;
		
		}
								
	}
	
	/** -----------------------------------------------------
	/** Get array of files in specified path + file details
	/** ----------------------------------------------------*/	
	
	function get_files($path){
	
		if(directory_exists($path)){
		
			$files = get_filenames($path);
			
			$items = array();
						
			foreach($files as $file){
				
				$file_info = get_libraree_file_info($path.$file, array('name', 'server_path', 'size', 'date', 'creationdate', 'fileperms'));
			
			    $name 		= file_raw_name($file_info["name"]);
			    
			    $filename 	= $file_info["name"];
			 
			    $sync_time 	= strtotime(date('Y-m-d H:i:s', ceil($file_info["date"]/$this->roundTime)*$this->roundTime));//$file_info["date"];
			    
			    $creation_time 	= strtotime(date('Y-m-d H:i:s', ceil($file_info["creationdate"]/$this->roundTime)*$this->roundTime));//$file_info["creationdate"];
			    
			    $sync_time_human = date('Y-m-d H:i:s', ceil($file_info["date"]/$this->roundTime)*$this->roundTime); //date("Y-m-d H:i:s",$file_info["date"]);
			    
			    $creation_time_human = date('Y-m-d H:i:s', ceil($file_info["creationdate"]/$this->roundTime)*$this->roundTime); // date("Y-m-d H:i:s",$file_info["creationdate"]);
			    
			    $server_path = $file_info["server_path"];
			   
			    $fileperms  = substr(sprintf('%o', $file_info["fileperms"]), -4);
			    
			    $remove 	= false;
			    //Delete the file and db entry
			    if(strpos($name, $this->delete_trigger) !== false){
			    
					$remove = true;
			    }
			    
				$items[$name] = array("remove" => $remove, "sync_time" => $sync_time, "sync_time_human" => $sync_time_human , "creation_time" => $creation_time, "creation_time_human" => $creation_time_human, "fileperms" => $fileperms, "server_path" => $server_path, "filename" => $filename);
				
			    
			}
			
			return $items;
			
		}else{
		
			return;
			
		}
	}
	
	
	// SUPPORT FOR LOW VARIABLES DELETION!
	function low_variables_delete($variables)
	{
		
		//if MSM disabled, use the default site_id
		$site_id = $this->EE->config->item('site_id');
				
		//if MSM enabled, run through the settings to check which site syncing has been enabled
				
		$this->EE->db->select('site_id, site_name');				
		$this->EE->db->from($this->EE->db->dbprefix("sites"));
		$query = $this->EE->db->get()->row();
		
		foreach($variables as $var)
		{
			$this->EE->db->select("variable_name")
							->from("global_variables")
							->where("variable_id",$var);
							
			$var_name = $this->EE->db->get()->row()->variable_name;
			
			unlink($this->settings['path'].'global_variables/'.$query->site_name.'/'.$var_name.'.glob');
			
		}
	}
	
	
	
	/** -------------------------------------
	/** Get db entries + details
	/** -------------------------------------*/	
	
	// get_from_db("snippets", "snippet", "contents")
	// get_from_db("global_variables", "variable", "data")
	
	function get_from_db($table, $site_id, $id_field, $name_field, $data_field){ //, $name){
		
		$this->EE->db->select('*, UNIX_TIMESTAMP(sync_time) as unix_sync_time');
					
		$this->EE->db->from($this->EE->db->dbprefix($table));
		
		$this->EE->db->where(array('site_id' => $site_id));//, $fieldprefix.'_name' => $name ));
		
		$query = $this->EE->db->get();
		
		$items = array();
		
		if($query->num_rows() > 0){	
			
			foreach($query->result_array() as $row){
			    
			    $id   		= $row[$id_field];
			    $name 		= $row[$name_field];
			    $data 		= $row[$data_field];
			    $sync_time 	= strtotime(date('Y-m-d H:i:s', ceil(strtotime($row['sync_time'])/$this->roundTime)*$this->roundTime)); //$row['unix_sync_time'];
			   
			    $sync_time_human = date('Y-m-d H:i:s', ceil(strtotime($row['sync_time'])/$this->roundTime)*$this->roundTime); //date("Y-m-d H:i:s",$row['unix_sync_time']);
			    $subject 	= ($table == "specialty_templates") ? $row['data_title'] : "";
			    $remove 	= false;

			    //Delete the file and db entry
			    if(strpos($name, $this->delete_trigger) !== false){
			    
					$remove = true;
			    }
		    
				$items[$name] = array("remove" => $remove, "id" => $id, "name" => $name, "data" => $data, "sync_time" => $sync_time, "sync_time_human" => $sync_time_human, "subject" => $subject);
				
			}
			
		}
		
		return $items;
	}
	
	
	function BR_mail($str){
	
		$this->EE->extensions->end_script = FALSE;
		
		$this->EE->load->helper('librareestring');
		
		if ( ! class_exists('EE_Template'))
		{
		
			require APPPATH.'libraries/Template.php';
	
		}	
				
		//Wrap email content into a email wrapper
		//check the existence of a mailwrapper snippet
		$query2 = $this->EE->db->query("SELECT content as template_data FROM exp_br_email WHERE site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."' AND title = 'wrapper'");

		if ($query2->num_rows() > 0){
		
			$template_data_wrapper = $query2->row("template_data");	
    		
    		$template_data = str_replace("{template_content}", $str, $template_data_wrapper );
    		
			
		}else{
			//not found, ignore
			
			$template_data = $str;
			
		}
		
		$this->EE->TMPL = new EE_Template;

		$this->EE->TMPL->parse_php = TRUE;

		//PARSE SNIPPETS, because request is not coming from PAGE or ACTION

		$this->EE->db->select('snippet_name, snippet_contents');
		$this->EE->db->where('(site_id = '.$this->EE->db->escape_str($this->EE->config->item('site_id')).' OR site_id = 0)');
		$fresh = $this->EE->db->get('snippets');

		if ($fresh->num_rows() > 0)
		{
			$snippets = array();

			foreach ($fresh->result() as $var)
			{
				$snippets[$var->snippet_name] = $var->snippet_contents;
			}

			$this->EE->config->_global_vars = $this->EE->config->_global_vars + $snippets; 

			unset($snippets);
			unset($fresh);
		}	
			
		$this->EE->TMPL->parse($template_data, FALSE, $this->EE->config->item('site_id'));
	
		$template_data = $this->EE->TMPL->parse_globals($this->EE->TMPL->final_template);	

	
		//$this->EE->load->library('security');
		
		if ($this->EE->session->userdata['language'] != '')
		{
			$user_lang = $this->EE->session->userdata['language'];
		}
		else
		{
			if ($this->EE->input->cookie('language'))
			{
				$user_lang = $this->EE->input->cookie('language');
			}
			elseif ($this->EE->config->item('deft_lang') != '')
			{
				$user_lang = $this->EE->config->item('deft_lang');
			}
			else
			{
				$user_lang = 'english';
			}
		}
	
		return $template_data;
	}
	
	/** -------------------------------------
	/** Activate
	/** -------------------------------------*/

	function activate_extension() {

		$data = array(
			'class'        => "Libraree_ext",
			'method'       => "process",
			'hook'         => 'sessions_start',
			'settings'     => "",
			'priority'     => 1,
			'version'      => $this->version,
			'enabled'      => "y"
		);

		$this->EE->db->insert('exp_extensions', $data);
		
		$data = array(
			'class'        => "Libraree_ext",
			'method'       => "process_check",
			'hook'         => 'cp_js_end',
			'settings'     => "",
			'priority'     => 2,
			'version'      => $this->version,
			'enabled'      => "y"
		);

		$this->EE->db->insert('exp_extensions', $data);
		
		$data = array(
			'class'        => "Libraree_ext",
			'method'       => "low_variables_delete",
			'hook'         => 'low_variables_delete',
			'settings'     => "",
			'priority'     => 2,
			'version'      => $this->version,
			'enabled'      => "y"
		);

		$this->EE->db->insert('exp_extensions', $data);

		$this->EE->db->query("ALTER TABLE exp_snippets ADD sync_time TIMESTAMP default now() on update now();");
		
		$this->EE->db->query("ALTER TABLE exp_global_variables ADD sync_time TIMESTAMP default now() on update now();");
		
		$this->EE->db->query("ALTER TABLE exp_specialty_templates ADD sync_time TIMESTAMP default now() on update now();");
		
		/* what's all this snooping around? this is a little something we have to fine-tune ;) 
		$data = array(
			'class'        => "Libraree_ext",
			'method'       => "BR_mail",
			'hook'         => 'br_order_update_before',
			'settings'     => "",
			'priority'     => 2,
			'version'      => $this->version,
			'enabled'      => "y"
		);

		$this->EE->db->insert('exp_extensions', $data);
		*/
	}


	/** -------------------------------------
	/** Update Extension
	/** -------------------------------------*/
	
	function update_extension($current='') {

		if ($current == '' OR $current == $this->version) {
			return FALSE;
		}
		$this->EE->db->where('class', 'Libraree_ext');
		$this->EE->db->update('extensions', array('version' => $this->version));
	}


	/** -------------------------------------
	/** Disable
	/** -------------------------------------*/
	
	function disable_extension() {

	    $this->EE->db->where('class', 'Libraree_ext');
	    $this->EE->db->delete('exp_extensions');
	    
	    $this->EE->db->query("ALTER TABLE exp_snippets DROP sync_time;");
		$this->EE->db->query("ALTER TABLE exp_global_variables DROP sync_time;");
		$this->EE->db->query("ALTER TABLE exp_specialty_templates DROP sync_time;");
	}

	/** -------------------------------------
	/** settings
	/** -------------------------------------*/	
	function settings()
	{
		$settings = array();
		
		//$settings['path'] 			=  $this->EE->config->config['tmpl_file_basepath'].'/';
		//$settings['libraree_path'] 	=  $settings['path']."libraree/";
		
		//Snippets available for all sites or all future sites
		$settings['snippets_enabled'] = 0;
		$settings['global_variables_enabled'] = 0;
		$settings['message_pages_enabled'] = 0;
		$settings['br_enabled'] = 0;
		
		$settings['user_message_parsing_enabled'] = 0;
		$settings['user_email_parsing_enabled'] = 0;
		
		return $settings;
	}

	/** -------------------------------------
	/** Settings Form
	/** -------------------------------------*/
	function settings_form($current)
	{

		$this->EE->load->helper('form');
		$this->EE->load->library('table');
		
		$this->EE->load->library("cp");

		//check mailtype
		$this->EE->load->library("email");
		$this->EE->email->EE_initialize();
		$mailtype = $this->EE->email->mailtype;

	
		if (isset($this->EE->config->config['libraree_basepath'])) {
			$current['path'] = $this->EE->config->config['libraree_basepath'];
		}
		
		$path			 			= isset($current['path']) 						? $current['path'] : $this->EE->config->config['tmpl_file_basepath'];
		$license_key	 			= isset($current['license_key']) 				? $current['license_key'] : ""; 
		$snippets_enabled 			= isset($current['snippets_enabled']) 			? $current['snippets_enabled'] : false; 
		$global_variables_enabled 	= isset($current['global_variables_enabled']) 	? $current['global_variables_enabled'] : false; 
		$message_pages_enabled 		= isset($current['message_pages_enabled'])		? $current['message_pages_enabled'] : false; 
		$br_enabled 				= isset($current['br_enabled'])		? $current['br_enabled'] : false; 
		
		$user_message_parsing_enabled 	= isset($current['user_message_parsing_enabled'])		? $current['user_message_parsing_enabled'] : false;
		$user_email_parsing_enabled 	= isset($current['user_email_parsing_enabled'])		? $current['user_email_parsing_enabled'] : false; 
		
		$vars = array();
		
		//if ($this->settings["br_installed"){
	    //	$vars['settings']["br_enabled"] = form_checkbox("br_enabled", '1', $br_enabled);
	    //}
	    
		$vars['settings1'] = array(
			'license_key'				=> form_input('license_key', $license_key),
			'path'						=> form_input('path', $path),
			'snippets_enabled' 			=> form_checkbox("snippets_enabled", '1', $snippets_enabled),
			'global_variables_enabled'	=> form_checkbox("global_variables_enabled", '1', $global_variables_enabled) ,
			'message_pages_enabled' 	=> form_checkbox("message_pages_enabled", '1', $message_pages_enabled)
		);

		
		$vars['settings3'] = array(
			'user_message_parsing_enabled' 		=> form_checkbox("user_message_parsing_enabled", '1', $user_message_parsing_enabled),
			'user_email_parsing_enabled' 		=> form_checkbox("user_email_parsing_enabled", '1', $user_email_parsing_enabled)
			);

		return $this->EE->load->view('extension_index', $vars, TRUE);			
	}

	/** -------------------------------------
	/** Save settings
	/** -------------------------------------*/
	
	function save_settings()
	{	
	
		if (empty($_POST))
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}
	
		unset($_POST['submit']);
		 
		$this->EE->load->helper('librareestring');
		$this->EE->load->helper('path');
		
		if(!isset($_POST["license_key"]) || !isValidLicense($_POST["license_key"])){
			
			show_error($this->EE->lang->line("invalid_license_key"));
			exit;
		} 
		
		$path = set_realpath($_SERVER['DOCUMENT_ROOT'].$_POST['path']);

		if(!directory_exists($path)){
			$path = set_realpath($_POST['path']);
		}

		$libraree_path  = $path;
		
		$snippets_path 	= $libraree_path."snippets/";
		$glob_path 		= $libraree_path."global_variables/";
		$message_path 	= $libraree_path."specialty_templates/";
		$template_path 	= $libraree_path."templates/";
		$br_path 		= $libraree_path."brilliant_retail/";
	

		// is syncing of snippets enabled?
		if(isset($_POST['snippets_enabled']) && $_POST['snippets_enabled'] == 1){
			// does the snippet path exist?
			if(!directory_exists($libraree_path)){
				show_error($this->EE->lang->line("libraree_folder_error")." : ".$libraree_path);
			}else{
				//create the appropriate snippet folders
				$this->checkFolders($snippets_path, TRUE);
			}
		}
		
		// is syncing of global variables enabled?
		if(isset($_POST['global_variables_enabled']) && $_POST['global_variables_enabled'] == 1){
			//does the global variables path exist?
			if(!directory_exists($libraree_path)){
				show_error($this->EE->lang->line("libraree_folder_error")." : ".$libraree_path);
			}else{
				$this->checkFolders($glob_path, FALSE);
			}
		}
			
		// is syncing of message pages enabled?
		if(isset($_POST['message_pages_enabled']) && $_POST['message_pages_enabled'] == 1){
			//does the global variables path exist?
			if(!directory_exists($libraree_path)){
				show_error($this->EE->lang->line("libraree_folder_error")." : ".$libraree_path);
			}else{
				$this->checkFolders($message_path, FALSE);
			}
		}

		// is syncing of brilliant retail enabled?
		if(isset($_POST['br_enabled']) && $_POST['br_enabled'] == 1){
			//ADD sync time field
			if (!$this->EE->db->field_exists('sync_time', 'exp_br_email')){
				$this->EE->db->query("ALTER TABLE exp_br_email ADD sync_time TIMESTAMP default now() on update now();");
			}
			// does the snippet path exist?
			if(!directory_exists($libraree_path)){
				show_error($this->EE->lang->line("libraree_folder_error")." : ".$libraree_path);
			}else{
				//create the appropriate br folders
				$this->checkFolders($br_path, FALSE);
			}
		}
					
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->update('extensions', array('settings' => serialize($_POST)));
		
		
		$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('preferences_updated'));
		
	}
	

	
	/** --------------------------------------------------------------
	/** Create/update folders 
	/** -------------------------------------------------------------*/
	function checkFolders($path, $hasGlobal){
		
		$this->createFolder($path);
		
		$this->EE->db->select('site_id, site_label, site_name');				
		$this->EE->db->from($this->EE->db->dbprefix("sites"));
		$query = $this->EE->db->get();
		
		//If MSM is enabled, create snippets/global folder
		
		if($query->num_rows() > 1 && $hasGlobal){	
			
			$this->createFolder($path."global/");
			
		}	
		
		//Create local site folders
		
		if($query->num_rows() >= 1){	
				
				foreach($query->result_array() as $row){
				
					$this->createFolder($path.$row["site_name"]."/");
				
				}
		}
		
	}
		
	function createFolder($path){
	   
	    $parts = explode("/",$path);
	    array_splice($parts, -2, 2);
	    $parent = implode("/",$parts);
	    $parent .= "/";
	
		if(!directory_exists($path)){
			if(!is_really_writable($parent)){
		
				$this->show_folder_error($this->EE->lang->line("create_folder_error")." ".$parent);
			}else{
				if (!mkdir($path)) {
			
					$this->show_folder_error($this->EE->lang->line("create_folder_error").$path);
			
				}else{
				
					chmod($path,0777);
					
					return TRUE;
					
				}
			}
		}
	}
	
	function show_folder_error($message){
	
		$_SESSION["libraree_message"] = "create_folder_error";
	}


	//cp_js_end hook function
	function process_check($str){

		if(isset($_SESSION["libraree_message"])){

				$message = '<div style="padding:10px; margin:10px; display:block; width:500px ; margin-left:auto; margin-right:auto; border:4px #af3004 solid">'.lang($_SESSION["libraree_message"]).'  <a href="'.BASE.AMP.'C=addons_extensions'.AMP.'M=extension_settings'.AMP.'file=libraree">Go to LibrarEE Settings page</a></div>';
		
				$str = "$('#mainContent').prepend('".$message."');";
			
		}
		
		if($this->EE->extensions->last_call)
		{
			$js = $this->EE->extensions->last_call;
		}
		else
		{
			$js = '';
		}
			
		return $js.$str;
	}
	
}

class Libraree_Output extends EE_Output

{	

	function show_message($data, $xhtml = TRUE)
	{

		$EE =& get_instance();
		
		if( ! class_exists('EE_Template')) 
        {
            $EE->load->library('template');
            $EE->TMPL = new EE_Template();
        }
        
		@header("Cache-Control: no-cache, must-revalidate");
		@header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
		@header("Pragma: no-cache");
		
		foreach (array('title', 'heading', 'content', 'redirect', 'rate', 'link') as $val)
		{
			if ( ! isset($data[$val]))
			{
				$data[$val] = '';
			}
		}
		
		if ( ! is_numeric($data['rate']) OR $data['rate'] == '')
		{
			$data['rate'] = $this->refresh_time;
		}
		
		$data['meta_refresh']	= ($data['redirect'] != '') ? "<meta http-equiv='refresh' content='".$data['rate']."; url=".$EE->security->xss_clean($data['redirect'])."'>" : '';
		$data['charset']		= $EE->config->item('output_charset');	
				
		if (is_array($data['link']) AND count($data['link']) > 0)
		{
			$refresh_msg = ($data['redirect'] != '' AND $this->refresh_msg == TRUE) ? $EE->lang->line('click_if_no_redirect') : '';
		
			$ltitle = ($refresh_msg == '') ? $data['link']['1'] : $refresh_msg;
			
			$url = (strtolower($data['link']['0']) == 'javascript:history.go(-1)') ? $data['link']['0'] : $EE->security->xss_clean($data['link']['0']);
		
			$data['link'] = "<a href='".$url."'>".$ltitle."</a>";
		}

		if ($xhtml == TRUE && isset($EE->session))
		{
			$EE->load->library('typography');
	
			$data['content'] = $EE->typography->parse_type(stripslashes($data['content']), array('text_format' => 'xhtml'));
		}		

		$query = $EE->db->query("SELECT template_data FROM exp_specialty_templates WHERE site_id = '".$EE->db->escape_str($EE->config->item('site_id'))."' AND template_name = 'message_template'");
		
		
		$row = $query->row_array();		
		
		$EE->TMPL = new EE_Template;

		$EE->TMPL->parse_php = TRUE;
		
		$EE->TMPL->parse($row["template_data"], FALSE, $EE->config->item('site_id'));
		
		$template_data = $EE->TMPL->parse_globals($EE->TMPL->final_template);
			
		foreach ($data as $key => $val)
		{
			$template_data  = str_replace('{'.$key.'}', $val, $template_data );
		}
		
		echo  stripslashes($template_data );
				
		exit;
	} 
	

	function system_off_msg()
	{
		
		$EE =& get_instance();
		
		if( ! class_exists('EE_Template')) 
        {
            $EE->load->library('template');
            $EE->TMPL = new EE_Template();
        }
        
		$query = $EE->db->query("SELECT template_data FROM exp_specialty_templates WHERE site_id = '".$EE->db->escape_str($EE->config->item('site_id'))."' AND template_name = 'offline_template'");
		
		$this->set_status_header(503, 'Service Temporarily Unavailable');
		@header('Retry-After: 3600');
		
		
		$EE->TMPL = new EE_Template;

		$EE->TMPL->parse_php = TRUE;
		
		$EE->TMPL->parse($query->row('template_data'), FALSE, $EE->config->item('site_id'));
		
		$template_data = $EE->TMPL->parse_globals($EE->TMPL->final_template);
			
		echo  stripslashes($template_data );
		
		exit;						
	}



}


class Libraree_Functions extends EE_Functions

{	
	
	
	function fetch_email_template($name)
	{
		$this->EE->load->add_package_path(PATH_THIRD.'libraree/'); 
		$this->EE->load->helper('librareestring');
		
		if ( ! class_exists('EE_Template'))
		{
		
			require APPPATH.'libraries/Template.php';
	
		}	
				
		$query = $this->EE->db->query("SELECT template_name, data_title, template_data, enable_template FROM exp_specialty_templates WHERE site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."' AND template_name = '".$this->EE->db->escape_str($name)."'");
		

		if ($query->num_rows() == 0)
		{
			return array('title' => '', 'data' => '');
			
		}else{
		
			$this->EE->TMPL = new EE_Template;

			$this->EE->TMPL->parse_php = TRUE;
		
			$this->EE->TMPL->parse($query->row("template_data"), FALSE, $this->EE->config->item('site_id'));
		
			$template_data = $this->EE->TMPL->parse_globals($this->EE->TMPL->final_template);	
    
   		 	$template_data = nl2br(relative_to_absolute($template_data, $this->EE->config->item('site_url')));
    
    		//Wrap email content into a email wrapper
    		//Check the mailtype
    		$this->EE->load->library("email");
		
			if($this->EE->email->mailtype == "html"){
				//check the existence of a mailwrapper snippet
				$query2 = $this->EE->db->query("SELECT template_name, data_title, template_data, enable_template FROM exp_specialty_templates WHERE site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."' AND template_name = 'wrapper'");
		
				if ($query2->num_rows() > 0){

					$this->EE->TMPL = new EE_Template;

					$this->EE->TMPL->parse_php = TRUE;
				
					$this->EE->TMPL->parse($query2->row("template_data"), FALSE, $this->EE->config->item('site_id'));
				
					$template_data_wrapper = $this->EE->TMPL->parse_globals($this->EE->TMPL->final_template);	
		    
		    		$template_data_wrapper = str_replace("{subject}", $query->row("data_title"), $template_data_wrapper );
		    		
		    		$template_data_final = str_replace("{template_content}", $template_data, $template_data_wrapper );
		    		
					
				}else{
					//not found, ignore
					
					$template_data_final = str_replace("\r\n","",$template_data);//nl2br($template_data);
					
				}
					
			}else{
					
				$template_data_final = strip_tags(preg_replace('/\<br(\s*)?\/?\>/i', "\n", $template_data));//; //preg_replace('/\<br(\s*)?\/?\>/i', "\n", $template_data);
					
			}

		}

		if ($query->row('enable_template')  == 'y')
		{
			return array('title' => $query->row('data_title') , 'data' => $template_data_final );
		}
		
		//$this->EE->load->library('security');
		
		if ($this->EE->session->userdata['language'] != '')
		{
			$user_lang = $this->EE->session->userdata['language'];
		}
		else
		{
			if ($this->EE->input->cookie('language'))
			{
				$user_lang = $this->EE->input->cookie('language');
			}
			elseif ($this->EE->config->item('deft_lang') != '')
			{
				$user_lang = $this->EE->config->item('deft_lang');
			}
			else
			{
				$user_lang = 'english';
			}
		}

		$user_lang = $this->EE->security->sanitize_filename($user_lang);

		if ( function_exists($name))
		{
			$title = $name.'_title';
		
			return array('title' => $title(), 'data' => $name());
		}
		else
		{
			if ( ! @include(APPPATH.'language/'.$user_lang.'/email_data.php'))
			{
				return array('title' => $query->row('data_title') , 'data' => $template_data_final );
			}
			
			if (function_exists($name))
			{
				$title = $name.'_title';
		
				return array('title' => $title(), 'data' => $name());
			}
			else
			{
				return array('title' => $query->row('data_title') , 'data' => $template_data_final );
			}
		}
	}	
}


class Libraree_Loader extends CI_Loader {

	public $_ci_view_path = ''; // deprecated, do not change, was private in 2.1.5 and will be private again in the near future
	private $ee_view_depth = 0;
	
	
	/**
	 * Load CI View
	 *
	 * This is extended to keep some backward compatibility for people
	 * changing _ci_view_path. I tried doing a getter/setter, but since all
	 * of CI's object references are stuck onto the loader when loading views
	 * I get access errors left and right. -pk
	 *
	 * @deprecated
	 * @access	public
	 */
	public function view($view, $vars = array(), $return = FALSE)
	{
		if ($this->ee_view_depth === 0 && $this->_ci_view_path != '')
		{
			$this->_ci_view_paths = array($this->_ci_view_path => FALSE) + $this->_ci_view_paths;
		}
		
		$this->ee_view_depth++;
		
		$ret = parent::view($view, $vars, $return);
		
		$this->ee_view_depth--;		
		
		if ($this->ee_view_depth === 0 && $this->_ci_view_path != '')
		{
			array_shift($this->_ci_view_paths);
		}
		
		return $ret;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Load EE View
	 *
	 * This is for limited use inside packages. It loads from EE's main cp
	 * theme folder and ignores the package's view folder. The main reason
	 * for doing this are layout things, like the glossary. Most developers
	 * will not need this. -pk
	 *
	 * @param	string		
	 * @param	array 	variables to be loaded into the view
	 * @param	bool 	return or not
	 * @return	void
	 */
	public function ee_view($view, $vars = array(), $return = FALSE)
	{
		$ee_only = array();
		$orig_paths = $this->_ci_view_paths;
		
		// Regular themes cascade down to the first
		// path (APPPATH.'views'), so we copy them over
		// until we hit a third party or non_cascading path.
		
		foreach (array_reverse($orig_paths, TRUE) as $path => $cascade)
		{
			if (strpos($path, PATH_THIRD) !== FALSE OR $cascade === FALSE)
			{
				break;
			}
			
			$ee_only[$path] = TRUE;
		}
		
		// Temporarily replace them, load the view, and back again
		$this->_ci_view_paths = array_reverse($ee_only, TRUE);
		
		$ret = $this->view($view, $vars, $return);
		
		$this->_ci_view_paths = $orig_paths;
		
		return $ret;
	}
	
	// ------------------------------------------------------------------------	
	
	/**
	 * Add to the theme cascading
	 *
	 * Adds a theme to cascade down to. You probably don't
	 * need to call this. No really, don't.
	 */
	public function add_theme_cascade($theme_path)
	{
		$this->_ci_view_paths = array($theme_path => TRUE) + $this->_ci_view_paths;
	}
	
	// ------------------------------------------------------------------------	
	
	/**
	 * Get top of package path
	 *
	 * We use this to allow package js/css loading, where we need to figure out
	 * a theme name. May be renamed in the future, don't use it.
	 */
	public function first_package_path()
	{
		reset($this->_ci_view_paths);
		return key($this->_ci_view_paths);
	}
	
	
	function library($library = '', $params = NULL, $object_name = NULL)
	{
		
		if (is_array($library))
		{
			foreach($library as $read)
			{
				$this->library($read);	
			}
			
			return;
		}
		
		//2.1.3 - Build: 20110411 fix
		if (strtolower($library) == 'security')
		{
			return NULL;
		}

		if (is_array($library))
		{
			foreach ($library as $class)
			{
				$this->_ci_load_class($library, $params, $object_name);
				if($library == "email"){
					$this->loadMail();
				}
			}
		}
		else
		{
		///////CHECK IF LIBRAREE HAS BEEN INSTALLED, OR ONLY LOAD THIS EXTENSION OF LIBRAREE EXISTS + SETTING IN ADDON SETTINGS (html or plain)
			$this->_ci_load_class($library, $params, $object_name);
			if($library == "email"){
				$this->loadMail();
			}
		}

		//EE 2.1.1
		if(method_exists($this,'_ci_assign_to_models')){
			$this->_ci_assign_to_models();
		}
		
		//2.1.3 - Build: 20110411 fix
		return parent::library($library, $params, $object_name);
		
	
	}

	
	function loadMail(){
    		include_once(PATH_THIRD."libraree/libraries/LibrarEE_Email.php");
			$EE =& get_instance();
			$EE->email = new LibrarEE_Email;
	}
	
}

	
//$EE =& get_instance();
//$results = $EE->db->query("SELECT module_id FROM ".$EE->db->dbprefix('modules')." WHERE module_name = 'Libraree'");
//
//if ($results->num_rows > 0){
//	//$EE->load 	 = new Libraree_Loader; 
//	//2.1.3 - Build: 20110411 fix
//	
//	$EE->load =& load_class('Loader', 'core');
//	$EE->load = new Libraree_Loader; 
//
//
//}	