<?php if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * RTE Celltype Class for EE2
 *
 * @package   Matrix
 * @author    Brandon Kelly <brandon@pixelandtonic.com>
 * @copyright Copyright (c) 2011 Pixel & Tonic, Inc
 */
class Matrix_rte_ft {

	var $info = array(
		'name' => 'Rich Text'
	);

	private $_default_settings = array(
		'field_text_direction' => 'ltr',
		'field_ta_rows' => 10,
	);

	/**
	 * Constructor
	 */
	function __construct()
	{
		$this->EE =& get_instance();
		$this->EE->load->add_package_path(APPPATH.'modules/rte');

		// -------------------------------------------
		//  Prepare Cache
		// -------------------------------------------

		if (! isset($this->EE->session->cache['matrix']['celltypes']['rte']))
		{
			$this->EE->session->cache['matrix']['celltypes']['rte'] = array();
		}
		$this->cache =& $this->EE->session->cache['matrix']['celltypes']['rte'];


		// -------------------------------------------
		//  Load RTE Buttons
		// -------------------------------------------

		$this->EE->load->model(array('rte_toolset_model','rte_tool_model'));

		// grab the rte toolset so we can get the buttons
		$toolset_id = $this->EE->rte_toolset_model->get_member_toolset();

		// get the toolset
		$toolset = $this->EE->rte_toolset_model->get($toolset_id);

		$this->settings['buttons'] = array();

		if ( ! empty($toolset['tools']) && $tools = $this->EE->rte_tool_model->get_tools($toolset['tools']))
		{
			foreach ($tools as $tool)
			{
				// skip tools that are not available to the front-end
				if ($tool['info']['cp_only'] === 'y' && REQ !== 'CP')
				{
					continue;
				}

				// add to toolbar
				$this->settings['buttons'][] = strtolower(str_replace(' ', '_', $tool['info']['name']));
			}
		}
	}

	/**
	 * Prep Settings
	 */
	private function _prep_settings(&$settings)
	{
		$settings = array_merge($this->_default_settings, $settings);
	}

	// --------------------------------------------------------------------

	/**
	 * Display Cell Settings
	 */
	function display_cell_settings($data)
	{
		$this->EE->lang->loadfile('admin_content');

		$this->_prep_settings($data);

		return array(
			array(
				lang('textarea_rows', 'rte_ta_rows'),
				form_input(array(
					'id'	=> 'field_ta_rows',
					'name'	=> 'field_ta_rows',
					'class' => 'matrix-textarea',
					'value' => $data['field_ta_rows'] ? $data['field_ta_rows'] : 10,
				)),
			),
		);

	}

	// --------------------------------------------------------------------

	/**
	 * Display Cell
	 */
	function display_cell($data)
	{
		$this->_prep_settings($this->settings);

		$this->EE->load->library('rte_lib');

		if (! isset($this->cache['displayed']))
		{
			// include matrix_rte.js
			$theme_url = $this->EE->session->cache['matrix']['theme_url'];
			$this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$theme_url.'scripts/matrix_rte.js"></script>');
			$this->EE->cp->add_to_foot('<script type="text/javascript">' . @$this->EE->rte_lib->build_js(0, '') . '</script>');

			$this->cache['displayed'] = TRUE;
		}

		$this->EE->load->add_package_path(PATH_MOD.'rte/');
		$this->EE->load->library('rte_lib');


		//prep the data
		form_prep($data, $this->cell_name);

		//use the Rte_ft::display_field method
		$cell = array(
			'data' => $this->EE->rte_lib->display_field($data, $this->cell_name, $this->settings),
			'class' => 'matrix-rte',
		);

		return $cell;
	}

	/**
	 * Validate Cell
	 */
	public function validate_cell($data)
	{
		$this->EE->load->add_package_path(PATH_MOD.'rte/');
		$this->EE->load->library('rte_lib');

		if ($this->settings['col_required'] === 'y' && $this->EE->rte_lib->is_empty($data))
		{
			return lang('col_required');
		}

		return TRUE;
	}

	/**
	 * Save Cell
	 */
	public function save_cell($data)
	{
		$this->EE->load->add_package_path(PATH_MOD.'rte/');
		$this->EE->load->library('rte_lib');

		return $this->EE->rte_lib->save_field($data);
	}

	/**
	 * Display the RTE cell.
	 *
	 * @param $data
	 * @return mixed
	 */
	public function replace_tag($data)
	{
		$rte = new Rte_ft();
		$rte->row = $this->row;
		return $rte->replace_tag($data);
	}
}
