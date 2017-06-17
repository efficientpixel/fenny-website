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

class Wyvern_helper {

    private $system_path;
    private $site_url;
    private $site_id;
    private $pages;
    private $cache = array();
    private $field_settings = array();
    private $display_height = 200;
    private $toolbar_buttons;

    // Change to ckeditor_source for better/easier debugging.
    private $ckeditor_file = 'ckeditor';

    private $supported_themes = array(
        'default',
        'corporate',
        'fruit'
    );

    // Fieldtype settings
    public $default_settings = array(
        1 => array( // default site ID
            'field_css_path' => '/themes/third_party/wyvern/wysiwyg.css',
            'field_js_path' => '/themes/third_party/wyvern/wysiwyg.js',
            'field_google_fonts' => '',
            'field_typekit' => '',
            'field_image_paragraphs' => 'yes',
            'field_encode_ee_tags' => 'no',
            'field_obfuscate_email' => 'no',
            'field_extra_config' => "pasteFromWordPromptCleanup: true\nforcePasteAsPlainText: true",
            'field_wymeditor_style' => 'no',
            'field_default_link_type' => 'site_pages',
            'field_file_manager' => 'default',
            'field_templates'           => array(
                'templates'     => '',
                'show_all'      => '',
                'show_group'    => '',
                'show_selected' => ''
            )
        )
    );

    // To make sure folder names match up to the button names.
    public $plugin_names = array(
        'channelimages' => 'ChannelImages'
    );

    public $native_plugins = array(
        'video'
    );

    // Used to encode emails within linked text created by the pattern below.
    public $email_pattern = "([a-z0-9!#\$%&'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#\$%&'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?)";
    // Used to find and linked email addresses, linked or not.
    // Special thanks to regex ninja Adrienne Travis (@adrienneleigh) for figuring this out for me
    public $full_pattern = "/(?P<preaddr>\<a(?:[a-z0-9;%&'\s\x22?~+_=-]+?)href\=[\x22|']mailto\:)?(?P<addr>[a-z0-9!#\$%&'*+=?^_`{|}~\-]+(?:\.[a-z0-9!#\$%&'*+=?^_`{|}~\-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+(?:museum|[a-z]{2,4}))(?P<postaddr>(?:[a-z0-9%&'\s\x22;?~+_=-]+?)(?=\>)\>)?(?P<linktext>(?:.*?)(?=\<))(?P<linkend>\<\s?\/a\s?\>)?/ism";

    public function __construct()
    {
        $this->EE =& get_instance();

        $this->site_id = $this->EE->input->get('site_id') ? $this->EE->input->get('site_id') : $this->EE->config->item('site_id');
        $this->system_path = $this->EE->config->system_url();
        $this->site_url = $this->EE->config->item('site_url');

        // EE 2.1.3 does not explictly load the addons library
        $this->EE->load->library('addons');

        // Create cache
        if (! isset($this->EE->session->cache['wyvern']))
        {
            $this->EE->session->cache['wyvern'] = array();
        }
        $this->cache =& $this->EE->session->cache['wyvern'];

        // Grab our default buttons
        require PATH_THIRD.'wyvern/config.php';
        $this->toolbar_buttons = $config['toolbar_buttons'];
    }

    /*
        1.2.4.2
        TODO: use this to load all field settings instead of just upload paths
        create a big fat object containing all settings instead of returning the silly $obj below
    */
    public function _get_field_config($settings, $field_name)
    {
        // For Low Variables support
        $field_name = (substr($field_name, 0, 3) == 'var') ? str_replace(array('[', ']'), '', $field_name) : $field_name;

        $upload_prefs = isset($settings['upload_prefs']) ? json_encode($settings['upload_prefs']) : '["all"]';

        $script = '';

        // Prevent excessive JS being added to the page.
        if ( !isset($this->cache['wyvern_config_'.$field_name]))
        {
            $script = '
                wyvern_config["'. $field_name .'"] = wyvern_config["'. $field_name .'"] || {};

                wyvern_config["'. $field_name .'"].upload_prefs = {
                    content_type: "any",
                    directory: '. $upload_prefs .'
                }';

            // If Wyvern Video is installed, add the field's specific settings
            if (array_key_exists('wyvern_video', $this->EE->addons->get_installed()) && isset($settings['wyvern_video']))
            {
                $script .= '
                    wyvern_config.wyvern_video = wyvern_config.wyvern_video || {};
                    wyvern_config.wyvern_video["'. $field_name .'"] = wyvern_config.wyvern_video["'. $field_name .'"] || {};
                    wyvern_config.wyvern_video["'. $field_name .'"].settings = wyvern_config.wyvern_video["'. $field_name .'"].settings || {};

                    wyvern_config.wyvern_video["'. $field_name .'"].settings = {
                        video_width: '. $settings['wyvern_video']['field_width'] .',
                        video_height: '. $settings['wyvern_video']['field_height'] .',
                        allow_resize: "'. $settings['wyvern_video']['allow_resize'] .'"
                    };';
            }

            $this->cache['wyvern_config_'.$field_name] = $script;

            $this->EE->cp->add_to_head('<!-- BEGIN Wyvern assets --><script type="text/javascript">$(function(){'. preg_replace("/\s+/", " ", $script) .'});</script><!-- END Wyvern assets -->');
        }

        $this->EE->javascript->output($script);
        $this->EE->javascript->compile();

        $obj = '{field_id: \''.$this->_create_id($field_name) .'\','.
                'height: '. $this->_get_height($settings) .','.
                'resizable: '. $this->_get_resizable($settings) .','.
                'text_direction: \''. (isset($settings['field_text_direction']) ? $settings['field_text_direction'] : 'ltr') .'\'}';

        return $obj;
    }

    public function _isset($setting, $equals = false, $return_false = false)
    {
        $settings = $this->_get_global_settings(true);

        if($equals)
        {
            return (isset($settings[$setting]) AND $settings[$setting] == $equals) ? $settings[$setting] : $return_false;
        }
        else
        {
            return (isset($settings[$setting]) AND $settings[$setting] != '') ? $settings[$setting] : $return_false;
        }
    }

