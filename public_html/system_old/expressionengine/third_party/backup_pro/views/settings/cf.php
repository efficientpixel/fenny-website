<h3  class="accordion"><span class="cf_settings_header"><?=lang('configure_cf')?></span></h3>
<div>
	<?php 
	$cf_location_options = array('us' => 'US', 'uk' => 'UK');
	$this->table->set_heading(lang('setting'),lang('value'));
	
	$this->table->add_row('<label for="cf_username">'.lang('cf_username').'</label><div class="subtext">'.lang('cf_username_instructions').'</div>', form_input('cf_username', $settings['cf_username'], 'id="cf_username"'. $settings_disable));
	$this->table->add_row('<label for="cf_api">'.lang('cf_api').'</label><div class="subtext">'.lang('cf_api_instructions').'</div>', form_password('cf_api', $settings['cf_api'], 'id="cf_api"'. $settings_disable));
	$this->table->add_row('<label for="cf_bucket">'.lang('cf_bucket').'</label><div class="subtext">'.lang('cf_bucket_instructions').'</div>', form_input('cf_bucket', $settings['cf_bucket'], 'id="cf_bucket"'. $settings_disable));
	$this->table->add_row('<label for="cf_location">'.lang('cf_location').'</label><div class="subtext">'.lang('cf_location_instructions').'</div>', form_dropdown('cf_location', $cf_location_options, $settings['cf_location'], 'id="cf_location"'. $settings_disable));
	$this->table->add_row('<label for="cf_prune_remote">'.lang('cf_prune_remote').'</label><div class="subtext">'.lang('cf_prune_remote_instructions').'</div>', form_checkbox('cf_prune_remote', '1', $settings['cf_prune_remote'], 'id="cf_prune_remote"'. $settings_disable));
	
	echo $this->table->generate();
	$this->table->clear();	
	?>
</div>