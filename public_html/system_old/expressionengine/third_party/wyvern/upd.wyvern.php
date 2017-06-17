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

class Wyvern_upd {

    public $version = WYVERN_VERSION;

    public function Wyvern_upd($switch = TRUE)
    {
        $this->EE =& get_instance();
    }

    public function install()
    {
        // Module data
        $data = array(
            'module_name' => WYVERN_NAME,
            'module_version' => WYVERN_VERSION,
            'has_cp_backend' => 'n',
            'has_publish_fields' => 'n'
        );

        $this->EE->db->insert('modules', $data);

        // Create our external tables
        $this->EE->load->dbforge();

        if (! $this->EE->db->table_exists('wyvern_toolbars'))
        {
            $this->EE->dbforge->add_field(array(
                'id'                 => array('type' => 'int', 'constraint' => 10, 'unsigned' => TRUE, 'auto_increment' => TRUE),
                'toolbar_name'       => array('type' => 'varchar', 'constraint' => 256),
                'toolbar_settings'   => array('type' => 'text'),
            ));

            $this->EE->dbforge->add_key('id', TRUE);
            $this->EE->dbforge->create_table('wyvern_toolbars');

            require PATH_THIRD.'wyvern/config.php';
            $this->EE->db->insert('wyvern_toolbars', array('toolbar_name' => 'Default', 'toolbar_settings' => serialize($config['toolbar_buttons'])));
        }

        // Insert our Action
        $query = $this->EE->db->get_where('actions', array('class' => WYVERN_NAME));

        if($query->num_rows() == 0)
        {
            $data = array(
                'class' => WYVERN_NAME,
                'method' => 'save_toolbar'
            );

            $this->EE->db->insert('actions', $data);

            $data = array(
                'class' => WYVERN_NAME,
                'method' => 'load_toolbar'
            );

            $this->EE->db->insert('actions', $data);

            $data = array(
                'class' => WYVERN_NAME,
                'method' => 'delete_toolbar'
            );

            // Add additional actions
            $this->update_125();
            $this->update_140();

            $this->EE->db->insert('actions', $data);
        }

        $query = $this->EE->db->where('field_type', 'wyvern')
                              ->get('channel_fields');

        foreach($query->result_array() as $row)
        {
            // Get old settings
            $settings = unserialize(base64_decode($row['field_settings']));

            if(isset($settings['wyvern']['toolbar']) AND !isset($settings['wyvern']['version']))
            {
                $data = array(
                    'toolbar_name' => $row['field_label'] .' (imported)',
                    'toolbar_settings' => serialize($settings['wyvern']['toolbar'])
                );

                // Create a toolbar based on the old settings toolbar value
                $this->EE->db->insert('wyvern_toolbars', $data);

                $new_settings = $settings;
                // Set this, used in check above, just incase someone uninstalls and re-installs after upgrading to 1.1.
                $new_settings['wyvern']['version'] = '1.1';
                // Reset it
                $new_settings['wyvern']['toolbar'] = array();

                $members = $this->EE->db->where('can_access_cp', 'y')
                                        ->where('site_id', $this->EE->config->item('site_id'))
                                        ->get('member_groups');

                foreach($members->result_array() as $group)
                {
                    $new_settings['wyvern']['toolbar']['group_'. $group['group_id']] = $this->EE->db->insert_id();
                }

                // Update the field's settings with old values, but with toolbar set to the newly created wyvern_toolbars id
                $this->EE->db->where('field_id', $row['field_id'])
                             ->update('channel_fields', array('field_settings' => base64_encode(serialize($new_settings))));
            }
        }

        if (! isset($this->EE->wyvern_helper))
        {
            require PATH_THIRD.'wyvern/helper.wyvern.php';
            $this->EE->wyvern_helper = new Wyvern_helper;
        }

        $this->EE->db->where('name', 'wyvern')
                     ->update('fieldtypes', array('settings' => base64_encode(serialize($this->EE->wyvern_helper->default_settings))));

        return TRUE;
    }

    public function uninstall()
    {
        $this->EE->db->where('module_name', WYVERN_NAME);
        $this->EE->db->delete('modules');

        $this->EE->db->where('class', WYVERN_NAME)->delete('actions');

        $this->EE->load->dbforge();
        $this->EE->dbforge->drop_table('wyvern_toolbars');

        return TRUE;
    }

    public function update($current = '')
    {
        if($current < '1.2.5')
        {
            $this->update_125();
        }

        if($current < '1.4.0')
        {
            $this->update_140();
        }

        if($current < '1.6.3')
        {
            $this->update_163();
        }

        return TRUE;
    }

    private function update_125()
    {
        $this->EE->load->dbforge();

        $data = array(
            'class' => WYVERN_NAME,
            'method' => 'load_pages'
        );

        $this->EE->db->insert('actions', $data);

        $data = array(
            'class' => WYVERN_NAME,
            'method' => 'load_templates'
        );

        $this->EE->db->insert('actions', $data);
    }

    private function update_140()
    {
        $this->EE->load->dbforge();

        $this->EE->db->where('module_name', 'Wyvern')->update('modules', array('has_cp_backend' => 'y'));
        $this->EE->db->where('name', 'wyvern')->update('fieldtypes', array('has_global_settings' => 'n'));
    }

    private function update_163()
    {
        $query = $this->EE->db->where('field_type', 'wyvern')
                              ->get('channel_fields');

        foreach($query->result_array() as $row)
        {
            // Get old settings
            $settings = unserialize(base64_decode($row['field_settings']));

            if(isset($settings['wyvern']) && isset($settings['wyvern']['allow_img_urls']))
            {
                $settings['wyvern']['allow_img_urls'] = 'yes';

                // Update the field's settings with old values, but with toolbar set to the newly created wyvern_toolbars id
                $this->EE->db->where('field_id', $row['field_id'])
                             ->update('channel_fields', array(
                                 'field_settings' => base64_encode(serialize($settings))
                             ));
            }
        }
    }
}