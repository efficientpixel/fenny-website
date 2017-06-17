<?php

/**
 * ExpressionEngine Taxonomy Helper Class
 *
 * @package     ExpressionEngine
 * @category    Helpers
 * @author      Brian Litzinger
 * @copyright   Copyright 2010 - Brian Litzinger
 * @link        http://boldminded.com/
 */

class Taxonomy_Pages {

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

        // 1.x
        if(file_exists(PATH_THIRD .'taxonomy/libraries/MPTtree.php'))
        {
            require_once PATH_THIRD .'taxonomy/libraries/MPTtree.php';
            $this->EE->mpttree = new MPTtree;

            $taxonomy_version = 1;
        }
        // 2.x
        else if(file_exists(PATH_THIRD .'taxonomy/libraries/Ttree.php'))
        {
            require_once PATH_THIRD .'taxonomy/libraries/Ttree.php';
            $this->EE->ttree = new Ttree;

            $taxonomy_version = 2;
        }
        else if(file_exists(PATH_THIRD .'taxonomy/models/taxonomy_model.php'))
        {
            require_once PATH_THIRD .'taxonomy/models/taxonomy_model.php';
            $this->EE->tmodel = new Taxonomy_model;
            // require_once PATH_THIRD .'taxonomy/mod.taxonomy.php';
            // $this->EE->tmodule = new Taxonomy;

            $taxonomy_version = 3;
        }

        //just to prevent any errors
        if ( ! defined('BASE'))
        {
            $s = ($this->EE->config->item('admin_session_type') != 'c') ? $this->EE->session->userdata('session_id') : 0;
            define('BASE', SELF.'?S='.$s.'&amp;D=cp');
        }

        $options = array();
        $return = '';

        $wyvern_hidden_config = $this->EE->config->item('wyvern');

        if (isset($wyvern_hidden_config['taxonomy_show_trees']) AND is_array($wyvern_hidden_config['taxonomy_show_trees']))
        {
            $this->EE->db->where_in('id', $wyvern_hidden_config['taxonomy_show_trees']);
        }

        $trees = $this->EE->db->get('taxonomy_trees');

        $options['depth']           = 100 ;
        $options['display_root']    = "yes";
        $options['root']            = 1;
        $options['root_entry_id']   = NULL;
        $options['root_node_id']    = NULL;
        $options['entry_id']        = NULL;
        $options['ul_css_id']       = NULL;
        $options['ul_css_class']    = NULL;
        $options['hide_dt_group']   = NULL;
        $options['path']            = NULL;
        $options['url_title']       = NULL;

        foreach($trees->result_array() as $tree)
        {
            if($taxonomy_version == 1)
            {
                $this->EE->mpttree->set_opts(array(
                    'table' => 'exp_taxonomy_tree_'.$tree['id'],
                    'left' => 'lft',
                    'right' => 'rgt',
                    'id' => 'node_id',
                    'title' => 'label'
                ));

                $return .= '<h4>'. $tree['label'] .'</h4>';
                $return .= $this->build_list($tree_array, $options);

                $tree_array = $this->EE->mpttree->tree2array_v2($options['root'], $options['root_entry_id'], $options['root_node_id']);
            }
            else if ($taxonomy_version == 2)
            {
                $this->EE->ttree->set_table($tree['id']);

                $tree_array = $this->EE->ttree->tree_to_array($options['root'], $options['root_entry_id'], $options['root_node_id']);

                $return .= '<h4>'. $tree['label'] .'</h4>';
                $return .= $this->build_list($tree_array, $options);
            }
            else if ($taxonomy_version == 3)
            {
                $this->EE->tmodel->tree_table = 'taxonomy_tree_'.$tree['id'];
                $this->EE->tmodel->tree_id = $tree['id'];
                $this->EE->tmodel->get_tree();
                $this->EE->tmodel->get_nodes();
                $tree_data = $this->EE->tmodel->cache['trees'][$tree['id']]['nodes']['by_node_id'];
                $tree_name = $this->EE->tmodel->cache['trees'][$tree['id']]['label'];

                $return .= '<h4>'. $tree_name .'</h4><ul class="structure_pages">';

                $tagdata = '<li id="page-{node_entry_id}" class="page-item">
                    <div class="item_wrapper round"><a href="{page_edit_url}" data-taxonomy="yes" data-type="{node_link_type}" data-url="{node_url}" data-id="{node_entry_id}" class="round_left round_right">{node_title}</a>{children}</div>';

                $return .= $this->build_list_v3($tagdata, $tree_data, $options);
            }
        }

