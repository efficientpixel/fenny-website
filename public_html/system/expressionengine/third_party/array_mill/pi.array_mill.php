<?php

/*
=====================================================
 This ExpressionEngine plugin was created by Laisvunas
 - http://devot-ee.com/developers/ee/laisvunas/
=====================================================
 Copyright (c) Laisvunas
=====================================================
 This is commercial Software.
 One purchased license permits the use this Software on the SINGLE website.
 Unless you have been granted prior, written consent from Laisvunas, you may not:
 * Reproduce, distribute, or transfer the Software, or portions thereof, to any third party
 * Sell, rent, lease, assign, or sublet the Software or portions thereof
 * Grant rights to any other person
=====================================================
 File: pi.sring_mill.php
-----------------------------------------------------
 Purpose: Enables using PHP array functions in EE templates. 
=====================================================
*/

$plugin_info = array(
  'pi_name' => 'Array Mill',
  'pi_version' => '1.0.1',
  'pi_author' => 'Laisvunas',
  'pi_author_url' => 'http://devot-ee.com/developers/ee/laisvunas/',
  'pi_description' => 'Enables using PHP array functions in EE templates.',
  'pi_usage' => Array_mill::usage()
  );
  
class Array_mill
{
  var $return_data = '';
  
  function Array_mill()
  {
    $this->EE =& get_instance();
    
    // fetch the tagdata
		  $tagdata = $this->EE->TMPL->tagdata;

    // fetch param values
    $function = $this->EE->TMPL->fetch_param('function');
  		$param1 = $this->EE->TMPL->fetch_param('param1');
  		$param2 = $this->EE->TMPL->fetch_param('param2');
  		$param3 = $this->EE->TMPL->fetch_param('param3');   		
  		$param4 = $this->EE->TMPL->fetch_param('param4');   		
  		$param5 = $this->EE->TMPL->fetch_param('param5');
    $empty_values = $this->EE->TMPL->fetch_param('empty_values') ? $this->EE->TMPL->fetch_param('empty_values') : 'no'; // or constant "filter" which means that empty values will be eliminated
    $false_values = $this->EE->TMPL->fetch_param('false_values') ? $this->EE->TMPL->fetch_param('false_values') : 'no'; // or constant "filter" which means that values FALSE will be eliminated
    $truth_values = $this->EE->TMPL->fetch_param('true_values') ? $this->EE->TMPL->fetch_param('true_values') : 'yes';
    $array_params = explode('|', $this->EE->TMPL->fetch_param('array_params'));
    
    // Define variables
    $conds = array();
    $conds['array_mill_result'] = '';
    $conds['array_mill_param1'] = '';
    $temp_array = array();
    
    if (in_array('param1', $array_params))
    {
      $param1 = explode('|', $param1);
    }
    if (in_array('param2', $array_params))
    {
      $param2 = explode('|', $param2);
    }
    if (in_array('param3', $array_params))
    {
      $param3 = explode('|', $param3);
    }
    if (in_array('param4', $array_params))
    {
      $param4 = explode('|', $param4);
    }
    if (in_array('param5', $array_params))
    {
      $param5 = explode('|', $param5);
    }
    
    if ($param5 AND $param4 AND $param3 AND $param2 AND $param1 AND $function)
    {
      $array_mill_result = @$function($param1, $param2, $param3, $param4, $param5);
    }
    elseif ($param4 AND $param3 AND $param2 AND $param1 AND $function)
    {
      $array_mill_result = @$function($param1, $param2, $param3, $param4);
    }
    elseif ($param3 AND $param2 AND $param1 AND $function)
    {
      $array_mill_result = @$function($param1, $param2, $param3);
    }
    elseif ($param2 AND $param1 AND $function)
    {
      $array_mill_result = @$function($param1, $param2);
    }
    elseif ($param1 AND $function)
    {
      $array_mill_result = @$function($param1);
      //$array_mill_result = asort($param1);
      //echo '$array_mill_result: ['.$array_mill_result.']'.PHP_EOL;
    }
    
    if (!is_array($array_mill_result))
    {
      $temp_array[0] = $array_mill_result;
      $array_mill_result = $temp_array;
    }
    
    if (is_array($array_mill_result))
    {
      if ($empty_values == 'filter')
      {
        $array_mill_result = remove_item_by_value($array_mill_result, '');
      }
      else
      {
        $array_mill_result = change_item_by_value($array_mill_result, '', $empty_values);
      }
      if ($false_values == 'filter')
      {
        $array_mill_result = remove_item_by_value($array_mill_result, FALSE);
      }
      else
      {
        $array_mill_result = change_item_by_value($array_mill_result, FALSE, $false_values);
      }
      $array_mill_result = change_item_by_value($array_mill_result, TRUE, $truth_values);
      $array_mill_result = array_values($array_mill_result);
      $array_mill_result = implode('|', $array_mill_result);
      $conds['array_mill_result'] = $array_mill_result;
    }
    
    if (!is_array($param1))
    {
      $temp_array[0] = $param1;
      $param1 = $temp_array;
    }
    
    if (is_array($param1))
    {
      if ($empty_values == 'filter')
      {
        $param1 = remove_item_by_value($param1, '');
      }
      else
      {
        $param1 = change_item_by_value($param1, '', $empty_values);
      }
      if ($false_values == 'filter')
      {
        $param1 = remove_item_by_value($param1, FALSE);
      }
      else
      {
        $param1 = change_item_by_value($param1, FALSE, $false_values);
      }
      $param1 = change_item_by_value($param1, TRUE, $truth_values);
      $param1 = array_values($param1);
      $param1 = implode('|', $param1);
      $conds['array_mill_param1'] = $param1;
    }
    
    // Prepare conditionals
    $tagdata = $this->EE->functions->prep_conditionals($tagdata, $conds);
    
    // Output variables
    $tagdata = $this->EE->TMPL->swap_var_single('array_mill_result', $conds['array_mill_result'], $tagdata);
    $tagdata = $this->EE->TMPL->swap_var_single('array_mill_param1', $conds['array_mill_param1'], $tagdata);
    
    // Output tagdata 
    return $this->return_data = $tagdata;
  }
  // END FUNCTION
  
