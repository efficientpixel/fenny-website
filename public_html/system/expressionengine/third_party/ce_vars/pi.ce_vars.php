<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
//error_reporting(E_ALL);
//ini_set('display_errors', '1');
/*
====================================================================================================
 Author: Aaron Waldon (Causing Effect)
 http://www.causingeffect.com
====================================================================================================
 This file must be placed in the /system/expressionengine/third_party/ce_vars folder in your ExpressionEngine installation.
 package 		CE Variables (EE2 Version)
 copyright 		Copyright (c) 2014 Causing Effect <causingeffect.com>, Aaron Waldon <aaron@causingeffect.com> - MIT License <http://opensource.org/licenses/mit-license.php>
 Last Update	14 February 2014
----------------------------------------------------------------------------------------------------
 Purpose: Allow smarter, more efficient development through variables.
====================================================================================================
*/

include( PATH_THIRD . 'ce_vars/config.php' );

$plugin_info = array(
	'pi_name'		=> 'CE Variables',
	'pi_version'	=> CE_VARIABLES_VERSION,
	'pi_author'		=> 'Aaron Waldon (Causing Effect)',
	'pi_author_url'	=> 'http://www.causingeffect.com/',
	'pi_description'=> 'Invariably awesome variables.',
	'pi_usage'		=> Ce_vars::usage()
);


