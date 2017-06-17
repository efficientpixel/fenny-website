<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * SurgeEO Module
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Module
 * @author		Digital Surgeons
 * @link		http://www.digitalsurgeons.com
 */

class Surgeeo {

	protected $return_data = '';
	protected $site_id;
	protected $options = array();

	protected $defaults = array(
					'append_to_title' => '',
					'prepend_to_title' => '',
					'robots' => 'follow,index',
					'default_title' => '',
					'default_keywords' => '',
					'default_description' => '',
					'default_og_img' => '',
					'default_og_description' => '',
					'default_twtr_title' => '',
					'default_twtr_img' => '',
					'default_twtr_description' => '',
					'use_default_title' => '',
					'use_default_keywords' => '',
					'use_default_author' => '',
					'use_default_gplus' => '',
					'use_default_description' => '',
					'use_default_og_description' => '',
					'use_default_og_img' => '',
					'use_default_twtr_title' => '',
					'use_default_twtr_description' => '',
					'use_default_twtr_img' => ''
				);

	protected $funcs = [
		'title',
		'keywords',
		'author',
		'description',
		'gplus',
		'og_description',
		'og_img',
		'og_type',
		'og_url',
		'twtr_title',
		'twtr_img',
		'twtr_type',
		'twtr_description'
	];

	/**
	*	Setup
	*
	*	Obtain EE Super Object, as per usual
	*	Obtain default values as defined by user
	*	Obtain Site ID
	*/
	function __construct() {
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		// Get site_id for use in db queries.
		$this->site_id = $this->EE->config->item('site_id');

		// Get Options (configuration) on class load.
		// Since this constructor can get called quite a bit,
		// we cache this in the session cache.
		$options = $this->EE->session->cache('SurgeEO', 'options');

		if (empty($options)) {

			// Manually set this to an array in case we
			// got false back from the cache check.
			$options = array();

			$res = $this->EE->db->select()
				->from('surgeeo_options')
				->where('site_id', $this->site_id)
				->get();

			if ($res->num_rows() > 0) {

				foreach($res->result_array() as $row) {
					$options[$row['key']] = $row['value'];
				}

			} else {

				//Revert to defaults if no results found
				$options = $this->defaults;

			}

			$this->EE->session->set_cache('SurgeEO', 'options', $options);

		}

		$this->options = $options;

	}


/* ----------------------------------------------- HELPERS ------------------------------------------------------*/


	/**
	* 	Retrieve entry ID
	*	Retrieves entry ID either:
	*		a) Directly, with entry_id parameter
	*		b) Indirectly, via segment parameter
	*		c) Guessing, via last URL segment (takes pagination into account, but not categories or 'other')
	*
	*	@param int 		Entry ID - Result of passing {entry_id}, usually obtained in {exp:channel:entries} loop
	*	@param string 	segment - Result of passing {segment_2} or other predefined global var
	*	@return int 	Entry ID
	*/
	protected function _getEntryID() {

		// Try and get this from cache.
		$entry_id = $this->EE->session->cache('SurgeEO', 'entry_id');

		if ($entry_id !== false) {

			return $entry_id;

		}

		// Then look for the entry_id parameter
		$entry_id = $this->EE->TMPL->fetch_param('entry_id', '');
		$url_title = $this->EE->TMPL->fetch_param('url_title', '');

		if ($entry_id == '') {

			// Set this so it's always defined
			$last_segment = '';

			$site_pages = $this->EE->config->item('site_pages');

			// If we're told the url segment, use that.
			if($url_title != '') {

				$last_segment = $url_title;

			// If not, let's check for Pages or Structure module before
			// we take the last segment and look for a channel entry.
			} elseif (!empty($site_pages) && isset($site_pages[$this->site_id]['uris'])) {

				$page_uris = $site_pages[$this->site_id]['uris'];

				// get canonicalized current uri string
				// will result in '/' if uri_string is blank
				$match_uri = '/'.trim(strtolower(ee()->uri->uri_string), '/');

				// trim page uris in case there's a trailing slash on any of them
				$page_uris = array_map(function($value) {
					return '/' . trim(strtolower($value), '/');
				}, $page_uris);

				// case insensitive URI comparison
				$entry_id = array_search($match_uri, $page_uris);

				if ( ! $entry_id AND $match_uri != '/') {
					$entry_id = array_search($match_uri.'/', $page_uris);
				}

			// Look for a channel entry with the last segment as the
			// url_title.
			} else {

				// Fallback to entry_id associated with last segment, if it exists
				$total_segments = $this->EE->uri->total_segments();

				if ($total_segments > 0) {
					$last_segment = $this->EE->uri->segment($total_segments);

					//Let's check for pagination shall we?
					$last_segment = (preg_match('/^P(\d+)|\/P(\d+)/', $last_segment)) ? $this->EE->uri->segment($total_segments - 1) : $last_segment;
				}

			}

			// If we found a last segment, and no entry id,
			// then we have a chance to find the entry_id..
			if ($last_segment !== '' && empty($entry_id)) {

				//Get entry_id from URL
				$res = $this->EE->db->select('entry_id')
					->from('channel_titles')
					->where('url_title', $last_segment)
					->where('site_id', $this->site_id)
					->get();

				if ($res->num_rows() > 0) {
					$entry_id = $res->row()->entry_id;
				}

			}

		}

		// Store for later calls, regardless of if this is empty
		// or not. We don't want to repeat the above if we know we'll
		// get empty back each time.
		$this->EE->session->set_cache('SurgeEO', 'entry_id', $entry_id);

		return $entry_id;

	}


