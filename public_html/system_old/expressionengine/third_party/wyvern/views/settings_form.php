<?php echo form_open('C=addons_extensions'.AMP.'M=save_extension_settings', 'id="wymeditor_settings"', $hidden)?>

<?php
$this->table->set_template($cp_table_template);
$this->table->set_heading(
    array('data' => lang('parse_order_title'), 'style' => 'width: 80%'),
    array()
);
$this->table->add_row(
    lang('parse_order_info'),
    form_dropdown('parse_order', array('early' => 'Early', 'late' => 'Late', 'disabled' => 'Disabled'), $parse_order)
);

echo $this->table->generate();
?>

<p class="centerSubmit"><?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit'))?></p>

<?php echo form_close(); ?>