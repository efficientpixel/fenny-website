<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine Wyvern Extension Class
 *
 * @package     ExpressionEngine
 * @subpackage  Extensions
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

class Wyvern_ext {

    public $settings       = array();
    public $name           = WYVERN_NAME;
    public $version        = WYVERN_VERSION;
    public $description    = 'Adds page ID paths to the global vars for use in templates, e.g. {page_url:123} ';
    public $settings_exist = 'y';
    public $docs_url       = 'http://boldminded.com/add-ons/wyvern/';
    public $cache;
    public $parse_order;

    // public $required_by    = array('module');

    /**
     * Constructor
     */
    function __construct($settings = '')
    {
        $this->EE =& get_instance();

        // Create cache
        if (! isset($this->EE->session->cache['wyvern']))
        {
            $this->EE->session->cache['wyvern'] = array();
        }
        $this->cache =& $this->EE->session->cache['wyvern'];

        $this->settings = $this->_get_settings();

        $this->parse_order = isset($this->settings['parse_order']) ? $this->settings['parse_order'] : 'early';
    }

    /**
     * Install the extension
     */
    function activate_extension()
    {
        // Delete old hooks
        $this->EE->db->query("DELETE FROM exp_extensions WHERE class = '". __CLASS__ ."'");

        // Add new hooks
        $ext_template = array(
            'class'    => __CLASS__,
            'settings' => '',
            'priority' => 8,
            'version'  => $this->version,
            'enabled'  => 'y'
        );

        $extensions = array(
            array('hook'=>'sessions_end', 'method'=>'sessions_end'),
            array('hook'=>'channel_entries_tagdata_end', 'method'=>'channel_entries_tagdata_end'),
            array('hook'=>'submit_new_entry_start', 'method'=>'submit_new_entry_start')
        );

        foreach($extensions as $extension)
        {
            $ext = array_merge($ext_template, $extension);
            $this->EE->db->insert('exp_extensions', $ext);
        }

        // Add default setting(s)
        $this->_add_settings();
    }

    /**
     * @param string $current currently installed version
     */
    function update_extension($current = '')
    {
        if ($current == '' OR $current == $this->version)
        {
            return FALSE;
        }
    }

    private function _add_settings()
    {
        // Add default settings
        $insert['parse_order'] = 'early';

        $this->EE->db->where('class', __CLASS__);
        $this->EE->db->update('extensions', array('settings' => serialize($insert)));
    }

    /**
     * Uninstalls extension
     */
    function disable_extension()
    {
        $this->EE->db->delete('extensions', array('class' => __CLASS__));
    }

    /**
     * Add all the page data/urls to the global vars so they can be used anywhere
     */
    function sessions_end($session)
    {
        if($this->parse_order == 'early')
        {
            log_message('debug', 'Wyvern: Parsing {page_url:N} variables - sessions_end - line 119 ext.wyvern.php');

            $pages = $this->_get_pages();
            $site_id = $this->EE->config->item('site_id');

            // Only do this if there is page data, and we're not within the control panel
            if($pages AND REQ == 'PAGE')
            {
                $data = array();
                foreach($pages[$site_id]['uris'] as $id => $url)
                {
                    $data['page_url:'. $id] = $this->_create_url($url);
                }

                $this->EE->config->_global_vars = array_merge($this->EE->config->_global_vars, $data);
            }
        }

        return $session;
    }

    /**
     * Actually called on new entry, and edit entry submissions.
     * Add all our pages to the Global Variables table each time an entry is saved.
     */
    function submit_new_entry_start()
    {
        if($this->parse_order == 'late')
        {
            log_message('debug', 'Wyvern: Parsing {page_url:N} variables - submit_new_entry_start - line 151 ext.wyvern.php');

            $pages = $this->_get_pages();
            $site_id = $this->EE->config->item('site_id');

            $data = array();
            foreach($pages[$site_id]['uris'] as $id => $url)
            {
                $data['page_url:'. $id] = $this->_create_url($url);
            }

            foreach($data as $name => $value)
            {
                // A simple insert_or_update
                $query = $this->EE->db->get_where('global_variables', array('variable_name' => $name), 1, 0);

                $insert_data = array('variable_name' => $name, 'variable_data' => $value);

                if ($query->num_rows() == 0)
                {
                  // A record does not exist, insert one.
                  $query = $this->EE->db->insert('global_variables', $insert_data);
                }
                else
                {
                  // A record does exist, update it.
                  $query = $this->EE->db->update('global_variables', $insert_data, array('variable_name' => $name));
                }
            }
        }
    }

