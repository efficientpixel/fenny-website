<?php

/**
 * ExpressionEngine Template Selection Helper Class
 *
 * @package     ExpressionEngine
 * @category    Helpers
 * @author      Brian Litzinger
 * @copyright   Copyright 2010 - Brian Litzinger
 * @link        http://boldminded.com/
 */

class Template_Selection {
    
    var $EE;
    
    function __construct($EE)
    {
        $this->EE = $EE;
    }
    
    function _create_settings_options($data, $prefix)
    {
        $templates = $this->_query_templates();
        $settings = (!isset($data['template_snippet_select']) OR $data['template_snippet_select'] == '') ? array() : $data['template_snippet_select'];

        $template_checkbox_options = '<p>'. form_checkbox($prefix.'[field_show_all_templates]', 'y', (isset($data['field_templates']['show_all']) AND $data['field_templates']['show_all'] == 'y') ? TRUE : FALSE, 'id="show_all_templates"') . ' <label for="show_all_templates">Show all</label></p>';

        $groups = array();
        foreach($templates->result_array() as $template)
        {
            if(!in_array($template['group_name'], $groups))
            {
                $template_checkbox_options .= '<p>'. form_checkbox($prefix.'[field_show_group_templates][]', $template['group_name'], (isset($template['group_name']) AND isset($data['field_templates']['show_group']) AND is_array($data['field_templates']['show_group']) AND in_array($template['group_name'], $data['field_templates']['show_group'])) ? TRUE : FALSE, 'class="show_group_templates" id="show_'. $template['group_name'] .'"') . ' <label for="show_'. $template['group_name'] .'">Show all <i>'. $template['group_name'] .'</i> templates</label></p>';
            }
            $groups[] = $template['group_name'];
        }

        $template_checkbox_options .= '<p>'. form_checkbox($prefix.'[field_show_selected_templates]', 'y', (isset($data['field_templates']['show_selected']) AND $data['field_templates']['show_selected'] == 'y') ? TRUE : FALSE, 'id="show_selected_templates"') . ' <label for="show_selected_templates">Show only specific templates</label></p>';

        return $template_checkbox_options;
    }
    
    function _query_templates()
    {
        // Get the current Site ID
        $site_id = $this->EE->config->item('site_id');

        $sql = "SELECT tg.group_name, t.template_name, t.template_id
                FROM exp_template_groups tg, exp_templates t
                WHERE tg.group_id = t.group_id
                AND tg.site_id = '".$site_id."' 
                ORDER BY tg.group_name, t.template_id";

        return $this->EE->db->query($sql);
    }
    
    function _create_template_options()
    {
        $options = array();
        $templates = $this->_query_templates();

        foreach($templates->result_array() as $row) 
        {
            $file = $row['group_name'] .'/'. $row['template_name'];
            $options[$row['template_id']] = $file;
        }
        
        return $options;
    }
    
    // JS for the field settings page
    function _load_js_settings()
    {
        $script = '
            function show_all_templates(on_load){
                if($("#show_all_templates").is(":checked")){
                    $(".show_group_templates, #show_selected_templates").attr("checked", false).attr("disabled", true);
                } else if(!on_load) {
                    $(".show_group_templates, #show_selected_templates").attr("disabled", false);
                }
            }
            function show_group_templates(on_load){
                if($("input[name*=\'field_show_group\']").is(":checked")){
                    $("#show_all_templates, #show_selected_templates").attr("checked", false).attr("disabled", true);
                } else if(!on_load) {
                    $("#show_all_templates, #show_selected_templates").attr("disabled", false);
                }
            }
            function show_selected_templates(on_load){
                if($("#show_selected_templates").is(":checked")){
                    $(".show_group_templates, #show_all_templates").attr("checked", false).attr("disabled", true);
                    $(".field_template_select").show();
                } else if(!on_load) {
                    $(".show_group_templates, #show_all_templates").attr("disabled", false);
                    $(".field_template_select").hide();
                    $(".field_template_select option").attr("selected", false);
                }
            }
            
            function show_all_snippets(on_load){
                if($("#show_all_snippets").is(":checked")){
                    $(".show_group_snippets, #show_selected_snippets").attr("checked", false).attr("disabled", true);
                } else if(!on_load) {
                    $(".show_group_snippets, #show_selected_snippets").attr("disabled", false);
                }
            }
            function show_selected_snippets(on_load){
                if($("#show_selected_snippets").is(":checked")){
                    $("#show_all_snippets").attr("checked", false).attr("disabled", true);
                    $(".field_snippet_select").show();
                } else if(!on_load) {
                    $("#show_all_snippets").attr("disabled", false);
                    $(".field_snippet_select").hide();
                    $(".field_snippet_select option").attr("selected", false);
                }
            }
            
            $("#show_all_templates").live("click", function(){
                show_all_templates(false);
            });
            $(".show_group_templates").live("click", function(){
                show_group_templates(false);
            });
            $("#show_selected_templates").live("click", function(){
                show_selected_templates(false);
            });
            
            $("#show_all_snippets").live("click", function(){
                show_all_snippets(false);
            });
            $("#show_selected_snippets").live("click", function(){
                show_selected_snippets(false);
            });
            
            show_all_templates(true);
            show_group_templates(true);
            show_selected_templates(true);
            
            show_all_snippets(true);
            show_selected_snippets(true);
        ';

        $this->EE->cp->add_to_foot('<!-- BEGIN Wyvern Assets --><script type="text/javascript">$(function(){'. preg_replace("/\s+/", " ", $script) .'});</script><!-- END Wyvern Assets -->');
    }
    
}