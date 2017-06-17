<h3  class="accordion"><?=lang('configure_cron')?></h3>
<input type="hidden" name="cron_notify_member_ids[]" value="" />
<?php 
	if(count($cron_commands) >= 1)
	{
		$this->table->set_heading(
			array('data' => lang('backup_type'), 'width' => '50%'), 
			array('data' => lang('cron_commands'), 'width' => '30%'), 
			array('data' => lang('test'), 'width' => '20%')
		);
		foreach($cron_commands AS $key => $value)
		{
			$this->table->add_row(
				array('data' => lang($key), 'width' => '50%'), 
				'<div class="select_all">'.$value['cmd'].'</div>',
				'<a href="'.$value['url'].'" class="test_cron" rel="'.$key.'"><img src="'.$theme_folder_url.'backup_pro/images/test.png" /></a> <img src="'.$theme_folder_url.'backup_pro/images/indicator.gif" id="animated_'.$key.'" style="display:none" />');
		}
		echo $this->table->generate();
		$this->table->clear();	
	}	
	//
	?>
<h3  class="accordion"><?=lang('configure_cron_email_attachment')?></h3>
<div>
	<?php 
	$this->table->set_heading(array('data' => lang('setting'), 'width' => '50%'),lang('value'));
	$this->table->add_row('<label for="cron_attach_backups">'.lang('cron_attach_backups').'</label><div class="subtext">'.lang('cron_attach_backups_instructions').'</div>', form_checkbox('cron_attach_backups', '1', $settings['cron_attach_backups'], 'id="cron_attach_backups"'. $settings_disable));
	$this->table->add_row('<label for="cron_attach_threshold">'.lang('cron_attach_threshold').'</label><div class="subtext">'.lang('cron_attach_threshold_instructions').'</div>', form_input('cron_attach_threshold', $settings['cron_attach_threshold'], 'id="cron_attach_threshold"'. $settings_disable));

	echo $this->table->generate();
	$this->table->clear();	
?>
<h3  class="accordion"><?=lang('configure_cron_notification')?></h3>
<?php 
	$this->table->set_heading(lang('setting'),lang('value'));
	
		
	$this->table->add_row('<label for="cron_notify_member_ids">'.lang('cron_notify_member_ids').'</label><div class="subtext">'.lang('cron_notify_member_ids_instructions').'</div>', form_multiselect('cron_notify_member_ids[]', $allowed_notify_members, $settings['cron_notify_member_ids'], 'id="cron_notify_member_ids" data-placeholder="'.lang('cron_notify_member_ids').'"'. $settings_disable));
	$this->table->add_row('<label for="cron_notify_email_mailtype">'.lang('cron_notify_email_mailtype').'</label><div class="subtext">'.lang('cron_notify_email_mailtype_instructions').'</div>', form_dropdown('cron_notify_email_mailtype', $email_format_options, $settings['cron_notify_email_mailtype'], 'id="cron_notify_email_mailtype"'));
	$this->table->add_row('<label for="cron_notify_email_subject">'.lang('cron_notify_email_subject').'</label><div class="subtext">'.lang('cron_notify_email_subject_instructions').'</div>', form_input('cron_notify_email_subject', $settings['cron_notify_email_subject'], 'id="cron_notify_email_subject"'. $settings_disable));
	$this->table->add_row('<label for="cron_notify_email_message">'.lang('cron_notify_email_message').'</label><div class="subtext">'.lang('cron_notify_email_message_instructions').'</div>', form_textarea('cron_notify_email_message', $settings['cron_notify_email_message'], 'cols="90" rows="6" id="cron_notify_email_message" '. $settings_disable));
	
	echo $this->table->generate();
	$this->table->clear();	
?>
</div>