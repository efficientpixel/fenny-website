<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine Wyvern Fieldtype Class
 *
 * @package     ExpressionEngine
 * @subpackage  Fieldtypes
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

class wyvern_ft extends EE_Fieldtype {

    public $settings = array();
    public $has_array_data = FALSE;
    public $info = array(
        'name'      => WYVERN_NAME,
        'version'   => WYVERN_VERSION
    );

    private $site_id;
    private $cache = array();

    /**
     * Constructor
     *
     * @access  public
     */
    function __construct()
    {
        parent::__construct();

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
    }

    function validate($data)
    {
        return TRUE;
    }

    public function accepts_content_type($name)
    {
        return ($name == 'channel' || $name == 'grid');
    }

    /**
     * Normal field display.
     */
    function display_field($data)
    {
        $settings = $this->settings['wyvern'];

        $toolbar = $this->EE->wyvern_helper->_get_toolbar($settings);
        $config = $this->EE->wyvern_helper->_get_field_config($settings, $this->field_name);
        $data = $this->EE->wyvern_helper->_prep_data($data, 'display_field');

        $this->EE->wyvern_helper->_load_assets();

        $field = '<textarea style="margin-top: 30px; height: '. $this->EE->wyvern_helper->_get_height($settings) .'px;" class="wyvern" id="'. $this->EE->wyvern_helper->_create_id($this->field_name) .'" name="'. $this->field_name .'">'. $data .'</textarea>';
        $this->EE->cp->add_to_foot('<!-- Wyvern Assets -->'."\n".'<script type="text/javascript">jQuery(function(){ init_wyvern('. $config .', '. $toolbar .'); });</script>'."\n".'<!-- / Wyvern Assets -->');

        return $field;
    }

    /**
     * Grid field display in the CP
     *
     * @param  string $data
     * @return string
     */
    function grid_display_field($data)
    {
        $settings = $this->settings['wyvern'];
        $field_name = 'col_id_'.$this->settings['col_id'];

        $toolbar = $this->EE->wyvern_helper->_get_toolbar($settings);
        $config = '['. $this->EE->wyvern_helper->_get_field_config($settings, $field_name) .']';
        $data = $this->EE->wyvern_helper->_prep_data($data, 'display_field');

        $this->EE->wyvern_helper->_load_assets();
        $this->EE->wyvern_helper->_load_js_grid();

        $field = '<textarea style="margin-top: 30px; height: '. $this->EE->wyvern_helper->_get_height($settings) .'px;" class="wyvern" id="'. $this->settings['col_name'] .'" name="'. $this->field_name .'" data-toolbar="'. $toolbar .'" data-config="'. $config .'" data-field-id="'. $this->field_id .'">'. $data .'</textarea>';

        return $field;
    }

    /**
     * Content Element field display in the CP
     *
     * @param  string $data
     * @return string
     */
    function display_element($data)
    {
        $settings = $this->settings['wyvern'];
        $field_id = random_string('alnum', 16);

        $toolbar = $this->EE->wyvern_helper->_get_toolbar($settings);
        $config = $this->EE->wyvern_helper->_get_field_config($settings, $field_id);
        $data = $this->EE->wyvern_helper->_prep_data($data, 'display_field');

        $this->EE->wyvern_helper->_load_assets();
        $this->EE->wyvern_helper->_load_js_content_elements();
        $js = '';

        $field = '<textarea style="height: '. $this->EE->wyvern_helper->_get_height($settings) .'px;" class="wyvern" id="'. $field_id .'" name="'. $this->field_name .'" data-field-id="'. $field_id .'" data-toolbar="'. $toolbar .'" data-config="'. $config .'">'. $data .'</textarea>';

        // If its a visible instance on page load with existing content, then initialize immediately.
        // Dynamically added fields will be handled by content_element.js->onDisplay()
        if ($this->field_name != '__element_name__[__index__][data]')
        {
            $this->EE->cp->add_to_foot('<!-- Wyvern Assets -->'."\n".'<script type="text/javascript">jQuery(function(){ init_wyvern('. $config .', '. $toolbar .'); });</script>'."\n".'<!-- / Wyvern Assets -->');
        }

        return $field;
    }

