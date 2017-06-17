<?php $this->load->view('errors'); ?>
<?php $this->load->view('partials/backups_submenu'); ?>
<br clear="all" />
<?php 
echo lang('module_instructions'); ?>

<div class="clear_left shun"></div>
<div>
<?php 

$space_available_header = lang('total_space_available');
if($settings['auto_threshold'] != '0')
{
	$space_available_header .= ' ('.$available_space['available_percentage'].'%)';
}

$this->table->set_heading(
	lang('total_backups'), 
	lang('total_space_used'),
	$space_available_header, 
	array('data' => lang('last_backup_taken'), 'align' => 'right'), 
	array('data' => lang('first_backup_taken'), 'align' => 'right')
);
$data = array(
	array('data' => $backup_meta['global']['total_backups'], 'width' => 80), 
	array('data' => $backup_meta['global']['total_space_used'], 'width' => 150), 
	array('data' => ($settings['auto_threshold'] == '0' ? lang('unlimited') : $available_space['available_space'].' / '.$available_space['max_space'])),
	array('data' => ($backup_meta['global']['newest_backup_taken'] != '' ? $backup_meta['global']['newest_backup_taken'] : lang('na')), 'width' => 150, 'align' => 'right'),
	array('data' => ($backup_meta['global']['oldest_backup_taken'] != '' ? $backup_meta['global']['oldest_backup_taken'] : lang('na')), 'width' => 150, 'align' => 'right')
);
$this->table->add_row($data);
echo $this->table->generate();
$this->table->clear();
?>
</div>
<div class="clear_left shun"></div>

<?php echo form_open($query_base, array('id'=>'my_accordion')); ?>

<table width="100%">
	<tr>
		<td width="50%">
		<?php 
		$this->table->set_heading(array('data' => lang('database_backups'), 'width' => '50%'),' ');
		$this->table->add_row('<strong>'.lang('total_backups').'</strong>', $backup_meta['database']['total_backups']);
		$this->table->add_row('<strong>'.lang('total_space_used').'</strong>', $backup_meta['database']['total_space_used']);
		$this->table->add_row('<strong>'.lang('last_backup_taken').'</strong>', ($backup_meta['database']['newest_backup_taken'] != '' ? $backup_meta['database']['newest_backup_taken'] : lang('na')));

		echo $this->table->generate();
		// Clear out of the next one
		$this->table->clear();		
		?>
		</td>
		<td valign="top">
		<?php 
		$this->table->set_heading(array('data' =>lang('file_backups'), 'width' => '50%'),' ');
		$this->table->add_row('<strong>'.lang('total_backups').'</strong>', $backup_meta['files']['total_backups']);
		$this->table->add_row('<strong>'.lang('total_space_used').'</strong>', $backup_meta['files']['total_space_used']);
		$this->table->add_row('<strong>'.lang('last_backup_taken').'</strong>', ($backup_meta['files']['newest_backup_taken'] != '' ? $backup_meta['files']['newest_backup_taken'] : lang('na')));

		echo $this->table->generate();
		// Clear out of the next one
		$this->table->clear();		
		?>
		</td>
	</tr>
</table>
<h3  class="accordion"><?=lang('recent_backups').' ('.count($backups).')';?></h3>
<div id="backups">
	<?php 
		if(count($backups) > 0):
			$options = array('enable_type' => true);
			$this->load->view('partials/backup_table', $options);
	?>
	<?php else: ?>
		<div class="no_backup_found"><?php echo lang('no_backups_exist')?> <a href="<?php echo $nav_links['backup_db']; ?>"><?php echo lang('would_you_like_to_backup_database_now')?></a></div>
	<?php endif; ?>
</div>

<?php echo form_close()?>