<?=form_open('C=addons_extensions'.AMP.'M=save_extension_settings'.AMP.'file=snaptcha');?>
<?=form_hidden('unique_secret', $unique_secret);?>

<?php
$this->table->set_template($cp_pad_table_template);
$this->table->set_heading(
    array('data' => lang('preference'), 'style' => 'width: 35%;'),
    lang('setting')
);

foreach ($settings as $key => $val)
{
	$label = ($key == 'license_number') ? '<label>'.lang($key).' <strong class="notice">*</strong></label>' : '<label>'.lang($key).'</label>';
	
	$val = ($key == 'license_number' AND !$valid_license) ? $val.' <strong class="notice">'.lang('invalid_license').'</strong>' : $val;
	
	$val = ($key == 'member_registration_validation') ? $val.'<div style="display: none; margin-top: 15px;">'.lang('member_register_notice_extended').':<br/><textarea rows="2" readonly>{exp:snaptcha:field}</textarea><br/><br/>'.lang('member_register_notice').':<br/><div class="member_registration_html"><textarea style="display: none;" rows="4" readonly>'.$member_registration_html_low.'</textarea><textarea style="display: none;" rows="4" readonly>'.$member_registration_html_medium.'</textarea><textarea style="display: none;" rows="4" readonly>'.$member_registration_html_high.'</textarea></div></div>' : $val;
	
	$val = ($key == 'logging' AND $log_file_not_writable) ? $val.' <span class="notice" style="display: none;">'.lang('log_file_not_writable').'</span>' : $val;
	
    $this->table->add_row($label, $val);
}

echo $this->table->generate();
?>

<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>
<?php $this->table->clear()?>
<?=form_close()?>