    /**
     * Matrix cell display
     */
    function display_cell($data)
    {
        $settings = $this->settings['wyvern'];
        $field_name = 'col_id_'.$this->col_id;

        $toolbar = $this->EE->wyvern_helper->_get_toolbar($settings);
        $config = '['. $this->EE->wyvern_helper->_get_field_config($settings, $field_name) .']';

        // Fix for Matrix: http://help.pixelandtonic.com/brandonkelly/topics/wygwam_in_a_matrix_field_how_to_output_xml_html
        // Apparently not needed for Matrix 2.3?
        if(defined('MATRIX_VER') AND version_compare(MATRIX_VER, '2.3', '<'))
        {
            $data = htmlentities($data, ENT_COMPAT, 'UTF-8');
        }

        $data = $this->EE->wyvern_helper->_prep_data($data, 'display_field');

        $this->EE->wyvern_helper->_load_assets();
        $this->EE->wyvern_helper->_load_js_matrix();

        $field = '<textarea style="margin-top: 30px; height: '. $this->EE->wyvern_helper->_get_height($settings) .'px;" class="wyvern" id="'. $this->cell_name .'" name="'. $this->cell_name .'" data-toolbar="'. $toolbar .'" data-config="'. $config .'">'. $data .'</textarea>';

        $r['class'] = 'wyvern-matrix';
        $r['data'] = $field;

        return $r;
    }

    /*
     * Low Variables Fieldtype Display
     */
    function display_var_field($data)
    {
        return $this->display_field($data);
    }

    /**
     * Used for template display
     */
    function replace_tag($data, $params = '', $tagdata = '', $typography = FALSE)
    {
        $global_settings = $this->EE->wyvern_helper->_get_global_settings(true);
        $field_settings  = $this->settings;

        // Something is passing $this->settings as the default value the 2nd time this is called
        // for a single field. Makes sense, ya? So resorting to getting the settings straight
        // from the db instead of relying on what EE is sending us.
        // Turned off in 1.6.2, I can't replicate why this was needed for a customer.
        // $qry = $this->EE->db->select('field_settings')->get_where('channel_fields', array('field_id' => $this->field_id));

        // if ($qry->num_rows())
        // {
        //     $field_settings = unserialize(base64_decode($qry->row('field_settings')));
        //     $field_settings = isset($field_settings['wyvern']) ? $field_settings['wyvern'] : $this->settings;
        // }

        // Deprecated in 1.2.4, use <code|pre> blocks instead.
        if(isset($global_settings['field_encode_ee_tags']) AND $global_settings['field_encode_ee_tags'] == 'yes')
        {
            // Use our version, which does not encode {path="group/template"} or {filedir_N} tags
            $data = $this->EE->wyvern_helper->_encode_ee_tags($data);
        }

        // Obfuscate email addresses if requested
        if(isset($global_settings['field_obfuscate_email']) AND $global_settings['field_obfuscate_email'] == 'yes')
        {
            $data = preg_replace_callback($this->EE->wyvern_helper->full_pattern, array($this->EE->wyvern_helper, '_email_obfuscate'), $data);
        }

        // Encode as [code] so EE highlights it properly
        // $data = str_replace(
        //     array('<code>', '</code>', '<pre>', '</pre>'),
        //     array('[code]', '[/code]', '[code]', '[/code]'),
        //     $data);

        $data = str_replace(
            array('<code>', '</code>'),
            array('[code]', '[/code]'),
            $data);

        // Syntaxhighlight support: replace <pre class="brush:xml;">
        $data = preg_replace('/<pre class="(\S+)">/', '[code]', $data);

        $this->EE->load->library('typography');

        $typography = $this->_get_typography($field_settings);

        $typography = array_merge(array(
            'text_format'   => 'none',
            'html_format'   => 'all',
            'auto_links'    => @$this->row['channel_auto_link_urls'],
            'allow_img_url' => @$this->row['channel_allow_img_urls']
        ), $typography);

        return $this->EE->typography->parse_type($data, $typography);
    }

