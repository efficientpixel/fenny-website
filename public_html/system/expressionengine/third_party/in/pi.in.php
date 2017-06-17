<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * In: http://www.causingeffect.com/software/expressionengine/in
 *
 * Author: Aaron Waldon (Causing Effect)
 * http://www.causingeffect.com
 *
 * License: MIT license.
 */

if ( ! defined('IN_VERSION') )
{
	include( PATH_THIRD . 'in/config.php' );
}

$plugin_info = array(
	'pi_name' => 'In',
	'pi_version' => IN_VERSION,
	'pi_author' => 'Aaron Waldon (Causing Effect)',
	'pi_author_url' => 'http://www.causingeffect.com/',
	'pi_description' => 'Include or Insert template files. A light alternative to snippets and embeds.',
	'pi_usage' => In::usage()
);

class In
{
	//debug mode flag
	private $debug = false;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE = get_instance();

		//if the template debugger is enabled, and a super admin user is logged in, enable debug mode
		$this->debug = ( $this->EE->session->userdata['group_id'] == 1 && $this->EE->config->item('template_debugging') == 'y' );
	}

	/**
	 * Attempts to include the specified template.
	 * The include functionality of this add-on is based on the Pre Embed add-on by @_rsan (http://devot-ee.com/add-ons/pre-embed) and is licensed under the MIT license.
	 *
	 * @return mixed
	 */
	public function clude()
	{
		//make sure template files are enabled
		if ( $this->EE->config->item('save_tmpl_files') !== 'y' && $this->EE->config->item('tmpl_file_basepath') )
		{
			$this->log_debug_message( 'Saving templates as files does *not* appear to be enabled.' );
			return $this->EE->TMPL->no_results();
		}

		//determine the template group and name
		$tag_parts = $this->EE->TMPL->tagparts;
		if ( is_array( $tag_parts ) && isset( $tag_parts[2] ) )
		{
			$template = trim( $tag_parts[2], '/' );
		}
		else
		{
			$this->log_debug_message( 'No include template was specified.' );
			return $this->EE->TMPL->no_results();
		}
		$template = explode('/', $template);
		$group_name = $template[0];
		$template_name = ( isset( $template[1] ) ) ? $template[1] : 'index';

		//determine the path
		$path = rtrim($this->EE->config->item('tmpl_file_basepath'), '/').'/';
		$path .= $this->EE->config->item('site_short_name') . '/' . $group_name . '.group/' . $template_name . '.html';

		$save_as = $this->EE->TMPL->fetch_param('save_as', 'no');

		if ( $save_as != 'no' && isset( $this->EE->session->cache[ 'in' ][$save_as] ) )
		{
			$embed = $this->EE->session->cache[ 'in' ][$save_as];
		}
		else
		{
			//get the template contents
			if ( file_exists($path) )
			{
				$embed = file_get_contents($path);

				//check if there was an error getting the file contents.
				if ( $embed === false )
				{
					$this->log_debug_message( 'Unable to retrieve the file contents.' );
					return $this->EE->TMPL->no_results();
				}
			}
			else
			{
				$this->log_debug_message( 'The file "' . $path . '" could not be found.' );
				return $this->EE->TMPL->no_results();
			}
		}

		if ( $save_as != 'no' )
		{
			$this->EE->session->cache[ 'in' ][$save_as] = $embed;
		}

		//parse embed variables
		if (@preg_match_all('/embed:(\w+)/', $embed, $matches))
		{
			foreach ( $matches[0] as $i => $full_match )
			{
				//determine the value
				$value = isset( $this->EE->TMPL->tagparams[$matches[1][$i]] ) ? $this->EE->TMPL->tagparams[$matches[1][$i]] : '';

				//set the embed vars, so that they can be used in advanced conditionals
				$this->EE->TMPL->embed_vars[ $full_match ] = $value;

				//parse the curly tag variables
				$embed = str_replace( LD . $full_match . RD, $value, $embed );
			}
		}

		//strip comments and parse segment_x vars
		$embed = preg_replace("/\{!--.*?--\}/s", '', $embed);

		//swap config global vars
		$embed = $this->_parse_global_vars( $embed );

		//segment variables
		for ($i = 1; $i < 10; $i++)
		{
			$embed = str_replace(LD.'segment_'.$i.RD, $this->EE->uri->segment($i), $embed);
		}

		//replace current time variable
		if ( strpos( $embed, '{current_time' ) !== false )
		{
			if ( preg_match_all( '/{current_time\s+format=([\"\'])([^\\1]*?)\\1}/', $embed, $matches ) )
			{
				for ($j = 0; $j < count($matches[0]); $j++)
				{
					if ( version_compare( APP_VER, '2.6.0', '<' ) )
					{
						$embed = str_replace($matches[0][$j], $this->EE->localize->decode_date($matches[2][$j], $this->EE->localize->now), $embed);
					}
					else
					{
						$embed = str_replace($matches[0][$j], $this->EE->localize->format_date($matches[2][$j]), $embed);
					}
				}
			}

			$embed = str_replace( '{current_time}', $this->EE->localize->now, $embed);
		}

		//parse globals if applicable
		$parse_globals = $this->EE->TMPL->fetch_param('globals');
		if ( $parse_globals == 'all' ) //parse late globals (expensive)
		{
			$embed = $this->EE->TMPL->parse_globals($embed);
		}
		else if ( $parse_globals == 'member' ) //parse member vars
		{
			foreach( array( 'member_id', 'group_id', 'member_group', 'username', 'screen_name' ) as $val )
			{
				if (isset($this->EE->session->userdata[$val]) AND ($val == 'group_description' OR strval($this->EE->session->userdata[$val]) != ''))
				{
					$embed = str_replace('{logged_in_'.$val.'}', $this->EE->session->userdata[$val], $embed);
				}
			}
		}

		//parse nested in:serts
		if ( @preg_match( '/'.LD.'in:sert:(.+?)'.RD.'/', $embed ) )
		{
			$embed = $this->parse_serts( $embed );
		}

		return $embed;
	}

	/**
	 * If this method is called, all inserts within the tagdata will be parsed.
	 *
	 * @return string
	 */
	public function serts()
	{
		return $this->parse_serts( $this->EE->TMPL->tagdata );
	}

	/**
	 * Parses all in:sert tags.
	 *
	 * @param $tagdata
	 * @return string
	 */
	private function parse_serts( $tagdata )
	{
		//load the class if needed
		if ( ! class_exists( 'In_sert' ) )
		{
			include PATH_THIRD . 'in/libraries/In_sert.php';
		}

		$inserts = new In_sert();

		$tagdata = $inserts->parse( $tagdata );
		unset( $inserts );
		return $tagdata;
	}

	/**
	 * Parse config global variables.
	 * @param string $tagdata
	 * @return string
	 */
	protected function _parse_global_vars( $tagdata = '' )
	{
		if ( strpos( $tagdata, '{' ) !== FALSE ) //if there are no curly brackets, no need to parse...
		{
			$tagdata = $this->EE->TMPL->parse_variables_row( $tagdata, $this->EE->config->_global_vars );
		}

		return $tagdata;
	}

	/**
	 * Simple method to log a debug message to the EE console if debug mode is enabled.
	 *
	 * @param string $message The debug message.
	 * @return void
	 */
	protected function log_debug_message( $message = '' )
	{
		if ( $this->debug )
		{
			$this->EE->TMPL->log_item( '&nbsp;&nbsp;***&nbsp;&nbsp;In debug: ' . $message );
		}
	}

	/**
	 * Returns usage instructions for the plugin to the EE control panel.
	 *
	 * @return string
	 */
	public static function usage()
	{
		ob_start();
?>
----- Include -----
Include a template like you would an embed, like this:

		{exp:in:clude:includes/.header default_description="{home_meta_description}" default_keywords="{home_meta_keywords}"}

The parameters are passed to the included template as embed parameters, and are available to it like this: {embed:default_description} {embed:home_meta_keywords}


----- Insert -----
Or, if you would rather insert the template as an early parsed global variable (which doesn't accept embed parameters), you can simply do this:

		{in:sert:blog/.blog_entries}
<?php
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}
}
/* End of file pi.pre_emned.php */
/* Location: ./system/expressionengine/third_party/pre_embed/pi.pre_embed.php */