<h3  class="accordion"><span class="s3_settings_header"><?=lang('configure_s3')?></span></h3>
<div>
	<?php 
	$this->table->set_heading(lang('setting'),lang('value'));
		
	$this->table->add_row('<label for="s3_access_key">'.lang('s3_access_key').'</label><div class="subtext">'.lang('s3_access_key_instructions').'</div>', form_input('s3_access_key', $settings['s3_access_key'], 'id="s3_access_key"'. $settings_disable));
	$this->table->add_row('<label for="s3_secret_key">'.lang('s3_secret_key').'</label><div class="subtext">'.lang('s3_secret_key_instructions').'</div>', form_password('s3_secret_key', $settings['s3_secret_key'], 'id="s3_secret_key"'. $settings_disable));
	$this->table->add_row('<label for="s3_bucket">'.lang('s3_bucket').'</label><div class="subtext">'.lang('s3_bucket_instructions').'</div>', form_input('s3_bucket', $settings['s3_bucket'], 'id="s3_bucket"'. $settings_disable));
	$this->table->add_row('<label for="s3_prune_remote">'.lang('s3_prune_remote').'</label><div class="subtext">'.lang('s3_prune_remote_instructions').'</div>', form_checkbox('s3_prune_remote', '1', $settings['s3_prune_remote'], 'id="s3_prune_remote"'. $settings_disable));
	
	echo $this->table->generate();
	$this->table->clear();	
	?>
</div>