    /**
     * Let users decide if URLs and images are allowed. Mostly useful for Low Variables
     * @param  boolean $settings
     * @return array
     */
    function _get_typography($settings = FALSE)
    {
        $settings = isset($settings['wyvern']) ? $settings['wyvern'] : $settings;

        // Default to yes unless set to otherwise
        $typography_settings = array(
            'auto_links' => 'n',
            'allow_img_url' => 'n'
        );

        if (isset($settings['auto_link_urls']) AND $settings['auto_link_urls'] == 'yes')
        {
            $typography_settings['auto_links'] = 'y';
        }

        if (isset($settings['allow_img_urls']) AND $settings['allow_img_urls'] == 'yes')
        {
            $typography_settings['allow_img_url'] = 'y';
        }

        return $typography_settings;
    }

    /**
     * Used for template display {field_name:text_only word_limit="50" suffix="..."}
     */
    function replace_text_only($data, $params = '', $tagdata = '')
    {
        $data = $this->replace_tag($data, $params, $tagdata);

        // Strip everything but links. May need to revise the allowed list later.
        $data = trim(strip_tags($data, '<a>'));

        if (isset($params['word_limit']) AND is_numeric($params['word_limit']))
        {
            // Get the words
            $words = explode(" ", str_replace("\n", '', $data));

            // limit it to specified number of words
            $data = implode(" ", array_splice($words, 0, $params['word_limit']));

            // See if last character is not punctuation or another special char and remove it
            // (note this is basic and might not work in multi-lingual sites)
            $data = ! preg_match("/^[a-z0-9\.\?\!]$/i", substr($data, -1)) ? substr($data, 0, -1) : $data;

            // Add whatever suffix the user wants...
            // Suffix was the first param, added append as an alias b/c it makes more sense, should have used it first
            if (isset($params['suffix']))
            {
                $data .= $params['suffix'];
            }
            else if (isset($params['append']))
            {
                $data .= $params['append'];
            }
        }

        return $data;
    }

    /**
     * Render a Table of Contents at the beginning of the content
     *
     * Usage: {my_content_field:with_toc}
     *
     * @param  string $data
     * @param  array  $params
     * @param  string $tagdata
     * @return string
     */
    function replace_with_toc($data, $params = '', $tagdata = '')
    {
        return $this->EE->wyvern_helper->_create_toc($data, $params);
    }

    /**
     * Low Variables replace tag
     */
    function display_var_tag($data, $params = '', $tagdata = '')
    {
        $typography = $this->_get_typography($this->settings['wyvern']);

        return $this->replace_tag($data, $params, $tagdata, $typography);
    }

    /*
     * Display normal individual field settings
     */
    function display_settings($data)
    {
        $rows = $this->EE->wyvern_helper->_get_field_settings_options($data);

        foreach ($rows as $row)
        {
            $this->EE->table->add_row($row[0], $row[1]);
        }
    }

    /**
     * Display Matrix Cell Settings
     */
    function display_cell_settings($data)
    {
        return $this->EE->wyvern_helper->_get_field_settings_options($data, true);
    }

    /**
     * Display Low Variables Settings
     */
    function display_var_settings($data)
    {
        return $this->EE->wyvern_helper->_get_field_settings_options($data);
    }

    /**
     * Display settings in Grid
     *
     * @param  array $data
     * @return array
     */
    function grid_display_settings($data)
    {
        return array($this->grid_settings_row(
            '',
            $this->EE->wyvern_helper->_get_field_settings_options($data, true),
            true
        ));
    }

    /**
      *  Display Content Element Settings
      */
    function display_element_settings($data)
    {
        return $this->EE->wyvern_helper->_get_field_settings_options($data);
    }

    /**
     * Redirect to module page now that the settings are there.
     */
    function display_global_settings()
    {
        $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=wyvern');
    }

    /*
     * Save individual field settings
     */
    function save_settings($settings)
    {
        $data = $this->EE->input->post('wyvern');
        return $this->_save_settings($data);
    }

    /**
     * Save settings for Grid
     *
     * @param  array $settings
     * @return array
     */
    function grid_save_settings($settings)
    {
        return $this->_save_settings($settings['wyvern']);
    }