	/**
	* Used to determine if the current lookup should be an "entry" otherwise it's a page (we hope)
	*/
	protected function _isEntry()
	{

		// Get what the uri is
		$uri = $this->EE->uri->uri_string();

		// Fallback on bad uris to home page
		if (empty($uri)) {

			$uri = '/';

		}

		// Ensure left /
		$uri = '/'.ltrim($uri, '/');

		// Can we get an entry id for this?
		$entry_id = $this->_getEntryID();

		// Can we get a page for this?
		$page = ($uri != '') ? $this->_retrievePageData('title', $uri) : $this->_retrievePageData('title');

		// Do we have an entry id?
		if($entry_id !== NULL && ($page === FALSE || $page === NULL))
		{
			return true;
		}

		// Can we match this URI?
		if($page !== FALSE)
		{
			// This is a page!
			return false;
		}

		return null;

	}

	/**
	* Retrieve default values from user-set configuration
	*
	* If user selects they want to use default values
	* See __construct() for details on obtaining default values
	*
	* @param string 	Type: title|keywords|description
	* @return string 	Default value of defined type.
	*/
	protected function _defaultValue($type) {

		$use_default_on = (isset($this->options["use_default_$type"]) && $this->options["use_default_$type"] == 'yes');
		return ($use_default_on) ? htmlentities(strip_tags($this->options["default_$type"]), ENT_QUOTES) : '';

	}

	/**
	 * Retrieve entry data matching a given entry_id
	 *
	 * @param  string  $key      The column of the database, eg title
	 * @param  integer $entry_id The id of the entry
	 * @return strig             The value of the key.
	 */
	protected function _retrieveEntryData($key, $entry_id) {

		// This is our fallback value
		$value = '';

		// First attempt to retrieve from session cache,
		// which only lasts for this request alone.
		$cache_key = $entry_id . '::' . $key;
		$value = $this->EE->session->cache('SurgeEO', $cache_key);

		// If not, get ALL the data for the entry to save
		// us queries later on. Only do this if the key was
		// not found, not if it was merely empty.
		if ($value === false) {

			$result = $this->EE->db->select()
				->from('surgeeo_data')
				->where('entry_id', $entry_id)
				->where('site_id', $this->site_id)
				->limit(1)
				->get();

			if ($result->num_rows() > 0) {

				foreach ($result->row_array() as $result_key => $result_value) {

					$cache_key = $entry_id . '::' . $result_key;

					$result_value = htmlentities(strip_tags($result_value), ENT_QUOTES);

					// Store it in the cache for the next time we call this.
					$this->EE->session->set_cache('SurgeEO', $cache_key, $result_value);

					// Don't forget to return the value we're
					// currently looking for, :)
					if ($result_key == $key) {

						$value = $result_value;

					}

				}

			}

		}

		return $value;

	}

