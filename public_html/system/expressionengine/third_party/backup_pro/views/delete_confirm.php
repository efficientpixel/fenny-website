<?php $this->load->view('partials/backups_submenu'); ?>
<br clear="all" />
<?php echo form_open($form_action)?>
<?php foreach($backups as $id):?>
	<?php echo form_hidden('delete[]', m62_encode_backup($id['path']))?>
<?php endforeach;?>


<p class="notice"><?php echo lang('action_can_not_be_undone')?></p>
<h3><?php echo lang($download_delete_question); ?></h3>
	<?php 

	$options = array('enable_type' => true, 'backups' => $backups);
	$this->load->view('partials/backup_table', $options);	
	?>

<p>
	<br />Remove from:<br />
	<label for="remove_cf">Cloudfiles</label> <?php echo form_checkbox('remove_cf', '1', '1', 'id="remove_cf"'); ?>
	<label for="remove_s3">Amazon S3</label> <?php echo form_checkbox('remove_s3', '1', '1', 'id="remove_s3"'); ?>
	<label for="remove_gcs">Google Cloud Storage</label> <?php echo form_checkbox('remove_gcs', '1', '1', 'id="remove_gcs"'); ?>
	<label for="remove_ftp">Remote FTP</label> <?php echo form_checkbox('remove_ftp', '1', '1', 'id="remove_ftp"'); ?>
</p>
<p>
	<?php echo form_submit(array('name' => 'submit', 'value' => lang('delete'), 'class' => 'submit'))?>
</p>

<?php echo form_close()?>