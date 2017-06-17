<h3  class="accordion"><?=lang('configure_backups')?></h3>
<div>
	<?php 
	
	$this->table->set_heading(lang('setting'),lang('value'));
	$this->table->add_row('<label for="backup_store_location">'.lang('backup_store_location').'</label><div class="subtext">'.lang('backup_store_location_instructions').'</div>', form_input('backup_store_location', $settings['backup_store_location'], 'id="backup_store_location"'. $settings_disable));
	$this->table->add_row('<label for="allowed_access_levels">'.lang('allowed_access_levels').'</label><div class="subtext">'.lang('allowed_access_levels_instructions').'</div>', form_multiselect('allowed_access_levels[]', $member_groups, $settings['allowed_access_levels'], 'id="allowed_access_levels"'. $settings_disable));

	$this->table->add_row('<label for="dashboard_recent_total">'.lang('dashboard_recent_total').'</label><div class="subtext">'.lang('dashboard_recent_total_instructions').'</div>', form_input('dashboard_recent_total', $settings['dashboard_recent_total'], 'id="dashboard_recent_total"'. $settings_disable));
	$this->table->add_row('<label for="auto_threshold">'.lang('auto_threshold').' <!--('.$total_space_used.')--></label><div class="subtext">'.lang('auto_threshold_instructions').'</div>', form_dropdown('auto_threshold', $threshold_options, $settings['auto_threshold'], 'id="auto_threshold"' . $settings_disable).form_input('auto_threshold_custom', $settings['auto_threshold_custom'], 'id="auto_threshold_custom" style="display:none; width:40%; margin-left:10px;"'));
	$this->table->add_row('<label for="date_format">'.lang('date_format').'</label><div class="subtext">'.lang('date_format_instructions').'</div>', form_input('date_format', $settings['date_format'], 'id="date_format"'. $settings_disable));
	$this->table->add_row('<label for="relative_time">'.lang('relative_time').'</label><div class="subtext">'.lang('relative_time_instructions').'</div>', form_checkbox('relative_time', '1', $settings['relative_time'], 'id="relative_time"'. $settings_disable));
	$this->table->add_row('<label for="license_number">'.lang('license_number').'</label>', form_input('license_number', $settings['license_number'], 'id="license_number"'. $settings_disable));
	
	echo $this->table->generate();
	$this->table->clear();	
	?>
</div>