    /**
     * Single function printed to the page to initiate all Wyverns
     */
    public function _display_wyvern_js()
    {
        $site_url = isset($this->EE->config->_global_vars['root_url']) ?
                    $this->EE->config->_global_vars['root_url'] :
                    $this->EE->config->slash_item('site_url');

        $settings = $this->_get_global_settings(true);
        $stylesheets_array = array();

        // Append our extra CSS files based on settings, user's always comes last so they can override if needed
        if($this->_isset('field_wymeditor_style', 'yes'))
        {
            $stylesheets_array[] = $this->_get_theme_url() .'skins/ee/contents-wymeditor-style.css';

            // If not IE, then append this file, which contains 1 rule :(
            if( ! $this->_is_ie())
            {
                $stylesheets_array[] = $this->_get_theme_url() .'skins/ee/contents-wymeditor-style-modern.css';
            }
        }

        if($this->_isset('field_google_fonts'))
        {
            $stylesheets_array[] = 'http://fonts.googleapis.com/css?family='. $settings['field_google_fonts'];
        }

        if($this->_isset('field_css_path'))
        {
            $stylesheets_array[] = reduce_double_slashes($site_url . $settings['field_css_path']);
        }

        // Always load at least our default contents.css file, but also load any user defined styles after it.
        if(count($stylesheets_array) > 0) {
            $stylesheets = '["'. $this->_get_theme_url() .'skins/ee/contents.css", "' . implode('","', $stylesheets_array) .'"]';
        } else {
            $stylesheets = '"'. $this->_get_theme_url() .'skins/ee/contents.css' .'"';
        }

        // If a custom JS file is created for the styles combo, make sure it's in the proper format
        if(isset($settings['field_js_path']) AND $settings['field_js_path'] != '') {
            $javascripts = ', stylesCombo_stylesSet: "wyvern:'. reduce_double_slashes($site_url . $settings['field_js_path']) .'"';
        } else {
            $javascripts = '';
        }

        // Add our native plugins so they are loaded first, and make sure the directory exists.
        foreach ($this->native_plugins as $k => $plugin)
        {
            if( !is_dir($this->_get_theme_folder_path().'wyvern/plugins/'.$plugin))
            {
                unset($this->native_plugins[$k]);
            }
        }

        $extra_plugins = $this->native_plugins;
        $extra_plugins = implode(',', array_merge($extra_plugins, $this->_get_ckeditor_plugins()));

        // Get the Typekit ID if it's set. Using class var b/c I need it in the _load_assets method
        $this->typekit_id = $this->_isset('field_typekit');

        // Used to determin in the plugin if links should use {page_url:N} or {site_url}path/to/page
        $link_type = $this->_ext_enabled() ? 'id' : 'site_url';

        $default_link_type = $this->_isset('field_default_link_type', false, 'site_pages');

        // Do we keep images inside paragraph tags? Or force them outside?
        $image_paragraphs = $this->_isset('field_image_paragraphs', false, 'no');

        // Which file manager are we using?
        $file_manager = $this->_isset('field_file_manager', 'assets');

        $assets_version = '1';
        if ($file_manager == 'assets')
        {
            require_once PATH_THIRD.'assets/config.php';
            $assets_version = ASSETS_VER;
        }

        // Extra Config set by the user
        $extra_config = str_replace(array("\n", "{site_url}"), array(", ", $site_url), $this->_isset('field_extra_config'));
        $extra_config = $extra_config ? ', '. $extra_config : '';

        // ACT Urls used in ajax calls when requested within the dialog windows.
        $load_pages_url = $this->_get_site_index() . '?ACT='. $this->EE->cp->fetch_action_id('Wyvern', 'load_pages') .'&site_id='. $this->site_id;
        $load_templates_url = $this->_get_site_index() . '?ACT='. $this->EE->cp->fetch_action_id('Wyvern', 'load_templates') .'&site_id='. $this->site_id;
        $load_vimeo_url = $this->_get_site_index() . '?ACT='. $this->EE->cp->fetch_action_id('wyvern_video', 'load_vimeo') .'&site_id='. $this->site_id;

        $cp_theme  = $this->EE->config->item('cp_theme');
        $cp_theme_url = $this->EE->config->slash_item('theme_folder_url').'cp_themes/'.$cp_theme.'/';

        $cp_global_images = $this->EE->config->slash_item('theme_folder_url').'cp_global_images/';

        $script = '
        if (typeof window.wyvern_config == "undefined") {
            window.wyvern_config = {};
        }

        wyvern_config.theme = "'. $this->_get_theme() .'";
        wyvern_config.link_type = "'. $link_type .'";
        wyvern_config.default_link_type = "'. $default_link_type .'";
        wyvern_config.file_manager = "'. $file_manager .'";
        wyvern_config.assets_version = "'. $assets_version .'";
        wyvern_config.upload_paths = '. $this->_get_upload_paths_object() .';
        wyvern_config.typekit_id = "'. $this->typekit_id .'";
        wyvern_config.load_pages_url = "'. $load_pages_url .'";
        wyvern_config.load_templates_url = "'. $load_templates_url .'";
        wyvern_config.load_vimeo_url = "'. $load_vimeo_url .'";
        wyvern_config.theme_url = "'. $this->_get_theme_url() .'";
        wyvern_config.cp_theme_url = "'. $cp_theme_url .'";
        wyvern_config.cp_global_images = "'. $cp_global_images .'";
        wyvern_config.image_paragraphs = "'. $image_paragraphs .'";
        wyvern_config.extra_plugins = "'. $extra_plugins .'";
        wyvern_config.ee_version = "'. str_replace('.', '', APP_VER) .'";

        function init_wyvern(config, toolbar)
        {
            var wyvern = CKEDITOR.replace(config.field_id, {
                toolbar: toolbar
                , resize_enabled: config.resizable
                , height: config.height
                , customConfig: "'. $this->_get_theme_url() .'config.js"
                , contentsCss: '. $stylesheets .'
                , contentsLangDirection: config.text_direction
                '. $javascripts . $extra_config .'
            });
        }';

        return $script;
    }

    public function _get_pages()
    {
        // Make sure pages cache is empty, and also see if we are in the CP. Since fieldtype files get loaded
        // on the front end, I don't want unecessary queries/processing to be done when not needed.
        if(!isset($this->cache['pages'][$this->site_id]) AND (REQ != 'PAGE' OR $this->_is_safecracker()))
        {
            $this->cache['pages'][$this->site_id] = "";

            if(array_key_exists('structure', $this->EE->addons->get_installed()))
            {
                require_once $this->_get_theme_folder_path().'boldminded_themes/libraries/structure_pages.php';
                $pages = Structure_Pages::get_instance();
                $this->cache['pages'][$this->site_id] = $pages->get_pages($this->site_id);
            }
            elseif(array_key_exists('pages', $this->EE->addons->get_installed()))
            {
                require_once $this->_get_theme_folder_path().'boldminded_themes/libraries/pages.php';
                $pages = Pages::get_instance();
                $this->cache['pages'][$this->site_id] = $pages->get_pages($this->site_id);
            }
        }

        return $this->cache['pages'][$this->site_id];
    }

    public function _get_taxonomy_pages()
    {
        if(!isset($this->cache['taxonomy_pages'][$this->site_id]) AND (REQ != 'PAGE' OR $this->_is_safecracker()))
        {
            $this->cache['taxonomy_pages'][$this->site_id] = '';

            if(array_key_exists('taxonomy', $this->EE->addons->get_installed()))
            {
                require_once $this->_get_theme_folder_path().'boldminded_themes/libraries/taxonomy_pages.php';
                $pages = Taxonomy_Pages::get_instance();
                $this->cache['taxonomy_pages'][$this->site_id] = $pages->get_pages($this->site_id);
            }
        }

        return $this->cache['taxonomy_pages'][$this->site_id];
    }

    public function _get_navee_pages()
    {
        if(!isset($this->cache['navee_pages'][$this->site_id]) AND (REQ != 'PAGE' OR $this->_is_safecracker()))
        {
            $this->cache['navee_pages'][$this->site_id] = '';

            if(array_key_exists('navee', $this->EE->addons->get_installed()))
            {
                require_once $this->_get_theme_folder_path().'boldminded_themes/libraries/navee_pages.php';
                $pages = NavEE_Pages::get_instance();
                $this->cache['navee_pages'][$this->site_id] = $pages->get_pages($this->site_id);
            }
        }

        return $this->cache['navee_pages'][$this->site_id];
    }

    public function _get_templates()
    {
        if(!isset($this->cache['templates'][$this->site_id]))
        {
            $this->cache['templates'][$this->site_id] = '';

            // use Template Selection helper
            require_once $this->_get_theme_folder_path().'boldminded_themes/libraries/template_selection.php';
            $template_selection = new Template_Selection($this->EE);

            $settings = $this->_get_global_settings(true);
            $settings = isset($settings['field_templates']) ? $settings['field_templates'] : '';
            $templates = $template_selection->_query_templates();

            // Get the templates (if they exist)
            if($templates->num_rows() == 0)
            {
                $this->cache['templates'][$this->site_id] = '';
            }
            elseif(is_array($settings))
            {
                $groups = array();
                $template_options = array();

                foreach($templates->result_array() as $row)
                {
                    // Depending on which settings show the appropriate templates
                    if(isset($settings['show_all']) AND $settings['show_all'] == 'y')
                    {
                        $file = $row['group_name'] .'/'. $row['template_name'];
                        $template_options[$file] = $row['template_name'];
                    }
                    elseif(isset($settings['show_group']) AND $settings['show_group'] !== '' AND in_array($row['group_name'], $settings['show_group']))
                    {
                        $file = $row['group_name'] .'/'. $row['template_name'];
                        $template_options[$file] = $row['template_name'];
                    }
                    elseif(isset($settings['show_selected']) AND $settings['show_selected'] == 'y' AND in_array($row['template_id'], $settings['templates']))
                    {
                        $file = $row['group_name'] .'/'. $row['template_name'];
                        $template_options[$file] = $row['template_name'];
                    }
                }

                if(count($template_options) == 0)
                {
                    $r = 'No templates found.';
                }
                else
                {
                    require $this->_get_theme_folder_path() .'boldminded_themes/libraries/page_styles.php';
                    $css = preg_replace("/\s+/", " ", $css);

                    $r = $css . '<ul class="structure_pages">';
                    $last_group = '';
                    $i = 0;

                    foreach($template_options as $file => $template)
                    {
                        $group = explode('/', $file);

                        // Nest our templates into groups
                        if($group[0] != $last_group)
                        {
                            $r .= $i != 0 ? '</ul></li><ul>' : '';
                            $r .= '<li>'. $group[0] .'<ul class="page-list">';
                        }

                        $r .= '<li id="page-'. $file . '" class="page-item"><div class="item_wrapper round"><a href="#" data-url="'. $file .'" data-type="template">'. $template .'</a></div>';

                        $i++;
                        $last_group = $group[0];
                    }

                    $r .= '<ul>';
                }

                $this->cache['templates'][$this->site_id] = $r;
            }
        }

        return $this->cache['templates'][$this->site_id];
    }

