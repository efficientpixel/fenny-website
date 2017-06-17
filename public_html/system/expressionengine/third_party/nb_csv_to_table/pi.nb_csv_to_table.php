<?php

// NB CSV to Table 2.1
// For ExpressionEngine 2.0
// Copyright Nicolas Bottari
// -----------------------------------------------------
// 
// The NB CSV to Table plugin for ExpressionEngine by Nicolas Bottari is licensed under a Creative Commons Attribution
// Noncommercial-Share Alike 3.0 Unported License
// http://creativecommons.org/licenses/by-nc-sa/3.0/
//
// Description
// -----------
// Creates a table from a CSV file
//
// More info: http://nicolasbottari.com/expressionengine_cms/nb_csv_to_table/
//

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$plugin_info = array(
  'pi_name' => 'NB CSV to Table',
  'pi_version' =>'2.1',
  'pi_author' =>'Nicolas Bottari',
  'pi_author_url' => 'http://nicolasbottari.com/expressionengine_cms/nb_csv_to_table',
  'pi_description' => 'Displays a table from a CSV file',
  'pi_usage' => Nb_csv_to_table::usage()
  );


class Nb_csv_to_table
{

var $return_data = "";

  function Nb_csv_to_table()
  {
	$this->EE =& get_instance();
	
	// ----------
	// Parameters
	// ----------
	
	$file = ($this->EE->TMPL->fetch_param('file') !== FALSE) ? $this->EE->TMPL->fetch_param('file') : "";
	$delimiter = ($this->EE->TMPL->fetch_param('delimiter') !== FALSE) ? $this->EE->TMPL->fetch_param('delimiter') : ",";
	$enclosure = ($this->EE->TMPL->fetch_param('enclosure') !== FALSE) ? $this->EE->TMPL->fetch_param('enclosure') : '"';
	//$escape = ($this->EE->TMPL->fetch_param('escape') !== FALSE) ? $this->EE->TMPL->fetch_param('escape') : "";
	$table_class = ($this->EE->TMPL->fetch_param('table_class') !== FALSE) ? $this->EE->TMPL->fetch_param('table_class') : "";
	$table_border = ($this->EE->TMPL->fetch_param('table_border') !== FALSE) ? $this->EE->TMPL->fetch_param('table_border') : "1";
	$table_width = ($this->EE->TMPL->fetch_param('table_width') !== FALSE) ? $this->EE->TMPL->fetch_param('table_width') : "100%";
	$header_rows = explode("|", ($this->EE->TMPL->fetch_param('header_rows') !== FALSE) ? $this->EE->TMPL->fetch_param('header_rows') : "0");
	$header_class = ($this->EE->TMPL->fetch_param('header_class') !== FALSE) ? $this->EE->TMPL->fetch_param('header_class') : "";
	$row_class = ($this->EE->TMPL->fetch_param('row_class') !== FALSE) ? explode('|', $this->EE->TMPL->fetch_param('row_class')) : array();
	$show_row_count = ($this->EE->TMPL->fetch_param('show_row_count') !== FALSE) ? $this->EE->TMPL->fetch_param('show_row_count') : "no";

	// -----------
	// Build table
	// -----------
	
	if(isset($file) && empty($file))
	{
		return $this->return_data;
	}
	
	if(file_exists($file))
	{
		$handle = fopen($file, "r") or die("Cannot open file");
	} else {
		return $this->return_data;
	}
	
	$file = html_entity_decode($file);
	$rownumber = 1;
	
	$this->return_data = '<table border="' . $table_border . '" width="' . $table_width . '" class="' . $table_class . '">';
	
	while (($data = fgetcsv($handle, 0, $delimiter, $enclosure)) !== FALSE) {
	    $cellnumber = count($data);
	   	
	    $this->return_data .= '<tr>';
	    
    	if ($show_row_count == "yes" || $show_row_count == "on" || $show_row_count == "enable")
		{
			$this->return_data .= '<td>' . $rownumber . '</td>';
		}
    
		for ($c = 0; $c < $cellnumber; $c++)
		{
			
			if (in_array($rownumber, $header_rows))
			{
				$this->return_data .= '<th class="' . $header_class .'">' . $data[$c] . '</th>';
			} else {
				if(empty($row_class))
				{
					$rclass = "";
				} else {
					$rclass = $row_class[($rownumber + count($row_class)) % count($row_class)];
				}
				$this->return_data .=  '<td class="' . $rclass . '">' . $data[$c] . '</td>';
			}
		
		} // close for loop
		$rownumber++;
		$this->return_data .= '</tr>';

	
	} // close while loop
	
	$this->return_data .= '</table>';
	fclose($handle);
		
		
		
	
	return $this->return_data;
	
	}

    
    

  
 
  
// ----------------------------------------
  //  Plugin Usage
  // ----------------------------------------

  // This function describes how the plugin is used.
  //  Make sure and use output buffering

  function usage()
  {
  ob_start(); 
  ?>
Creates a table from a CSV file
-----------
 Usage
-----------

{exp:nb_csv_to_table file="./files/example.csv" table_border="1" table_class="standard" header_rows="1|30" header_class="resultColumnOne" row_class="resultColumnTwo" show_row_count="no" delimiter="|"}


---------------------
 Parameters
---------------------

file="./files/example.csv"
CSV file to parse

table_border="1"
Table border, in pixels

table_class="standard"
The class given to the <table> tag.

header_rows="1|30"
Rows whose cells will be given a header <th> tag.

header_class="resultColumnOne"
The class given to the <th> header cells.

row_class="resultColumnTwo"
The class given to regular, <td> cells.

show_row_count="no"
If set to "yes" or "on" or "enable", the first column of the table will display a simple cell showing the row count.

delimiter="|"
Field delimiter. Default is comma (,).

enclosure='"'
Field enclosure character. Default is the double quotation mark (").


  <?php
  $buffer = ob_get_contents();
	
  ob_end_clean(); 

  return $buffer;
  }
  // END
  
}
?>