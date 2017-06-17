<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
====================================================================================================
 Author: Brandon O'Hara
 http://brandonohara.com
====================================================================================================
 This file must be placed in the system/expressionengine/third_party/simplee_search folder in your ExpressionEngine installation.
 package 		simplEE Search (EE2 Version)
 version 		Version 1.0.0
 copyright 		Copyright (c) 2014 Brandon O'Hara <brandon@brandonohara.com>
----------------------------------------------------------------------------------------------------
 Purpose: Quickly search channel entries by multiple fields.
====================================================================================================

*/
require_once APPPATH."modules/channel/mod.channel.php";
					

class Simplee_search extends Channel {
	
	function __construct(){
		//EE super global
		$this->EE =& get_instance();
	}
	
	function results(){
		$fields = $this->_get_fields($this->EE->TMPL->fetch_param("fields"));
		$channels = $this->_get_channels($this->EE->TMPL->fetch_param("channel"));
			
		$search = strtolower($this->EE->TMPL->fetch_param('query'));
		$explode = $this->EE->TMPL->fetch_param('explode') == 'no' ? FALSE : TRUE;
		$weight_fields = $this->EE->TMPL->fetch_param('weight') == 'no' ? FALSE : TRUE;
		$delimiter = $this->EE->TMPL->fetch_param('delimiter') ? $this->EE->TMPL->fetch_param('delimiter') : ' ';
				
		$terms = $explode ? explode($delimiter, $search) : array($search);
		$query = $this->EE->db->from('exp_channel_titles')
							->join('exp_channel_data', 'exp_channel_titles.entry_id = exp_channel_data.entry_id')
							->where_in('exp_channel_titles.channel_id', $this->_get_channel_ids($channels))
							->order_by('entry_date', 'ASC')
							->get();
		
		if ($query->num_rows() == 0 || $search == '')
		        return ee()->TMPL->no_results();
		
		$entries = array();       
	    foreach($query->result_array() as $row){
	    	$points = 0;
			$weight = $weight_fields ? count($fields) : 1; 
	    	foreach($fields as $field){
	    		foreach($terms as $term){
		    		$points += substr_count(strtolower($row[$field]), $term) * $weight;
	    		}
	    		$points += substr_count(strtolower($row[$field]), $search) * 10 * $weight;
				$weight = $weight_fields ? $weight - 1 : $weight;
	    	}
	    	
	    	if($points > 0){
		    	if(!isset($entries[$points]))
		    		$entries[$points] = array();
		    	array_unshift($entries[$points], $row['entry_id']);
	    	}
		}
		
		ksort($entries);
		$entries = array_reverse($entries);
		
		$segments = array();
		foreach($entries as $point){
			array_push($segments, implode("|", $point));
		}
		$entries = implode("|", $segments);
		
		if($entries == "")
			return ee()->TMPL->no_results();
			
	    parent::Channel();
        $this->EE->TMPL->tagparams['dynamic'] = 'no';
        $this->EE->TMPL->tagparams['fixed_order'] = $entries;
        return parent::entries();
	}
	
	function _get_channels($channels){
		$channels = explode("|", $channels);
		$query = $this->EE->db->where_in('channel_name', $channels)->from('exp_channels')->get();
		if($query->num_rows() == 0)
			return FALSE;
		return $query->result_array();	
	}
	
	function _get_channel_ids($channels){
		$channel_names = array();
		foreach($channels as $channel){
			array_push($channel_names, $channel['channel_id']); 
		}
		return $channel_names;
	}
	
	function _get_fields($fields_string){
		$fields = array();
		$fields_string = explode("|", $fields_string);
		foreach($fields_string as $field_name){
			$query = $this->EE->db->where('field_name', $field_name)->from('exp_channel_fields')->get();
			if($query->num_rows() == 1){
				$row = $query->row_array();
				array_push($fields, 'field_id_'.$row['field_id']);
			} else if($field_name == 'title')
				array_push($fields, $field_name);
		}
		
		if(count($fields) == 0)
			array_push($fields, 'title');
		
		return $fields;
	}

}
/* End of file mod.simplee_search.php */
/* Location: system/expressionengine/third_party/simplee_search/mod.simplee_search.php */