  //  Plugin Usage
 	// ----------------------------------------
 
 	// This function describes how the plugin is used.
 	//  Make sure and use output buffering
  
  function usage()
  {
    ob_start(); 
?>

PARAMETERS

1) For any results String Mill plugin requires the "function" parameter to be a PHP array function.

2) Array Mill accepts up to five optional parameters (param1, param2, param3, param4 and param5) 
needed by the chosen PHP string function. These parameters must be in sequence, 
so that if there's a param2 there must also be a param1. If the value of the parameter should be an array,
its values should be separated using pipe character e.g. param1="3936|3937|3942|3122|3944|3949". So, 
associative and multilevel arrays cannot be the values of these parameters param1, param2, param3, param4 and param5.

3) array_params - required. Allows you to specify if the values of the parameters param1, param2, param3, param4 and param5 
are arrays. E.g. in case the values of the parameters param1 param2 are arrays you will use array_params="param1|param2"

4) empty_values - optional. Allows you to specify how should be handled those values of arrays which are empty strings.
You can specify that empty strings should be changed into certain string e.g. empty_values="empty_str". Default is "no".
If you would like those values of arrays which are empty strings to be removed, set "filter" as the value of this parameter.

5) false_values - optional. Allows you to specify how should be handled those values of arrays which are Boolean FALSE.
You can specify that Boolean FALSE should be changed into certain string e.g. false_values="false_val". Default is "no".
If you would like those values of arrays which are Boolean FALSE to be removed, set "filter" as the value of this parameter.

6) true_values - optional. Allows you to specify how should be handled those values of arrays which are Boolean TRUE.
You can specify that Boolean TRUE should be changed into certain string e.g. true_values="true_val". Default is "no".

VARIABLES

1) array_mill_result - will output return value of the function. In case return value is array, array_values function will be applied and its members will be separated
using pipe character, e.g the array [1] => 'apples', [4] => 'roses', [5] => 'watermelons' will be outputted as apples|roses|watermelons

2) array_mill_param1 - will output first parameter of the function (the variables which act as first parameters of array functions often change their values as the
result of the execution of the function). In case the value of the first parameter is array, array_values function will be applied and its members will be separated
using pipe character.

USAGE

{exp:array_mill function="array_intersect" param1="3936|3937|3942|3122|3944|3949" param2="3115|3122|3944|3949|4026|3896" array_params="param1|param2" parse="inward"}
{array_mill_result}
{/exp:array_mill}

Will output 3122|3944|3949 .

<?php
  		$buffer = ob_get_contents();
  	
  		ob_end_clean(); 
  
  		return $buffer;  
  }
  // END FUNCTION
} 
// END CLASS

function remove_item_by_value($array, $val = '', $preserve_keys = TRUE) {
 	if (empty($array) OR !is_array($array) OR !in_array($val, $array))
  {
    return $array;
  }
  
 	foreach($array as $key => $value) 
  {
  		if ($value === $val)
    {
      unset($array[$key]);
    } 
 	}
 
 	return ($preserve_keys === TRUE) ? $array : array_values($array);
}

function change_item_by_value($array, $old_val = '', $new_val = '') {
 	if (empty($array) OR  !is_array($array) OR !in_array($old_val, $array))
  {
    return $array;
  }
 
 	foreach($array as $key => $value) 
  {
  		if ($value === $old_val)
    {
      $array[$key] = $new_val;
    } 
 	}
 
 	return  $array;
}
?>