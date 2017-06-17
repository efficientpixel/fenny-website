<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* Rye Date
*
* @package     ExpressionEngine
* @category    Plugin
* @author      Rye Digital Marketing
* @copyright   Copyright (c) 2015, Andrew Fairlie
* @link        http://rye.agency/
*/

$plugin_info = array(
'pi_name'         => 'Rye Date',
'pi_version'      => '1.0',
'pi_author'       => 'Rye Digital Marketing',
'pi_author_url'   => 'http://rye.agency/',
'pi_description'  => 'Lets you use ExpressionEngine date formatting on any date string'
);

class Rye_date {
  public $return_data = "";

  public function __construct()
  {
    $this->EE =& get_instance();
    $date = $this->EE->TMPL->fetch_param('date');
    $format = $this->EE->TMPL->fetch_param('format');
    $this->return_data = $this->EE->localize->format_date($format, $date);
  }
}
