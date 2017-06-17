<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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

class Wyvern_mcp {

    function __construct()
    {
        $this->EE =& get_instance();

        $this->site_id = $this->EE->config->item('site_id');

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

        $this->base_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=wyvern';

        $msg = $this->EE->session->flashdata('message_success');

        if ($msg)
        {
            $this->_destroy_notice();
        }

        $this->EE->view->cp_page_title = WYVERN_NAME;
    }

    public function index()
    {
        $this->EE->load->library('table');
        $this->EE->load->helper('path');
        $this->EE->load->helper('form');

        $vars['action_url'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=wyvern'.AMP.'method=save_settings';
        $vars['hidden'] = NULL;
        $vars['files'] = array();
        $vars['morphine'] = array_key_exists('nsm_morphine', $this->EE->addons->get_installed());

        $vars['settings'] = array_merge($this->EE->wyvern_helper->default_settings[1], $this->EE->wyvern_helper->_get_global_settings(true));

        return $this->EE->load->view('index', $vars, TRUE);
    }

    public function save_settings()
    {
        $data = $this->EE->input->post('wyvern');

        // Save the current toolbar first
        require_once PATH_THIRD .'wyvern/mod.wyvern.php';
        $wyvern = new wyvern;

        $wyvern->save_toolbar(false);

        // Now save the global settings
        $settings = array(
            'field_css_path' => isset($data['field_css_path']) ? $data['field_css_path'] : '',
            'field_js_path' => isset($data['field_js_path']) ? $data['field_js_path'] : '',
            'field_google_fonts' => isset($data['field_google_fonts']) ? $data['field_google_fonts'] : '',
            'field_typekit' => isset($data['field_typekit']) ? $data['field_typekit'] : '',
            'field_encode_ee_tags' => isset($data['field_encode_ee_tags']) ? $data['field_encode_ee_tags'] : '',
            'field_extra_config' => isset($data['field_extra_config']) ? $data['field_extra_config'] : '',
            'field_obfuscate_email' => isset($data['field_obfuscate_email']) ? $data['field_obfuscate_email'] : '',
            'field_wymeditor_style' => isset($data['field_wymeditor_style']) ? $data['field_wymeditor_style'] : '',
            'field_image_paragraphs' => isset($data['field_image_paragraphs']) ? $data['field_image_paragraphs'] : 'yes',
            // 'field_extra_plugins' => isset($data['field_extra_plugins']) ? $data['field_extra_plugins'] : '',
            'field_default_link_type' => isset($data['field_default_link_type']) ? $data['field_default_link_type'] : '',
            'field_file_manager' => isset($data['field_file_manager']) ? $data['field_file_manager'] : '',

            'field_templates' => array(
                'show_all' => isset($data['field_show_all_templates']) ? $data['field_show_all_templates'] : '',
                'show_group' => isset($data['field_show_group_templates']) ? $data['field_show_group_templates'] : '',
                'show_selected' => isset($data['field_show_selected_templates']) ? $data['field_show_selected_templates'] : '',
                'templates' => isset($data['field_template_select']) ? $data['field_template_select'] : ''
            )
        );

        // Grab ALL our global settings, and refresh the cache
        $global_settings = $this->EE->wyvern_helper->_get_global_settings(false, true);

        // Save our new/updated settings to current site
        $global_settings[$this->site_id] = $settings;

        $data = array('settings' => base64_encode(serialize($global_settings)));
        $where = array('name' => 'wyvern');

        $this->EE->wyvern_helper->insert_or_update('fieldtypes', $data, $where, 'fieldtype_id');

        $this->EE->session->set_flashdata('message_success', 'global_settings_saved');
        $this->EE->functions->redirect($this->base_url);
        exit;
    }

    private function _cp_menu()
    {
        $default = array(
            'menu_home' => $this->base_url
        );

        $this->EE->cp->set_right_nav($default);
    }

    /*
        Show EE notification and hide it after a few seconds
    */
    private function _destroy_notice()
    {
        $this->EE->javascript->output(array(
            'window.setTimeout(function(){$.ee_notice.destroy()}, 4000);'
        ));
    }
}