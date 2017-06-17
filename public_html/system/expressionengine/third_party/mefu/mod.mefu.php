<?php 

class Mefu {

    var $settings       = array();
    
    var $name           = 'MEFU';
    var $version        = '1.0.0';
    var $description    = 'Multiple Entry Field Updater';
    var $docs_url       = 'http://expressionengine.com';
	    
    // -------------------------------
    //   Constructor - Extensions use this for settings
    // -------------------------------
    
	function Mefu(){
        $this->EE =& get_instance();
    }
    
    function save(){
    	
    	$this->EE->load->dbforge();
    	//if(isset($_POST["mefu"])){ 
    	if(isset($_POST)){   
    		$tagdata = $this->EE->TMPL->tagdata; 

    		foreach($_POST as $key => $value){
    			$key_data = explode(":", $key);
    			if(sizeof($key_data)==2){
					$test = 'yes';
    				$entry_id = $key_data[0];
    				$field_name = $key_data[1];
    				$data = $value;
    				if($field_name=='status'){
    					$this->EE->db->query("UPDATE exp_channel_titles SET status = '$data' WHERE entry_id = $entry_id");
    					//echo "UPDATE exp_channel_titles SET status = '$data' WHERE channel_id = $entry_id";
    				} else {
	    				$field_ids = $this->EE->db->query("SELECT field_id FROM exp_channel_fields WHERE field_name = '$field_name'");
	    				
	    				foreach($field_ids->result_array() AS $row)
	    					$field_id = $row['field_id'];
	    					
	    				$this->EE->db->query("UPDATE exp_channel_data SET field_id_".$field_id." = '$data' WHERE entry_id = $entry_id");
    				}
    			}
    		}
    		
      		return $tagdata;
    	} 
    	
    }
	
	function getids() {
		$entryids = array();
		
		//$this->return_data = 'test';
		foreach($_POST as $key => $value){
    			$key_data = explode(":", $key);
    			if(sizeof($key_data)==2){
					$test = 'yes';
    				$entry_id = $key_data[0];
    				$field_name = $key_data[1];
    				$data = $value;
    				if($field_name=='status'){
						array_push($entryids, $entry_id);
    				}
    			}
    		}
		$ids = implode('|', $entryids);
		return $ids;
	}
    
    

    
    
}
