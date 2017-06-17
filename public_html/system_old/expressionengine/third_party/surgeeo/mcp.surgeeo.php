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

class Surgeeo_mcp {

	/**
	 * Version number of the addon
	 * @var string
	 */
	public $version;

	/**
	 * Array of passed and default options
	 * @var array
	 */
	private $options = array();

	/**
	 * Default configuration for when options have no values
	 * @var array
	 */
	private $defaults = array('append_to_title' => '',
						  'prepend_to_title' => '',
						  'robots' => 'follow,index',
						  'default_title' => '',
						  'default_keywords' => '',
						  'default_description' => '',
						  'default_og_description' => '',
						  'default_og_img' => '',
						  'default_author' => '',
						  'default_gplus' => '',
						  'default_twtr_title' => '',
						  'default_twtr_img' => '',
						  'default_twtr_description' => '',
						  'use_default_title' => '',
						  'use_default_keywords' => '',
						  'use_default_description' => '',
						  'use_default_author' => '',
						  'use_default_gplus' => '',
						  'use_default_og_description' => '',
						  'use_default_og_img' => '',
						  'use_default_twtr_title' => '',
						  'use_default_twtr_img' => '',
						  'use_default_twtr_description' => ''
						  );

	/**
	 * Whether to use the default or options array
	 * @var boolean
	 */
	private $usedefaults = false;

	/**
	 * Url to the addon control panel pages
	 * @var boolean|string
	 */
	private $base_url = false;

	/**
	 * Url to the addon control panel pages
	 * @var boolean|string
	 */
	private $base_frontend_url = false;

	/**
	 * Which MSM site is currently active
	 * @var integer
	 */
	private $site_id = 0;

	/**
	 * Simplistic cache store.
	 * @var array
	 */
	private $cache = array();

	public function getDefaults(){
		return $this->defaults;
	}

	/**
	* Setup configuration
	*/
	public function Surgeeo_mcp() {

		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		// Get NSM Addon Updater config file so we
		// can keep naming and versioning consistent.
		require PATH_THIRD.'surgeeo/config.php';
		$this->version = $config['version'];

		// We want these to be site-specific.
		$this->site_id = $this->EE->config->item('site_id');

		//Get Options (configuration) on class load
		$res = $this->EE->db->select()
			->from('surgeeo_options')
			->where('site_id', $this->site_id)
			->get();

		if ($res->num_rows() > 0) {

			foreach($res->result_array() as $row) {
				$this->options[$this->site_id][$row['key']] = $row['value'];
			}

			$this->usedefaults = false;

		} else {

			//Revert to defaults if no results found
			$this->usedefaults = true;

		}

		//Base URL for forms, linking, redirecting
		$this->base_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=surgeeo';

		//Set nav in CP
		$this->EE->cp->set_right_nav(array(
			'Options'	=> $this->base_url,
			'Pages'	=> $this->base_url.AMP.'method=pages',
			'Import CSV'	=> $this->base_url.AMP.'method=import'
		));

		//Front-end base url for URI parsing (pages)
		$this->base_frontend_url = $this->EE->config->item('base_url');

		// -------------------------------------------
		//  Prepare Cache
		// -------------------------------------------

		if (! isset($this->EE->session->cache['surgeeo']))
		{
			$this->EE->session->cache['surgeeo'] = array();
		}
		$this->cache =& $this->EE->session->cache['surgeeo'];
	}

	/**
	* View/Edit Configuration
	*/
	public function index() {
		$this->EE->cp->cp_page_title = lang('surgeeo_module_name');

		if($this->usedefaults) {
			return $this->EE->load->view('index', $this->defaults, TRUE);
		} else {
			return $this->EE->load->view('index', $this->options[$this->site_id], TRUE);
		}
	}

