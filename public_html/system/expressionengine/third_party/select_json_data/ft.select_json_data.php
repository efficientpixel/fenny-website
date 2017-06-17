<?php

/*
=====================================================
 Select from JSON data
-----------------------------------------------------
 http://www.intoeetive.com/
-----------------------------------------------------
 Copyright (c) 2016 Yuri Salimovskiy
=====================================================
*/



if ( ! defined('BASEPATH'))
{
    exit('Invalid file request');
}


class Select_json_data_ft extends EE_Fieldtype {
	
	var $info = array(
		'name'		=> 'Select from JSON data',
		'version'	=> '0.1'
	);
    
    public $has_array_data = true;
    
	
	public function __construct()
	{
        ee()->lang->loadfile('select_json_data');
	}
	
    
    function display_settings($data)
    {
        $modes = array(
            'single'    => lang('single_selection'),
            'multiple'    => lang('multiple_selection')
        );    
        
        ee()->table->add_row(
            lang('mode'),
            form_dropdown('mode', $modes, (isset($data['mode'])?$data['mode']:''))
        );
        
        ee()->table->add_row(
            lang('source_url'),
            form_input('source_url', (isset($data['source_url'])?$data['source_url']:''))
        );
        
        ee()->table->add_row(
            lang('root_element'),
            form_input('root_element', (isset($data['root_element'])?$data['root_element']:''))
        );
        
        ee()->table->add_row(
            lang('value_element'),
            form_input('value_element', (isset($data['value_element'])?$data['value_element']:''))
        );
        
        ee()->table->add_row(
            lang('label_element'),
            form_input('label_element', (isset($data['label_element'])?$data['label_element']:''))
        );
    }
    
	/**
	 * Display Field on Publish
	 *
	 * @access	public
	 * @param	existing data
	 * @return	field html
	 *
	 */
	function display_field($data)
	{
        if (!empty($data))
        {
            if (is_array($data))
            {
                $data['json'] = $data;
            }
            else
            {
                $data = unserialize(base64_decode($data));
            }
        }
        if (!isset($this->settings['source_url']) || $this->settings['source_url']=='')
        {
            return;
        }
        
        $link = '<link rel="stylesheet" href="'.URL_THIRD_THEMES.'/select_json_data/searchable-option-list/sol.css" type="text/css" media="screen" />'.PHP_EOL;
        ee()->cp->add_to_head($link);
        
        $link = '<script type="text/javascript" src="'.URL_THIRD_THEMES.'/select_json_data/searchable-option-list/sol.js"></script>'.PHP_EOL;
        ee()->cp->add_to_head($link);
        
        $file = file_get_contents($this->settings['source_url']);
        if ($file===false)
        {
            return;
        }
        
        $options = array();
        if ($this->settings['mode']!='multiple')
        {
            $options[''] = '';
        }
        
        $json = json_decode($file, true);
        if ($json!==NULL)
        {
            //use json data
            if ($this->settings['root_element']!='')
            {
                $json = $json[$this->settings['root_element']];
            }
            
            foreach ($json as $item)
            {
                $options[$item[$this->settings['value_element']]] = $item[$this->settings['label_element']];
            }
        }
        else if (!empty($data))
        {
            //use stored data
            foreach ($data['json'] as $key=>$item)
            {
                $options[$key] = $item[$this->settings['label_element']];
            }
        }
        else
        {
            return;
        }
        
        $name = (isset($this->cell_name)) ? $this->cell_name : $this->field_name;
        
        if (!empty($data))
        {
            $data = $data['data'];
        }
        
        if ($this->settings['mode']=='multiple')
        {
            $input = form_multiselect($name.'[]', $options, $data, ' id="'.ee()->security->sanitize_filename($name).'"');
        }
        else
        {
            $input = form_dropdown($name, $options, $data, ' id="'.ee()->security->sanitize_filename($name).'"');
        }
        
        
        $script = '$(\'#'.ee()->security->sanitize_filename($name).'\').searchableOptionList();';
        
        ee()->javascript->output($script);
		
		return $input;
        
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Replace tag
	 *
	 * @access	public
	 * @param	field contents
	 * @return	replacement text
	 *
	 */
	function replace_tag($data, $params = array(), $tagdata = FALSE, $modifier='')
    {
        if (!empty($data))
        {
            $data = unserialize(base64_decode($data));
        }
        else
        {
            return;
        }
        
        
        if ($tagdata===FALSE)
        {
            return $data['data'];
        }
        
        $vars = array();
        $json_a = $data['json'];

        foreach ($data['data'] as $item)
        {
            $row = array();
            $row[$this->settings['value_element']] = $item;
            foreach ($json_a[$item] as $key=>$val)
            {
                if (!is_array($val))
                {
                    $row[$key] = $val;
                }
            }
            $vars[] = $row;
        }

      
        $tagdata = ee()->TMPL->parse_variables($tagdata, $vars);
        
        return $tagdata;
        
    }
    


    function save($data)
	{
        if (!is_array($data))
        {
            $data = array($data);
        }
        $save = array(
            'data'  => $data
        );
        
        $file = file_get_contents($this->settings['source_url']);
        
        if ($file===false)
        {
            return base64_encode(serialize(array()));
        }
        
        $json = json_decode($file, true);
        if ($json!==NULL)
        {
            //use json data
            if ($this->settings['root_element']!='')
            {
                $json = $json[$this->settings['root_element']];
            }
            
            $data_json = array();

            foreach ($json as $item)
            {
                if (in_array($item[$this->settings['value_element']], $data))
                {
                    $data_json[$item[$this->settings['value_element']]] = $item;
                }
            }
            $save['json'] = $data_json;
        }

        return base64_encode(serialize($save));
	}
    
    function save_settings($data) {
        return array(
            'mode' => ee()->input->post('mode'),
            'source_url' => ee()->input->post('source_url'),
            'root_element' => ee()->input->post('root_element'),
            'value_element' => ee()->input->post('value_element'),
            'label_element' => ee()->input->post('label_element')
        );
    }
    
    
   	// ------------------------
	// P&T MATRIX SUPPORT
	// ------------------------
	
	/**
	 * Display Matrix field
	 */
	function display_cell($data) {
		return $this->display_field($data);
    }
	
    function display_cell_settings($data)
	{
	   return array();  
    }
    
    function save_cell_settings($data) {
		return $this->save_settings($data);
	}
    
	function save_cell($data)
	{
		return $this->save($data);
	}
    
    
   	function grid_display_settings($data)
	{
		return array(
			$this->grid_field_formatting_row($data)
		);
	}
    
	// --------------------------------------------------------------------
	
	/**
	 * Install Fieldtype
	 *
	 * @access	public
	 * @return	default global settings
	 *
	 */
	function install()
	{
		return array();
	}
	

}

/* End of file ft.google_maps.php */
/* Location: ./system/expressionengine/third_party/google_maps/ft.google_maps.php */