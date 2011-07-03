<?=form_open('C=addons_extensions'.AMP.'M=save_extension_settings'.AMP.'file=registration_codes');?>

<?php 

// ------------------------
// General Preferences
// ------------------------

$this->table->set_template($cp_pad_table_template);
$this->table->set_heading(
    array('data' => lang('rogee_rc_general_preferences'), 'colspan' => '2')
);

foreach ($general_settings_fields as $key => $field)
{
	$this->table->add_row(array('data' => lang("rogee_rc_".$key, $key), 'style' => 'width:40%;'), $field);
}

echo $this->table->generate();

$this->table->clear() ;

// ------------------------
// Registration Codes
// ------------------------

$this->table->set_template($cp_pad_table_template);
$this->table->set_heading(
    array('data' => "", 'style' => 'width:5%;'),
    array('data' => lang('rogee_rc_code_string'), 'style' => 'width:30%;'),
    array('data' => lang('rogee_rc_destination_group'), 'style' => 'width:30%;'),
    array('data' => lang('rogee_rc_site_id'), 'style' => 'width:35%;')
);

if (count($codes_fields) > 1)
{
	// show instructions if there are existing rows
	$this->table->add_row(
		array('data' => lang('rogee_rc_instructions_code_id'), 'style' => 'width:5%;'),
		array('data' => lang('rogee_rc_instructions_code_string'), 'style' => 'width:30%;'),
		array('data' => lang('rogee_rc_instructions_destination_group'), 'style' => 'width:30%;'),
		array('data' => ($show_multi_site_field == 'yes' ? lang('rogee_rc_instructions_site_id_enabled') : lang('rogee_rc_instructions_site_id_disabled')), 'style' => 'width:35%;')
	);
}

foreach ($codes_fields as $key => $fields)
{
	$this->table->add_row(
		array('data' => ($key == "new" ? "<em>".lang('rogee_rc_new')."</em>" : ""), 'style' => 'width:5%;'),
		array('data' => $fields['code_string'], 'style' => 'width:30%;'),
		array('data' => $fields['destination_group'], 'style' => 'width:30%;'),
		array('data' => $fields['site_id'], 'style' => 'width:35%;')
	);
}

echo $this->table->generate();

$this->table->clear() ;

?>

<p><?=form_submit('submit', lang('rogee_rc_save'), 'class="submit"')?> <?=form_submit('submit_finished', lang('rogee_rc_save_finished'), 'class="submit"')?></p>

<?=form_close()?>

<?php

/* End of file index.php */
/* Location: ./system/expressionengine/third_party/registration_codes/views/index.php */