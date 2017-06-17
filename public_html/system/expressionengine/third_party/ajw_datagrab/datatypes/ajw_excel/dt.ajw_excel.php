<?php

/**
 * DataGrab excel import class
 *
 * Allows excel imports
 * 
 * @package   DataGrab
 * @author    Andrew Weaver <aweaver@brandnewbox.co.uk>
 * @copyright Copyright (c) Andrew Weaver
 */
class Ajw_excel extends Datagrab_type {

	var $datatype_info = array(
		'name'		=> 'Excel',
		'version'	=> '0.1'
	);
	
	var $settings = array(
		"filename" => "",
		"skip" => 0
		);
	
	var $items;
	
	function __construct() {
		require "reader.php";
		$this->data = new Spreadsheet_Excel_Reader();
		
	}
	
	function settings_form( $values = array() ) {
		
		$form = array(
			array(
				form_label('Filename', 'filename') .
				'<div class="subtext"></div>', 
				form_input(
					array(
						'name' => 'filename',
						'id' => 'filename',
						'value' => $this->get_value( $values, "filename" ),
						'size' => '50'
						)
					)
				),
			array(
				form_label('Use first row as titles', 'skip') .
				'<div class="subtext">Select this if the first row of the file contains titles and should not be imported</div>',
				form_checkbox('skip', '1', ( $this->get_value( $values, "skip" ) == 1 ? TRUE : FALSE ), ' id="skip"')
				)
			);
	
		return $form;
	}
	
	function fetch() {

		$this->data->read( $this->settings["filename"] );
		
		$this->items = $this->data->sheets[0]['cells'];
		
	}

	function next() {

		$item = current( $this->items );
		next( $this->items );

		return $item;
		
	}
	
	function fetch_columns() {

		$this->fetch();
		$columns = $this->next();

		// Loop through fields, adding Column # and truncating any long labels
		$titles = array();
		$count = 0;
		foreach( $columns as $idx => $title ) {
			if ( strlen( $title ) > 32 ) {
				$title = substr( $title, 0, 32 ) . "...";
			}
			$titles[ $idx ] = "Column " . $idx . " - eg, " . $title;
		}

		return $titles;
	}
	
}

?>