class Ce_vars
{
	private $forbidden_globals = array( 'save_globals', 'cache', 'refresh', 'order', 'site_id', 'last_segment', 'site_short_name', 'site_label', 'site_id', 'freelancer_version', 'parse', 'name', 'file' );

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE = get_instance();
	}

	//TODO add ability set 'late:' and 'now:' prefixes in the config
	//TODO add the ability to specify the number of loops to parse, or leave at the default setting of 'auto'
	//TODO add the ability to parse user-defined global variables from http://loweblog.com/downloads/ee-parse-order.pdf

	/**
	 * Parse on-the-fly variables and global variables.
	 * @param string $tagdata
	 * @return string
	 */
	public function parse( $tagdata = '' )
	{
		//get the tagdata
		if ( empty( $tagdata ) ) //probably not called from another method (called from template)
		{
			$tagdata = $this->EE->TMPL->tagdata;
			if ( empty( $tagdata ) )
			{
				return $this->get_default();
			}
		}

		//get the parse order
		$order = explode( '|', $this->EE->TMPL->fetch_param('order', '') );

		$no_parse_placeholders = array();
		$no_parse_values = array();

		$loops = 0;
		do
		{
			//remove any EE comments and replace any late vars in the tag data
			$tagdata = preg_replace(
				array(
					'@\{!--.*?--\}@s',
					'@\{late:(.*)\}@Us'
				),
				array(
					'',
					'{$1}'
				),
				$tagdata
			);

			foreach ( $order as $ord )
			{
				//replace any no_parse tags with placeholders
				if ( preg_match_all( '@\{no_parse\}(.*)\{/no_parse\}@Us', $tagdata, $matches, PREG_SET_ORDER ) )
				{
					foreach( $matches as $match )
					{
						//create a placeholder
						$placeholder = '-ce_var_placeholder' . md5($match[0] ) . mt_rand( 0, 1000000 ) . '-';

						//replace the no_parse content with the tag
						$tagdata = preg_replace( '@' . preg_quote( $match[0], '@' ) . '@Us' , $placeholder, $tagdata, 1, $count );

						if ( $count ) //at least one replacement was made
						{
							//add to the no_parse array
							$no_parse_placeholders[] = $placeholder;
							$no_parse_values[] = $match[1];
						}
					}
				}

				switch ( $ord )
				{
					case 'vars':
						$tagdata = $this->_parse_vars( $tagdata );
						break;
					case 'params':
						$tagdata = $this->_parse_param_vars( $tagdata );
						break;
					case 'globals':
						$tagdata = $this->_parse_global_vars( $tagdata );
						break;
					case 'segs':
						$tagdata = $this->_parse_seg_vars( $tagdata );
						break;
					case 'now':
						$tagdata = $this->_parse_now_vars( $tagdata );
						break;
					case 'conditionals':
						$tagdata = $this->_parse_conditionals( $tagdata );
						break;
					case 'simple':
						$tagdata = $this->_parse_simple_conditionals( $tagdata );
						break;
					case 'advanced':
						$tagdata = $this->_parse_advanced_conditionals( $tagdata );
						break;
					case 'tags':
					case 'exp':
						$tagdata = $this->_parse_exp_tags( $tagdata );
						break;
					case 'lists':
						preg_match_all( '@\{(\S+)(\:|\|)(\d*,?\d*)\}@Us', $tagdata, $matches, PREG_SET_ORDER );
						/*
						$match[0] = full - {list:1,3}
						$match[1] = list_name - list
						$match[2] = delimiter - :
						$match[3] = [start,end] - 1,3
						*/

						foreach ( $matches as $match )
						{
							if ( count( $match ) == 4 ) //we have all of the pieces
							{
								//we don't want to match 'late:' variables
								if ( $match[1] == 'late' )
								{
									continue;
								}

								//get the list data
								$items = $this->get_list_by_dot_syntax( $match[1], false );

								if ( $items !== false ) //we have a list
								{
									$start = $match[3];
									$end = null;

									//get the start and end params
									if ( strpos( $start, ',' ) !== FALSE )
									{
										$start = explode( ',', $match[3], 2 );
										if ( count( $start ) === 2 ) //we have a start and end index
										{
											list( $start, $end ) = $start;
										}
										else if ( count( $start ) === 1 ) //we only have a start index
										{
											$start = $start[0];
										}
									}

									//replace the tagdata variable with the content
									if ( empty( $start ) )
									{
										/*
										//no start, replace the var with the list var
										$tagdata = str_replace( $match[0], '{' . $match[1] . '}' , $tagdata );
										continue;
										*/
										$start = 0;
									}

									if ( $match[2] == ':' ) //start, end parameters
									{

										if ( ! is_numeric( $start ) || ! isset( $items[ $start - 1 ] ) ) //start is bogus, replace the var with ''
										{
											$tagdata = str_replace( $match[0], '' , $tagdata );
										}
										else if ( empty( $end ) || ! is_numeric( $end ) ) //just the start, end is not usable
										{
											$tagdata = str_replace( $match[0], $this->add_list_pre_and_post( $match[1], $items[ $start - 1 ] ), $tagdata );
										}
										else //both start and end
										{
											if ( ! isset( $items[ $end - $start ] ) )
											{
												$end = count( $items );
											}

											$final = '';

											$items = array_slice( $items, $start - 1, $end - $start + 1 );

											foreach ( $items as $item )
											{
												if ( ! is_array( $item ) )
												{
													$final .= $item;
												}
											}

											$tagdata = str_replace( $match[0], $this->add_list_pre_and_post( $match[1], $final ), $tagdata );
										}
									}
									else if ( $match[2] == '|' ) //start, length
									{
										if ( ! is_numeric( $start ) || ! isset( $items[ $start - 1 ] ) ) //start is bogus, replace the var with ''
										{
											$tagdata = str_replace( $match[0], '' , $tagdata );
										}
										else
										{
											if ( empty( $end ) || ! is_numeric( $end ) ) //just the start, end is not usable
											{
												$end = null;
											}

											$final = '';

											$items = array_slice( $items, $start - 1, $end );

											foreach ( $items as $item )
											{
												if ( ! is_array( $item ) )
												{
													$final .= $item;
												}
											}

											$tagdata = str_replace( $match[0], $this->add_list_pre_and_post( $match[1], $final ), $tagdata );
										}
									}
								}
							}
						}
						break;
					case 'default':
						//$tagdata = $this->_parse_vars( $tagdata );
						//$tagdata = $this->_parse_conditionals( $tagdata );
						//$tagdata = $this->_parse_exp_tags( $tagdata );
						break;
				}
			}

			//current_time variable {current_time format="%Y %m %d %H:%i:%s"}
			if ( strpos( $tagdata, '{current_time' ) !== FALSE && preg_match_all( "#\{current_time\s+format=([\"\'])([^\\1]*?)\\1\}#", $tagdata, $matches, PREG_SET_ORDER ) )
			{
				foreach ( $matches as $match )
				{
					if ( isset( $match[2] ) )
					{
						$tagdata = str_replace( $match[0], $this->EE->localize->decode_date( $match[2], $this->EE->localize->now ) , $tagdata );
					}
				}
			}

			//If a late: variable is found, go again. If this is the fifth loop, shut it down.
			$flag = ( $loops < 5 && preg_match( '@\{late:(.*)\}@Us', $tagdata ) == 1 );
			$loops++;
		} while ( $flag );

		//add the no_parse content back in, without the no_parse tags
		$tagdata = str_replace( $no_parse_placeholders, $no_parse_values, $tagdata );

		//remove any ee comments
		$tagdata = $this->EE->TMPL->remove_ee_comments( $tagdata );

		unset( $no_parse_placeholders, $no_parse_values );

		if ( $this->EE->TMPL->fetch_param('trim', 'no') == 'yes' )
		{
			$tagdata = trim( $tagdata );
		}

		return $tagdata;
	}

	/**
	 * A shorthand alias for the alt_conditionals method.
	 *
	 * @return string
	 */
	public function alt_if()
	{
		return $this->alt_conditionals();
	}

	/*
	 * Adds a new conditional parsing layer to your code. It allows you to leverage conditionals like you normally would, but it will happen way before the normal conditional parsing order.
	 *
	 * @return string
	 */
	public function alt_conditionals()
	{
		//get the tagdata
		$tagdata = $this->EE->TMPL->tagdata;

		//get the var prefix
		$var_prefix = '';

		$tag_parts = $this->EE->TMPL->tagparts;
		if ( is_array( $tag_parts ) && isset( $tag_parts[2] ) )
		{
			$var_prefix = $tag_parts[2] . ':';
		}

		$conditional_matches = array();

		$replace_count = 1;

		//find the conditional chunks
		$escaped = preg_quote( $var_prefix, '@' );
		preg_match_all( '@\{(' . $escaped . 'alt\:if|' . $escaped . 'alt\:elseif)\}(.*)\{/\\1\}@Usi', $tagdata, $matches );
		foreach ( $matches[0] as $match )
		{
			$conditional_matches[] = $match;
		}
		preg_match_all( '@\{('. $escaped . 'alt\:endif|' . $escaped . 'alt\:else)\}@Usi', $tagdata, $matches );
		foreach ( $matches[0] as $match )
		{
			$conditional_matches[] = $match;
		}

		//replace the conditionals with placeholders
		foreach ( $conditional_matches as $index => $conditional_match )
		{
			$tagdata = str_replace( $conditional_match, "#--__alt-tag-{$index}__--#", $tagdata, $replace_count );
		}

		//replace the tagdata with placeholders
		$content_matches = preg_split( '@#--__alt-tag-(\d+)__--#@', $tagdata );
		foreach ( $content_matches as $index => $content_match )
		{
			if ( trim( $content_match ) == '' )
			{
				continue;
			}
			$tagdata = preg_replace( '@' . preg_quote( trim( $content_match ), '@' ) . '@sS', "#--__alt-content-{$index}__--#", $tagdata );
		}

		//add the conditional tags back in
		foreach ( $conditional_matches as $index => $match )
		{
			$tagdata = str_replace( "#--__alt-tag-{$index}__--#", $match, $tagdata, $replace_count );
		}

		//convert the conditional tags to EE conditional tags
		$tagdata = $this->_convert_alt_to_ee_conditionals( $tagdata, $var_prefix );

		//parse the conditional chunks using the parse method and options (set defaults here)
		$this->EE->TMPL->tagdata = $tagdata;

		$order = $this->EE->TMPL->fetch_param( 'order', 'advanced' );
		$this->EE->TMPL->tagparams['order'] = $order;

		$tagdata = $this->parse();

		//inject the chunks back into their assigned positions in the original tagdata
		foreach ( $content_matches as $index => $match )
		{
			$tagdata = str_replace( "#--__alt-content-{$index}__--#", $match, $tagdata, $replace_count );
		}
		foreach ( $conditional_matches as $index => $match )
		{
			$tagdata = str_replace( "#--__alt-tag-{$index}__--#", $match, $tagdata, $replace_count );
		}

		return $tagdata;
	}

	/**
	 * Converts the alternative conditional tags to native EE conditional tags.
	 *
	 * @param string $tagdata
	 * @param string $var_prefix
	 * @return string
	 */
	private function _convert_alt_to_ee_conditionals( $tagdata, $var_prefix = '' )
	{
		$escaped = preg_quote( $var_prefix, '@' );

		return preg_replace(
			array(
				'@\{' . $escaped . 'alt:elseif\}@Usi',
				'@\{/' . $escaped . 'alt:elseif\}@Usi',
				'@\{' . $escaped . 'alt:endif\}@Usi',
				'@\{' . $escaped . 'alt:else\}@Usi',
				'@\{' . $escaped . 'alt:if\}@Usi',
				'@\{/' . $escaped . 'alt:if\}@Usi'
			),
			array(
				'{if:elseif ',
				'}',
				'{/if}',
				'{if:else}',
				'{if ',
				'}'
			),
			$tagdata
		);
	}

	/**
	 * Parse any variables that were passed into the exp:parse tag.
	 * @param string $tagdata
	 * @return string
	 */
	private function _parse_param_vars( $tagdata = '' )
	{
		//get the tagparams
		$tagparams = $this->EE->TMPL->tagparams;

		//get the set_global param - defaults to yes
		$set_globals = ( $this->EE->TMPL->fetch_param('save_globals') != 'no' );

		//prep the data and optionally set the global variables
		$tagparams = $this->prep_data( $tagparams, $set_globals );

		//parse variables with the prepared data
		$tagdata = $this->EE->TMPL->parse_variables_row( $tagdata, $tagparams );

		return $tagdata;
	}


	/**
	 * Parse global variables.
	 * @param string $tagdata
	 * @return string
	 */
	private function _parse_global_vars( $tagdata = '' )
	{
		if ( strpos( $tagdata, '{' ) !== FALSE ) //if there are no curly brackets, no need to parse...
		{
			$tagdata = $this->EE->TMPL->parse_variables_row( $tagdata, $this->EE->config->_global_vars );

			//$tagdata = $this->EE->TMPL->parse_globals( $tagdata );
		}

		return $tagdata;
	}

	/**
	 * Parse the segment variables.
	 * @param string $tagdata
	 * @return string
	 */
	private function _parse_seg_vars( $tagdata = '' )
	{
		if ( strpos( $tagdata, '{segment_' ) !== FALSE ) //if there are not segment variables, then there's no need to parse
		{
			if ( preg_match_all( '@\{segment_(\d+)\}@Us', $tagdata, $matches, PREG_SET_ORDER ) )
			{
				$segs = $this->EE->uri->segment_array();
				$matches = array_unique( $matches );

				foreach ( $matches as $match )
				{
					$tagdata = ( is_array( $segs ) && isset( $segs[ $match[1] ] ) ) ? str_replace( $match[0], $segs[ $match[1] ], $tagdata ) : str_replace( $match[0], '', $tagdata );
				}
			}
		}

		return $tagdata;
	}

	/**
	 * Now variables in the form {now:variable_name}. By default, EE will not parse a variable if its value is unknown. The now: variables are advantageous, as they will immediately return '' if a global variable by that name is not found, or the value of the variable if it is found.
	 * @param string $tagdata
	 * @return string
	 */
	private function _parse_now_vars( $tagdata = '' )
	{
		if ( preg_match_all( '@\{now:(.*)\}@Us', $tagdata, $matches, PREG_SET_ORDER ) )
		{
			foreach ( $matches as $match )
			{
				if ( isset( $this->EE->config->_global_vars[ $match[1] ] ) )
				{
					$tagdata = str_replace( $match[0], $this->EE->config->_global_vars[ $match[1] ], $tagdata );
				}
				else
				{
					$tagdata = str_replace( $match[0], '', $tagdata );
				}
			}
		}

		return $tagdata;
	}

	/**
	 * Parses all of the variables in the 'default' order.
	 * @param string $tagdata
	 * @return string
	 */
	private function _parse_vars( $tagdata = '' )
	{
		$tagdata = $this->_parse_param_vars( $tagdata );
		$tagdata = $this->_parse_seg_vars( $tagdata );
		$tagdata = $this->_parse_global_vars( $tagdata );
		$tagdata = $this->_parse_now_vars( $tagdata );

		return $tagdata;
	}

	/**
	 * Parse simple segment conditionals and simple conditionals
	 * @param string $tagdata
	 * @return string
	 */
	private function _parse_simple_conditionals( $tagdata = '' )
	{
		if ( strpos( $tagdata, '{if' ) !== FALSE )
		{
			//escape the exp tags
			$tagdata = str_replace( array( '{exp', '{/exp' ), array( '{_exp', '{/_exp' ), $tagdata );
			$tagdata = $this->EE->TMPL->parse_simple_segment_conditionals( $tagdata );
			$tagdata = $this->EE->TMPL->simple_conditionals( $tagdata, $this->EE->config->_global_vars);
			//unescape the exp tags
			$tagdata = str_replace( array( '{_exp', '{/_exp' ), array( '{exp', '{/exp' ), $tagdata );
		}
		return $tagdata;
	}

	/**
	 * Parse advanced conditionals.
	 * @param string $tagdata
	 * @return string
	 */
	private function _parse_advanced_conditionals( $tagdata = '' )
	{
		if ( strpos( $tagdata, '{if' ) !== FALSE )
		{
			//escape the exp tags
			$tagdata = str_replace( array( '{exp', '{/exp' ), array( '{_exp', '{/_exp' ), $tagdata );
			$tagdata = $this->EE->TMPL->advanced_conditionals( $tagdata );
			//unescape the exp tags
			$tagdata = str_replace( array( '{_exp', '{/_exp' ), array( '{exp', '{/exp' ), $tagdata );
		}

		return $tagdata;
	}

	/**
	 * Parses all of the conditionals in the 'default' order.
	 * @param string $tagdata
	 * @return string
	 */
	private function _parse_conditionals( $tagdata = '' )
	{
		$tagdata = $this->_parse_simple_conditionals( $tagdata );
		$tagdata = $this->_parse_advanced_conditionals( $tagdata );

		return $tagdata;
	}

	/**
	 * Parses {exp:..} tags and pretty much everything else. Basically, this method parses the tagdata like a full template.
	 * @param string $tagdata
	 * @return string
	 */
	private function _parse_exp_tags( $tagdata = '' )
	{
		if ( strpos( $tagdata, '{exp' ) !== FALSE )
		{
			//start code from Croxtons's Stash module (MIT License) - https://github.com/croxton/Stash & used with permission
			$TMPL2 = $this->EE->TMPL;
			unset($this->EE->TMPL);

			$this->EE->TMPL = new EE_Template();
			$this->EE->TMPL->start_microtime = $TMPL2->start_microtime;
			$this->EE->TMPL->template = $tagdata; //pass in value
			$this->EE->TMPL->tag_data	= array();
			$this->EE->TMPL->var_single = array();
			$this->EE->TMPL->var_cond	= array();
			$this->EE->TMPL->var_pair	= array();
			$this->EE->TMPL->plugins = $TMPL2->plugins;
			$this->EE->TMPL->modules = $TMPL2->modules;
			$this->EE->TMPL->parse_tags();
			$this->EE->TMPL->process_tags();
			$this->EE->TMPL->loop_count = 0;
			$tagdata = $this->EE->TMPL->template; //get back the value
			$this->EE->TMPL = $TMPL2; //return the original template object
			unset($TMPL2);
			//end code from Croxtons's Stash module (MIT License) - https://github.com/croxton/Stash & used with permission
		}
		return $tagdata;
	}

	//TODO add in this functionality
	public function prep_wysiwyg_list_vars()
	{
		/*
		{exp:ce_str:ing preg_rep="<p>\s*?(.*)?\s*?</p>|<p>$1</p>|Usi" preg_rep="<p>({tabs\:|\|?.*})</p>,<p>({videos\:|\|?.*})</p>,<p>({images\:|\|?.*})</p>,<p>({quotes\:|\|?.*})</p>,<p>({list\:|\|?.*})</p>|$1,$1,$1,$1,$1|Usi"}
		*/
	}

	/**
	 * Sets the tag params as global variables.
	 *
	 * @return string
	 */
	public function set()
	{
		//set the tag params as global variables
		$this->prep_data( $this->EE->TMPL->tagparams );

		return;
	}

	/**
	 * Return the tagdata if a variable is set.
	 *
	 * @return string
	 */
	public function is_set()
	{
		$tagdata = $this->EE->TMPL->tagdata;

		$name = $this->EE->TMPL->fetch_param('name');

		return isset( $this->EE->config->_global_vars[$name] ) ? $tagdata : $this->EE->TMPL->no_results();
	}

	/**
	 * Return the variable if it is set, otherwise, return the tagdata
	 *
	 * @return string The variable or tagdata.
	 */
	public function block()
	{
		$tagdata = $this->EE->TMPL->tagdata;

		$name = $this->EE->TMPL->fetch_param('name');

		return isset( $this->EE->config->_global_vars[$name] ) ? $this->EE->config->_global_vars[$name] : $tagdata;
	}

	/**
	 * Return the tagdata if the specified variable is empty or does not exist.
	 *
	 * @return string
	 */
	public function is_empty()
	{
		$tagdata = $this->EE->TMPL->tagdata;

		$name = $this->EE->TMPL->fetch_param('name');
		$trim = $this->EE->TMPL->fetch_param('trim', 'yes');

		if ( ! isset ( $this->EE->config->_global_vars[$name] ) )
		{
			return $tagdata;
		}

		$temp = $this->EE->config->_global_vars[$name];

		if ( $trim == 'yes' )
		{
			$temp = trim( $temp );
		}

		return empty( $temp ) ? $tagdata : $this->EE->TMPL->no_results();
	}

	/**
	 * Return the tagdata if the specified variable exists and is not empty.
	 *
	 * @return bool
	 */
	public function is_not_empty()
	{
		$tagdata = $this->EE->TMPL->tagdata;

		$name = $this->EE->TMPL->fetch_param('name');
		$trim = $this->EE->TMPL->fetch_param('trim', 'yes');

		if ( ! isset ( $this->EE->config->_global_vars[$name] ) )
		{
			return $this->EE->TMPL->no_results();
		}

		$temp = $this->EE->config->_global_vars[$name];

		if ( $trim == 'yes' )
		{
			$temp = trim( $temp );
		}

		return empty( $temp ) ? $this->EE->TMPL->no_results() : $tagdata;
	}

	/**
	 * Alias for is_not_empty.
	 *
	 * @return bool
	 */
	public function not_empty()
	{
		return $this->is_not_empty();
	}

	/**
	 * Get the global variable.
	 *
	 * @return string
	 */
	public function get()
	{
		$name = $this->EE->TMPL->fetch_param('name');

		if ( $name == FALSE )
		{
			return $this->EE->TMPL->no_results();
		}

		//get the delay
		$delay = $this->EE->TMPL->fetch_param('delay');

		if ( ! empty( $delay ) && is_numeric( $delay ) )
		{
			$delay = (int) $delay;

			if ( is_int( $delay ) && $delay > 0 && $delay < 5 )
			{
				return $this->delay_get_parsing( $delay );
			}
		}

		$is_file = $this->EE->TMPL->fetch_param('file') == 'yes';
		if ( $is_file ) //we're dealing with a file
		{
			$path = $this->EE->config->item( 'tmpl_file_basepath' );

			//replace backslashes with forward slashes
			$name = str_replace( '\\', '/', $name );

			//trim dots, slashes, and underscores
			$name = trim( $name, './_' );

			//remove dots to prevent unsafely getting into other directories
			$name = preg_replace( array('@\.{2,}@', '@\./|/\.@'), array('.',''), $name );

			//determine the full file path
			$name = rtrim( $path, '/') . '/' . trim( $name, '/' ) . '.html';

			//if the file exists, return it
			return  ( @file_exists( $path ) && @file_exists( $name) && ($tagdata = @file_get_contents( $name )) ) ? $this->parse( $tagdata ) : $this->get_default();
		}

		if ( ! isset( $this->EE->config->_global_vars[$name] ) ) //the variable is not available
		{
			return $this->delay_get_parsing();
		}

		if ( in_array( $name, $this->forbidden_globals ) ) //not a global to manipulate, return it as is
		{
			return $this->EE->config->_global_vars[$name];
		}
		else //parse with the current global variables and return
		{
			return $this->parse( $this->EE->config->_global_vars[$name], $this->EE->config->_global_vars );
		}
	}

	/**
	 * Return the default value or no_results.
	 *
	 * @return mixed
	 */
	private function get_default()
	{
		$default = $this->EE->TMPL->fetch_param( 'default' );
		if ( $default === false )
		{
			$this->EE->TMPL->no_results();
		}

		return $default;
	}

	/**
	 * This allows get calls to be postponed up to 5 parse loops.
	 *
	 * @param int $delay The number of loops to delay parsing.
	 * @return string
	 */
	private function delay_get_parsing( $delay = 0 )
	{
		//get the var prefix
		$var_prefix = '';

		$tag_parts = $this->EE->TMPL->tagparts;
		if ( is_array( $tag_parts ) && isset( $tag_parts[2] ) )
		{
			$var_prefix = ':' . $tag_parts[2];
		}

		//get the tag params
		$tag_params = $this->EE->TMPL->tagparams;
		if ( ! is_array( $tag_params ) )
		{
			$tag_params = array();
		}

		//get the nest level
		$nest_level = $this->EE->TMPL->fetch_param('nest_level');
		if ( empty($nest_level) || ! is_numeric( $nest_level ) )
		{
			$nest_level = 1;
		}
		else
		{
			$nest_level += 1;
		}

		if ( $nest_level > 5 ) //nesting is too deep, bail
		{
			return $this->get_default();
		}

		$tag_params['nest_level'] = $nest_level;

		//if there is a delay, let's decrease the delay count
		if ( ! empty( $delay ) )
		{
			$delay -= 1;
			$tag_params['delay'] = $delay;

			if ( $delay <= 0 ) //this is the last delay
			{
				$tag_params['delay'] = '';
			}
		}

		//create tag from the attributes
		$attributes = '';
		foreach ( $tag_params as $param => $value )
		{
			//$attributes .= $param . '="' . $value . '" ';
			$attributes .= ( strpos( $value, '"' ) === FALSE) ? "{$param}=\"{$value}\" " : "{$param}='{$value}' ";
		}
		return '{exp:ce_vars:get' . $var_prefix . ' ' . $attributes . '}';
	}

	/**
	 * Set the content of a global variable.
	 *
	 * @return string
	 */
	public function set_content()
	{
		$name = $this->EE->TMPL->fetch_param('name');

		if ( $name == FALSE || in_array( $name, $this->forbidden_globals ) )
		{
			return $this->EE->TMPL->no_results();
		}

		$this->prep_data( array( $name => $this->EE->TMPL->tagdata) );

		return '';
	}

	/**
	 * Prepares the variables and optionally saves them as global variables.
	 * @param null $vars
	 * @param bool $save_as_globals
	 * @return array|bool
	 */
	private function prep_data( $vars = null, $save_as_globals = TRUE )
	{
		if ( ! isset( $vars ) || ! is_array( $vars ) || count( $vars ) == 0 )
		{
			return FALSE;
		}

		$new = array();

		//loop through the vars and prep them
		foreach( $vars as $key => $value )
		{
			//prep the value
			$value = $this->prep_value( $value );

			//use a default if available
			if ( empty( $value ) )
			{
				$value = $this->prep_value( $this->EE->TMPL->fetch_param('default', '' ) );
			}

			//the asterisk free key
			$cleaned = str_replace( '*', '', $key );

			//the current global var value
			$current = '';
			if ( isset( $this->EE->config->_global_vars[$cleaned] ) )
			{
				$current = $this->EE->config->_global_vars[$cleaned];
			}

			//determine value based on asterisk presence and position
			$pos = strpos( $key, '*' );
			if ( $pos !== false && $current != '' ) //there is at least one asterisk
			{
				if ( $pos === 0  ) //starts with an asterisk - append
				{
					$current = $value . $current;
				}
				else if ( $pos === strlen( $key ) - 1 ) //ends with an asterisks - prepend
				{
					$current .= $value;
				}
				else //the asterisks was somewhere else - default value
				{
					$current = $value;
				}
			}
			else //no asterisks - default value
			{
				$current = $value;
			}

			//add the clean key and current value to the new array
			$new[$cleaned] = $current;
		}

		if ( $save_as_globals )
		{
			//remove the forbidden globals
			foreach( $this->forbidden_globals as $value )
			{
				unset( $new[$value] );
			}

			//set the global variables
			foreach ( $new as $key => $value )
			{
				$this->EE->config->_global_vars[$key] = $value;
			}
		}

		return $new;
	}

	private function prep_value( $value )
	{
		//little trick to allow global variables to be parsed *late*
		$value = preg_replace( '@^late:(.*)@', '$1', $value );

		//see if the value is escaped by a pair of asterisks
		//this is a little trick to preserve whitespace in parameter values
		if ( preg_match( '@^\*(.*)\*$@Us', $value, $matches ) )
		{
			$value = $matches[1];
		}

		return $value;
	}

	/**
	 * Sets a list.
	 *
	 * @return string
	 */
	public function list_set()
	{
		//get the list name
		$name_orig = $this->EE->TMPL->fetch_param('name');
		$name = str_replace( '*', '', $name_orig );

		if ( $name == FALSE || in_array( $name, $this->forbidden_globals ) ) //the name is not valid
		{
			return $this->EE->TMPL->no_results();
		}

		//determine value based on asterisk presence and position
		$pos = strpos( $name_orig, '*' );
		$action = '';
		if ( $pos !== FALSE ) //there is at least one asterisk
		{
			if ( $pos === 0  ) //starts with an asterisk - append
			{
				$action = 'append';
			}
			else if ( $pos === strlen( $name_orig ) - 1 ) //ends with an asterisks - prepend
			{
				$action = 'prepend';
			}
		}

		//get the tagdata
		$tagdata = $this->EE->TMPL->tagdata;
		if ( trim( $tagdata ) == '' ) //no tagdata means no list
		{
			return $this->EE->TMPL->no_results();
		}

		//trim the tagdata?
		$trim = $this->EE->TMPL->fetch_param('trim', 'yes');
		if ( $trim == 'yes' )
		{
			$tagdata = trim( $tagdata );
		}

		//global scope? If not, cache will be used.
		$global = $this->EE->TMPL->fetch_param('global') == 'yes';

		//get the delimiter
		$delimiter = $this->EE->TMPL->fetch_param('delimiter', '|');

		//explode by delimiter
		$items = explode( $delimiter, $tagdata );

		//set any keys
		$temp = array();
		foreach ( $items as $index => $item )
		{
			if ( strpos( $item, '=>' ) !== FALSE )
			{
				list( $k, $v ) = explode( '=>', $item, 2 );
				$temp[ trim( $k ) ] = $v;
			}
			else
			{
				$temp[] = $item;
			}
		}

		$items = $temp;
		unset( $temp );

		//do we remove empty items?
		$remove_empty = $this->EE->TMPL->fetch_param( 'remove_empty', 'yes' );

		//remove the empty items
		if ( $remove_empty == 'yes' )
		{
			$empty_elements = array_keys( $items, '' );
			foreach ($empty_elements as $e)
			{
				unset( $items[$e] );
			}
		}

		switch ( $action )
		{
			case 'append':
				$current = $this->get_list_by_dot_syntax( $name, $global );
				if ( !! $current ) //there is a current list
				{
					if ( ! is_array( $current ) )
					{
						$current = (array) $current;
					}
					//combine the lists
					$this->set_list_by_dot_syntax( $name, array_merge( $current, $items ), $global );
				}
				else //no list exists
				{
					//add the list
					$this->set_list_by_dot_syntax( $name, $items, $global );
				}
				break;
			case 'prepend':
				$current = $this->get_list_by_dot_syntax( $name, $global );

				if ( !! $current ) //there is a current list
				{
					if ( ! is_array( $current ) )
					{
						$current = (array) $current;
					}
					//combine the lists
					$this->set_list_by_dot_syntax( $name, array_merge( $items, $current ), $global );
				}
				else //no list exists
				{
					//add the list
					$this->set_list_by_dot_syntax( $name, $items, $global );
				}
				break;
			default:
				$this->set_list_by_dot_syntax( $name, $items, $global );
		}

		return '';
	}

	/**
	 * Allows pre content to be saved for a list. This content will automatically be prepended when a list is requested.
	 * @return string
	 */
	public function list_set_pre_content()
	{
		//get the list name
		$name_orig = $this->EE->TMPL->fetch_param('name');
		$name = str_replace( '*', '', $name_orig );

		$print = $this->EE->TMPL->fetch_param('print', 'no');

		//determine value based on asterisk presence and position
		$pos = strpos( $name_orig, '*' );
		$action = '';
		if ( $pos !== FALSE ) //there is at least one asterisk
		{
			if ( $pos === 0  ) //starts with an asterisk - prepend
			{
				$action = 'append';
			}
			else if ( $pos === strlen( $name_orig ) - 1 ) //ends with an asterisks - append
			{
				$action = 'prepend';
			}
		}

		//get the tagdata
		$tagdata = $this->EE->TMPL->tagdata;
		if ( trim( $tagdata ) == '' ) //no tagdata, nothing left to do...
		{
			return $this->EE->TMPL->no_results();
		}

		//trim the tagdata?
		$trim = $this->EE->TMPL->fetch_param('trim', 'yes');
		if ( $trim == 'yes' )
		{
			$tagdata = trim( $tagdata );
		}

		switch ( $action )
		{
			case 'append':
				$current = $this->get_cache_by_dot_syntax( $name, 'pre' );
				if ( !! $current ) //there is previous content
				{
					$this->set_cache_by_dot_syntax( $name, $current . $tagdata, 'pre' );
				}
				else //no previous content
				{
					$this->set_cache_by_dot_syntax( $name, $tagdata, 'pre' );
				}
				break;
			case 'prepend':
				$current = $this->get_cache_by_dot_syntax( $name, 'pre' );

				if ( !! $current ) //there is previous content
				{
					$this->set_cache_by_dot_syntax( $name, $tagdata . $current, 'pre' );
				}
				else //no previous content
				{
					$this->set_cache_by_dot_syntax( $name, $tagdata, 'pre' );
				}
				break;
			default:
				$this->set_cache_by_dot_syntax( $name, $tagdata, 'pre' );
		}

		if ( $print == 'yes' )
		{
			return $tagdata;
		}

		return '';
	}

	/**
	 * Allows post content to be saved for a list. This content will automatically be prepended when a list is requested.
	 * @return string
	 */
	public function list_set_post_content()
	{
		//get the list name
		$name_orig = $this->EE->TMPL->fetch_param('name');
		$name = str_replace( '*', '', $name_orig );

		$print = $this->EE->TMPL->fetch_param('print', 'no');

		//determine value based on asterisk presence and position
		$pos = strpos( $name_orig, '*' );
		$action = '';
		if ( $pos !== FALSE ) //there is at least one asterisk
		{
			if ( $pos === 0  ) //starts with an asterisk - prepend
			{
				$action = 'append';
			}
			else if ( $pos === strlen( $name_orig ) - 1 ) //ends with an asterisks - append
			{
				$action = 'prepend';
			}
		}

		//get the tagdata
		$tagdata = $this->EE->TMPL->tagdata;
		if ( trim( $tagdata ) == '' ) //no tagdata, nothing left to do...
		{
			return $this->EE->TMPL->no_results();
		}

		//trim the tagdata?
		$trim = $this->EE->TMPL->fetch_param('trim', 'yes');
		if ( $trim == 'yes' )
		{
			$tagdata = trim( $tagdata );
		}

		switch ( $action )
		{
			case 'append':
				$current = $this->get_cache_by_dot_syntax( $name, 'post' );
				if ( !! $current ) //there is previous content
				{
					$this->set_cache_by_dot_syntax( $name, $current . $tagdata, 'post' );
				}
				else //no previous content
				{
					$this->set_cache_by_dot_syntax( $name, $tagdata, 'post' );
				}
				break;
			case 'prepend':
				$current = $this->get_cache_by_dot_syntax( $name, 'post' );

				if ( !! $current ) //there is previous content
				{
					$this->set_cache_by_dot_syntax( $name, $tagdata . $current, 'post' );
				}
				else //no previous content
				{
					$this->set_cache_by_dot_syntax( $name, $tagdata, 'post' );
				}
				break;
			default:
				$this->set_cache_by_dot_syntax( $name, $tagdata, 'post' );
		}

		if ( $print == 'yes' )
		{
			return $tagdata;
		}

		return '';
	}

	/**
	 * Add the pre and post content for a list name to the passed in content
	 * @param  $list_name
	 * @param string $content
	 * @return bool|mixed|string
	 */
	private function add_list_pre_and_post( $list_name, $content = '' )
	{
		if ( empty( $list_name ) )
		{
			return $content;
		}

		$final = '';

		//get the pre-list info
		$pre = $this->get_cache_by_dot_syntax( $list_name, 'pre' );
		if ( !! $pre )
		{
			$final = $pre;
		}

		//add the content
		$final .= $content;

		//get the post-list info
		$post = $this->get_cache_by_dot_syntax( $list_name, 'post' );
		if ( !! $post )
		{
			$final .= $post;
		}

		return $final;
	}

	/**
	 * Sets a session cache value by dot syntax notation.
	 * @param  $path
	 * @param $content
	 * @param string $prefix
	 * @return bool|mixed
	 */
	private function set_cache_by_dot_syntax( $path, $content, $prefix = '' )
	{
		//make sure it is not empty
		if ( empty( $path ) || ( $prefix != 'pre' && $prefix != 'post') ) //the path is empty
		{
			return false;
		}

		//get the pieces
		$pieces = explode( '.', $path );
		$path =& $this->EE->session->cache[ __CLASS__ . '_' . $prefix ];

		foreach ( $pieces as $piece )
		{
			//make sure the path is set
			if ( ! isset( $path[$piece] ) )
			{
				$path[$piece] = array();
			}

			//update the path
			$path =& $path[$piece];
		}

		//set the content
		$path = $content;
		return true;
	}

	/**
	 * Retrieves a session cache value by dot syntax notation.
	 * @param  $path
	 *
	 * @param string $prefix
	 * @internal param $content
	 * @return bool|mixed
	 */
	private function get_cache_by_dot_syntax( $path, $prefix = '' )
	{
		//make sure it is not empty
		if ( empty( $path ) || ( $prefix != 'pre' && $prefix != 'post' ) ) //the path is empty
		{
			return false;
		}

		//get the pieces
		$pieces = explode( '.', $path );
		$path =& $this->EE->session->cache[ __CLASS__ . '_' . $prefix ];

		foreach ( $pieces as $piece )
		{
			//make sure the path is set
			if ( ! isset( $path[$piece] ) )
			{
				return false;
			}

			//update the path
			$path =& $path[$piece];
		}

		return $path;
	}

	/**
	 * Returns the number of items in the list.
	 * @return int
	 */
	public function list_count()
	{
		//get the list
		$items = $this->list_get( true );

		if ( $items == false ) //no items
		{
			return 0;
		}
		else if ( is_array( $items ) ) //return the array
		{
			return count( $items );
		}
		else //assume it is a string...
		{
			return 1;
		}
	}

	/**
	 * Get the list.
	 *
	 * @param bool $return_raw
	 * @param string $name
	 * @return array|bool|mixed|string
	 */
	public function list_get( $return_raw = false, $name = null )
	{
		//get the list name, if not defined
		if ( empty( $name ) )
		{
			$name = $this->EE->TMPL->fetch_param('name');
		}

		if ( empty( $name ) || in_array( $name, $this->forbidden_globals ) )
		{
			return $this->EE->TMPL->no_results();
		}

		//global scope? If not, cache will be used.
		$global = $this->EE->TMPL->fetch_param('global') == 'yes';

		//get the list data
		$items = $this->get_list_by_dot_syntax( $name, $global );

		if ( $items !== false )
		{
			if ( is_array( $items ) )
			{
				$flatten = $this->EE->TMPL->fetch_param('flatten', 'no');

				if ( $flatten == 'yes' ) //flatten any nested arrays
				{
					$items = $this->array_flatten( $items );
				}
				else //loop through and remove any nested arrays
				{
					$nested_text = $this->EE->TMPL->fetch_param('nested_text', '[remove]');

					foreach ( $items as $index => $item )
					{
						if ( is_array( $item ) )
						{
							if ( $nested_text == '[remove]' )
							{
								unset( $items[$index ] );
							}
							else
							{
								$items[$index] = $nested_text;
							}
						}
					}
				}

				//make the items unique?
				$unique = $this->EE->TMPL->fetch_param( 'unique', 'yes' );
				if ( $unique != 'no' )
				{
					$items = array_unique( $items );
				}

				//offset
				$offset = $this->EE->TMPL->fetch_param( 'offset', 0 );
				if ( ! is_numeric( $offset ) || $offset < 0 )
				{
					$offset = 0;
				}

				//limit
				$limit = $this->EE->TMPL->fetch_param( 'limit', '' );
				if ( is_numeric($limit) && $limit >= 0 )
				{
					$items = array_slice( $items, $offset, $limit );
				}
				else if ( $offset != 0 )
				{
					$items = array_slice( $items, $offset );
				}

				//keys
				$keys = $this->EE->TMPL->fetch_param( 'keys', '' );
				if ( ! empty( $keys ) )
				{
					$keys = explode( '|', $keys );

					//limit the items to the allowed keys, while maintaining the specified key order
					$temp = $items;
					$items = array();

					foreach ( $keys as $key )
					{
						if ( isset( $temp[$key] ) )
						{
							$items[$key] = $temp[$key];
						}
					}

					unset( $temp );
				}

				//sort
				$sort = $this->EE->TMPL->fetch_param( 'sort', '');
				if ( $sort == 'asc' )
				{
					sort( $items );
				}
				else if ( $sort == 'desc' )
				{
					rsort( $items );
				}

				if ( $return_raw )
				{
					return $items;
				}
				else if ( $this->EE->TMPL->fetch_param( 'json_encode' ) == 'yes' && function_exists( 'json_encode' ) )
				{
					return json_encode( $items );
				}
				else
				{
					//get the glue
					$glue = $this->EE->TMPL->fetch_param( 'glue', ', ' );

					//implode with glue
					$content = implode( $glue, $items );

					//add the pre and post content
					return $this->add_list_pre_and_post( $name, $content );
				}
			}
			else //not an array
			{
				return $this->add_list_pre_and_post( $name, $items );
			}
		}

		return $this->EE->TMPL->no_results();
	}

	/**
	 * Determine if the list contains the specified item.
	 * @return bool
	 */
	public function list_contains()
	{
		//get the item
		$item = $this->EE->TMPL->fetch_param( 'item', false );
		if ( $item === false )
		{
			return false;
		}

		//get the list
		$items = $this->list_get( true );

		if ( $items != FALSE )
		{
			if ( is_array( $items ) )
			{
				return in_array( $item, $items );
			}
			else
			{
				return ( $items === $item );
			}
		}

		return false;
	}

	/**
	 * Loops through the list items.
	 * @return mixed
	 */
	public function list_loop()
	{
		//get the list
		$items = $this->list_get( true );

		if ( $items === false || ! is_array( $items ) )
		{
			return $this->EE->TMPL->no_results();
		}

		$final = array();

		//create the rows
		foreach ( $items as $k => $t )
		{
			$final[] = array( 'list:key' => $k, 'list:value' => $t, 'list:item' => $t );
		}

		if ( count( $final ) > 0 )
		{
			return $this->EE->TMPL->parse_variables( $this->EE->TMPL->tagdata, $final );
		}
		else
		{
			return $this->EE->TMPL->no_results();
		}
	}

	/**
	 * Sets each item in a list to a global variable. The global variables will be in the format list_name.count
	 * @return void
	 */
	public function list_to_global()
	{
		//get the list
		$items = $this->list_get( true );

		if ( $items === false || ! is_array( $items ) )
		{
			return;
		}

		$name = $this->EE->TMPL->fetch_param('name');

		//set the global variables
		foreach ( $items as $key => $value )
		{
			$this->EE->config->_global_vars[ $name . '.' . ( $key + 1 ) ] = $value;
		}
	}

	/**
	 * Remove a list.
	 * @return void
	 */
	public function list_remove()
	{
		//get the list name
		$name = $this->EE->TMPL->fetch_param( 'name' );
		if ( $name == FALSE || in_array( $name, $this->forbidden_globals ) )
		{
			return;
		}

		//get the pieces
		$pieces = explode( '.', $name );

		if ( count( $pieces ) > 0 )
		{
			$last = array_pop( $pieces );
		}
		else
		{
			return;
		}

		$path =& $this->EE->session->cache[ __CLASS__ ];

		foreach ( $pieces as $piece )
		{
			//make sure the path is set
			if ( ! isset( $path[$piece] ) )
			{
				return;
			}
			//update the path
			$path =& $path[$piece];
		}

		unset( $path[$last] );
	}

	/**
	 * Sets a session cache value by dot syntax notation.
	 * @param string $path
	 * @param string $content
	 * @param bool $global
	 * @return bool|mixed
	 */
	private function set_list_by_dot_syntax( $path, $content, $global = FALSE )
	{
		//make sure it is not empty
		if ( empty( $path ) ) //the path is empty
		{
			return false;
		}

		if ( ! $global )
		{
			//get the pieces
			$pieces = explode( '.', $path );

			$path =& $this->EE->session->cache[ __CLASS__ ];

			foreach ( $pieces as $piece )
			{
				//make sure the path is set
				if ( ! isset( $path[$piece] ) )
				{
					$path[$piece] = array();
				}

				//update the path
				$path =& $path[$piece];
			}

			//set the content
			$path = $content;
		}
		else
		{
			//set the content
			$this->EE->config->_global_vars[ __CLASS__ . '-' . $path ] = (string) json_encode( $content );
		}

		return true;
	}

	/**
	 * Retrieves a session cache value by dot syntax notation.
	 * @param string $path
	 * @param bool $global
	 * @return bool|mixed
	 */
	private function get_list_by_dot_syntax( $path, $global = FALSE )
	{
		//make sure it is not empty
		if ( empty( $path ) ) //the path is empty
		{
			return false;
		}

		if ( ! $global )
		{
			//get the pieces
			$pieces = explode( '.', $path );

			$path =& $this->EE->session->cache[ __CLASS__ ];

			foreach ( $pieces as $piece )
			{
				//make sure the path is set
				if ( ! isset( $path[$piece] ) )
				{
					return false;
				}

				//update the path
				$path =& $path[$piece];
			}

			return $path;
		}
		else
		{
			if ( ! isset( $this->EE->config->_global_vars[ __CLASS__ . '-' . $path ] ) )
			{
				return false;
			}

			return json_decode( $this->EE->config->_global_vars[ __CLASS__ . '-' . $path ], true );
		}
	}

	/** Flattens an array.
	 * Code from comment by Ralph Holzmann (January 13, 2011) on http://davidwalsh.name/flatten-nested-arrays-php
	 * @param array $array
	 * @param array $return
	 * @return array
	 */
	private function array_flatten( array $array, array $return = array() )
	{
		foreach ( $array as $k => $item )
		{
			if ( is_array( $item ) )
			{
				$return = $this->array_flatten( $item, $return );
			}
			else if ( $item )
			{
				$return[] = $item;
			}
		}
		return $return;
	}

	// ----------------------------------------------------------------
	/**
	 * Dynamic set_content/get.
	 *
	 * @param $name
	 * @param $arguments
	 * @return string
	 */
	public function __call($name, $arguments)
	{
		$this->EE->TMPL->tagparams['name'] = $name;
		return $this->EE->TMPL->tagdata ? $this->set_content() : $this->get();
	}

	/**
	 * Plugin Usage
	 * @return string
	 */
	public static function usage()
	{
		ob_start();
		?>
		http://www.causingeffect.com/software/expressionengine/ce-variables
		<?php
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}
} /* End of class */
/* End of file pi.ce_vars.php */
/* Location: /system/expressionengine/third_party/ce_vars/pi.ce_vars.php */