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

class Surgeeo_tab {

	function Surgeeo_tab()
	{
		$this->EE =& get_instance();
		$this->site_id = $this->EE->config->item('site_id');
	}

	function publish_tabs($channel_id, $entry_id = '')
	{
		$settings = array();

		$seo_title = '';
		$seo_keywords = '';
		$seo_description = '';
		$seo_author = '';
		$seo_gplus = '';

		$og_description = '';
		$og_url = '';
		$og_img = '';
		$og_type = '';

		$twtr_title = '';
		$twtr_img = '';
		$twtr_description = '';
		$twtr_type = '';

		if(!empty($entry_id)) {

			$res = $this->EE->db->select()->from('surgeeo_data')
										  ->where('site_id', $this->site_id)
										  ->where('channel_id', $channel_id)
										  ->where('entry_id', $entry_id)
										  ->get();

			if($res->num_rows() > 0) {
				$seo_title = ($res->row('title'));				//removed htmlentities()
				$seo_keywords = ($res->row('keywords'));		//removed htmlentities()
				$seo_description = ($res->row('description'));	//removed htmlentities()
				$seo_author = ($res->row('author'));	//removed htmlentities()
				$seo_gplus = ($res->row('gplus'));	//removed htmlentities()

				$og_description = ($res->row('og_description'));
				$og_url = ($res->row('og_url'));
				$og_type = ($res->row('og_type'));
				$og_img = ($res->row('og_img'));

				$twtr_title = ($res->row('twtr_title'));
				$twtr_img = ($res->row('twtr_img'));
				$twtr_description = ($res->row('twtr_description'));
				$twtr_type = ($res->row('twtr_type'));
			}
		}

		$settings[] = array(
			'field_id'             => 'seo_title',
			'field_label'          => "Title",
			'field_required'       => 'n',
			'field_data'           => $seo_title,
			'field_list_items'     => '',
			'field_fmt'            => '',
			'field_instructions'   => 'Data for the title field',
			'field_show_fmt'       => 'n',
			'field_pre_populate'   => 'n',
			'field_text_direction' => 'ltr',
			'field_type'           => 'text',
			'field_maxl'		   => 1024,
			'field_channel_id'     => $channel_id
		);

		$settings[] = array(
			'field_id'             => 'seo_keywords',
			'field_label'          => "Keywords",
			'field_required'       => 'n',
			'field_data'           => $seo_keywords,
			'field_list_items'     => '',
			'field_fmt'            => '',
			'field_instructions'   => 'Data for the keywords meta field',
			'field_show_fmt'       => 'n',
			'field_pre_populate'   => 'n',
			'field_text_direction' => 'ltr',
			'field_type'           => 'text',
			'field_maxl'		   => 1024,
			'field_channel_id'     => $channel_id
		);

		$settings[] = array(
			'field_id'             => 'seo_description',
			'field_label'          => "Description",
			'field_required'       => 'n',
			'field_data'           => $seo_description,
			'field_list_items'     => '',
			'field_fmt'            => '',
			'field_instructions'   => 'Data for the description meta field',
			'field_show_fmt'       => 'n',
			'field_pre_populate'   => 'n',
			'field_text_direction' => 'ltr',
			'field_type'           => 'textarea',
			'field_ta_rows'		   => 5,
			'field_channel_id'     => $channel_id
		);

		$settings[] = array(
			'field_id'             => 'seo_author',
			'field_label'          => "Author Information",
			'field_required'       => 'n',
			'field_data'           => $seo_author,
			'field_list_items'     => '',
			'field_fmt'            => '',
			'field_instructions'   => 'Data for the author meta field (Name, Email)',
			'field_show_fmt'       => 'n',
			'field_pre_populate'   => 'n',
			'field_text_direction' => 'ltr',
			'field_type'           => 'text',
			'field_maxl'		   => 1024,
			'field_channel_id'     => $channel_id
		);

		$settings[] = array(
			'field_id'             => 'seo_gplus',
			'field_label'          => "Google+ Profile",
			'field_required'       => 'n',
			'field_data'           => $seo_gplus,
			'field_list_items'     => '',
			'field_fmt'            => '',
			'field_instructions'   => 'Google+ profile URL of Author',
			'field_show_fmt'       => 'n',
			'field_pre_populate'   => 'n',
			'field_text_direction' => 'ltr',
			'field_type'           => 'text',
			'field_maxl'		   => 1024,
			'field_channel_id'     => $channel_id
		);

		// Open Graph Data

		$settings[] = array(
			'field_id'             => 'og_description',
			'field_label'          => "Open Graph - Description",
			'field_required'       => 'n',
			'field_data'           => $og_description,
			'field_list_items'     => '',
			'field_fmt'            => '',
			'field_instructions'   => 'Description to use for Open Graph data',
			'field_show_fmt'       => 'n',
			'field_pre_populate'   => 'n',
			'field_text_direction' => 'ltr',
			'field_type'           => 'textarea',
			'field_ta_rows'		   => 5,
			'field_channel_id'     => $channel_id
		);

		$settings[] = array(
			'field_id'             => 'og_url',
			'field_label'          => "Open Graph - URL",
			'field_required'       => 'n',
			'field_data'           => $og_url,
			'field_list_items'     => '',
			'field_fmt'            => '',
			'field_instructions'   => 'URL to use for Open Graph data',
			'field_show_fmt'       => 'n',
			'field_pre_populate'   => 'n',
			'field_text_direction' => 'ltr',
			'field_type'           => 'text',
			'field_maxl'		   => 1024,
			'field_channel_id'     => $channel_id
		);

		$settings[] = array(
			'field_id'             => 'og_img',
			'field_label'          => "Open Graph - Image",
			'field_required'       => 'n',
			'field_data'           => $og_img,
			'field_list_items'     => '',
			'field_fmt'            => '',
			'field_instructions'   => 'Image URL to use for Open Graph data',
			'field_show_fmt'       => 'n',
			'field_pre_populate'   => 'n',
			'field_text_direction' => 'ltr',
			'field_type'           => 'text',
			'field_maxl'		   => 1024,
			'field_channel_id'     => $channel_id
		);

		$settings[] = array(
			'field_id'             => 'og_type',
			'field_label'          => "Open Graph - Type",
			'field_required'       => 'n',
			'field_data'           => $og_type,
			'field_list_items'     => '',
			'field_fmt'            => '',
			'field_instructions'   => 'Media type to use for Open Graph data',
			'field_show_fmt'       => 'n',
			'field_pre_populate'   => 'n',
			'field_text_direction' => 'ltr',
			'field_type'           => 'text',
			'field_maxl'		   => 1024,
			'field_channel_id'     => $channel_id
		);

		// Twitter Card Data
		$settings[] = array(
			'field_id'             => 'twtr_type',
			'field_label'          => "Twitter Card - Type",
			'field_required'       => 'n',
			'field_data'           => $twtr_type,
			'field_list_items'	   => array('summary'=>'Default', 'summary_large_image'=>'Image + Summary', 'photo'=>'Large Image'),
			'field_instructions'   => 'Media type to use for the Twitter Card',
			'field_pre_populate'   => 'n',
			'field_text_direction' => 'ltr',
			'field_type'           => 'select',
			'field_channel_id'     => $channel_id
		);

		$settings[] = array(
			'field_id'             => 'twtr_title',
			'field_label'          => "Twitter Card - Title",
			'field_required'       => 'n',
			'field_data'           => $twtr_title,
			'field_list_items'     => '',
			'field_fmt'            => '',
			'field_instructions'   => 'Title to use on the Twitter Card',
			'field_show_fmt'       => 'n',
			'field_pre_populate'   => 'n',
			'field_text_direction' => 'ltr',
			'field_type'           => 'text',
			'field_maxl'		   => 1024,
			'field_channel_id'     => $channel_id
		);

		$settings[] = array(
			'field_id'             => 'twtr_img',
			'field_label'          => "Twitter Card - Image",
			'field_required'       => 'n',
			'field_data'           => $twtr_img,
			'field_list_items'     => '',
			'field_fmt'            => '',
			'field_instructions'   => 'Image to use on the Twitter Card',
			'field_show_fmt'       => 'n',
			'field_pre_populate'   => 'n',
			'field_text_direction' => 'ltr',
			'field_type'           => 'text',
			'field_maxl'		   => 1024,
			'field_channel_id'     => $channel_id
		);

		$settings[] = array(
			'field_id'             => 'twtr_description',
			'field_label'          => "Twitter Card - Description",
			'field_required'       => 'n',
			'field_data'           => $twtr_description,
			'field_list_items'     => '',
			'field_fmt'            => '',
			'field_instructions'   => 'Description to use on the Twitter Card',
			'field_show_fmt'       => 'n',
			'field_pre_populate'   => 'n',
			'field_text_direction' => 'ltr',
			'field_type'           => 'textarea',
			'field_ta_rows'		   => 5,
			'field_channel_id'     => $channel_id
		);




		return $settings;
	}