    /**
     * Handle formatting the settings array for normal field and Grid
     *
     * @param  array $data
     * @return array
     */
    function _save_settings($data)
    {
        $settings = array('wyvern' => array());
        $settings['wyvern']['field_display_height'] = isset($data['field_display_height']) ? $data['field_display_height'] : false;
        $settings['wyvern']['field_resize_enabled'] = isset($data['field_resize_enabled']) ? $data['field_resize_enabled'] : 'no';
        $settings['wyvern']['field_text_direction'] = isset($data['field_text_direction']) ? $data['field_text_direction'] : 'ltr';
        $settings['wyvern']['allow_img_urls'] = isset($data['allow_img_urls']) ? $data['allow_img_urls'] : 'no';
        $settings['wyvern']['auto_link_urls'] = isset($data['auto_link_urls']) ? $data['auto_link_urls'] : 'no';
        $settings['wyvern']['toolbar'] = isset($data['toolbar']) ? $data['toolbar'] : false;
        $settings['wyvern']['upload_prefs'] = isset($data['upload_prefs']) ? $data['upload_prefs'] : array('all');

        if ($wyvern_video_data = $this->EE->input->post('wyvern_video'))
        {
            if (! isset($this->EE->wyvern_video_helper))
            {
                require PATH_THIRD.'wyvern_video/helper.wyvern_video.php';
                $this->EE->wyvern_video_helper = new Wyvern_video_helper;
            }

            $global_settings = $this->EE->wyvern_video_helper->get_settings();

            $settings['wyvern']['wyvern_video']['field_width'] = isset($data['wyvern_video']['field_width']) ? $data['wyvern_video']['field_width'] : $global_settings['settings_global']['global_width'];
            $settings['wyvern']['wyvern_video']['field_height'] = isset($data['wyvern_video']['field_height']) ? $data['wyvern_video']['field_height'] : $global_settings['settings_global']['global_height'];
            $settings['wyvern']['wyvern_video']['allow_resize'] = isset($data['wyvern_video']['allow_resize']) ? $data['wyvern_video']['allow_resize'] : 'no';
        }

        return $settings;
    }

    /**
     * Save Matrix Cell Settings
     */
    function save_cell_settings($settings)
    {
        return array(
            'wyvern' => array(
                'field_display_height'  => isset($settings['wyvern']['field_display_height']) ? $settings['wyvern']['field_display_height'] : false,
                'field_resize_enabled'  => isset($settings['wyvern']['field_resize_enabled']) ? $settings['wyvern']['field_resize_enabled'] : false,
                'field_text_direction'  => isset($settings['wyvern']['field_text_direction']) ? $settings['wyvern']['field_text_direction'] : 'ltr',
                'allow_img_urls'        => isset($settings['wyvern']['allow_img_urls']) ? $settings['wyvern']['allow_img_urls'] : 'no',
                'auto_link_urls'        => isset($settings['wyvern']['auto_link_urls']) ? $settings['wyvern']['auto_link_urls'] : 'no',
                'toolbar'               => isset($settings['wyvern']['toolbar']) ? $settings['wyvern']['toolbar'] : false,
                'upload_prefs'          => isset($settings['wyvern']['upload_prefs']) ? $settings['wyvern']['upload_prefs'] : false
            )
        );
    }

    /**
     * Save Low Variables Settings
     */
    function save_var_settings($settings)
    {
        return $this->save_settings($settings);
    }

    /*
     * Save Normal Field Data
     */
    function save($data)
    {
        // Clear out if just whitespace or <br />
        if (! $data || preg_match('/^\s*(<\w+>\s*(&nbsp;)*\s*<\/\w+>|<br \/>)?\s*$/s', $data))
        {
            return '';
        }

        $data = $this->EE->wyvern_helper->_prep_data($data, 'save');

        return $data;
    }

    function grid_save($data)
    {
        return $this->save($data);
    }

    /**
     * Save Matrix Cell Data
     */
    function save_cell($data)
    {
        return $this->save($data);
    }

    private function debug($str, $die = false)
    {
        echo '<pre>';
        var_dump($str);
        echo '</pre>';

        if($die) die;
    }
}