	/**
	* Update configuration
	*/
	public function update() {
		$this->EE->cp->cp_page_title = lang('surgeeo_module_name');

		foreach($_POST as $k => $v) {

			// Don't process the submit value..
			if($k == 'submit') continue;

			// Use presence in the options array for this
			// site_id as an indication of it's being in theseo_id
			// database, since it's grabbed from there during
			// constructor.
			if (isset($this->options[$this->site_id][$k])) {

				//UPDATE, if changed
				if ($this->options[$this->site_id][$k] != $v) {

					$data = array('value' => $v);
					$this->EE->db->where('key', $k)
						->where('site_id', $this->site_id)
						->update('surgeeo_options', $data);

				}

			} else {

				//INSERT if not added
				$data = array('key' => $k, 'value' => $v);
				$data['site_id'] = $this->site_id;
				$this->EE->db->insert('surgeeo_options', $data);

			}

		}

		$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('options_updated'));
		$this->EE->functions->redirect($this->base_url);

	}

	/**
	* View/edit pages
	*/
	public function pages() {

		$this->EE->cp->cp_page_title = lang('pages');
		$this->EE->cp->add_to_head('<link rel="stylesheet" type="text/css" href="'.$this->_theme_url().'css/surgeeo.css?'.$this->version.'" />');
		$this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$this->_theme_url().'js/surgeeo.js?'.$this->version.'"></script>');

		//Get pages
		//No pagination for time being
		$res = $this->EE->db->select()
			->from('surgeeo_uri')
			->where('site_id', $this->site_id)
			->get();

		$pages = ($res->num_rows > 0) ? $res->result() : false;

		$data = array(
			'pages' => $pages,
			'ajax_url' => $this->base_url.AMP.'method=ajax_delete_page'
		);

		return $this->EE->load->view('pages', $data, TRUE);

	}

	/**
	* Update pages
	*/
	public function update_pages() {
		/*
		uri_id, site_id, language, uri, title, keywords, description
		$pages[0]['uri']
				 ['title']
				 ['keywords']
				 ['description']
		*/

		//pages isset?
		$pages = $this->EE->input->post('pages');
		if(is_array($pages)) {
			foreach($pages as $page) {
				//Don't do anything if not a valid page entry
				if( $this->_isValid($page) === FALSE ) { continue; }

				if( $this->_isNewEntry($page) === TRUE ) {
					//Create New
					$data = array(
					   'site_id' => $this->site_id,
					   'uri' => $this->_parseUri($this->base_frontend_url, strip_tags($page['uri'])),
					   'title' => strip_tags($page['title']),
					   'keywords' => strip_tags($page['keywords']),
					   'author' => strip_tags($page['author']),
					   'gplus' => strip_tags($page['gplus']),
					   'description' => strip_tags($page['description']),
					   'og_description' => strip_tags($page['og_description']),
					   'og_img' => strip_tags($page['og_img']),
					   'og_url' => strip_tags($page['og_url']),
					   'og_type' => strip_tags($page['og_type']),
					   'twtr_title' => strip_tags($page['twtr_title']),
					   'twtr_img' => strip_tags($page['twtr_img']),
					   'twtr_description' => strip_tags($page['twtr_description']),
					   'twtr_type' => strip_tags($page['twtr_type'])
					);
					$this->EE->db->insert('surgeeo_uri', $data);
				} else {
					//Update
					$data = array(
					   'site_id' => $this->site_id,
					   'uri' => $this->_parseUri($this->base_frontend_url, strip_tags($page['uri'])),
					   'title' => strip_tags($page['title']),
					   'keywords' => strip_tags($page['keywords']),
					   'author' => strip_tags($page['author']),
					   'gplus' => strip_tags($page['gplus']),
					   'description' => strip_tags($page['description']),
					   'og_description' => strip_tags($page['og_description']),
					   'og_img' => strip_tags($page['og_img']),
					   'og_url' => strip_tags($page['og_url']),
					   'og_type' => strip_tags($page['og_type']),
					   'twtr_title' => strip_tags($page['twtr_title']),
					   'twtr_img' => strip_tags($page['twtr_img']),
					   'twtr_description' => strip_tags($page['twtr_description']),
					   'twtr_type' => strip_tags($page['twtr_type'])
					);
					$this->EE->db->where('uri_id', $page['uri_id'])->update('surgeeo_uri', $data);
				}
			}
		}
		$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('pages_updates'));
		$this->EE->functions->redirect($this->base_url.AMP.'method=pages');
	}

	/**
	* Import From CSV file with properties
	*/

	public function import() {

		$this->EE->cp->cp_page_title = lang('import');
		$this->EE->cp->add_to_head('<link rel="stylesheet" type="text/css" href="'.$this->_theme_url().'css/surgeeo.css?'.$this->version.'" />');

		$res = $this->EE->db->select()
			->from('surgeeo_uri')
			->where('site_id', $this->site_id)
			->get();

		return $this->EE->load->view('import', null, TRUE);

	}

	public function uploadCSV() {

		$this->EE->cp->cp_page_title = lang('surgeeo_module_name');

		// make sure we have no upload errors and the file is a CSV

		/**
		* Verify the MIME type even if it's not the most reliable
		* We're just going to trust the admin to not upload non-CSV files here
		**/
		$mimes = array('application/vnd.ms-excel','text/plain','text/csv','text/tsv');

		if(!in_array($_FILES['file_name']['type'],$mimes)){
			$this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('import_invalid'));
			$this->EE->functions->redirect($this->base_url.AMP.'method=import');
			return;
		}


		$handle = fopen($_FILES['file_name']['tmp_name'], "r");
		while (($data = fgetcsv($handle, 1000, $_POST['import_delimiter'])) !== false) {

			foreach ($data as $k => $v) {
				$data[$k] = trim(strip_tags($data[$k]));
			}

			// build the data and insert it into the requested type
			if($_POST['import_type'] == 'p') {
				// We are going to insert a page

				// Make sure the input is the right length
				if(count($data) != 7) {
					$this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('import_invalid'));
					$this->EE->functions->redirect($this->base_url.AMP.'method=import');
					return;
				}
				$page = array(
					'site_id' => $data[0],
					'uri' => $data[1],
					'title' => $data[2],
					'keywords' => $data[3],
					'author' => $data[4],
					'gplus' => $data[5],
					'description' => $data[6]
				);
				$this->EE->db->insert('surgeeo_uri', $page);

			}

			if($_POST['import_type'] == 'e') {
				// We are going to inset an entry

				// Make sure the input is the right length
				if(count($data) != 8) {
					$this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('import_invalid'));
					$this->EE->functions->redirect($this->base_url.AMP.'method=import');
					return;
				}
				$entry = array(
					'channel_id' => $data[0],
					'site_id' => $data[1],
					'entry_id' => $data[2],
					/* 'language' => 'en', */
					'title' => $data[3],
					'keywords' => $data[4],
					'author' => $data[5],
					'gplus' => $data[6],
					'description' => $data[7]
				);
				$sql = $this->EE->db->insert_string('exp_surgeeo_data', $entry);
				$this->EE->db->query($sql);
			}

		}

		fclose($handle);

		$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('import_success'));
		$this->EE->functions->redirect($this->base_url.AMP.'method=import');

	}


	/**
	 * Delete pages items via AJAX
	 */
	public function ajax_delete_page() {
		if(!$this->EE->input->is_ajax_request()) {
			$this->EE->functions->redirect($this->base_url.AMP.'method=pages');
			exit();
		}

		$page_id = $this->EE->input->get('page_id');

		if($page_id === '' || $page_id === FALSE || $page_id === NULL) {
			$resp = array('status' => 'error', 'message' => 'No page ID found');
		} else {
			$this->EE->db->delete('surgeeo_uri', array('uri_id' => $page_id));
			$resp = array('status' => 'success', 'message' => 'Page Deleted');
		}

		$this->EE->output->send_ajax_response($resp);
		exit();
	}

	/**
	* Test if page is valid (Validation)
	*
	* Only a URI is required data at this point
	*
	* @param $page array Data to be written
	* @return bool Whether or not this is valid pages data
	*/
	private function _isValid($page) {

		if (!isset($page['uri']) || $page['uri'] === '') {
			// Not valid (Not URI set)
			return false;
		}

		//Valid - Has a URI
		return true;

	}

	/**
	* Test if page is a new entry
	*
	* @param array $page Data about to be written
	* @return bool Whether or not that entry already exists.
	*/
	private function _isNewEntry($page) {

		//Test if valid data given
		if (isset($page['uri_id']) && is_numeric($page['uri_id'])) {

			//Test database for entry
			$res = $this->EE->db->select('uri_id')
				->from('surgeeo_uri')
				->where('uri_id', $page['uri_id'])
				->get();

			if( $res->num_rows() > 0 ) {
				// Entry already exists..
				return false;
			}

		}

		// It's a new entry then..
		return true;

	}

	/**
	 * Parse the URI provided by the user
	 *
	 * Formats URI and removes site base_url, also accounting for EE
	 * being in sub-directories. This allows $this->EE->uri->uri_string()
	 * to be compared to user-inputed URI.
	 *
	 * @param string $base_url The base url to the CP pages
	 * @param string $input_url The url to be stripped of $base_url
	 * @return string Stripped url.
	 */
	private function _parseUri($base_url, $input_url) {

		$baseParsed = parse_url($base_url);
		$inputParsed = parse_url($input_url);

		//Remove trailing slash in all instances.
		$baseParsed['path'] = isset($baseParsed['path']) ? rtrim($baseParsed['path'], '/') : '/';
		$inputParsed['path'] = isset($inputParsed['path']) ? rtrim($inputParsed['path'], '/') : '/';

		//Add leading slash if needed
		if(strpos($inputParsed['path'], '/') !== 0) {
			$inputParsed['path'] = '/'.$inputParsed['path'];
		}

		//Remove baseURL sections to keep final url segment
		if($baseParsed['path'] !== '/') {
			$final_url = str_replace($baseParsed['path'], '', $inputParsed['path']);
		} else {
			$final_url = $inputParsed['path'];
		}

		return $final_url;

	}

	/**
	 * Get and cache the theme URL
	 *
	 * @return string The theme url.
	 */
	private function _theme_url() {

		if (!isset($this->cache['theme_url'])) {

			// Figure it out
			$theme_folder_url = defined('URL_THIRD_THEMES') ? URL_THIRD_THEMES : $this->EE->config->slash_item('theme_folder_url').'third_party/';

			// Store in cache
			$this->cache['theme_url'] = $theme_folder_url.'surgeeo/';

		}

		// Retrieve from cache
		return $this->cache['theme_url'];

	}

}
// END CLASS

/* End of file mcp.seo.php */
/* Location: ./system/expressionengine/thirdparty/seo/mcp.seo.php */