	/**
	* Return the PAGE data matching the current URI segment
	*
	* @param string $key          Column of database, eg title|keywords|description
	* @param string $uri_override The value of the uri in case we want to override it.
	* @return string              The value of the key
	*/
	protected function _retrievePageData($key, $uri_override = null) {

		// Get what the uri is
		$uri = (is_string($uri_override)) ? $uri_override : $this->EE->uri->uri_string();

		// Fallback on bad uris to home page
		if (empty($uri)) {

			$uri = '/';

		}

		// Ensure left /
		$uri = '/'.ltrim($uri, '/');

		// This is our fallback value
		$value = '';

		// First attempt to retrieve from session cache,
		// which only lasts for this request alone.
		$cache_key = 'URI::' . $key;
		$value = $this->EE->session->cache('SurgeEO', $cache_key);

		// If not, get ALL the data for the entry to save
		// us queries later on. Only do this if the key was
		// not found, not if it was merely empty.
		if ($value === false) {

			// Query for the requested data.
			$result = $this->EE->db->select()
				->from('surgeeo_uri')
				->where('uri', $uri)
				->where('site_id', $this->site_id)
				->limit(1)
				->get();

			if ($result->num_rows() > 0) {

				foreach ($result->row_array() as $result_key => $result_value) {


					$cache_key = 'URI::' . $result_key;

					$result_value = htmlentities(strip_tags($result_value), ENT_QUOTES);

					// Store it in the cache for the next time we call this.
					$this->EE->session->set_cache('SurgeEO', $cache_key, $result_value);

					// Don't forget to return the value we're
					// currently looking for, :)
					if ($result_key == $key) {

						$value = $result_value;

					}

				}

			}

		}

		return $value;

	}


	/**
	*	Get SEO title, given uri (pages)
	*	If not found, fallback to defaults
	*
	*	@param string prepend - Override global prepend option
	*	@param string append - Override global append option
	*	@param string fallback - Last resort fallback
	*	@return string The title, with prepend/append
	*
	*/
	protected function _page_title() {

		// Parameters
		$prepend = $this->EE->TMPL->fetch_param('prepend');
		$append = $this->EE->TMPL->fetch_param('append');
		$fallback = htmlentities(strip_tags($this->EE->TMPL->fetch_param('fallback', '')), ENT_QUOTES);
		$uri = $this->EE->TMPL->fetch_param('uri', '');

		// Get the data for this page.
		$title_data = ($uri != '') ? $this->_retrievePageData('title', $uri) : $this->_retrievePageData('title');

		//Page title & fallbacks/defaults
		if($title_data !== FALSE && $title_data != '') {
			//User-defined data (in page options)
			$this->return_data = $title_data;
		} else {
			if($fallback == '') {
				//User-defined fallback (in global options)
				$this->return_data = $this->_defaultValue('title');
			} else {
				//User-defined fallback (in tag)
				$this->return_data = $fallback;
			}
		}

		//Add in prepend/append
		$final_prepend = '';
		if (!empty($prepend)) {
			$final_prepend = $prepend;
		} else {
			$final_prepend = $this->options['prepend_to_title'];
		}

		$final_append = '';
		if (!empty($append)) {
			$final_append = $append;
		} else {
			$final_append = $this->options['append_to_title'];
		}

		$this->return_data = $final_prepend.$this->return_data.$final_append;

		return $this->return_data;
	}

	/**
	* 	Get SEO title from entry
	*	See _getEntryID() for details on obtaining entry_id and its parameters
	*
	*	Fill <title> tag of page
	*
	*	@param string 	Prepend - Prepend to title. Overrides user-set defaults.
	*	@param string 	Append - Append to title. Overrides user-set defaults.
	*	@param string 	Fallback - Fallback title, Overrides user-set defaults.
	*	@return string 	SEO Title of entry
	*
	*	TO DO: Will Fallback ALWAYS be used? _defaultValue() never called unless entry_id
	*		   not set. Should use fallback if $fallback is undefined, however. Grab title
	* 		   of article as last resort, rather than fallback being last resort.
	*/
	protected function _title() {

		//Get entry_id first
		$entry_id = $this->_getEntryID();
		$prepend = $this->EE->TMPL->fetch_param('prepend');
		$append = $this->EE->TMPL->fetch_param('append');
		$fallback = htmlentities(strip_tags($this->EE->TMPL->fetch_param('fallback', '')), ENT_QUOTES);

		$title = '';

		if (!empty($entry_id)) {
			//Go ahead and actually get the title.

			$title = $this->_retrieveEntryData('title', $entry_id);

			// Additional fallback to title of entry,
			// in case we don't have an inline one.
			if ($title == '' && $fallback == '') {

				$subres = $this->EE->db->select('title')
					->from('channel_titles')
					->where('entry_id', $entry_id)
					->where('site_id', $this->site_id)
					->get();

				if ($subres->num_rows() > 0) {
					$title = $subres->row('title');
				}

			}

		}

		// Handle fallback
		if ($title != '') {
			$this->return_data = $title;
		} else {
			$this->return_data = ($fallback != '') ? $fallback : $this->_defaultValue('title');
		}

		//Add in prepend/append
		$prepend = (empty($prepend)) ? $this->options['prepend_to_title'] : $prepend;
		$append = (empty($append)) ? $this->options['append_to_title'] : $append;
		$this->return_data = $prepend.$this->return_data.$append;

		return $this->return_data;

	}

/* ----------------------------------------------- BASIC TAGS ------------------------------------------------------*/


