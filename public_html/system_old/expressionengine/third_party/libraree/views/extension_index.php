<div class="rightNav">
	<div style="float: left; width: 100%;">
		<span class="button"><? echo '<a href="'.BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=libraree" class="submit" >LibrarEE file overview</a>'; ?></span>
	</div>
</div>
<div style="clear:left;">&nbsp;</div>
<?=form_open('C=addons_extensions'.AMP.'M=save_extension_settings'.AMP.'file=libraree');?>

<?php 
$this->table->set_template($cp_pad_table_template);
$this->table->set_heading(
    array('data' => lang('preference'), 'style' => 'width:40%;'),
    lang('setting')
);

foreach ($settings1 as $key => $val)
{
	$this->table->add_row(lang($key, $key), $val);
}

echo $this->table->generate();
$this->table->clear();



$this->table->set_template($cp_pad_table_template);
$this->table->set_heading(
    array('data' => "Parsing preferences", 'style' => 'width:40%;'),
    lang('setting')
);

foreach ($settings3 as $key => $val)
{
	$this->table->add_row(lang($key, $key), $val);
}

echo $this->table->generate();
$this->table->clear();

/*
if($settings["mailtype"] != "html"){
	echo "<br/><fieldset style='text-align:center;'><b>The default mailtype is 'Plain text', please follow the 'enable HTML emails' instruction on <a href='http://www.libraree.net/docs' >Libraree.net</a> in order to activate HTML in emails.</b></fieldset><br/>";
}
*/

?>

<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>
<?php $this->table->clear()?>
<?=form_close()?>
