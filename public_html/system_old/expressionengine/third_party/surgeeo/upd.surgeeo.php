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

class Surgeeo_upd {

	public $version;

	public function Surgeeo_upd()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		// Will want this for various methods
		$this->EE->load->library('layout');
		$this->EE->load->dbforge();

		// Get NSM Addon Updater config file so we
		// can keep naming and versioning consistent.
		require PATH_THIRD.'surgeeo/config.php';
		$this->version = $config['version'];
	}


	// --------------------------------------------------------------------

	/**
	 * Module Installer
	 *
	 * @access	public
	 * @return	bool
	 */
	public function install()
	{

		// Register module.
		$data = array(
			'module_name' => 'Surgeeo' ,
			'module_version' => $this->version,
			'has_cp_backend' => 'y',
			'has_publish_fields' => 'y'
		);

		$this->EE->db->insert('modules', $data);

		// Create data table
		$fields = array(
			'seo_id' => array(
				'type'           => 'INT',
				'constraint'     => '10',
				'unsigned'       => true,
				'auto_increment' => true
			),
			'channel_id' => array(
				'type'           => 'INT',
				'constraint'     => '6'
			),
			'site_id' => array(
				'type'           => 'INT',
				'constraint'     => '4'
			),
			'entry_id' => array(
				'type'           => 'INT',
				'constraint'     => '10'
			),
			'language' => array(
				'type'           => 'VARCHAR',
				'constraint'     => '6',
				'default'        => 'en'
			),
			'title' => array(
				'type'           => 'TEXT',
				'null'           => true
			),
			'keywords' => array(
				'type'           => 'TEXT',
				'null'           => true
			),
			'author' => array(
				'type'           => 'TEXT',
				'null'           => true
			),
			'gplus' => array(
				'type'           => 'TEXT',
				'null'           => true
			),
			'description' => array(
				'type'           => 'TEXT',
				'null'           => true
			),

			// Opengraph Data
			'og_description' => array(
				'type'           => 'TEXT',
				'null'           => true,
			),
			'og_url' => array(
				'type'           => 'TEXT',
				'null'           => true,
			),
			'og_img' => array(
				'type'           => 'TEXT',
				'null'           => true,
			),
			'og_type' => array(
				'type'           => 'TEXT',
				'null'           => true,
			),

			// Twitter Card Data
			'twtr_title' => array(
				'type'           => 'TEXT',
				'null'           => true,
			),
			'twtr_description' => array(
				'type'           => 'TEXT',
				'null'           => true,
			),
			'twtr_img' => array(
				'type'           => 'TEXT',
				'null'           => true,
			),
			'twtr_type' => array(
				'type'           => 'TEXT',
				'null'           => true,
			)

		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('seo_id', true);
		$this->EE->dbforge->create_table('surgeeo_data', true);

		// Create uri table
		$fields = array(
			'uri_id' => array(
				'type'           => 'INT',
				'constraint'     => '10',
				'unsigned'       => true,
				'auto_increment' => true
			),
			'site_id' => array(
				'type'           => 'INT',
				'constraint'     => '4',
			),
			'language' => array(
				'type'           => 'VARCHAR',
				'constraint'     => '6',
				'default'        => 'en'
			),
			'uri' => array(
				'type'           => 'VARCHAR',
				'constraint'     => '255',
			),
			'title' => array(
				'type'           => 'TEXT',
				'null'           => true,
			),
			'keywords' => array(
				'type'           => 'TEXT',
				'null'           => true,
			),
			'author' => array(
				'type'           => 'TEXT',
				'null'           => true
			),
			'gplus' => array(
				'type'           => 'TEXT',
				'null'           => true
			),
			'description' => array(
				'type'           => 'TEXT',
				'null'           => true,
			),

			// Opengraph Data
			'og_description' => array(
				'type'           => 'TEXT',
				'null'           => true,
			),
			'og_url' => array(
				'type'           => 'TEXT',
				'null'           => true,
			),
			'og_img' => array(
				'type'           => 'TEXT',
				'null'           => true,
			),
			'og_type' => array(
				'type'           => 'TEXT',
				'null'           => true,
			),

			// Twitter Card Data
			'twtr_title' => array(
				'type'           => 'TEXT',
				'null'           => true,
			),
			'twtr_description' => array(
				'type'           => 'TEXT',
				'null'           => true,
			),
			'twtr_img' => array(
				'type'           => 'TEXT',
				'null'           => true,
			),
			'twtr_type' => array(
				'type'           => 'TEXT',
				'null'           => true,
			)

		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('uri_id', true);
		$this->EE->dbforge->create_table('surgeeo_uri', true);

		// Create options table
		$fields = array(
			'id' => array(
				'type'           => 'INT',
				'unsigned'       => true,
				'auto_increment' => true
			),
			'site_id' => array(
				'type'           => 'INT',
				'constraint'     => '4',
			),
			'key' => array(
				'type'           => 'VARCHAR',
				'constraint'     => '255',
			),
			'value' => array(
				'type'           => 'TEXT',
			),
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('id', true);
		$this->EE->dbforge->create_table('surgeeo_options', true);

		// Register default config
		require_once('mcp.surgeeo.php');
		$mcp = new Surgeeo_mcp();

		foreach ($mcp->getDefaults() as $k => $v) {
			//INSERT if not added
			$data = array('key' => $k, 'value' => $v);
			$data['site_id'] = $this->EE->config->item('site_id');
			$this->EE->db->insert('surgeeo_options', $data);
		}


		// Register tabs
		$this->EE->layout->add_layout_tabs($this->tabs(), 'surgeeo');

		// Check for old version and migrate.
		$this->_migrateFromSEO();

		return true;

	}


	// --------------------------------------------------------------------

	/**
	 * Module Uninstaller
	 *
	 * @access	public
	 * @return	bool
	 */
	public function uninstall()
	{

		// Need this to unregister.
		$module_id = $this->EE->db->select('module_id')
			->from('modules')
			->where('module_name', 'Surgeeo')
			->get()->row('module_id');

		// Unregister the module
		$this->EE->db->delete('module_member_groups', array('module_id' => $module_id));
		$this->EE->db->delete('modules', array('module_name' => 'Surgeeo'));
		$this->EE->db->where('class', 'Surgeeo')->or_where('class', 'Surgeeo_mcp')
			->delete('actions');

		// Remove our tables.
		$this->EE->dbforge->drop_table('surgeeo_options');
		$this->EE->dbforge->drop_table('surgeeo_data');
		$this->EE->dbforge->drop_table('surgeeo_uri');

		// Remove tabs
		$this->EE->layout->delete_layout_tabs($this->old_tabs(), 'surgeeo');
		$this->EE->layout->delete_layout_tabs($this->tabs(), 'surgeeo');

		return true;

	}


	// --------------------------------------------------------------------


	// upgrade from an earlier version of the plugin when it was known as 'seo'
	private function _migrateFromSEO()
	{

		if (file_exists( PATH_THIRD."seo/upd.seo.php")) {

			// Let's find out if SEO is installed.
			$seoInstalled = !!$this->EE->db->select('COUNT(*)')
			 	->from('modules')
			 	->where('seo')
			 	->get()
			 	->num_rows();

			if ($seoInstalled) {

				// Migrate the data from the old module.
				$this->EE->db->query("INSERT INTO ".$this->EE->db->dbprefix."surgeeo_data ( SELECT * FROM ".$this->EE->db->dbprefix."seo_data )");

				$this->EE->db->query("INSERT INTO ".$this->EE->db->dbprefix."surgeeo_options ( SELECT * FROM ".$this->EE->db->dbprefix."seo_options )");

				// Uri table didn't exist in SEO.

				// Uninstall the old module.
				include_once PATH_THIRD."seo/upd.seo.php";
				$upd_obj = new Seo_upd();
				$oldversion = $upd_obj->uninstall();

			}

		}

		return true;

	}

	/**
	 * Module Updater
	 *
	 * @access	public
	 * @return	bool
	 */
	public function update($current = '')
	{

		// if `surgeeo` is updating from older `seo` version
		if ($current == false) {

			$this->_migrateFromSEO();

		}

		if (version_compare($current, '1.2.2', '<=')) {

			// New field to track site_id in options
			$field = array(
				'site_id' => array(
					'type' => 'INT',
					'constraint' => '4',
					'null' => false
				)
			);

			$this->EE->dbforge->add_column('surgeeo_options', $field);

			// Need to associate the existing options with their site_id
			$site_id = $this->EE->config->item('site_id');
			$this->EE->db->update('surgeeo_options', array( 'site_id' => $site_id ));

			// Rename the tab from 'surgeeo' to 'SurgeEO'
			$this->EE->layout->delete_layout_tabs($this->old_tabs(), 'surgeeo');
			$this->EE->layout->add_layout_tabs($this->tabs(), 'surgeeo');

		}

		// Check if we need to update from version lower than 1.5.0

		if(version_compare($current, '1.5.0', '<')) {

			// Author and Google+ fields to add
			$fields = array(
				'author' => array(
					'type'           => 'TEXT',
					'null'           => true
				),
				'gplus' => array(
					'type'           => 'TEXT',
					'null'           => true
				)
			);

			// add these columns to uris and entries
			$this->EE->dbforge->add_column('surgeeo_uri', $fields);
			$this->EE->dbforge->add_column('surgeeo_data', $fields);

		}

		if(version_compare($current, '1.6.0', '<')) {

			$fields = array(
				// Opengraph Data
				'og_description' => array(
					'type'           => 'TEXT',
					'null'           => true,
				),
				'og_url' => array(
					'type'           => 'TEXT',
					'null'           => true,
				),
				'og_img' => array(
					'type'           => 'TEXT',
					'null'           => true,
				),
				'og_type' => array(
					'type'           => 'TEXT',
					'null'           => true,
				),
				// Twitter Card Data
				'twtr_title' => array(
					'type'           => 'TEXT',
					'null'           => true,
				),
				'twtr_description' => array(
					'type'           => 'TEXT',
					'null'           => true,
				),
				'twtr_img' => array(
					'type'           => 'TEXT',
					'null'           => true,
				),
				'twtr_type' => array(
					'type'           => 'TEXT',
					'null'           => true,
				)
			);

			$newOptions = array(
				'use_default_og_description' => '',
				'use_default_og_img' => '',
				'use_default_twtr_title' => '',
				'use_default_twtr_img' => '',
				'use_default_twtr_description' => '',
				'default_twtr_title' => '',
				'default_twtr_img' => '',
				'default_og_description' => '',
				'default_og_img' => '',
				'default_twtr_description' => '',
				);

			foreach ($newOptions as $k => $v) {
				//INSERT if not added
				$data = array('key' => $k, 'value' => $v);
				$data['site_id'] = $this->EE->config->item('site_id');
				$this->EE->db->insert('surgeeo_options', $data);
			}

			$this->EE->dbforge->add_column('surgeeo_uri', $fields);
			$this->EE->dbforge->add_column('surgeeo_data', $fields);

		}

		return true;

	}

	/**
	 * Register tabs
	 *
	 * @return array Tab configuration
	 */
	public function tabs()
	{

		$tabs['SurgeEO'] = array(
		'seo_title'=> array(
			'visible'	=> 'true',
			'collapse'	=> 'false',
			'htmlbuttons'	=> 'false',
			'width'		=> '100%'
			),
		'seo_keywords'=> array(
			'visible'	=> 'true',
			'collapse'	=> 'false',
			'htmlbuttons'	=> 'false',
			'width'		=> '100%'
			),
		'seo_description'=> array(
			'visible'	=> 'true',
			'collapse'	=> 'false',
			'htmlbuttons'	=> 'true',
			'width'		=> '100%'
			)
		);

		return $tabs;

	}

	/**
	 * The old version of the tabs
	 *
	 * @return array Tab configuration
	 */
	private function old_tabs()
	{

		$tabs['surgeeo'] = array(
		'seo_title'=> array(
			'visible'	=> 'true',
			'collapse'	=> 'false',
			'htmlbuttons'	=> 'false',
			'width'		=> '100%'
			),
		'seo_keywords'=> array(
			'visible'	=> 'true',
			'collapse'	=> 'false',
			'htmlbuttons'	=> 'false',
			'width'		=> '100%'
			),
		'seo_description'=> array(
			'visible'	=> 'true',
			'collapse'	=> 'false',
			'htmlbuttons'	=> 'true',
			'width'		=> '100%'
			)
		);

		return $tabs;

	}

}
// END CLASS

/* End of file upd.seo.php */
/* Location: ./system/expressionengine/third_party/seo/upd.seo.php */
