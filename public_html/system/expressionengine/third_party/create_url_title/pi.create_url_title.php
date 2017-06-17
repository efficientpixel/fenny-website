<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$plugin_info = array(
	'pi_name' => 'Create URL Title',
	'pi_version' => '1.0.1',
	'pi_author' => 'Rob Sanchez',
	'pi_author_url' => 'http://barrettnewton.com/',
	'pi_description' => 'Creates a url title string with the supplied tag data.',
	'pi_usage' => Create_url_title::usage()
);

class Create_url_title
{
	public $return_data = '';
	
	public function Create_url_title($tagdata = '')
	{
		$this->EE = get_instance();
		
		$this->EE->load->helper('url');
		
		$separator = NULL;
		
		if (in_array($this->EE->TMPL->fetch_param('separator'), array('dash', '-')))
		{
			$separator = 'dash';
		}
		elseif ($this->EE->TMPL->fetch_param('separator') == '_')
		{
			$separator = '_';
		}
		else
		{
			$separator = $this->EE->config->item('word_separator');
		}
		
		if (func_num_args() === 0)
		{
			$tagdata = $this->EE->TMPL->tagdata;
		}
		
		$this->return_data = url_title(
			trim($tagdata),
			$separator,
			(preg_match('/0|no|off|n/i', $this->EE->TMPL->fetch_param('lowercase'))) ? FALSE : TRUE
		);
	}
	
	public static function usage()
	{
		ob_start();
?>
This plugin takes the tag data (the text in between the tag pair)
and outputs a url title (strips spaces and non-alphanumeric characters) from it.

Example:
{exp:create_url_title}
The brown cow jumps over the moon.
{/exp:create_url_title}

Output:
the_brown_cow_jumps_over_the_moon

Parameters:
separator - the word separator, accepts "-", "_", or "dash", if not specified, uses your default word separator
lowercase - set to no to prevent conversion to all lowercase
<?php
		$buffer = ob_get_contents();
		
		ob_end_clean();
		
		return $buffer;
	}
}
