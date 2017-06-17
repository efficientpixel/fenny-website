<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Entry Revisions Plugin
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Plugin
 * @author		Bhashkar Yadav
 * @link		http://www.sidd3.com/entry-revisions-plugin
 */

$plugin_info = array(
	'pi_name'		=> 'Entry Revisions',
	'pi_version'	=> '1.0.1',
	'pi_author'		=> 'Bhashkar Yadav',
	'pi_author_url'	=> 'http://www.sidd3.com',
	'pi_description'=> 'Entry Revisions',
	'pi_usage'		=> Entry_revisions::usage()
);

class Entry_revisions {

	public $return_data;
	private $current_site = 1;

	public function __construct()
	{
		$this->EE =& get_instance();
		$this->current_site = $this->EE->config->item('site_id');

		/****
		Channel Field API to get custom fields of channel
		****/
		$this->EE->load->library('api');
		$this->EE->api->instantiate('channel_fields');
		$fields = $this->EE->api_channel_fields->fetch_custom_channel_fields();

		$custom_channel_fields = reset( $fields['custom_channel_fields'] );
		
		// Parameter # channel [Optional]
		$channel_id = $this->EE->TMPL->fetch_param('channel') ? $this->_channel_exists($this->EE->TMPL->fetch_param('channel')) : 0; 

		// Parameter # author_id [Optional]
		$author_id = $this->EE->TMPL->fetch_param('author_id', 0);
		
		// Parameter # entry_id [Optional]
		$entry_id = $this->EE->TMPL->fetch_param('entry_id', 0);
		
		// Parameter # site_id [Optional]
		$site_id = $this->EE->TMPL->fetch_param('site_id') ? $this->EE->TMPL->fetch_param('site_id') : $this->EE->config->item('site_id');
		
		// Parameter # url_title [Optional]
		$url_title = $this->EE->TMPL->fetch_param('url_title', '');

		// Parameter # status [Optional : Default = open]
		$status = $this->EE->TMPL->fetch_param('status', 'open');
		
		// Parameter # version_date_sort [Optional : Default = desc]		
		$version_date_sort = $this->EE->TMPL->fetch_param('version_date_sort', 'desc'); 

		// Parameter # limit [Optional : Default = 10]
		$limit = $this->EE->TMPL->fetch_param('limit', 10);

		//if($entry_id == 0 OR $url_title == '' )
			//$this->return_data = '';

		$sql = "SELECT exp_entry_versioning.version_data, exp_entry_versioning.version_date, exp_entry_versioning.entry_id FROM exp_entry_versioning
				LEFT JOIN exp_channel_titles ON exp_channel_titles.entry_id = exp_entry_versioning.entry_id ";
			
		$cond = ' WHERE 1 = 1';
		if( $channel_id )
			$cond .= " AND exp_entry_versioning.channel_id IN ($channel_id)";
		if( $author_id )
			$cond .= " AND exp_entry_versioning.author_id = $author_id";
		if( $entry_id )
			$cond .= " AND exp_entry_versioning.entry_id IN ('".str_replace('|', "','", $this->EE->db->escape_str($entry_id))."')";

		$cond .= " AND exp_channel_titles.site_id = $site_id";
		$cond .= " AND exp_channel_titles.status IN ('".str_replace('|', "','", $this->EE->db->escape_str($status))."')";
		
		if( $url_title != '' )
			$cond .= " AND exp_channel_titles.url_title IN  ('".str_replace('|', "','", $this->EE->db->escape_str($url_title))."')";

		$cond .= ' ORDER BY exp_entry_versioning.version_date '.$version_date_sort;
		$cond .= ' LIMIT '.$limit;
		
		$query = $this->EE->db->query($sql.$cond);

		
		if( $query->num_rows() > 0 )
		{
			$total_counts = $query->num_rows();
			$count = 1;
			foreach($query->result_array() as $k => $row)
			{
				$version_data = unserialize($row['version_data']);
				$tags[$k]['revision:title'] = $version_data['title'];
				$tags[$k]['revision:url_title'] = $version_data['url_title'];
				$tags[$k]['revision:count'] = $count;
				$tags[$k]['revision:total_counts'] = $total_counts;
				$tags[$k]['revision:version_date'] = $row['version_date'];
				$tags[$k]['revision:entry_id'] = $row['entry_id'];

				foreach( $custom_channel_fields as $field_name => $field_id )
				{
					if( isset($version_data['field_id_'.$field_id]) )
						$tags[$k]['revision:'.$field_name] = $version_data['field_id_'.$field_id];
				}
			}

			$this->return_data = $this->EE->TMPL->parse_variables($this->EE->TMPL->tagdata, $tags);
			$query->free_result();
		}
		else
		{
			$this->return_data = $this->EE->TMPL->no_results();
		}
	}

	private function _channel_exists($channel = FALSE)
	{
		if( $channel == FALSE ) return FALSE;

		$sql = "SELECT GROUP_CONCAT(CAST(channel_id AS CHAR(7)) SEPARATOR ',') AS channel_ids FROM exp_channels WHERE site_id = '{$this->current_site}' AND (channel_id IN ('".str_replace('|', "','", $this->EE->db->escape_str($channel))."') OR channel_name IN ('".str_replace('|', "','", $this->EE->db->escape_str($channel))."'))";

		$results = $this->EE->db->query($sql);

		return ($results->num_rows() > 0) ? $results->row('channel_ids') : FALSE;
	}

	public static function usage()
	{
			$usase = 'Parameters:

			channel [Optional]: Name of the channel. You can use the pipe character ("|") to query by multiple channels.		
					
			author_id [Optional]: The member id of the author of the entry or entry revisions.

			entry_id [Optional]: Entry ID to get the revisions. You can use the pipe character ("|") to query by multiple entry IDs.

			site_id [Optional]: Default will be current site.

			url_title [Optional]: URL title of entry which revisions will be fetched.

			status [Optional]: Entry status. Default = "open". You can use the pipe character ("|") to query by multiple statuses.

			limit [Optional]: Default = 10

			version_date_sort [Optional]: asc/desc. Default = desc

			Variables:

			{revision:title}: Title of entry revision.

			{revision:url_title}: URL Title of entry revision.

			{revision:count}: Revision counts - 1, 2, 3...

			{revision:total_counts}: Total number of revisions.

			{revision:version_date format="%Y %m %d"}: Entry revision date. See Date Variable Formatting for more information.

			{revision:entry_id}: Entry ID

			{revision:custom_field_name}: You can get the custom fields value for entry revisions. It will not work for the field type which stores data separately within its own database tables like Relationship, Matrix, Grid (EE2.6+) etc.';
		return $usase;
	}
}


/* End of file pi.entry_revisions.php */
/* Location: /system/expressionengine/third_party/entry_revisions/pi.entry_revisions.php */