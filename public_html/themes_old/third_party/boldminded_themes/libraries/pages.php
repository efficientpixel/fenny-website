<?php

/**
 * ExpressionEngine Pages Helper Class
 *
 * @package     ExpressionEngine
 * @category    Helpers
 * @author      Brian Litzinger
 * @copyright   Copyright 2010 - Brian Litzinger
 * @link        http://boldminded.com/
 */

/*
 A few lines of code were borrowed from Mark Huot's
 original Nested Pages Menu plugin for EE 1.6
*/

class Pages {

    static $inst = null;
    static $singleton = 0;

    private $return_data;
    private $str;
    private $pages;
    private $titles;
    private $params;
    private $ordered;
    private $cfields;
    private $nesting_path;
    private $page_counter = 1;
    private $root = 'page_url';
    private $include_root = 'yes';
    private $current_depth = 1;
    private $order = 'title';
    private $loop_count = 0;
    private $EE;

    public function __construct()
    {
        if(self::$singleton == 0)
        {
            throw new Exception('This class cannot be instantiated by the new keyword.');
        }
    }

    public function get_pages($site_id = 1)
    {
        $this->EE =& get_instance();

        //just to prevent any errors
        if ( ! defined('BASE'))
        {
            $s = ($this->EE->config->item('admin_session_type') != 'c') ? $this->EE->session->userdata('session_id') : 0;
            define('BASE', SELF.'?S='.$s.'&amp;D=cp');
        }

        $this->return_data = '';

        //  =============================================
        //  Get Site Pages
        //  =============================================
        $qry = $this->EE->db->get_where('sites', array('site_id' => $site_id));
        $pages = unserialize(base64_decode($qry->row('site_pages')));
        $pages = $pages[$site_id];

        // Make sure pages exist, otherwise we get notices
        if(!isset($pages['uris']))
            return '';

        natcasesort($pages['uris']);

        //  =============================================
        //  Are Any Blank?
        //  =============================================
        unset($pages['uris']['']);

        $this->pages = $pages;

        //  =============================================
        //  Are There Pages?
        //  =============================================
        //  If not we'll return out and skip all this
        //  silly processing.
        //  ---------------------------------------------
        if(count($this->pages['uris']) == 0) return '';

        //  =============================================
        //  Get Full Titles
        //  =============================================
        if(!isset($this->EE->session->cache['bl_weblog_data']))
        {
            $this->EE->session->cache['bl_weblog_data'] = $this->EE->db->query('SELECT t.entry_id, t.url_title, t.title, t.channel_id, t.status FROM exp_channel_titles t, exp_channel_data d WHERE t.entry_id IN ('.implode(',', array_keys($this->pages['uris'])).') AND t.entry_id=d.entry_id ORDER BY t.entry_id='.implode(' DESC, t.entry_id=', array_keys($this->pages['uris'])).' DESC');
        }

        //  =============================================
        //  Store Titles
        //  =============================================
        $this->titles = $this->EE->session->cache['bl_weblog_data'];
        $this->titles = $this->titles->result_array();

        //  =============================================
        //  Parse Root Parameter
        //  =============================================
        $root = '/';
        if($this->root !== false)
        {
            $root = $this->root;

            //  =============================================
            //  Fix Current Pages
            //  =============================================
            if($root == 'page_url')
            {
                $root = '/'.implode('/', $this->EE->uri->segments).'/';
            }

            //  =============================================
            //  Make Sure Root Has a /
            //  =============================================
            $root = '/'.preg_replace('/^\/|^&#47;|\/$|&#47;$/', '', $root).'/';
            $root = str_replace('&#47;', '/', $root);
        }
        $this->params['root'] = $root;

        //  =============================================
        //  Order Pages
        //  =============================================
        $title_counter = 0;
        $ordered = array();

        foreach($this->pages['uris'] as $entry_id => $uri)
        {
            $depth = 0;
            $base = &$ordered['children'];
            $segs = array_filter(preg_split('/\//', $uri));
            if(count($segs) == 0) $segs[] = '';

            foreach($segs as $seg)
            {
                $depth++;

                if (! isset($base[$seg]))
                {
                    $base[$seg]['depth'] = $depth;
                    $base[$seg]['data'] = $this->titles[$title_counter];
                    $base[$seg]['data']['uri'] = $uri;
                    $base[$seg]['children'] = array();
                }

                $base = &$base[$seg]['children'];
            }

            $title_counter++;
        }

        //  =============================================
        //  Get HTML
        //  =============================================
        $this->build($ordered);

        if($this->return_data != ''){
            return $this->_get_styles() . '<ul class="structure_pages">'. $this->return_data .'</ul>';
        } else {
            return '';
        }
    }

    private function build($array)
    {
        foreach($array as $key => $value)
        {
            if($key == 'depth' and $value > $this->current_depth)
            {
                $this->current_depth++;
                $this->return_data = substr($this->return_data, 0, -5);
            }

            if($key == 'data' and is_array($value) and !empty($value))
            {
                $uri = reduce_double_slashes($value['uri']);
                // If first character is a /, strip it.
                $uri = $uri[0] == '/' ? substr($uri, 1) : $uri;

                $page_edit_url = BASE . '&C=content_publish&M=entry_form&channel_id='.$value['channel_id'].'&entry_id='.$value['entry_id'];

                 // establish indentation
                if($this->current_depth == 1)
                {
                    $spacer = '';
                }
                else
                {
                    $spacer = (15 * ($this->current_depth - 1));
                }

                $this->return_data .= '<li><div class="item_wrapper round" style="margin-left:'. $spacer .'px;"><a href="'. $page_edit_url .'" data-value="{page_url:'. $value['entry_id'] .'}" data-url="'. $uri .'" data-id="'. $value['entry_id'] .'" class="round_left round_right">'. $value['title'] .'</a></div></li>';

                $this->loop_count++;
            }

            if($key == 'depth')
            {
                $this->current_depth = $value;
            }

            if(is_array($value) and !empty($value))
            {
                $this->build($value);
            }
        }
    }

    private function _get_styles()
    {
        require 'page_styles.php';
        return preg_replace("/\s+/", " ", $css);
    }

    private function debug($str)
    {
        echo '<pre>';
        var_dump($str);
        echo '</pre>';
    }

    static function get_instance()
    {
        if(self::$inst == null)
        {
            self::$singleton = 1;
            self::$inst = new Pages();
        }

        return self::$inst;
    }
}