	/**
	 * We use this to call an arbitrary data method.
	 *
	 * @param  strong $method Name of the method to call.
	 * @param  array $args    Argument list (required param, usually empty)
	 * @return string         Output if truthy, else empty string.
	 */
	public function __call($method, $args)
	{

		$method = str_replace("page_", "", $method);

		if(in_array($method, $this->funcs))
		{

			$check = $this->_isEntry();

			if($check === TRUE)
			{
				// This is an entry, so use the entry helpers!
				$call_string = "_getEntryData";

			} else if($check === FALSE) {

				// This is a page, use page helpers here!
				$call_string = "_getPageData";

			} else if($check === NULL) {
				// nothing to do here!
				return;
			}

			return call_user_func_array(array($this, $call_string), array($method));

		}


	}


	/**
	* The unified default method to get data from a page
	* @param string - the value we want to look up
	**/
	protected function _getPageData($method_string)
	{

		if ($method_string === "title")
		{
			// forget about this request, we need to get titles
			return $this->_page_title();
		}

		// Parameters
		$fallback = htmlentities(strip_tags($this->EE->TMPL->fetch_param('fallback', '')), ENT_QUOTES);
		$uri = $this->EE->TMPL->fetch_param('uri', '');

		// Get the data for this page.
		$data = ($uri != '') ? $this->_retrievePageData($method_string, $uri) : $this->_retrievePageData($method_string);

		if ($data !== FALSE && $data != '') {
			$this->return_data = $data;
		} else {
			if ($fallback == '') {
				//User-defined fallback (in global options)
				$this->return_data = $this->_defaultValue($method_string);
			} else {
				//User-defined fallback (in tag)
				$this->return_data = $fallback;
			}
		}

		return $this->return_data;

	}

	protected function _getEntryData($method_string)
	{

		if ($method_string === "title")
		{
			// forget about this request, we need to get titles
			return $this->_title();
		}


		//Get entry_id first.
		$entry_id = $this->_getEntryID();
		$fallback = htmlentities(strip_tags($this->EE->TMPL->fetch_param('fallback', '')), ENT_QUOTES);

		$data = '';

		//Go ahead and get the data.
		if (!empty($entry_id)) {

			$data = $this->_retrieveEntryData($method_string, $entry_id);

		}

		// Handle fallback
		if ($data != '') {
			$this->return_data = $data;
		} else {
			$this->return_data = ($fallback != '') ? $fallback : $this->_defaultValue($method_string);
		}

		return $this->return_data;
	}



/* ----------------------------------------------- ADDITIONAL TAGS --------------------------------------------------*/


	/**
	* 	Retrieve Canonical URL tag
	*	URL Unfortunately needs to be user-defined due to EE url schemes and template usage scenarios.
	*
	*	Produces <link rel="canonical" href="" /> tag
	*
	*	@param  string URL of page
	*	@return string URL of page within canonical tag
	*/
	function canonical() {
		$url = $this->EE->TMPL->fetch_param('url');

		if (empty($url)) {
			$this->return_data = '';
		} else {
			$this->return_data = '<link rel="canonical" href="'.$url.'" />';
		}

		return $this->return_data;
	}

	/**
	* 	Retrieve user site-wide privacy settings
	*	Where Privacy is from white-hat search-bots which pay attention to robots meta tag
	*
	*	Produces <meta name="robots" content="" /> tag of page
	*
	*	@return string  One of 4 options: Noindex,Nofollow | Noindex,Follow | Index,NoFollow | Index,Follow
	*/
	function robots() {
		if (empty($this->options['robots'])) {
			$this->options['robots'] = $this->defaults['robots'];
		}
		return $this->return_data = '<meta name="robots" content="'.$this->options['robots'].'" />';
	}

}
// END CLASS

/* End of file mod.seo.php */
/* Location: ./system/expressionengine/third_party/seo/mod.seo.php */
