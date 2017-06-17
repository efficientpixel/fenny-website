<h3  class="accordion"><?=lang('config_files')?></h3>
<div>
	<?php 
	$settings['exclude_paths'] = (is_array($settings['exclude_paths']) ? implode("\n", $settings['exclude_paths']) : $settings['exclude_paths']);
	$settings['backup_file_location'] = (is_array($settings['backup_file_location']) ? implode("\n", $settings['backup_file_location']) : $settings['backup_file_location']);
	$this->table->set_heading(lang('setting'),lang('value'));
	$this->table->add_row('<label for="max_file_backups">'.lang('max_file_backups').' </label><div class="subtext">'.lang('max_file_backups_instructions').'</div>', form_input('max_file_backups', $settings['max_file_backups'], 'id="max_file_backups"' . $settings_disable));
	$this->table->add_row('<label for="file_backup_alert_threshold">'.lang('file_backup_alert_threshold').' </label><div class="subtext">'.lang('file_backup_alert_threshold_instructions').'</div>', form_input('file_backup_alert_threshold', $settings['file_backup_alert_threshold'], 'id="file_backup_alert_threshold"' . $settings_disable));
	$this->table->add_row('<label for="backup_file_location">'.lang('backup_file_locations').'</label><div class="subtext">'.lang('backup_file_location_instructions').'</div>', form_textarea('backup_file_location', $settings['backup_file_location'], 'id="backup_file_location" cols="90" rows="6"'. $settings_disable));
	$this->table->add_row('<label for="exclude_paths">'.lang('exclude_paths').'</label><div class="subtext">'.lang('exclude_paths_instructions').'</div>', form_textarea('exclude_paths', $settings['exclude_paths'], 'cols="90" rows="6" id="exclude_paths"'. $settings_disable));
	echo $this->table->generate();
	$this->table->clear();	
	?>
</div>