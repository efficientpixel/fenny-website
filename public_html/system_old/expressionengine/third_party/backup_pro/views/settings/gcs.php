<h3  class="accordion"><span class="gcs_settings_header"><?=lang('configure_gcs')?></span></h3>
<div>
	<?php 
	$this->table->set_heading(lang('setting'),lang('value'));
		
	$this->table->add_row('<label for="gcs_access_key">'.lang('gcs_access_key').'</label><div class="subtext">'.lang('gcs_access_key_instructions').'</div>', form_input('gcs_access_key', $settings['gcs_access_key'], 'id="gcs_access_key"'. $settings_disable));
	$this->table->add_row('<label for="gcs_secret_key">'.lang('gcs_secret_key').'</label><div class="subtext">'.lang('gcs_secret_key_instructions').'</div>', form_password('gcs_secret_key', $settings['gcs_secret_key'], 'id="gcs_secret_key"'. $settings_disable));
	$this->table->add_row('<label for="gcs_bucket">'.lang('gcs_bucket').'</label><div class="subtext">'.lang('gcs_bucket_instructions').'</div>', form_input('gcs_bucket', $settings['gcs_bucket'], 'id="gcs_bucket"'. $settings_disable));
	$this->table->add_row('<label for="gcs_prune_remote">'.lang('gcs_prune_remote').'</label><div class="subtext">'.lang('gcs_prune_remote_instructions').'</div>', form_checkbox('gcs_prune_remote', '1', $settings['gcs_prune_remote'], 'id="gcs_prune_remote"'. $settings_disable));
	
	echo $this->table->generate();
	$this->table->clear();	
	?>
</div>