	function validate_publish($params)
	{
		//Of course I trust you!!!
		return true;
	}

	function publish_data_db($params)
	{
		//Do database stuff here - but only if there's data to add
		if(!isset($params['mod_data']['seo_title']) && !isset($params['mod_data']['seo_keywords']) && !isset($params['mod_data']['seo_description'])) {
			return;
		}

		//This already exist?
		$res = $this->EE->db->select()->from('surgeeo_data')
									  ->where('site_id', $params['meta']["site_id"])
									  ->where('channel_id', $params['meta']["channel_id"])
									  ->where('entry_id', $params["entry_id"])
									  ->get();

		if($res->num_rows() > 0) {
			//updating
			$data = array(
				'title' => trim($params['mod_data']['seo_title']),
				'keywords' => trim($params['mod_data']['seo_keywords']),
				'description' => trim($params['mod_data']['seo_description']),
				'gplus' => trim($params['mod_data']['seo_gplus']),
				'author' => trim($params['mod_data']['seo_author']),

				'og_description' => trim($params['mod_data']['og_description']),
				'og_url' => trim($params['mod_data']['og_url']),
				'og_img' => trim($params['mod_data']['og_img']),
				'og_type' => trim($params['mod_data']['og_type']),

				'twtr_description' => trim($params['mod_data']['twtr_description']),
				'twtr_title' => trim($params['mod_data']['twtr_title']),
				'twtr_img' => trim($params['mod_data']['twtr_img']),
				'twtr_type' => trim($params['mod_data']['twtr_type'])

			);

			$sql = $this->EE->db->update_string('surgeeo_data', $data, "seo_id = ".$res->row('seo_id'));
			$this->EE->db->query($sql);

		} else {
			//new entry
			$data = array(
				'channel_id' => $params['meta']["channel_id"],
				'site_id' => $params['meta']["site_id"],
				'entry_id' => $params["entry_id"],
				/* 'language' => 'en', */
				'title' => trim($params['mod_data']['seo_title']),
				'keywords' => trim($params['mod_data']['seo_keywords']),
				'gplus' => trim($params['mod_data']['seo_gplus']),
				'author' => trim($params['mod_data']['seo_author']),
				'description' => trim($params['mod_data']['seo_description']),

				'og_description' => trim($params['mod_data']['og_description']),
				'og_url' => trim($params['mod_data']['og_url']),
				'og_img' => trim($params['mod_data']['og_img']),
				'og_type' => trim($params['mod_data']['og_type']),

				'twtr_description' => trim($params['mod_data']['twtr_description']),
				'twtr_title' => trim($params['mod_data']['twtr_title']),
				'twtr_img' => trim($params['mod_data']['twtr_img']),
				'twtr_type' => trim($params['mod_data']['twtr_type'])
			);

			$sql = $this->EE->db->insert_string('exp_surgeeo_data', $data);
			$this->EE->db->query($sql);
		}
	}

	function publish_data_delete_db($params)
	{
		//delete data when entry deleted
		//This should check for site id?
		foreach($params["entry_ids"] as $key => $id) {
			$this->EE->db->where('entry_id', $id)->delete('surgeeo_data');
		}
	}
}
// END CLASS

/* End of file tab.seo.php */
/* Location: ./system/expressionengine/third_party/seo/tab.seo.php */
