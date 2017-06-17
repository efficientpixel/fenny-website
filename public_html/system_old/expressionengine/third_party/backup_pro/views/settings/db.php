<h3  class="accordion"><?=lang('config_db')?></h3>
<input type="hidden" name="db_backup_ignore_tables[]" value="" />
<input type="hidden" name="db_backup_ignore_table_data[]" value="" />
<div>
	<?php 
	$db_backup_methods = array('php' => 'PHP', 'mysqldump' => 'MySQLDUMP');
	$db_restore_methods = array('php' => 'PHP', 'mysql' => 'MySQL');
	$settings['db_backup_archive_pre_sql'] = (is_array($settings['db_backup_archive_pre_sql']) ? implode("\n", $settings['db_backup_archive_pre_sql']) : $settings['db_backup_archive_pre_sql']);
	$settings['db_backup_archive_post_sql'] = (is_array($settings['db_backup_archive_post_sql']) ? implode("\n", $settings['db_backup_archive_post_sql']) : $settings['db_backup_archive_post_sql']);
	$settings['db_backup_execute_pre_sql'] = (is_array($settings['db_backup_execute_pre_sql']) ? implode("\n", $settings['db_backup_execute_pre_sql']) : $settings['db_backup_execute_pre_sql']);
	$settings['db_backup_execute_post_sql'] = (is_array($settings['db_backup_execute_post_sql']) ? implode("\n", $settings['db_backup_execute_post_sql']) : $settings['db_backup_execute_post_sql']);
	
	$this->table->set_heading(lang('setting'),lang('value'));
	$this->table->add_row('<label for="max_db_backups">'.lang('max_db_backups').' </label><div class="subtext">'.lang('max_db_backups_instructions').'</div>', form_input('max_db_backups', $settings['max_db_backups'], 'id="max_db_backups"' . $settings_disable));
	$this->table->add_row('<label for="db_backup_alert_threshold">'.lang('db_backup_alert_threshold').' </label><div class="subtext">'.lang('db_backup_alert_threshold_instructions').'</div>', form_input('db_backup_alert_threshold', $settings['db_backup_alert_threshold'], 'id="db_backup_alert_threshold"' . $settings_disable));
	
	$this->table->add_row('<label for="db_backup_method">'.lang('db_backup_method').'</label><div class="subtext">'.lang('db_backup_method_instructions').'</div>', form_dropdown('db_backup_method', $db_backup_methods, $settings['db_backup_method'], 'id="db_backup_method"'. $settings_disable).form_input('mysqldump_command', $settings['mysqldump_command'], 'id="mysqldump_command" style="display:none; width:60%; margin-left:10px;"'));
	$this->table->add_row('<label for="db_restore_method">'.lang('db_restore_method').'</label><div class="subtext">'.lang('db_restore_method_instructions').'</div>', form_dropdown('db_restore_method', $db_restore_methods, $settings['db_restore_method'], 'id="db_restore_method"'. $settings_disable).form_input('mysqlcli_command', $settings['mysqlcli_command'], 'id="mysqlcli_command" style="display:none; width:60%; margin-left:10px;"'));
	
	echo $this->table->generate();
	$this->table->clear();	
	?>
	
	<h3  class="accordion"><?=lang('config_ignore_sql')?></h3>
	<?php 

	$this->table->set_heading(lang('setting'),lang('value'));
	$this->table->add_row('<label for="db_backup_ignore_tables">'.lang('db_backup_ignore_tables').'</label><div class="subtext">'.lang('db_backup_ignore_tables_instructions').'</div>', form_multiselect('db_backup_ignore_tables[]', $db_tables, $settings['db_backup_ignore_tables'], 'id="db_backup_ignore_tables" data-placeholder="'.lang('db_backup_ignore_tables').'"'. $settings_disable));
	$this->table->add_row('<label for="db_backup_ignore_table_data">'.lang('db_backup_ignore_table_data').'</label><div class="subtext">'.lang('db_backup_ignore_table_data_instructions').'</div>', form_multiselect('db_backup_ignore_table_data[]', $db_tables, $settings['db_backup_ignore_table_data'], 'id="db_backup_ignore_table_data" data-placeholder="'.lang('db_backup_ignore_table_data').'"'. $settings_disable));
	
	echo $this->table->generate();
	$this->table->clear();	
	?>
		
	<h3  class="accordion"><?=lang('config_extra_archive_sql')?></h3>
	<?php 

	$this->table->set_heading(lang('setting'),lang('value'));
	$this->table->add_row('<label for="db_backup_archive_pre_sql">'.lang('db_backup_archive_pre_sql').'</label><div class="subtext">'.lang('db_backup_archive_pre_sql_instructions').'</div>', form_textarea('db_backup_archive_pre_sql', $settings['db_backup_archive_pre_sql'], 'cols="90" rows="6" id="db_backup_archive_pre_sql"'. $settings_disable));
	$this->table->add_row('<label for="db_backup_archive_post_sql">'.lang('db_backup_archive_post_sql').'</label><div class="subtext">'.lang('db_backup_archive_post_sql_instructions').'</div>', form_textarea('db_backup_archive_post_sql', $settings['db_backup_archive_post_sql'], 'cols="90" rows="6" id="db_backup_archive_post_sql"'. $settings_disable));
	
	echo $this->table->generate();
	$this->table->clear();	
	?>
	
	<h3  class="accordion"><?=lang('config_execute_sql')?></h3>
	<?php 

	$this->table->set_heading(lang('setting'),lang('value'));
	$this->table->add_row('<label for="db_backup_execute_pre_sql">'.lang('db_backup_execute_pre_sql').'</label><div class="subtext">'.lang('db_backup_execute_pre_sql_instructions').'</div>', form_textarea('db_backup_execute_pre_sql', $settings['db_backup_execute_pre_sql'], 'cols="90" rows="6" id="db_backup_execute_pre_sql"'. $settings_disable));
	$this->table->add_row('<label for="db_backup_execute_post_sql">'.lang('db_backup_execute_post_sql').'</label><div class="subtext">'.lang('db_backup_execute_post_sql_instructions').'</div>', form_textarea('db_backup_execute_post_sql', $settings['db_backup_execute_post_sql'], 'cols="90" rows="6" id="db_backup_execute_post_sql"'. $settings_disable));
	
	echo $this->table->generate();
	$this->table->clear();	
	?>
</div>