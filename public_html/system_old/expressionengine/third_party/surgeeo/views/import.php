<h3><?=lang('surgeeo_module_import_title')?></h3>
	
<?=form_open_multipart('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=surgeeo'.AMP.'method=uploadCSV')?>
	<p style="margin-bottom:15px;">
		<?=form_upload('file_name', '', 'id="file_name"')?><br /><br />
		<?=form_label(lang('import_type'), 'import_type')?> <?=form_dropdown('import_type', array('p' => 'Pages', 'e' => 'Entries'));?><br /><br />
		<?=form_label(lang('import_delimiter'), 'import_type')?> <?=form_input('import_delimiter', ',', 'id="file_delimiter"');?><br /><br />
		<?=form_submit(array('name' => 'submit', 'value' => lang('import'), 'class' => 'submit'))?>
		<hr/>
		<p>IMPORTANT! - CSV Formatting:</p>
		<p><b>Page:</b> site_id, uri, title, keywords, author, Google+ URL, description</p>
		<p><b>Entry:</b> channel_id, site_id, entry_id, title, keywords, author, Google+ URL, description</p>
	</p>
<?=form_close()?>