    public function _get_toolbar_options_list($id = false)
    {
        $this->EE->lang->loadfile('wyvern');
        $this->EE->load->helper('form');
        $settings = $this->_get_global_settings(true);
        $default_buttons = array();
        $new_buttons = array();

        require PATH_THIRD.'wyvern/config.php';

        $default_buttons = $this->EE->db->where('toolbar_name', 'Default')->get('wyvern_toolbars')->row('toolbar_settings');

        if ( ! $id)
        {
            $toolbar_buttons = $default_buttons;
        }
        else
        {
            $toolbar_buttons = $this->EE->db->where('id', $id)->get('wyvern_toolbars')->row('toolbar_settings');
        }

        // If it isn't an array, then it is a serialized toolbar set.
        if ( !is_array($toolbar_buttons))
        {
            $toolbar_buttons = unserialize($toolbar_buttons);

            // See if there were any new buttons added since this toolbar was saved
            // and push them onto the end of the toolbar set.
            foreach ($config['toolbar_buttons'] as $button => $enabled)
            {
                if ( !array_key_exists($button, $toolbar_buttons))
                {
                    $button_name = $button;
                    $toolbar_buttons[$button_name] = isset($toolbar_buttons[$button_name]) ? $toolbar_buttons[$button_name] : 'no';
                }
            }
        }
        // If it is an array, then it's an empty result set, not a serialized array, so grab the default buttons.
        else
        {
            $toolbar_buttons = $config['toolbar_buttons'];
        }

        // If default is requested, see if any new buttons were added in the config file.
        if ($id == 1 OR $id == 'Default')
        {
            $toolbar_buttons = $toolbar_buttons + $config['toolbar_buttons'];
        }

        $plugins = $this->_get_ckeditor_plugins();

        foreach ($plugins as $plugin)
        {
            if (array_key_exists($plugin, $this->plugin_names))
            {
                $plugin_name = ucfirst($this->plugin_names[$plugin]);
            }
            else
            {
                $plugin_name = ucfirst($plugin);
            }

            if ( !isset($toolbar_buttons[$plugin_name]))
            {
                $toolbar_buttons[$plugin_name] = (isset($toolbar_buttons[$plugin_name]) && $toolbar_buttons[$plugin_name] == 'yes') ? 'yes' : 'no';
            }
        }

        $items = array();
        $item_names = array();
        $i = 0;

        foreach ($toolbar_buttons as $item => $value)
        {
            // Added b/c of a bug in 1.5.1 where a toolbar was not added to the array correctly,
            // and if the toolbar was saved you couldn't get rid of the numeric buttons/indexes.
            if (is_numeric($item))
            {
                continue;
            }

            // If Wyvern Video is not installed, don't show the video button
            if (strtolower($item) == 'video' AND ! array_key_exists('wyvern_video', $this->EE->addons->get_installed()))
            {
                continue;
            }

            $item_name = $item;

            $data_yes = array(
                'name'        => 'wyvern[toolbar]['. $item_name .']',
                'id'          => $item .'_yes',
                'value'       => 'yes',
                'checked'     => ($value == 'yes') ? true : false,
                'class'       => 'toolbar_button'
            );

            $data_no = array(
                'name'        => 'wyvern[toolbar]['. $item_name .']',
                'id'          => $item .'_no',
                'value'       => 'no',
                'checked'     => ($value == 'no') ? true : false,
                'class'       => 'toolbar_button'
            );

            $items[$i] = array(
                '<strong class="cke_icon cke_button__'. strtolower($item) .'_icon">'. preg_replace('/(.)([A-Z])/', "\\1 \\2", $item_name) .'</strong>',
                form_hidden('wyvern[toolbar]['. $item_name .']', 'no') .
                form_checkbox($data_yes)
            );

            $item_names[$i] = $item;

            $i++;
        }

        $html = '<table style="width: 100%;" class="wyvern_table settings_sortable cke_ltr">';
        $html .= '<thead><tr><th>Button</th><th>Visible?</th><th></th></tr></thead>';

        $debug_row = $this->EE->input->get('wyvern_debug') ? '<span class="remove_button" style="cursor: pointer;">X</span>' : '';

        foreach ($items as $k => $item)
        {
            $name = strtolower($item_names[$k]);
            $label = $name == 'new_row' ? '<strong>Start New Button Row</strong>' : $item[0];
            $is_new = in_array($name, $new_buttons) ? ' style="background: #C5FFBF !important;"' : '';

            $html .= '<tr class="sortable '. $name .'">
                        <td width="60%" class="cke_button__'. $name .'"'. $is_new .'><span class="handle"></span>'. $label .'</td>
                        <td width="35%"'. $is_new .'>'. $item[1] .'</td>
                        <td width="5%">'. $debug_row .'</td>
                    </tr>';
        }

        $html .= '</table>';

        return $html;
    }

    public function _get_site_index()
    {
        $site_index = $this->EE->config->item('site_index');
        $index_page = $this->EE->config->item('index_page');

        $index = ($site_index != '') ? $site_index : (($index_page != '') ? $index_page : '');

        return reduce_double_slashes($this->EE->config->slash_item('site_url') . $index);
    }

    public function _is_ie()
    {
        $user_agent = (isset( $_SERVER['HTTP_USER_AGENT'])) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';

        if (stristr($user_agent, 'msie'))
        {
            return true;
        }

        return false;
    }

    public function _get_toolbar($settings)
    {
        $has_settings = false;

        if(isset($settings['toolbar']['group_'.$this->EE->session->userdata['group_id']]))
        {
            $has_settings = true;
            $toolbar = array();

            $setting = $this->EE->db->select('toolbar_settings')
                                    ->where('id', $settings['toolbar']['group_'.$this->EE->session->userdata['group_id']])
                                    ->get('wyvern_toolbars')
                                    ->row('toolbar_settings');

            // If it's an array, it's coming back with no valid settings (empty result)
            // Can happen if someone deletes the Default group from the DB directly.
            // So set to default without settings.
            if(is_array($setting))
            {
                $has_settings = false;
                $toolbar = $this->toolbar_buttons;
            }
            else
            {
                $setting = unserialize($setting);
                foreach($setting as $button => $show)
                {
                    if($show == 'yes') $toolbar[] = $button;
                }
            }
        }
        else
        {
            // This should never get called except for if people upgrade to 1.1
            // and load a Wyvern field without resaving the field's settings.
            // New installs should never get here.
            $toolbar = $this->toolbar_buttons;
        }

        if($key = array_search('NEW_ROW', $toolbar))
        {
            $num = count($toolbar);

            // Find out where the new row split is, and break the toolbar into
            unset($toolbar[$key]);

            $row1 = array();
            $row2 = array();

            // Put each half of the toolbar into separate rows
            for($i = 0; $i < $num; $i++)
            {
                if($i < $key) $row1[] = $toolbar[$i];
                if($i > $key) $row2[] = $toolbar[$i];
            }

            return '[[\''. implode('\',\'', $row1) .'\'], \'/\', [\''. implode('\',\'', $row2) .'\']]';
        }
        else
        {
            if($has_settings)
            {
                return '[[\''. implode('\',\'', $toolbar) .'\']]';
            }
            else
            {
                $buttons = array();
                foreach($toolbar as $button => $show)
                {
                    if($show == 'yes')
                    {
                        $buttons[] = $button;
                    }
                }

                return '[[\''. implode('\',\'', $buttons) .'\']]';
            }
        }
    }

