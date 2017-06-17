<?php

/**
 * ExpressionEngine Structure Pages Helper Class
 *
 * @package     ExpressionEngine
 * @category    Helpers
 * @author      Brian Litzinger
 * @copyright   Copyright 2010 - Brian Litzinger
 * @link        http://boldminded.com/
 */

class Structure_Pages {
    
    static $inst = null;
    static $singleton = 0;

    private $return_data;
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

        $data = $this->_get_data($site_id);
        $qry = $this->EE->db->get_where('sites', array('site_id' => $site_id));
        $site_pages = unserialize(base64_decode($qry->row('site_pages')));
        $site_pages = $site_pages[$site_id];

        $ul_open = false;
        $last_page_depth = 0;
        $i = 1;

        $this->return_data = $this->_get_styles() . '<ul class="structure_pages">';
        
        foreach ($data as $eid => $page)
        {
            $li_open = '<li id="page-'. $page['entry_id'] . '" class="page-item">';
            $page_edit_url = BASE . '&C=content_publish&M=entry_form&channel_id='.$page['channel_id'].'&entry_id='.$page['entry_id'];
             
            // Start a sub nav
            if ($page['depth'] > $last_page_depth)
            {
                $markup = "<ul class=\"page-list\">".$li_open;
                $ul_open = true;
            }
            elseif ($i == 1)
            {
                $markup = $li_open;
            }
            elseif ($page['depth'] < $last_page_depth)
            {
                $back_to = $last_page_depth - $page['depth'];
                $markup  = "</li>";
                $markup .= str_repeat("</ul></li>", $back_to);
                $markup .= $li_open;
                $ul_open = false;
            }
            else
            {
                $markup = "</li>".$li_open;
            }
            
            $class = 'class="round_left round_right"';
            $expand = '';
            $listing_data = '';
            
            if ($page['listing_cid'])
            {
                $class = 'class="round_left has_listings"';
                $expand = '<a href="#" class="expand round_right"> + </a>';
                
                // Is there a listings method I can use here instead?
                $this->EE->db->select('t.title AS title, t.entry_id AS entry_id, t.channel_id');
                $this->EE->db->from('structure_listings AS sl');
                $this->EE->db->join('channel_titles AS t', 't.entry_id = sl.entry_id');
                $this->EE->db->where('parent_id', $eid);
                $query = $this->EE->db->get();

                $listing_data .= '<ul class="listings">';
                foreach($query->result_array() as $listing)
                {
                    $url = isset($site_pages['uris'][$listing['entry_id']]) ? substr($site_pages['uris'][$listing['entry_id']], 1) : '';
                    $listing_edit_url = BASE . '&C=content_publish&M=entry_form&channel_id='.$listing['channel_id'].'&entry_id='.$listing['entry_id'];
                    $listing_data .= '<li><div class="item_wrapper round listing"><a href="'. $listing_edit_url .'" data-value="{page_url:'. $listing['entry_id'] .'}" data-url="'. $url .'" data-id="'. $listing['entry_id'] .'" class="round">'. $listing['title'] .'</a></div></li>';
                }
                $listing_data .= '</ul>';
            }

            $this->return_data .= $markup;
            // Don't know why, but a couple of times I've seen this come back as undefined.
            $this->return_data .= isset($site_pages['uris'][$page['entry_id']]) ? '<div class="item_wrapper round"><a href="'. $page_edit_url .'" data-value="{page_url:'. $eid .'}" data-url="'. substr($site_pages['uris'][$page['entry_id']], 1) .'" data-id="'. $eid .'" '. $class .'>'. $page['title'] .'</a>'. $expand .'</div>'. $listing_data : '';
            $last_page_depth = $page['depth']; $i++;
        
        } // end foreach
        
        // Close out the end
        $this->return_data .= "</li>";
        $this->return_data .= str_repeat("</ul></li>", $last_page_depth);
        $this->return_data .= '</ul>';
        
        return $this->return_data;
    }

    private function _get_data($site_id)
    {
        $data = array();

        $sql = "SELECT node.*, (COUNT(parent.entry_id) - 1) AS depth, expt.title, expt.status
                FROM exp_structure AS node
                INNER JOIN exp_structure AS parent
                    ON node.lft BETWEEN parent.lft AND parent.rgt
                INNER JOIN exp_channel_titles AS expt
                    ON node.entry_id = expt.entry_id
                WHERE parent.lft > 1
                AND node.site_id = {$site_id}
                AND parent.site_id = {$site_id}
                GROUP BY node.entry_id
                ORDER BY node.lft";

        $result = $this->EE->db->query($sql);

        if ($result->num_rows() > 0)
        {
            foreach ($result->result_array() as $row)
            {
                $data[$row['entry_id']] = $row;
            }
        }

        return $data;
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
            self::$inst = new Structure_Pages();
        }
    
        return self::$inst;
    }
}
