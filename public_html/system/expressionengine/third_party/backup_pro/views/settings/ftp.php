<h3  class="accordion"><span class="ftp_settings_header"><?=lang('configure_ftp')?></span></h3>
<div>
	<?php 
	$this->table->set_heading(lang('setting'),lang('value'));
	
	$this->table->add_row('<label for="ftp_hostname">'.lang('ftp_hostname').'</label><div class="subtext">'.lang('ftp_hostname_instructions').'</div>', form_input('ftp_hostname', $settings['ftp_hostname'], 'id="ftp_hostname"'. $settings_disable));
	$this->table->add_row('<label for="ftp_username">'.lang('ftp_username').'</label><div class="subtext">'.lang('ftp_username_instructions').'</div>', form_input('ftp_username', $settings['ftp_username'], 'id="ftp_username"'. $settings_disable));
	$this->table->add_row('<label for="ftp_password">'.lang('ftp_password').'</label><div class="subtext">'.lang('ftp_password_instructions').'</div>', form_password('ftp_password', $settings['ftp_password'], 'id="ftp_password"'. $settings_disable));
	$this->table->add_row('<label for="ftp_port">'.lang('ftp_port').'</label><div class="subtext">'.lang('ftp_port_instructions').'</div>', form_input('ftp_port', $settings['ftp_port'], 'id="ftp_port"'. $settings_disable));
	$this->table->add_row('<label for="ftp_passive">'.lang('ftp_passive').'</label><div class="subtext">'.lang('ftp_passive_instructions').'</div>', form_checkbox('ftp_passive', '1', $settings['ftp_passive'], 'id="ftp_passive"'. $settings_disable));
	$this->table->add_row('<label for="ftp_store_location">'.lang('ftp_store_location').'</label><div class="subtext">'.lang('ftp_store_location_instructions').'</div>', form_input('ftp_store_location', $settings['ftp_store_location'], 'id="ftp_store_location"'. $settings_disable));
	$this->table->add_row('<label for="ftp_prune_remote">'.lang('ftp_prune_remote').'</label><div class="subtext">'.lang('ftp_prune_remote_instructions').'</div>', form_checkbox('ftp_prune_remote', '1', $settings['ftp_prune_remote'], 'id="ftp_prune_remote"'. $settings_disable));
	
	echo $this->table->generate();
	$this->table->clear();	
	?>
</div>