    public function _get_height($settings)
    {
        return (isset($settings['field_display_height']) AND $settings['field_display_height'] != '') ? $settings['field_display_height'] : $this->display_height;
    }

    public function _get_resizable($settings)
    {
        return (isset($settings['field_resize_enabled']) AND $settings['field_resize_enabled'] == 'yes') ? 'true' : 'false';
    }

    public function _create_id($str)
    {
        return str_replace(array('[', ']'), array('', ''), $str);
    }

    /**
     *   Create list of saved toolbars for the left column of the Global Settings page
     */
    public function _get_toolbar_options_saved()
    {
        $query = $this->EE->db->get('wyvern_toolbars');

        $return = '<table style="width: 100%;" id="saved_toolbars" class="wyvern_table settings_sortable">';

        foreach($query->result_array() as $row)
        {
            $active = $row['toolbar_name'] == 'Default' ? 'active' : '';
            $delete = $active == '' ? '<td align="right"><a href="#" data-id="'. $row['id'] .'" class="delete_toolbar">delete</a></td>' : '<td>&nbsp;</td>';


            $return .= '<tr>
                            <td><a href="#" data-id="'. $row['id'] .'" class="load_toolbar '. $active .'">'. $row['toolbar_name'] .'</a></td>
                            '. $delete .'
                        </tr>';
        }

        $return .= '</table>';

        return $return;
    }

    /**
     *  Create our Global Settings for toolbar configuration for the right column.
     */
    public function _get_toolbar_options()
    {
        $save_toolbar_url = $this->_get_site_index() . '?ACT='. $this->EE->cp->fetch_action_id('Wyvern', 'save_toolbar');
        $load_toolbar_url = $this->_get_site_index() . '?ACT='. $this->EE->cp->fetch_action_id('Wyvern', 'load_toolbar');
        $delete_toolbar_url = $this->_get_site_index() . '?ACT='. $this->EE->cp->fetch_action_id('Wyvern', 'delete_toolbar');

        $script = 'function init_sortable(){
                        var fixHelper = function(e, ui) {
                            ui.children().each(function() {
                                $(this).width($(this).width());
                                $(this).height($(this).height());
                            });
                            return ui;
                          };

                        $("table.settings_sortable").sortable({
                            axis: "y",
                            placeholder: "ui-state-highlight",
                            distance: 5,
                            forcePlaceholderSize: true,
                            items: "tr.sortable",
                            helper: fixHelper,
                            handle: ".handle",
                            start: function (event, ui) {
                                ui.placeholder.html("<td>&nbsp;</td><td>&nbsp;</td>");
                            },
                        });

                        $("table.settings_sortable tr").click(function(event){
                            if (event.target.type !== "checkbox") {
                                $(":checkbox", this).trigger("click");
                            }
                        });
                    }

                    init_sortable();

