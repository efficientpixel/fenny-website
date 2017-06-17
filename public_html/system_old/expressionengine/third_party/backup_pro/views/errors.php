<?php
if(count($errors) >= 1)
{
	
	foreach($errors AS $key => $error)
	{
		echo '<div class="backup_pro_system_error" id="backup_pro_system_error_'.$key.'">';
		$replace = array('#db_dir#', '#files_dir#', '#config_url#');
		$paths[] = $url_base.'settings'.AMP.'section=general';
		$str = str_replace($replace, $paths, lang($error));
		echo $str;
		if(count($errors) > 1)
		{
			echo '<br />';
		}
		echo '</div>';
	}
}