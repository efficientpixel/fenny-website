<?php $this->load->view('errors'); ?>
<?php $this->load->view('partials/backups_submenu'); ?>

<br clear="all" />
<?php 
echo lang('module_instructions'); ?>
<div class="clear_left shun"></div>
<div>
<?php 
$this->table->set_heading(
	lang('total_backups'), 
	lang('total_space_used'), 
	array('data' => lang('last_backup_taken'), 'align' => 'right'), 
	array('data' => lang('first_backup_taken'), 'align' => 'right')
);
$data = array(
	array('data' => $backup_meta['files']['total_backups'], 'width' => 80), 
	array('data' => $backup_meta['files']['total_space_used'], 'width' => 150), 
	array('data' => ($backup_meta['files']['newest_backup_taken'] != '' ? $backup_meta['files']['newest_backup_taken'] : lang('na')), 'width' => 150, 'align' => 'right'),
	array('data' => ($backup_meta['files']['oldest_backup_taken'] != '' ? $backup_meta['files']['oldest_backup_taken'] : lang('na')), 'width' => 150, 'align' => 'right')
);
$this->table->add_row($data);
echo $this->table->generate();
$this->table->clear();
?>
</div>
<div class="clear_left shun"></div>

<?php echo form_open($query_base.'delete_backup_confirm', array('id'=>'my_accordion')); ?>

<h3  class="accordion"><?php echo lang('file_backups').' ('.count($backups['files']).')'?></h3>
<div id="file_backups">
	<?php if(count($backups['files']) > 0): ?>
	<?php 

	$options = array('enable_delete' => true, 'backups' => $backups['files'], 'toggle_dir' => 'files');
	$this->load->view('partials/backup_table', $options);	
	?>
	<?php else: ?>
		<div class="no_backup_found"><?php echo lang('no_file_backups')?> <a href="<?php echo $nav_links['backup_files']; ?>"><?php echo lang('would_you_like_to_backup_now')?></a></div>
	<?php endif; ?>	
</div>
<br />
<?php if(count($backups['files']) != '0'): ?>
<div class="tableFooter">
	<div class="tableSubmit">
		<?php echo form_submit(array('name' => 'submit', 'value' => lang('delete_selected'), 'class' => 'submit'));?>
	</div>
</div>	
<?php endif;?>
<?php echo form_close()?>