<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine Snaptcha Plugin
 *
 * @package		Snaptcha
 * @category	Plugin
 * @description	Simple Non-obtrusive Automated Public Turing test to tell Computers and Humans Apart
 * @author		Ben Croker
 * @link		http://www.putyourlightson.net/snaptcha
 */


// get config
require_once PATH_THIRD.'snaptcha/config'.EXT;


$plugin_info = array(
				'pi_name'			=> SNAPTCHA_NAME,
				'pi_version'		=> SNAPTCHA_VERSION,
				'pi_author'			=> SNAPTCHA_AUTHOR,
				'pi_author_url'		=> SNAPTCHA_URL,
				'pi_description'	=> SNAPTCHA_DESCRIPTION,
				'pi_usage'			=> Snaptcha::usage()
			);


class Snaptcha
{

	/**
	 * Constructor
	 */
  public function __construct($settings = '')
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	 * Create Form Field
	 */
  public function field()
	{
		// get extension
		require_once PATH_THIRD.'snaptcha/ext.snaptcha'.EXT;

		// create new object
		$Snaptcha = new Snaptcha_ext();

		// validate security level
		$security_level = (is_numeric($this->EE->TMPL->fetch_param('security_level')) AND $this->EE->TMPL->fetch_param('security_level') >=1 AND $this->EE->TMPL->fetch_param('security_level') <= 3) ? $this->EE->TMPL->fetch_param('security_level') : '';

		// return field
		return $Snaptcha->snaptcha_field($security_level);
	}

	// --------------------------------------------------------------------

	/**
	 * Plugin Usage
	 */
  public static function usage()
	{
		ob_start();
		?>
Use as follows:

{exp:snaptcha:field}

or

{exp:snaptcha:field security_level="1"}

security_level: 1=low, 2=medium, 3=high
		<?php
		$buffer = ob_get_contents();

		ob_end_clean();

		return $buffer;
	}

}
// END CLASS

/* End of file pi.snaptcha.php */
/* Location: ./system/expressionengine/third_party/snaptcha/pi.snaptcha.php */