        return $this->_get_styles() . $return;
    }

    function build_list_v3($tagdata, $tree, $params)
    {
        $str = '';

        $level_count = 0;
        $level_total_count = count($tree);

        // filter out nodes we don't want from this level
        $tree = $this->_pre_process_level($tree, $params);

        // flag subsequent requests to this method as false.
        $params['first_pass'] = FALSE;

        foreach($tree as $node)
        {
            if(isset($tree[$node['node_id']]))
            {
                // get the node attributes
                $att = $tree[$node['node_id']];

                $active = '';
                $active_parent = '';

                $link_type = $att['type'][0];
                $url = $att['url'];
                $site_index = $this->EE->functions->fetch_site_index();

                // If it contains the full site URL remove it, JS will add {site_url}
                $url = strpos($url, $this->EE->functions->fetch_site_index()) !== FALSE ? str_replace($site_index, '', $url) : $url;

                $vars = array(
                    'node_id' => $att['node_id'],
                    'node_title' => $att['label'],
                    'node_url' => $url,
                    'node_relative_url' => '', // @todo
                    'node_active' => $active,
                    'node_active_parent' => $active_parent,
                    'node_lft' => $att['lft'],
                    'node_rgt' => $att['rgt'],
                    'node_entry_id' => $att['entry_id'],
                    'node_channel_id' => $att['channel_id'],
                    'node_custom_url' => $att['custom_url'],
                    'node_entry_title' => $att['title'],
                    'node_entry_url_title' => $att['url_title'],
                    'node_entry_status' => $att['status'],
                    'node_entry_entry_date' => $att['entry_date'],
                    'node_entry_expiration_date' => $att['expiration_date'],
                    'node_entry_template_name' => '', // @todo
                    'node_entry_template_group_name' => '', // @todo
                    'node_has_children' => (isset($node['children'])) ? 'yes' : 0,
                    'node_next_child' => $att['lft']+1,
                    'node_level' => $att['depth'],
                    'node_level_count' => $level_count,
                    'node_level_total_count' => $level_total_count,
                    'node_indent' => str_repeat(' ', $level_count),
                    'children' => '',

                    'node_link_type' => $link_type,
                    'page_edit_url' => BASE .'&C=content_publish&M=entry_form&channel_id='. $att['channel_id'] .'&entry_id='. $att['entry_id']
                );

                // have we got children, go through this method recursively
                if((isset($node['children'])))
                {
                    $vars['children'] = $this->build_list_v3($tagdata, $node['children'], $params);
                }
            }

            // swappy swappy
            $tmp = ee()->functions->prep_conditionals($tagdata, $vars);
            $str .= ee()->functions->var_swap($tmp, $vars);

            // close out our list
            if($level_count == $level_total_count && $params['include_ul'] == 'yes' && $params['style'] == 'nested')
            {
                $str .= "\n</ul>";
            }

            $level_count++;
        }

        // et voila
        return $str;
    }


    function build_list($array, $options)
    {
        $options['depth']           = 100;
        $options['display_root']    = ($options['display_root']) ? $options['display_root'] : "yes";

        $str = '';
        $ul_id = '';
        $ul_class = '';

        if(!$array)
            return false;

        $str .= '<ul class="structure_pages">';

        $closing_ul = '</ul>';

        // Added by @nevsie
        $level_count = 0;
        $level_total_count = count($array);

        foreach($array as $data)
        {
            $active_parent = '';
            $level_count ++;

            if(($data['level'] == 0) && ($options['display_root'] == "no" && isset($data['children'])))
            {
                $str = $this->build_list($data['children'], $options);
                $closing_ul = '';
            }
            else
            {
                // remove default template group segments
                $template_group = ($data['is_site_default'] == 'y') ? '' : '/'.$data['group_name'];
                $template_name =    '/'.$data['template_name'];
                $url_title =        '/'.$data['url_title'];

                // don't display /index
                if($template_name == '/index')
                {
                    $template_name = '';
                }

                $link_type = 'template';
                $node_url = $template_group.$template_name.$url_title;

                // override template and entry slug with custom url if set
                if($data['custom_url'])
                {
                    $node_url = $data['custom_url'];

                    // if we've got a page_uri set, go fetch the pages uri
                    if($node_url == "[page_uri]")
                    {
                        $link_type = 'page';
                        $site_id = $this->EE->config->item('site_id');
                        $node_url = $this->entry_id_to_page_uri($data['entry_id'], $site_id);
                    }
                    elseif($node_url[0] == "#")
                    {
                        $link_type = 'custom';
                        $node_url = $data['custom_url'];
                    }
                    // if it's a relative url, prepend the site index
                    // otherwise just roll with the user's input
                    else
                    {
                        $link_type = 'custom';
                        // does the custom url start with http://,
                        // if not we add our site_index as it'll be a relative link
                        // and the nav tag will apply the $active css class to the node
                        $node_url = ((substr(ltrim($node_url), 0, 7) != 'http://') && (substr(ltrim($node_url), 0, 8) != 'https://') ? $this->EE->functions->fetch_site_index() : '') . $node_url;
                    }
                }

                // get rid of double slashes, and trailing slash
                $node_url = trim(reduce_double_slashes($node_url), '/');

                $children = '';
                $children_class = '';

                if(isset($data['has_children']))
                {
                    $children = 'yes';
                    $children_class = 'has_children';
                }

                $variables = array(
                    'node_id' => $data['node_id'],
                    'node_title' => $data['label'],
                    'node_url' => $node_url,
                    'node_lft' => $data['lft'],
                    'node_rgt' => $data['rgt'],
                    'node_entry_id' =>  $data['entry_id'],
                    'node_custom_url' => $data['custom_url'],
                    'node_extra' =>  isset($data['extra']) ? $data['extra'] : '',
                    'node_entry_title' => $data['title'],
                    'node_entry_url_title' => $data['url_title'],
                    'node_entry_status' =>  $data['status'],
                    'node_entry_entry_date' => $data['entry_date'],
                    'node_entry_template_name' => $data['template_name'],
                    'node_entry_template_group_name' => $data['group_name'],
                    'node_has_children' => $children,
                    'node_next_child' => $data['lft']+1,
                    'node_level' => $data['level'],
                    'node_level_count' => $level_count,
                    'node_level_total_count' => $level_total_count
                );

                // make sure each node has a unique class
                if($data['entry_id'] == "")
                {
                    $this->EE->load->helper('url');
                    $unique_class = str_replace(".","_", url_title(strtolower($data['label'])));
                }
                else
                {
                    $unique_class = $data['url_title'];
                }

                $entry = $this->EE->db->where('entry_id', $variables['node_entry_id'])
                                      ->get('channel_titles');

                $level = $data['level'];
                $page_edit_url = BASE .'&C=content_publish&M=entry_form&channel_id='. $entry->row('channel_id') .'&entry_id='. $variables['node_entry_id'];

                $str .= '<li id="page-'. $variables['node_entry_id'] . '" class="page-item">';
                $str .= '<div class="item_wrapper round"><a href="'. $page_edit_url .'" data-taxonomy="yes" data-type="'. $link_type .'" data-url="'. $node_url .'" data-id="'. $data['entry_id'] .'" class="round_left round_right">'. $variables['node_title'] .'</a></div>';

                if(isset($data['children']) && $data['level'] < $options['depth'])
                {
                    // reset css id and class if going deeper
                    $options['ul_css_id'] = NULL;
                    $options['ul_css_class'] = NULL;

                    // recurse dammit
                    $str .= $this->build_list($data['children'], $options);
                }

                $str .= '</li>';
            }
        }

        $str .= $closing_ul;

        return $str;
    }

    // returns a page_uri from an entry_id
    private function entry_id_to_page_uri($entry_id, $site_id = '1')
    {
        $site_pages = $this->EE->config->item('site_pages');

        if ($site_pages !== FALSE && isset($site_pages[$site_id]['uris'][$entry_id]))
        {
            $node_url = $site_pages[$site_id]['uris'][$entry_id];
        }
        else
        {
            // not sure what else to do really?
            $node_url = NULL;
        }

        return $node_url;
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
            self::$inst = new Taxonomy_Pages();
        }

        return self::$inst;
    }

    // filter for status, entry_date, expiration date etc
    private function _pre_process_level($taxonomy, $params)
    {
        $tree_id = $this->EE->tmodel->tree_id;

        if(!isset($this->EE->tmodel->cache['first_pass'])) $this->EE->tmodel->cache['first_pass'] = 1;

        foreach($taxonomy as $key => $node)
        {
            if(isset($this->EE->tmodel->cache['trees'][$tree_id]['nodes']['by_node_id'][ $node['node_id'] ]))
            {
                $att = $this->EE->tmodel->cache['trees'][$tree_id]['nodes']['by_node_id'][ $node['node_id'] ];

                if($node['node_id'] == $params['node_id']  && $node['level'] != 0)
                {
                    $this->act_lev[$node['level']]['act_lft']   = $att['lft'];
                    $this->act_lev[$node['level']]['act_rgt']   = $att['rgt'];
                }

                // taking out the root, or are we requesting a level below the current
                if( ($node['level'] == 0 || $this->EE->tmodel->cache['first_pass'] == 1) && $params['display_root'] == "no" && isset($node['children']))
                {
                    $this->EE->tmodel->cache['first_pass'] = 0;
                    return (isset($taxonomy[0]['children'])) ? $this->_pre_process_level($taxonomy[0]['children'], $params) : array();
                }
                // --------------------------------------------------
                // auto expanding of active branch
                // --------------------------------------------------
                if($params['auto_expand'] == 'yes')
                {

                    if (
                        $node['level'] == 0
                        ||
                        (
                            ( // are we on a sibling of an active parent?
                            isset($this->actp_lev[($node['level']-1)]['act_lft'])
                            &&
                            $att['lft'] >= $this->actp_lev[($node['level']-1)]['act_lft']
                            &&
                            $att['rgt'] <= $this->actp_lev[($node['level']-1)]['act_rgt']
                            )
                        ||
                            ( // are we on a sibling of the active
                            isset($this->act_lev[($node['level']-1)]['act_lft'])
                            &&
                            $att['lft'] >= $this->act_lev[($node['level']-1)]['act_lft']
                            &&
                            $att['rgt'] <= $this->act_lev[($node['level']-1)]['act_rgt']
                            )
                        )
                        || $node['level'] <= $params['active_branch_start_level']
                    )
                    {
                        // getting farking complicated
                    }
                    else
                    {
                        unset($taxonomy[$key]);
                    }

                }
                // --------------------------------------------------
            }
        }

        return $taxonomy;
    }
}
