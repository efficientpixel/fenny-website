<?php

/**
 * ExpressionEngine NavEE Helper Class
 *
 * @package     ExpressionEngine
 * @category    Helpers
 * @author      Brian Litzinger
 * @copyright   Copyright 2010 - Brian Litzinger
 * @link        http://boldminded.com/
 */

class NavEE_Pages {

    static $inst = null;
    static $singleton = 0;

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
        $return = '';
        $this->site_id = $site_id;

        $wyvern_hidden_config = $this->EE->config->item('wyvern');

        $this->EE->db->where('site_id', $this->site_id);

        if (isset($wyvern_hidden_config['navee_show_trees']) AND is_array($wyvern_hidden_config['navee_show_trees']))
        {
            $this->EE->db->where_in('navigation_id', $wyvern_hidden_config['navee_show_trees']);
        }

        $trees = $this->EE->db->get('navee_navs');

        if($trees->num_rows() == 0)
            return $return;

        foreach($trees->result_array() as $tree)
        {
            $nav = $this->_build_list($tree['navigation_id'], 0);

            $return .= '<h4>'. $tree['nav_name'] .'</h4>';
            $return .= $this->_style_list($nav);
        }

        return $this->_get_styles() . $return;
    }

    private function _build_list($nav_id, $parent)
    {
        $nav = array();

        $this->EE->db->select("n.navee_id,
                                n.parent,
                                n.text,
                                n.link,
                                n.class,
                                n.id,
                                n.sort,
                                n.include,
                                n.passive,
                                n.rel,
                                n.name,
                                n.target,
                                n.regex,
                                n.entry_id,
                                n.channel_id,
                                n.template,
                                n.type,
                                n.custom,
                                n.custom_kids,
                                n.access_key,
                                t.template_name,
                                tg.group_name,
                                ct.title,
                                ct.url_title,
                                nm.members");
        $this->EE->db->from("navee AS n");
        $this->EE->db->join("navee_members AS nm", "nm.navee_id=n.navee_id", "LEFT OUTER");
        $this->EE->db->join("templates AS t", "n.template=t.template_id", "LEFT OUTER");
        $this->EE->db->join("template_groups AS tg", "t.group_id=tg.group_id", "LEFT OUTER");
        $this->EE->db->join("channel_titles AS ct", "n.entry_id=ct.entry_id", "LEFT OUTER");
        $this->EE->db->where("n.navigation_id", $nav_id);
        $this->EE->db->where("n.parent", $parent);
        $this->EE->db->where("n.site_id", $this->site_id);
        $this->EE->db->order_by("n.sort", "asc");
        $query = $this->EE->db->get();

        if($query->num_rows() > 0)
        {
            $count = 0;

            $this->EE->load->model('template_model');

            foreach($query->result_array() as $node)
            {
                $nav[$count]["navee_id"]    = $node['navee_id'];
                $nav[$count]["parent"]      = $node['parent'];
                $nav[$count]["text"]        = $node['text'];
                $nav[$count]["link"]        = $this->_build_link($node);
                $nav[$count]["class"]       = $node['class'];
                $nav[$count]["id"]          = $node['id'];
                $nav[$count]["sort"]        = $node['sort'];
                $nav[$count]["include"]     = $node['include'];
                $nav[$count]["rel"]         = $node['rel'];
                $nav[$count]["name"]        = $node['name'];
                $nav[$count]["target"]      = $node['target'];
                $nav[$count]["regex"]       = $node['regex'];
                $nav[$count]["kids"]        = $this->_build_list($nav_id, $node['navee_id']);

                $count++;
            }
        }

        return $nav;
    }

    private function _build_link($node)
    {
        $link = '';

        // add index if necessary
        // if ($this->include_index == "true"){
        //     $link .= "/".$this->EE->config->item('index_page');
        // }

        $site_pages = $this->EE->config->item('site_pages');
        $site_pages = $site_pages[$this->site_id]['uris'];

        // Build link based on which type it is
        switch ($node['type']) {
            case "guided":

                // template group
                $link .= "/".$node['group_name'];

                // template
                if ($node['template_name'] !== "index") {
                    $link .= "/".$node['template_name'];
                }

                // url_title
                if (strlen($node['url_title'])>0) {
                    $link .= "/".$node['url_title'];
                }

            break;
            case "pages":

                // pages content
                if (count($site_pages)>0){
                    if (array_key_exists($node['entry_id'], $site_pages)){
                        $link .= $site_pages[$node['entry_id']];
                    }
                }

            break;
            default:
                $link = $this->_replacePathGlobal($node['link']);
            break;
        }

        return reduce_double_slashes($link);
    }

    private function _replacePathGlobal($link){

        if (strpos($link, 'path=') !== FALSE){
            $link = preg_replace_callback("/".LD."\s*path=(.*?)".RD."/", array(&$this->EE->functions, 'create_url'), $link);
        }

        return $link;
    }

    private function _style_list($nav, $depth = 0)
    {
        $str = '';

        if(count($nav) == 0)
            return $str;

        $str .= $depth == 0 ? '<ul class="structure_pages">' : '<ul>';

        $count = 0;
        $nav_count = count($nav);

        foreach ($nav as $k => $v)
        {
            $count++;

            $str .= '<li id="page-'. $v['id'] . '" class="page-item">';
            $str .= '<div class="item_wrapper round"><a href="#" data-type="navee" data-url="'. $v['link'] .'" class="round_left round_right">'. $v['text'] .'</a></div>';

            // If our nav item has kids, let's recurse
            if(count($v["kids"]) > 0)
            {
                $str .= $this->_style_list($v['kids'], $depth + 1);
            }

            $str .= '</li>';
        }

        $str .= '</ul>';

        return $str;
    }


    private function _get_styles()
    {
        require 'page_styles.php';
        return preg_replace("/\s+/", " ", $css);
    }

    private function debug($str, $die = false)
    {
        echo '<pre>';
        var_dump($str);
        echo '</pre>';

        if($die) die('debug terminated');
    }

    static function get_instance()
    {
        if(self::$inst == null)
        {
            self::$singleton = 1;
            self::$inst = new NavEE_Pages();
        }

        return self::$inst;
    }
}