                    $("#save_toolbar").click(function()
                    {
                        data = $("input[name*=\'wyvern\']").serialize();
                        name = $(".toolbar_name").val();
                        button = $(this);

                        button.hide();

                        if(data)
                        {
                            $.ajax({
                                type: "POST",
                                url: "'. $save_toolbar_url .'",
                                data: data,
                                success: function(id){
                                    found = false;
                                    $("#saved_toolbars .load_toolbar").each(function(){
                                        if($(this).text() == name){
                                            found = true;
                                        }
                                    });
                                    if(!found){
                                        $("#saved_toolbars").append(\'<tr><td><a href="#" data-id="\'+ id +\'" class="load_toolbar">\'+ name +\'</a></td><td align="right"><a href="#" data-id="\'+ id +\'" class="delete_toolbar">delete</a></td><tr>\');
                                    }
                                    $("#toolbar_heading .toolbar_message").fadeIn("fast")
                                        .animate({opacity: 1.0}, 3000)
                                        .fadeOut("fast", function(){
                                            button.show();
                                        });
                                }
                            });

                            return false;
                        }
                    });

                    $(".load_toolbar").live("click" ,function()
                    {
                        $(".load_toolbar").removeClass("active");
                        $(this).addClass("active");
                        name = $(this).text();

                        $.ajax({
                            type: "POST",
                            url: "'. $load_toolbar_url .'",
                            data: "id="+ $(this).attr("data-id"),
                            success: function(html){
                                $("#toolbar_heading .toolbar_name").val(name);
                                $("#toolbar_options").html(html);
                                init_sortable();
                            }
                        });

                        return false;
                    });

                    $(".delete_toolbar").live("click" ,function()
                    {
                        var tr = $(this).closest("tr");

                        $.ajax({
                            type: "POST",
                            url: "'. $delete_toolbar_url .'",
                            data: "id="+ $(this).attr("data-id"),
                            success: function(response){
                                if(response == "true"){
                                    tr.fadeOut("fast");
                                }
                            }
                        });

                        return false;
                    });

                    $(".remove_button").live("click", function()
                    {
                        var tr = $(this).closest("tr");
                        tr.remove();
                    });
                ';

        $css = '
            table.wyvern_table {
                border-collapse: collapse;
                border-spacing: 0;
                border-right: none;
            }
            table.mainTable table.wyvern_table td,
            table.mainTable table.wyvern_table td:last-child {
                border-left: none;
                border-right: none !important;
            }
            table.mainTable table.wyvern_table tr:last-child td {
                border-bottom: none;
            }
            table.wyvern_table span.handle {
                display: block;
                float: left;
                width: 16px;
                height: 16px;
                background: url('. $this->_get_theme_folder_url() .'boldminded_themes/images/icon_handle.gif) 50% 50% no-repeat;
            }
            #toolbar_heading {
                margin: 20px 0;
            }
            #toolbar_heading .toolbar_name {
                width: 60%;
            }
            #toolbar_heading .toolbar_message {
                display: none;
                margin-left: 20px;
                font-weight: bold;
            }
            #saved_toolbars {
                margin-top: 20px;
            }
            #saved_toolbars .load_toolbar {
                padding: 0 5px;
                display: inline-block;
            }
            #saved_toolbars .load_toolbar.active {
                font-weight: bold;
            }
            .wyvern_table .cke_icon {
                display: inline-block;
                margin-left: 10px;
                padding: 2px 0 1px 30px;
            }
            .wyvern_table .new_row td {
                background-color: rgba(255, 255, 255, 0.4);
            }
            .wyvern_table .new_row strong {
                margin-left: 10px;
                text-transform: uppercase;
                font-size: 0.8em;
                opacity: 0.7;
            }
            .wyvern_table .cke_button_new_row .cke_icon {
                background: none !important;
            }
            .pageContents form .wyvern_table strong {
                margin: 0 0 0 5px;
            }
            .wyvern_table .ui-state-highlight {
                border: 0;
            }
            .wyvern_table .ui-state-highlight td {
                background-color: rgba(255, 255, 255, .4);
            }
            .wyvern_table tr:hover td {
                background-color: rgba(255, 255, 255, .4);
            }
        ';

        $html = '';

        $items = $this->_get_toolbar_options_list();

        $html .= '<div id="toolbar_heading">
                    <input type="text" class="toolbar_name" name="wyvern[toolbar_name]" value="Default" />
                    <input type="submit" name="submit" value="Save Toolbar" class="submit" id="save_toolbar" />
                    <span class="toolbar_message">Saved!</span>
                  </div>';
        $html .= '<div id="toolbar_options">'. $items .'</div>';

        $return = $html;

        $this->EE->cp->add_to_head('<!-- BEGIN Wyvern assets --><style type="text/css">'. preg_replace("/\s+/", " ", $css) .'</style><!-- END Wyvern assets -->');
        $this->EE->cp->add_to_foot('<!-- BEGIN Wyvern assets --><script type="text/javascript">$(function(){'. preg_replace("/\s+/", " ", $script) .'});</script><!-- END Wyvern assets -->');

        return $return;
    }

    public function _get_toolbar_configurations()
    {
        if($this->EE->db->table_exists('wyvern_toolbars'))
        {
            $query = $this->EE->db->get('wyvern_toolbars');

            $return = array();

            foreach($query->result_array() as $row)
            {
                $return[$row['id']] = $row['toolbar_name'];
            }
        }
        else
        {
            $return = array();
        }

        return $return;
    }

    public function _get_member_groups()
    {
        $query = $this->EE->db->where('site_id', $this->site_id)
                              ->get('member_groups');

        $r = array();
        foreach($query->result_array() as $row)
        {
            $r[$row['group_id']] = $row['group_title'];
        }

        return $r;
    }

    public function _get_field_settings_options($settings, $matrix = false)
    {
        // Use this opportunity to make sure the user has the ckeditor folder. Get a few support tickets b/c of this.
        if( ! is_dir(PATH_THIRD_THEMES . 'ckeditor'))
        {
            show_error('It looks like you do not have CKeditor installed. Please download it from <a href="http://ckeditor.com/download">ckeditor.com</a>, and install into your /themes/third_party/ folder.');
        }

        $this->EE->lang->loadfile('wyvern');
        $settings = isset($settings['wyvern']) ? $settings['wyvern'] : array();

        // Get our global settings
        $global_settings = $this->_get_global_settings(true);
        $file_manager = (isset($global_settings['field_file_manager'])) ? $global_settings['field_file_manager'] : 'default';

        /*
            Auto links
        */
        $auto_link_urls = form_checkbox(array(
                        'name'      => 'wyvern[auto_link_urls]',
                        'id'        => 'auto_link_urls',
                        'value'     => 'yes',
                        'checked'   => (isset($settings['auto_link_urls']) AND $settings['auto_link_urls'] != 'yes') ? false : true
                    )) .' <label for ="resize_enabled">'. lang('yes') .'</label>';

        $allow_img_urls = form_checkbox(array(
                        'name'      => 'wyvern[allow_img_urls]',
                        'id'        => 'allow_img_urls',
                        'value'     => 'yes',
                        'checked'   => (isset($settings['allow_img_urls']) AND $settings['allow_img_urls'] != 'yes') ? false : true
                    )) .' <label for ="resize_enabled">'. lang('yes') .'</label>';

        /*
            Toolbar settings
        */

        // If it's set, and is array, then it's an old Wyvern config value. New value is just an ID
        $selected_toolbar = isset($settings['toolbar']) ? $settings['toolbar'] : '';

        $global_settings_link = '&nbsp;&nbsp;<a href="'. BASE.AMP.'C=addons_fieldtypes&M=global_settings&ft=wyvern">Create a new toolbar</a>';

        $dropdowns = '<table class="mainTable" border="0" cellspacing="0" cellpadding="0">';
        $dropdowns .= '<tr><th>Member Group</th><th>Toolbar</th></tr>';
        foreach($this->_get_member_groups() as $id => $name)
        {
            $selected = isset($selected_toolbar['group_'. $id]) ? $selected_toolbar['group_'. $id] : '';
            $dropdowns .= '<tr><td>'. $name .'</td><td>'. form_dropdown('wyvern[toolbar][group_'. $id .']', $this->_get_toolbar_configurations(), $selected) .'</td></tr>';
        }
        $dropdowns .= '</table>';

        /*
            Resize field?
        */

        $resize =   form_input('wyvern[field_display_height]', (isset($settings['field_display_height']) AND $settings['field_display_height'] != '') ? $settings['field_display_height'] : $this->display_height, 'style="width: 50px"') .'&nbsp;&nbsp;&nbsp;&nbsp;'.
                    form_checkbox(array(
                        'name'      => 'wyvern[field_resize_enabled]',
                        'id'        => 'resize_enabled',
                        'value'     => 'yes',
                        'checked'   => (isset($settings['field_resize_enabled']) AND $settings['field_resize_enabled'] == 'no') ? false : true
                    )) .' <label for ="resize_enabled">Resizable?</label>';

        /*
            Text direction? LTR/RTL
        */

        $text_direction =   form_radio(array(
                                'name'      => 'wyvern[field_text_direction]',
                                'id'        => 'field_text_direction_ltr',
                                'value'     => 'ltr',
                                'checked'   => ((isset($settings['field_text_direction']) AND $settings['field_text_direction'] == 'ltr') OR !isset($settings['field_text_direction'])) ? true : false
                            )) .' <label for ="field_text_direction_ltr">'. lang('ltr') .'</label>&nbsp;&nbsp;&nbsp;&nbsp;' .

                            form_radio(array(
                                'name'      => 'wyvern[field_text_direction]',
                                'id'        => 'field_text_direction_rtl',
                                'value'     => 'rtl',
                                'checked'   => (isset($settings['field_text_direction']) AND $settings['field_text_direction'] == 'rtl') ? true : false
                            )) .' <label for ="field_text_direction_rtl">'. lang('rtl') .'</label>';

        /*
            File upload prefs
        */

        $upload_prefs = $this->_get_upload_prefs();

        // Temporary until the default FM allow for multiple specific directories like Assets.
        $form_field_type = ($file_manager == 'default') ? 'form_radio' : 'form_checkbox';

        // Bug fix for some early users of 1.2.4
        $settings['upload_prefs'] = (isset($settings['upload_prefs']) AND is_array($settings['upload_prefs'])) ? $settings['upload_prefs'] : array('all');

        $directories = form_checkbox(array(
                            'name'      => 'wyvern[upload_prefs][]',
                            'id'        => 'upload_prefs_all',
                            'value'     => 'all',
                            'checked'   => ((isset($settings['upload_prefs']) AND in_array('all', $settings['upload_prefs'])) OR !isset($settings['upload_prefs'])) ? true : false,
                        )) .' <label for ="upload_prefs_all">All</label><br /><br />';

        foreach($upload_prefs as $id => $pref)
        {
            $directories .= $form_field_type(array(
                                'name'      => 'wyvern[upload_prefs][]',
                                'id'        => 'upload_prefs_'. $pref['id'],
                                'value'     => $pref['id'],
                                'checked'   => (isset($settings['upload_prefs']) AND in_array($pref['id'], $settings['upload_prefs'])) ? true : false
                            )) .' <label for ="upload_prefs_'. $pref['id'] .'">'. $pref['name'] .'</label><br />';
        }

        $script = '
            $(function(){
                $("#upload_prefs_all").click(function(){
                    box = $(this);
                    if(box.is(":checked")) {
                        box.siblings("input").attr("checked", false).attr("disabled", true);
                    } else {
                        box.siblings("input").attr("disabled", false);
                    }
                });
            });
        ';

        $this->EE->cp->add_to_foot('<script type="text/javascript">'. $script .'</script>');

        $wyvern_video_options = FALSE;

        if (array_key_exists('wyvern_video', $this->EE->addons->get_installed()))
        {
            if (! isset($this->EE->wyvern_video_helper))
            {
                require PATH_THIRD.'wyvern_video/helper.wyvern_video.php';
                $this->EE->wyvern_video_helper = new Wyvern_video_helper;
            }

            $wyvern_video_settings = isset($settings['wyvern_video']) ? $settings['wyvern_video'] : array();

            $wyvern_video_options = $this->EE->wyvern_video_helper->get_field_settings_options($this, $wyvern_video_settings, $matrix, true);
        }

        /*
            If we're displaying settings in a Matrix field
        */

        if($matrix)
        {
            $wyvern_video_options = $wyvern_video_options !== FALSE ? $wyvern_video_options : '';

            $html  = '<table class="matrix-col-settings">';
            $html .= '<tr class="matrix-first odd">
                        <th class="matrix-first" style="width: 40%">'. lang('display_height') .'</td>
                        <td class="matrix-last" style="width: 60%">'. $resize .'</td>
                      </tr>
                      <tr class="matrix even">
                        <th class="matrix-first">'. lang('toolbar') .'</th>
                        <td class="matrix-last">'. $dropdowns .' '. $global_settings_link .'</td>
                      </tr>
                      <tr class="matrix odd">
                        <th class="matrix-first">'. lang('text_direction') .'</th>
                        <td class="matrix-last">'. $text_direction .'</td>
                      </tr>
                      <tr class="matrix even">
                        <th class="matrix-first">'. lang('upload_prefs') .'</th>
                        <td class="matrix-last">'. $directories .'</td>
                      </tr>
                      <tr class="matrix odd">
                        <th class="matrix-first">'. lang('auto_link_urls') .'</th>
                        <td class="matrix-last">'. $auto_link_urls .'</td>
                      </tr>
                      <tr class="matrix-last even">
                        <th class="matrix-first">'. lang('channel_allow_img_urls') .'</th>
                        <td class="matrix-last">'. $allow_img_urls .'</td>
                      </tr>';
            $html .= '</table>';

            $return = $html . $wyvern_video_options;
        }

        /*
            Normal settings display
        */

        else
        {
            $wyvern_video_options = $wyvern_video_options !== FALSE ? $wyvern_video_options : array();

            $return = array(
                array('<strong>'. lang('display_height') .'</strong>', $resize),
                array('<strong>'. lang('toolbar') .'</strong>', $dropdowns .' '. $global_settings_link),
                array('<strong>'. lang('text_direction') .'</strong>', $text_direction),
                array('<strong>'. lang('upload_prefs') .'</strong>', $directories),
                array('<strong>'. lang('auto_link_urls') .'</strong>', $auto_link_urls),
                array('<strong>'. lang('channel_allow_img_urls') .'</strong>', $allow_img_urls)
            );

            $return = array_merge($return, $wyvern_video_options);
        }

        return $return;
    }

    // Create an object string to be used in the JavaScript
    public function _get_upload_paths_object()
    {
        if(!isset($this->cache['paths_objects']))
        {
            $this->EE =& get_instance();

            $prefs = $this->_get_upload_prefs();

            $paths = array();

            if(count($prefs) > 0)
            {
                foreach($prefs as $id => $pref)
                {
                    $is_image = $pref['allowed_types'] == 'img' ? 'true' : 'false';
                    $paths[] = "{directory:'". $pref['id'] ."', url:'". $pref['url'] ."', is_image:'". $is_image ."'}";
                }
            }

            $this->cache['paths_object'] = '['. implode(',', $paths) .']';
        }

        return $this->cache['paths_object'];
    }

    // Create an array to be used in this PHP class
    public function _get_upload_paths_array()
    {
        if(!isset($this->cache['paths_array']))
        {
            $this->EE =& get_instance();

            $prefs = $this->_get_upload_prefs();

            $paths = array();

            if(count($prefs) > 0)
            {
                foreach($prefs as $id => $pref)
                {
                    $is_image = $pref['allowed_types'] == 'img' ? 'true' : 'false';
                    $paths[] = array('directory' => $pref['id'], 'url' => $pref['url'], 'is_image' => $is_image);
                }
            }

            $this->cache['paths_array'] = $paths;
        }

        return $this->cache['paths_array'];
    }

    public function _get_upload_prefs($group_id = NULL, $id = NULL)
    {
        if(!isset($this->cache['upload_prefs']))
        {
            // Assets support contributed by @utilitarienne

            // Get our global Assets settings
            $global_settings = $this->_get_global_settings(TRUE);
            $file_manager = (isset($global_settings['field_file_manager'])) ? $global_settings['field_file_manager'] : 'default';

            // Insert assets sources if using assets
            if($file_manager == 'assets')
            {
                $sources = $this->EE->db->get('assets_sources')->result();
                $source_array = array();
                foreach ($sources as $source)
                {
                    $source_array[$source->source_type . ':' . $source->source_id] = array(
                        'id' => $source->source_type . ':' . $source->source_id,
                        'name' => $source->name,
                        'allowed_types' => 'all',
                        'url' => ''
                    );
                }

                asort($source_array);
            }

            if (version_compare(APP_VER, '2.4', '>='))
            {
                $this->EE->load->model('file_upload_preferences_model');
                $this->cache['upload_prefs'] = $this->EE->file_upload_preferences_model->get_file_upload_preferences($group_id, $id);

                if($file_manager == 'assets')
                {
                    $this->cache['upload_prefs'] = array_merge($this->cache['upload_prefs'], $source_array);
                }

                return $this->cache['upload_prefs'];
            }

            if (version_compare(APP_VER, '2.1.5', '>='))
            {
                $this->EE->load->model('file_upload_preferences_model');
                $result = $this->EE->file_upload_preferences_model->get_upload_preferences($group_id, $id);
            }
            else
            {
                $this->EE->load->model('tools_model');
                $result = $this->EE->tools_model->get_upload_preferences($group_id, $id);
            }

            $this->cache['upload_prefs'] = $result->result_array();
        }

        return $this->cache['upload_prefs'];
    }

    public function _is_safecracker()
    {
        if(REQ == 'PAGE')
        {
            foreach($this->EE->TMPL->tag_data as $tag => $data)
            {
                if($data['class'] == 'safecracker' || ($data['class'] == 'channel' && $data['method'] == 'form'))
                {
                    return true;
                }
            }
        }

        return false;
    }

    public function _is_low_vars()
    {
        if(REQ == 'CP' AND $this->EE->input->get('module') == 'low_variables')
        {
            return true;
        }

        return false;
    }

    public function _get_global_settings($return_site_settings = false, $refresh_cache = false)
    {
        if(!isset($this->cache['global_settings']) OR $refresh_cache)
        {
            $this->EE->db->select('settings');
            $this->EE->db->where('name', 'wyvern');
            $query = $this->EE->db->get('fieldtypes');

            $row = $query->row();

            $settings = unserialize(base64_decode($row->settings));

            $this->cache['global_settings'] = count($settings) > 0 ? $settings : array();
        }

        // Return global settings for current site, or return ALL global settings?
        return ($return_site_settings AND isset($this->cache['global_settings'][$this->site_id])) ? $this->cache['global_settings'][$this->site_id] : $this->cache['global_settings'];
    }

    public function _get_theme()
    {
        if(!isset($this->cache['theme']))
        {
            // Make sure the current theme is supported (e.g. I've created a css file for it)
            // Use default theme if current theme is not supported. The $this->EE->cp value
            // is not set if you're viewing the front end of your site, so this throws a notice.
            // Lets just make sure it's set before we try to access it.
            if(isset($this->EE->cp))
            {
                $this->cache['theme'] = in_array($this->EE->cp->cp_theme, $this->supported_themes) ? $this->EE->cp->cp_theme : 'default';
            }
            else
            {
                $this->cache['theme'] = 'default';
            }
        }

        return $this->cache['theme'];
    }

    public function _get_theme_url()
    {
        return $this->_get_theme_folder_url().'wyvern/';
    }

    public function _get_theme_folder_url()
    {
        return URL_THIRD_THEMES;
    }

    public function _get_theme_folder_path()
    {
        return PATH_THIRD_THEMES;
    }

    /*
        Get all the custom plugins installed in /themes/third_party/wyvern/plugins/
    */
    public function _get_ckeditor_plugins()
    {
        if(!isset($this->cache['ckeditor_plugins']))
        {
            $this->EE->load->helper('directory');
            $map = directory_map($this->_get_theme_folder_path() .'wyvern/plugins/', 1);
            $plugins = array();

            foreach($map as $k => $file)
            {
                if( ! in_array($file, $this->native_plugins) AND $file != 'wyvern')
                {
                    $plugins[$file] = $file;
                }
            }

            $this->cache['ckeditor_plugins'] = $plugins;
        }

        return (count($this->cache['ckeditor_plugins']) > 0) ? $this->cache['ckeditor_plugins'] : array();
    }

    public function _encode_ee_tags($str)
    {
        $str = preg_replace("/\{(\/){0,1}exp:(.+?)\}/", "&#123;\\1exp:\\2&#125;", $str);
        $str = str_replace(array('{exp:', '{/exp'), array('&#123;exp:', '&#123;\exp'), $str);
        $str = preg_replace("/\{embed=(.+?)\}/", "&#123;embed=\\1&#125;", $str);
        $str = preg_replace("/\{redirect=(.+?)\}/", "&#123;redirect=\\1&#125;", $str);

        return $str;
    }

    public function _ext_enabled()
    {
        if(!isset($this->cache['ext_enabled']))
        {
            $query = $this->EE->db->query("SELECT * FROM exp_extensions WHERE class = 'Wyvern_ext' AND enabled ='y'");

            if($query->num_rows())
            {
                $this->cache['ext_enabled'] = true;
            }
            else
            {
                $this->cache['ext_enabled'] = false;
            }
        }

        return $this->cache['ext_enabled'];
    }

    public function _load_assets()
    {
        if( !isset($this->cache['assets_added']))
        {
            // Get the base themes/third_party/ path
            $theme_path = $this->_get_theme_folder_path();
            $theme_url = $this->_get_theme_folder_url();

            // If CKEditor is installed in themes/third_party, load it from there. Will make Wyvern updates easier.
            $this->EE->load->helper('file');
            $ckeditor_path = get_dir_file_info($theme_path.'ckeditor/', true) ? $theme_url.'ckeditor/' : $this->_get_theme_url() .'ckeditor/';

            if (array_key_exists('wyvern_video', $this->EE->addons->get_installed()) AND ! isset($this->EE->session->cache['wyvern_video']['assets_added']))
            {
                if ( ! isset($this->EE->wyvern_video_helper))
                {
                    require PATH_THIRD.'wyvern_video/helper.wyvern_video.php';
                    $this->EE->wyvern_video_helper = new Wyvern_video_helper;
                }

                $this->EE->wyvern_video_helper->load_assets();
            }

            if($this->_is_safecracker())
            {
                $this->EE->cp->add_to_head('<!-- Wyvern Assets: SAEF CSS --><style type="text/css">'. $this->_ee_saef_css() .'</style><!-- END Wyvern Assets: SAEF CSS -->');
            }

            $this->EE->cp->add_to_head('
                <!-- BEGIN Wyvern Assets -->'."\n".'
                <!--<script type="text/javascript" src="https://getfirebug.com/firebug-lite.js"></script>-->
                <script type="text/javascript" src="'. $ckeditor_path . $this->ckeditor_file .'.js"></script>
                <script type="text/javascript">'. $this->_display_wyvern_js() .'</script>
                '."\n".'<!-- END Wyvern Assets -->
            ');

            if($this->typekit_id)
            {
                $this->EE->cp->add_to_foot('<iframe src="'. $theme_url .'wyvern/typekit_loader.php?typekit_id='. $this->typekit_id .'" width="0" height="0" style="display: none;"></iframe>');
            }

            $settings = $this->_get_global_settings(true);

            // Load Assets' assets
            if ( ! isset($this->EE->session->cache['assets']['included_sheet_resources'])
                AND isset($settings['field_file_manager'])
                AND $settings['field_file_manager'] == 'assets'
                AND array_key_exists('assets', $this->EE->addons->get_installed()))
            {
                if (! class_exists('Assets_helper'))
                {
                    require PATH_THIRD.'assets/helper.php';
                }

                $assets_helper = new Assets_helper;
                $assets_helper->include_sheet_resources();
            }

            $this->cache['assets_added'] = true;
        }
    }

    public function _ee_saef_css()
    {
        $files = array(
            'file_browser.css',
            'saef.css',
            'jquery-ui-1.8.16.custom.css',
            'jquery-ui-1.7.2.custom.css'
        );

        $out = '';

        foreach ($files as $file)
        {
            $base = PATH_THEMES.'cp_themes/default/css/';

            if (file_exists($base.$file))
            {
                $out .= file_get_contents($base.$file);

                if (strstr($file, 'jquery-ui-'))
                {
                    $theme_url = $this->_get_theme_url().'jquery_ui/'.$this->EE->config->item('cp_theme');

                    $out = str_replace('url(images/', 'url('.$theme_url.'/images/', $out);
                }

                if (strstr($file, 'file_browser'))
                {

                }
            }
        }

        // a few styles from global.css in the CP for consistency, but we dont want to include the entire global.css
        $out .= '
        .cke_dialog_ui_button_cancel span.submit { background-color: #999;  }
        .cke_dialog_ui_button_ok span.submit { background-color: #333; }
        .ui-dialog select { font-size: 12px; }
        .ui-dialog textarea,
        .ui-dialog textarea.markItUpEditor,
        .ui-dialog input[type="text"],
        .ui-dialog input[type="password"] {
            font-family:            Arial, "Helvetica Neue", Helvetica, sans-serif;
            font-size:              12px;
            border:                 1px solid #b6c0c2;
            color:                  #5f6c74;
            outline:                0;
            padding:                4px;
            width:                  99%;
            border-radius:          3px;
            -moz-border-radius:     3px;
            -webkit-border-radius:  3px;
        }
        .ui-dialog textarea {
            resize:                 vertical;
            -moz-box-sizing:        border-box;
        }
        .ui-dialog textarea:focus,
        .ui-dialog extarea.markItUpEditor:focus,
        .ui-dialog input[type="text"]:focus,
        .ui-dialog input[type="password"]:focus {
            border:                 2px solid #B2BEC0;
            padding:                3px;
        }';

        $cp_theme  = $this->EE->config->item('cp_theme');
        $cp_theme_url = $this->_get_theme_url().'cp_themes/'.$cp_theme.'/';

        $out = str_replace('../images', $this->EE->config->slash_item('theme_folder_url') .'cp_themes/'. $cp_theme .'/images', $out);

        return preg_replace("/\s+/", " ", str_replace('<?=$cp_theme_url?>', $cp_theme_url, $out));
    }

    // This needs to be loaded separately so it is included AFTER the Matrix class
    // is include, otherwise our callback in matrix2.js won't be bound.
    public function _load_js_matrix()
    {
        if(!isset($this->cache['js_added_matrix']))
        {
            $this->EE->cp->add_to_foot('<script type="text/javascript" src="'. $this->_get_theme_url() .'matrix2.js"></script>');
        }

        $this->cache['js_added_matrix'] = true;
    }

    public function _load_js_grid()
    {
        if(!isset($this->cache['js_added_grid']))
        {
            $this->EE->cp->add_to_foot('<script type="text/javascript" src="'. $this->_get_theme_url() .'grid.js"></script>');
        }

        $this->cache['js_added_grid'] = true;
    }

    public function _load_js_content_elements()
    {
        if(!isset($this->cache['js_added_content_elements']))
        {
            $this->EE->cp->add_to_foot('<script type="text/javascript" src="'. $this->_get_theme_url() .'content_element.js"></script>');
        }

        $this->cache['js_added_content_elements'] = true;
    }

    public function _prep_data($data, $action)
    {
        $paths = $this->_get_upload_paths_array();
        $path_directories = array();
        $path_urls = array();

        foreach($paths as $key => $value)
        {
            $path_directories[$value['directory']] = 'src="{filedir_'. $value['directory'] .'}';
            $path_urls[$value['directory']] = $value['url'];
        }

        switch($action)
        {
            case 'save':

                // turn brackets into encoded curly brackets for display in a template so the don't parse
                $data = preg_replace_callback('/<(code|pre)>(.*?)<\/(code|pre)>/s', array($this, '_encode_brackets'), $data);

                // On save, all paths found that are saved as an upload path are replaced with it's {filedir_N} equivelent
                foreach($paths as $key => $value)
                {
                    $data = str_replace($value['url'], '{filedir_'. $value['directory'] .'}', $data);
                }

            break;

            case 'display_field':

                // turn encoded curly brackets into curly brackets so you can actually edit them without sifting through encoded HTML
                $data = preg_replace_callback('/<(code|pre)>(.*?)<\/(code|pre)>/s', array($this, '_decode_brackets'), $data);

                // The only way images work is if this is here, otherwise the preg_match below does not work, and CKeditor
                // tries to load images with the following URL/string %7Dfiledir_1%7B.
                // Note: doing a var_dump on a field's data shows that all carrots are encoded. I tested this with
                // Wygwam as well and it does the same thing.

                $data = str_replace(
                    array('&lt;', '&gt;', '&quot;'),
                    array('<', '>', '"'),
                    $data
                );

                // On display, only switch image tags from their {filedir_N} equivelent to full path so the image displays
                // Linked assets don't get this treatment, we want them to always keep their {filedir_N} intact
                if($data != '' AND preg_match_all('/<img.*?src="({filedir_(.*?)}.*?)".*?\/?>/', $data, $matches))
                {
                    foreach($matches[1] as $match => $match_val)
                    {
                        $url = $path_urls[$matches[2][$match]];
                        $id = $matches[2][$match];

                        // Reassemble/replace our links
                        $data = str_replace('{filedir_'. $id .'}', $url, $data);

                        /*
                        $data = preg_replace('/<img(.*?)src="({.*?})(.*?)"(.*?)\/?>/', "<img src=\"$url$3\" $1 $4 />", $data);
                        */
                    }
                }

            break;
        }

        return $data;
    }

    /*
        Encode all linked or non-linked email addresses. Will work with any attributes
        in the <a> tag or query string parameters on the address, such as a subject line.
    */
    public function _email_obfuscate($matches)
    {
        // Group names don't work on some servers, so use indexes instead / added ver. 1.2.2
        $email = isset($matches[2]) ? $matches[2] : false;
        $pre_addr = isset($matches[1]) ? $matches[1] : '';
        $post_addr = isset($matches[3]) ? $matches[3] : '';
        $text = isset($matches[4]) ? $matches[4] : $email;
        $end = isset($matches[5]) ? $matches[5] : '';

        if(!$email)
        {
            return;
        }

        $encoded_string = $this->_encode_string($email);
        $text = preg_replace_callback($this->email_pattern, array($this, '_email_obfuscate_text'), $text);

        // Make sure un-linked emails are now linked, but only if the pref is set to yes.
        if(!$pre_addr AND !$post_addr AND @$this->row['channel_auto_link_urls'] == 'y')
        {
            $pre_addr = '<a href="mailto:'. $encoded_string .'">';
            $post_addr = '</a>';
        }

        $return = $pre_addr.$encoded_string.$post_addr.$text.$end;

        return $return;
    }

    /*
        Obfuscate any addresses in the linked text
    */
    public function _email_obfuscate_text($matches)
    {
        return $this->_encode_string($matches[0]);
    }

    /*
        From Ryan Masuga's Spam Me Not Plugin
        http://github.com/mdesign/md.spam_me_not.ee_addon/tree/master/plugins/
    */
    public function _encode_string($str)
    {
        $mode = "3";
        $encoded_string = "";
        $len = strlen($str);

        for ($i = 0; $i < $len; $i++)
        {
            if ($mode == 3) $mode = rand(1,2);
            switch ($mode)
            {
                case 1: // Decimal code
                    $encoded_string .= "&#" . ord($str[$i]) . ";";
                    break;
                case 2: // Hexadecimal code
                    $encoded_string .= "&#x" . dechex(ord($str[$i])) . ";";
                    break;
                default:
            }
        }

        return $encoded_string;
    }

    // On save
    public function _encode_brackets($matches)
    {
        return str_replace(array("{","}"), array("&#123;","&#125;"), $matches[0]);
    }

    // On display
    public function _decode_brackets($matches)
    {
        return str_replace(array("&#123;","&#125;"), array("{","}"), $matches[0]);
    }

    // Found at http://stackoverflow.com/questions/4912275/automatically-generate-nested-table-of-contents-based-on-heading-tags
    public function _create_toc($data, $params = array())
    {
        if ( !$data) return '';

        if (isset($params['show']) && in_array($params['show'], array('n', 'no')))
        {
            return $data;
        }

        $doc = new DOMDocument();
        $doc->loadHTML($data);

        // create document fragment
        $frag = $doc->createDocumentFragment();

        $list_type = isset($params['list_type']) ? $params['list_type'] : 'ol';
        $item_type = isset($params['item_type']) ? $params['item_type'] : 'li';
        $list_class = isset($params['list_class']) ? $params['list_class'] : FALSE;
        $item_class = isset($params['item_class']) ? $params['item_class'] : FALSE;
        $child_class = isset($params['child_class']) ? $params['child_class'] : FALSE;

        // create initial list
        $list = $doc->createElement($list_type);
        if ($list_class) $list->setAttribute('class', $list_class);

        $frag->appendChild($list);
        $head = &$frag->firstChild;
        $xpath = new DOMXPath($doc);
        $last = 1;

        // get all H1, H2, …, H6 elements
        foreach ($xpath->query('//*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6]') as $headline)
        {
            // get level of current headline
            sscanf($headline->tagName, 'h%u', $curr);

            // move head reference if necessary
            if ($curr < $last) {
                // move upwards
                for ($i=$curr; $i<$last; $i++) {
                    $head = &$head->parentNode->parentNode;
                }
            } else if ($curr > $last && $head->lastChild) {
                // move downwards and create new lists
                for ($i=$last; $i<$curr; $i++) {
                    $child = $doc->createElement($list_type);
                    if ($child_class) $child->setAttribute('class', $child_class);
                    $head->lastChild->appendChild($child);
                    $head = &$head->lastChild->lastChild;
                }
            }
            $last = $curr;

            // add list item
            $item = $doc->createElement($item_type);
            if ($item_class) $item->setAttribute('class', $item_class);
            $head->appendChild($item);
            $a = $doc->createElement('a', $headline->textContent);
            $head->lastChild->appendChild($a);

            // build ID
            $levels = array();
            $tmp = &$head;

            // walk subtree up to fragment root node of this subtree
            while (!is_null($tmp) && $tmp != $frag) {
                $levels[] = $tmp->childNodes->length;
                $tmp = &$tmp->parentNode->parentNode;
            }

            $id = 'sect'.implode('.', array_reverse($levels));

            // set destination
            $a->setAttribute('href', '#'.$id);

            // add anchor to headline
            $a = $doc->createElement('a');
            $a->setAttribute('name', $id);
            $a->setAttribute('id', $id);
            $headline->insertBefore($a, $headline->firstChild);
        }

        $body = $doc->getElementsByTagName('body')->item(0);
        $body->insertBefore($frag, $body->firstChild);

        return $doc->saveHTML();
    }

    /*
    @param - string
    @param - array of data to be inserted, key => value pairs
    @param - array of data used to find the row to update, key => value pairs

    _insert_or_update('some_table', array('foo' => 'bar'), array('id' => 1, 'something' => 'another-thing'))

    */
    public function insert_or_update($table, $data, $where, $primary_key = 'id')
    {
        $query = $this->EE->db->get_where($table, $where);

        // No records were found, so insert
        if ($query->num_rows() == 0)
        {
            $this->EE->db->insert($table, $data);
            return $this->EE->db->insert_id();
        }
        // Update existing record
        elseif ($query->num_rows() == 1)
        {
            $this->EE->db->where($where)->update($table, $data);
            return $this->EE->db->select($primary_key)->from($table)->where($where)->get()->row($primary_key);
        }
    }

    private function debug($str, $die = false)
    {
        echo '<pre>';
        var_dump($str);
        echo '</pre>';

        if($die) die;
    }

}
