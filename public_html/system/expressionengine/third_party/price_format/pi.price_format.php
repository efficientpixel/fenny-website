<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$plugin_info = array(
	'pi_name' => 'Price Format',
	'pi_version' => '1.0',
	'pi_author' => 'Steve Abraham',
	'pi_author_url' => 'http://steveabraham.co.uk/',
	'pi_description' => 'Formats numbers & prices, including tax calculations.',
	'pi_usage' => Price_format::usage()
	);

class Price_format {
	
	var $return_data = "";

	function Price_format() {
		$this->EE =& get_instance();
		$price = $this->EE->TMPL->fetch_param('price');
		$decimals = $this->EE->TMPL->fetch_param('decimals');
		$point = $this->EE->TMPL->fetch_param('point');
		$thousands = $this->EE->TMPL->fetch_param('thousands');
		$tax = $this->EE->TMPL->fetch_param('tax');
		$show = $this->EE->TMPL->fetch_param('show');

		$price = (is_numeric($price)) ? $price : 0;
		$decimals = (is_numeric($decimals)) ? $decimals : 2;
		$point = ($point=="") ? "." : $point;
		$thousands = ($thousands=="") ? "," : $thousands;
		$thousands = ($thousands=="none") ? "" : $thousands;
		$taxcalc = (is_numeric($tax)) ? ($tax/100) : 0;

		if ($show == "" || $show == "total") {
			$this->return_data = number_format(($taxcalc+1)*$price, $decimals, $point, $thousands);
		} elseif ($show == "tax") {
			$this->return_data = number_format($taxcalc*$price, $decimals, $point, $thousands);
		}
	}
	
	static function usage() {
		ob_start();
		?>
        Formats supplied price value with specified options and tax calculations.
        
        Accepts six parameters:
        
        price="" (required, numeric value)
        decimals="" (optional, number of decimal points to show, defaults to 2)
        point="" (optional, character to use as point separator, defaults to .)
        thousands="" (optional, character to use as thousands separator or "none", defaults to ,)
        tax="" (optional, tax percentage - numeric value only, defaults to 0)
        show="" (optional, value can be "tax" to show just the tax amount or "total" to show the price including tax, defaults to "total")
        
        Example usage:
        
        {exp:price_format price="1000"}
        (Output: 1,000.00)
        
        {exp:price_format price="1000" decimals="0"}
        (Output: 1,000)
        
        {exp:price_format price="1000" tax="20"}
        (Output: 1,200.00)
        
        {exp:price_format price="1000" tax="20" show="tax"}
        (Output: 200.00)
        
		Version 1.0
		******************
		- Initial release
        <?php
		$buffer = ob_get_contents();
		
		ob_end_clean();
		
		return $buffer;
	}
}

/* End of file pi.priceformat.php */ 