<?php

echo form_open($action_url, 'id="super_globals_module", class="index"', $hidden);

// $cp_pad_table_template in the views
$this->table->set_template(array(
    'table_open'    => '<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">',
    'row_start'     => '<tr class="even">',
    'row_alt_start' => '<tr class="odd">'
));

$this->table->set_heading(array('data' => lang('preference'), 'style' => 'width: 60%'), lang('setting'));

$this->table->add_row(
    lang('css_path', 'field_css_path'),
    form_input(array('id'=>'field_css_path','name'=>'wyvern[field_css_path]', 'style' => 'width: 98%','value' => $settings['field_css_path']))
);

$this->table->add_row(
    lang('js_path', 'field_js_path'),
    form_input(array('id'=>'field_js_path','name'=>'wyvern[field_js_path]', 'style' => 'width: 98%','value' => $settings['field_js_path']))
);

$this->table->add_row(
    lang('google_fonts', 'field_google_fonts'),
    form_input(array('id'=>'field_google_fonts','name'=>'wyvern[field_google_fonts]', 'style' => 'width: 98%','value' => $settings['field_google_fonts']))
);

$this->table->add_row(
    lang('typekit', 'field_typekit'),
    form_input(array('id'=>'field_typekit','name'=>'wyvern[field_typekit]', 'style' => 'width: 98%','value' => $settings['field_typekit']))
);

// Added 1.2.7
// if($this->wyvern_helper->_get_ckeditor_plugins())
// {
//     $this->table->add_row(
//         lang('extra_plugins', 'field_extra_plugins'),
//         form_multiselect('wyvern[field_extra_plugins][]', $this->wyvern_helper->_get_ckeditor_plugins(), $settings['field_extra_plugins'])
//     );
// }
// else
// {
//     $this->table->add_row(
//         lang('extra_plugins', 'field_extra_plugins'),
//         lang('no_extra_plugins', 'field_extra_plugins')
//     );
// }

$this->table->add_row(
    lang('wymeditor_style', 'field_wymeditor_style'),
    form_dropdown('wyvern[field_wymeditor_style]', array('no'=>'No', 'yes'=>'Yes'), $settings['field_wymeditor_style'])
);

// Added 1.2.4
$this->table->add_row(
    lang('default_link_type', 'field_default_link_type'),
    form_dropdown('wyvern[field_default_link_type]', array('site_pages' => 'Site Pages', 'url' => 'URL', 'asset' => 'Asset', 'template' => 'Template', 'anchor' => 'Anchor', 'email' => 'Email'), $settings['field_default_link_type'])
);

// Added 1.2.4
if(array_key_exists('assets', $this->addons->get_installed()))
{
    $file_manager_options = array('default' => 'EE File Manager', 'assets' => 'Assets');
}
else
{
    $file_manager_options = array('default' => 'EE File Manager');
}

$this->table->add_row(
    lang('file_manager', 'field_file_manager'),
    form_dropdown('wyvern[field_file_manager]', $file_manager_options, $settings['field_file_manager'])
);

// Added 1.2.4 - remove this field from new installs, only legacy users will still see it.
// Use <code> or <pre> tags now
if(isset($settings['field_encode_ee_tags']))
{
    $this->table->add_row(
        lang('encode_ee_tags', 'field_encode_ee_tags'),
        form_dropdown('wyvern[field_encode_ee_tags]', array('no'=>'No', 'yes'=>'Yes'), $settings['field_encode_ee_tags'])
    );
}

$this->table->add_row(
    lang('obfuscate_email', 'field_obfuscate_email'),
    form_dropdown('wyvern[field_obfuscate_email]', array('no'=>'No', 'yes'=>'Yes'), $settings['field_obfuscate_email'])
);

// Added in 1.3
$this->table->add_row(
    lang('extra_config', 'field_extra_config'),
    form_textarea(array('id'=>'field_extra_config','name'=>'wyvern[field_extra_config]', 'style' => 'width: 100%; height: 100px;', 'value' => $settings['field_extra_config']))
);

// use Template Selection helper
require_once $this->wyvern_helper->_get_theme_folder_path().'boldminded_themes/libraries/template_selection.php';
$template_selection = new Template_Selection($this);
$template_selection->_load_js_settings();

$this->table->add_row(
    lang('template_setting_title', 'field_template_select'),
    $template_selection->_create_settings_options($settings, 'wyvern') .
    form_multiselect('wyvern[field_template_select][]', $template_selection->_create_template_options(), $settings['field_templates']['templates'], 'size="10" class="field_template_select" style="display: none;"')
);

// Load up the CKeditor/Wyvern icons file
$this->cp->add_to_head('<link rel="stylesheet" href="'. $this->wyvern_helper->_get_theme_url() .'skins/ee/editor.css" />');

$this->table->add_row(
    array('data' => lang('toolbar_configuration', 'field_toolbar_configuration') . $this->wyvern_helper->_get_toolbar_options_saved(), 'style' => 'vertical-align: top;'),
    $this->wyvern_helper->_get_toolbar_options()
);

echo $this->table->generate();
?>

<p class="centerSubmit" id="publish_submit_buttons">
    <?php echo form_submit(array('name' => 'submit', 'value' => lang('save'), 'class' => 'submit')); ?>
</p>
</form>