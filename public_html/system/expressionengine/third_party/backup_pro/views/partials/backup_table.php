<?php
$this->table->set_template($cp_pad_table_template);

$header = array(
	'',
	lang('note'),
	array('data' => lang('md5_hash'), 'align' => 'right'),
	array('data' => lang('taken'), 'align' => 'right')
);

if(isset($enable_type) && $enable_type)
{
	$header[] = array('data' => lang('type'), 'align' => 'right');
}
$header[] = array('data' => lang('file_size'), 'align' => 'right');
$header[] = array('data' => '', 'align' => 'right');

if(isset($enable_delete) && $enable_delete)
{
	$header[] = form_checkbox('select_all', 'true', FALSE, 'class="toggle_all_files" id="select_all"').NBS.lang('delete', 'select_all');
}

$this->table->set_heading($header);
foreach($backups as $backup)
{
	$image_str = '';
	$images = array('S3' => 's3.png', 'FTP' => 'ftp.png', 'CF' => 'cf.png', 'GCS' => 'gcs.png');
	foreach($images AS $type => $image)
	{
		if(isset($backup['details'][$type]))
		{
			$class = '';
			if($backup['details'][$type] != '1')
			{
				$class = 'desaturate';
			}
			$image_str .= '<img src="'.$theme_folder_url.'backup_pro/images/'.$image.'" class="'.$class.'" />';
		}
	}
	
	$note_field = form_input('note_'.$backup['details']['hash'], $backup['details']['note'], 'id="note_'.$backup['details']['hash'].'" class="note_container" rel="'.m62_encode_backup($backup['backup_type'].'/'.$backup['file_name']).'" style="display:none;"');
	
	/**
	$details_string = array();
	if(!empty($backup['details']['item_count']))
	{
		$details_string[] = '<strong>'.($backup['backup_type'] == 'database' ? lang('total_tables') : lang('total_items')).'</strong> '.number_format($backup['details']['item_count']);
	}
	
	if(!empty($backup['details']['uncompressed_size']))
	{
		$details_string[] = '<strong>'.lang('raw_file_size').'</strong> '.m62_filesize_format($backup['details']['uncompressed_size']);
	}
	
	$details_string = (count($details_string) >= 1 ? '<small>'.implode(' || ', $details_string) : '');
	*/
	$details_string = '';
	$rows = array(
		array('data' => $image_str, 'width' => '90', 'nowrap' => 'true'),
		array('data' => '<div class="bp_editable" rel="'.$backup['details']['hash'].'" id="note_div_'.$backup['details']['hash'].'">'.(!empty($backup['details']['note']) ? $backup['details']['note'] : lang('click_to_add_note')).'</div>'.$note_field.$details_string),
		array('data' => $backup['details']['hash'], 'width' => '100'),
		array('data' => '<!-- '.$backup['file_date_raw'].'-->'. $backup['file_date'], 'width' => '80', 'align' => 'right')
	);
	
	if(isset($enable_type) && $enable_type)
	{
		$rows[] = array('data' => (strpos($backup['file_name'], 'sql') === false ? lang('file_backup') : lang('database_backup')), 'width' => '80', 'align' => 'right');
	}
	$rows[] = array('data' => '<!-- '.$backup['file_size_raw'].'-->'.$backup['file_size'], 'width' => '75', 'align' => 'right');
	$rows[] = array('data' => '<a href="'.$url_base.'download_backup'.AMP.'id='.m62_encode_backup( $backup['file_name']).AMP.'type='.$backup['backup_type'].'" title="'.lang('download').'"><img src="'.$theme_folder_url.'backup_pro/images/download.png" alt="'.lang('download').'" /></a> '.
					(strpos($backup['file_name'], 'sql') === false ? '' : '<a href="'.$url_base.'restore_db_confirm'.AMP.'id='.m62_encode_backup($backup['file_name']).'" title="'.lang('restore').'"><img src="'.$theme_folder_url.'backup_pro/images/restore.png" alt="'.lang('restore').'" /></a>')
					, 'width' => 70, 'align' => 'right');	
	
	if(isset($enable_delete) && $enable_delete)
	{
		$toggle = array(
				'name'		=> 'toggle[]',
				'id'		=> 'edit_box_'.$backup['file_name'],
				'value'		=> m62_encode_backup($toggle_dir.'/'.$backup['file_name']),
				'class'		=>'toggle_files'
		);
		$rows[]	= array('data' => form_checkbox($toggle), 'width' => '55');
	}
	
	$this->table->add_row($rows);
}

echo $this->table->generate();