    /**
     * D'oh! global vars can not be used as custom field data because it isn't parsed at the correct time, lets fix that.
     * This will only work if you know the variables name or pattern.
     * If we added a loop, we could go through the entire _global_vars array and replace everything, but we don't need to for this extension.
     */
    function channel_entries_tagdata_end($tagdata, $row, $instance)
    {
        if($this->parse_order == 'early')
        {
            log_message('debug', 'Wyvern: Parsing {page_url:N} variables - channel_entries_tagdata_end - line 193 ext.wyvern.php');

            // has this hook already been called?
            if ($this->EE->extensions->last_call)
            {
                $tagdata = $this->EE->extensions->last_call;
            }

            preg_match_all("/{page_url:(\d+)}/", $tagdata, $matches);

            if(count($matches) > 0)
            {
                foreach($matches[1] as $match => $mval)
                {
                    // If the page ID exists, replace the tag accordingly
                    if(isset($this->EE->config->_global_vars['page_url:'.$mval]))
                    {
                        $tagdata = preg_replace("/{page_url:$mval}/", $this->EE->config->_global_vars['page_url:'.$mval], $tagdata);
                    }
                    // If not, then replace the tag with a blank string, we don't want the tag itself to be rendered
                    else
                    {
                        $tagdata = preg_replace("/{page_url:$mval}/", '', $tagdata);
                    }
                }
            }

            // Something in EE 2.5.3 is messing up the encoding (?)
            preg_match_all("/&#123;page_url:(\d+)&#125;/", $tagdata, $matches);

            if(count($matches) > 0)
            {
                foreach($matches[1] as $match => $mval)
                {
                    // If the page ID exists, replace the tag accordingly
                    if(isset($this->EE->config->_global_vars['page_url:'.$mval]))
                    {
                        $tagdata = preg_replace("/&#123;page_url:$mval&#125;/", $this->EE->config->_global_vars['page_url:'.$mval], $tagdata);
                    }
                    // If not, then replace the tag with a blank string, we don't want the tag itself to be rendered
                    else
                    {
                        $tagdata = preg_replace("/&#123;page_url:$mval&#125;/", '', $tagdata);
                    }
                }
            }
        }

        return $tagdata;
    }

    function settings_form($vars)
    {
        $this->EE->lang->loadfile('wyvern');
        $this->EE->load->library('javascript');

        $vars = array(
            'parse_order' => $this->settings['parse_order'],
            'hidden' => array('file' => 'wyvern') // Don't forget this. save_settings white screens without it
        );

        // Load it up and return it to addons_extensions.php for rendering
        return $this->EE->load->view('settings_form', $vars, TRUE);
    }

    function save_settings()
    {
        if (empty($_POST))
        {
        	show_error($this->EE->lang->line('unauthorized_access'));
        }

        unset($_POST['submit']);

        $insert = array();

        $insert['parse_order'] = $this->EE->input->post('parse_order');

        $this->EE->db->where('class', __CLASS__);
        $this->EE->db->update('extensions', array('settings' => serialize($insert)));

        // If parse late is disabled, clean up the global variables table
        if($insert['parse_order'] != 'late')
        {
            $this->EE->db->delete('global_variables', array('variable_name LIKE' => 'page_url:%'));
        }

        $this->EE->session->set_flashdata('message_success', $this->EE->lang->line('preferences_updated'));
    }

    private function _create_url($url)
    {
        $site_url = $this->EE->config->item('site_url');
        $index_page = $this->EE->config->item('index_page');

        // Make sure the site_url does NOT have a trailing slash
        $site_url = substr($site_url, -1) == '/' ? substr($site_url, 0, -1) : $site_url;
        // Prefix index.php if needed
        $site_url = $index_page ? $site_url .'/'. $index_page : $site_url;

        return $site_url . $url;
    }

    private function _get_pages()
    {
        if(!isset($this->cache['pages_list']))
        {
            $pages = $this->EE->config->item('site_pages');
            $site_id = $this->EE->config->item('site_id');

            // Make sure pages exist, otherwise we get notices
            if(!isset($pages[$site_id]['uris'])) {
                $this->cache['pages_list'] = '';
            } else {
                $this->cache['pages_list'] = $pages;
            }
        }

        return $this->cache['pages_list'];
    }

    /**
    * Get the site specific settings from the extensions table
    * Originally written by Leevi Graham? Modified for EE2.0
    *
    * @param $force_refresh     bool    Get the settings from the DB even if they are in the session
    * @return array                     If settings are found otherwise false. Site settings are returned by default.
    */
    private function _get_settings($force_refresh = FALSE)
    {
        // assume there are no settings
        $settings = FALSE;
        $this->EE->load->helper('string');

        // Get the settings for the extension
        if(isset($this->cache['settings']) === FALSE || $force_refresh === TRUE)
        {
            // check the db for extension settings
            $query = $this->EE->db->query("SELECT settings FROM exp_extensions WHERE enabled = 'y' AND class = '" . __CLASS__ . "' LIMIT 1");

            // if there is a row and the row has settings
            if ($query->num_rows() > 0 && $query->row('settings') != '')
            {
                // save them to the cache
                $this->cache['settings'] = strip_slashes(unserialize($query->row('settings')));
            }
        }

        // check to see if the session has been set
        // if it has return the session
        // if not return false
        if(empty($this->cache['settings']) !== TRUE)
        {
            $settings = $this->cache['settings'];
        }

        return $settings;
    }

    private function debug($str)
    {
        echo '<pre>';
        var_dump($str);
        echo '</pre>';
    }
}