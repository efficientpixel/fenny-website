<?php $this->load->view('errors'); ?>

<div id="backup_instructions">
<?php echo lang('backup_in_progress_instructions'); ?><br />
</div>

<div class="bp_top_nav" id="_backup_start_container">
	<div class="bp_nav">
		<span class="button"> 
			<a class="nav_button" href="javascript:;" id="_backup_start"><?php //echo lang($key.'_bp_dashboard_menu')?>Start Backup</a>
		</span>	
	</div>
</div>

<div id="backup_dashboard_menu" style="display:none">
	<?php $this->load->view('partials/backups_submenu'); ?>
</div>
<br clear="all" />

<input type="hidden" id="__backup_proc_url" value="<?php echo $proc_url; ?>">
<input type="hidden" id="__url_base" value="<?php echo $url_base; ?>">
<input type="hidden" id="__backup_type" value="<?php echo $backup_type; ?>">
<input type="hidden" id="__lang_backup_progress_bar_stop" value="<?php echo lang('backup_progress_bar_stop'); ?>">
<input type="hidden" id="__lang_backup_progress_bar_running" value="<?php echo lang('backup_in_progress'); ?>">

<div id="progress_bar_container" style="display:none">
	<span id="active_item"></span> <br />
	<div id="progressbar"></div>
	Total Items: <span id="item_number"></span> of <span id="total_items"></span> <br />
	<span id="backup_complete"></span>
</div>
