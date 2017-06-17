<?php

/**
 * ExpressionEngine Wyvern Module Class
 *
 * @package     ExpressionEngine
 * @subpackage  Modules
 * @category    Wyvern
 * @author      Brian Litzinger
 * @copyright   Copyright (c) 2010, 2011 - Brian Litzinger
 * @link        http://boldminded.com/add-ons/wyvern
 * @license 
 *
 * Copyright (c) 2011, 2012. BoldMinded, LLC
 * All rights reserved.
 *
 * This source is commercial software. Use of this software requires a
 * site license for each domain it is used on. Use of this software or any
 * of its source code without express written permission in the form of
 * a purchased commercial or other license is prohibited.
 *
 * THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY
 * KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
 * PARTICULAR PURPOSE.
 *
 * As part of the license agreement for this software, all modifications
 * to this source must be submitted to the original author for review and
 * possible inclusion in future releases. No compensation will be provided
 * for patches, although where possible we will attribute each contribution
 * in file revision notes. Submitting such modifications constitutes
 * assignment of copyright to the original author (Brian Litzinger and
 * BoldMinded, LLC) for such modifications. If you do not wish to assign
 * copyright to the original author, your license to  use and modify this
 * source is null and void. Use of this software constitutes your agreement
 * to this clause.
 */

require_once PATH_THIRD.'wyvern/config.php';
 
class wyvern {
    
    function __construct()
    {
        $this->EE =& get_instance();
        
        // Create cache
        if (! isset($this->EE->session->cache['wyvern']))
        {
            $this->EE->session->cache['wyvern'] = array();
        }
        $this->cache =& $this->EE->session->cache['wyvern'];
        
        if (! isset($this->EE->wyvern_helper))
        {
            require PATH_THIRD.'wyvern/helper.wyvern.php';
            $this->EE->wyvern_helper = new Wyvern_helper;
        }
    }
    
    function load_pages()
    {
        $pages = $this->EE->wyvern_helper->_get_pages() . 
                 $this->EE->wyvern_helper->_get_taxonomy_pages() . 
                 $this->EE->wyvern_helper->_get_navee_pages();
        
        $this->send_ajax_response($pages);
    }
    
    function load_templates()
    {
        $templates = $this->EE->wyvern_helper->_get_templates();
        
        $this->send_ajax_response($templates);
    }
    
    function save_toolbar($return = true)
    {
        $data = $this->EE->input->post('wyvern');
        
        $toolbar = $data['toolbar'];
        $toolbar_name = $data['toolbar_name'];

        $query = $this->EE->db->where('toolbar_name', $toolbar_name)->get('wyvern_toolbars');
        
        $data = array(
            'toolbar_name' => $toolbar_name,
            'toolbar_settings' => serialize($toolbar)
        );

        if($query->num_rows() == 1)
        {
            $this->EE->db->where('toolbar_name', $toolbar_name)
                         ->update('wyvern_toolbars', $data);
                         
            $id = $this->EE->db->select('id')
                               ->where('toolbar_name', $toolbar_name)
                               ->get('wyvern_toolbars')
                               ->row('id');
        }
        else
        {
            $this->EE->db->insert('wyvern_toolbars', $data);
            $id = $this->EE->db->insert_id();
        }
        
        if($return)
        {
            $this->EE->output->enable_profiler(FALSE);
        
            echo $id;
            exit;
        }
    }
    
    function load_toolbar()
    {
        $this->send_ajax_response($this->EE->wyvern_helper->_get_toolbar_options_list($this->EE->input->post('id')));
    }
    
    function delete_toolbar()
    {
        $id = $this->EE->input->post('id');
        
        // Make sure we have a valid ID to delete so the response is correct
        $query = $this->EE->db->where('id', $id)->get('wyvern_toolbars');
        
        if($query->num_rows() == 1)
        {
            $this->EE->db->where('id', $id)
                         ->delete('wyvern_toolbars');
                         
            $this->send_ajax_response('true');
        }
        else
        {
            $this->send_ajax_response('false');
        }
    }

    private function send_ajax_response($msg)
    {
        $this->EE->output->enable_profiler(FALSE);
        
        @header('Content-Type: text/html; charset=UTF-8');  
        
        exit($msg);
    }
    
    private function debug($str, $die = false)
    {
        echo '<pre>';
        var_dump($str);
        echo '</pre>';
        
        if($die) die;
    }
    
}