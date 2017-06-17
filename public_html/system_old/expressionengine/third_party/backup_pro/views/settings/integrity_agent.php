<h3  class="accordion"><?=lang('configure_integrity_agent_backup_missed_schedule')?></h3>
<input type="hidden" name="backup_missed_schedule_notify_member_ids[]" value="" />
<div>
<?php 
	$this->table->set_heading(lang('setting'),lang('value'));
	$this->table->add_row('<label for="backup_missed_schedule_notify_email_interval">'.lang('backup_missed_schedule_notify_email_interval').'</label><div class="subtext">'.lang('backup_missed_schedule_notify_email_interval_instructions').'</div>', form_input('backup_missed_schedule_notify_email_interval', $settings['backup_missed_schedule_notify_email_interval'], 'id="backup_missed_schedule_notify_email_interval"'. $settings_disable));
	$this->table->add_row('<label for="backup_missed_schedule_notify_member_ids">'.lang('backup_missed_schedule_notify_member_ids').'</label><div class="subtext">'.lang('backup_missed_schedule_notify_member_ids_instructions').'</div>', form_multiselect('backup_missed_schedule_notify_member_ids[]', $allowed_notify_members, $settings['backup_missed_schedule_notify_member_ids'], 'id="backup_missed_schedule_notify_member_ids" data-placeholder="'.lang('backup_missed_schedule_notify_member_ids').'"'. $settings_disable));
	$this->table->add_row('<label for="backup_missed_schedule_notify_email_mailtype">'.lang('backup_missed_schedule_notify_email_mailtype').'</label><div class="subtext">'.lang('backup_missed_schedule_notify_email_mailtype_instructions').'</div>', form_dropdown('backup_missed_schedule_notify_email_mailtype', $email_format_options, $settings['backup_missed_schedule_notify_email_mailtype'], 'id="backup_missed_schedule_notify_email_mailtype"'));
	$this->table->add_row('<label for="backup_missed_schedule_notify_email_subject">'.lang('backup_missed_schedule_notify_email_subject').'</label><div class="subtext">'.lang('backup_missed_schedule_notify_email_subject_instructions').'</div>', form_input('backup_missed_schedule_notify_email_subject', $settings['backup_missed_schedule_notify_email_subject'], 'id="backup_missed_schedule_notify_email_subject"'. $settings_disable));
	$this->table->add_row('<label for="backup_missed_schedule_notify_email_message">'.lang('backup_missed_schedule_notify_email_message').'</label><div class="subtext">'.lang('backup_missed_schedule_notify_email_message_instructions').'</div>', form_textarea('backup_missed_schedule_notify_email_message', $settings['backup_missed_schedule_notify_email_message'], 'cols="90" rows="6" id="backup_missed_schedule_notify_email_message" '. $settings_disable));
	echo $this->table->generate();
	$this->table->clear();	
?>
</div>