<?php $this->load->view('errors'); ?>

<div class="bp_top_nav">
	<div class="bp_nav">
	
		<?php 
		foreach($menu_data AS $key => $value): ?>
		<span class="button"> 
			<a class="nav_button <?php echo ($type == $value['url'] ? 'current' : ''); ?>" href="<?php echo $url_base.'settings&section='.$value['url']; ?>"><?php echo lang($key.'_bp_settings_menu')?></a>
		</span>
		<?php endforeach; ?>	
			
	</div>
</div>

<?php 

$tmpl = array (
	'table_open'          => '<table class="mainTable" border="0" cellspacing="0" cellpadding="0">',

	'row_start'           => '<tr class="even">',
	'row_end'             => '</tr>',
	'cell_start'          => '<td style="width:50%;">',
	'cell_end'            => '</td>',

	'row_alt_start'       => '<tr class="odd">',
	'row_alt_end'         => '</tr>',
	'cell_alt_start'      => '<td>',
	'cell_alt_end'        => '</td>',

	'table_close'         => '</table>'
);

$this->table->set_template($tmpl); 
$this->table->set_empty("&nbsp;");
?>
<div class="clear_left shun"></div>

<?php echo form_open($query_base.'settings', array('id'=>'my_accordion'))?>
<input type="hidden" value="yes" name="go_settings" />
<input type="hidden" value="<?php echo $type; ?>" name="section" />
<input type="hidden" value="<?php echo $settings['relative_time']; ?>" name="relative_time" />
<input type="hidden" value="<?php echo $settings['cf_prune_remote']; ?>" name="cf_prune_remote" />
<input type="hidden" value="<?php echo $settings['s3_prune_remote']; ?>" name="s3_prune_remote" />
<input type="hidden" value="<?php echo $settings['gcs_prune_remote']; ?>" name="gcs_prune_remote" />
<input type="hidden" value="<?php echo $settings['ftp_passive']; ?>" name="ftp_passive" />
<input type="hidden" value="<?php echo $settings['ftp_prune_remote']; ?>" name="ftp_prune_remote" />
<input type="hidden" value="<?php echo $settings['cron_attach_backups']; ?>" name="cron_attach_backups" />
<?php 
switch($type)
{
	case 'cron':
	case 'ftp':
	case 's3':
	case 'db':
	case 'files':
	case 'gcs':
	case 'cf':
	case 'integrity_agent':
		$this->load->view('settings/'.$type, array('settings' => $settings));
		break;

	default:
		$this->load->view('settings/general');
		break;
}

?>
<div class="tableFooter">
	<div class="tableSubmit">
		<?php echo form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit'));?>
	</div>
</div>	
<?php echo form_close()?>