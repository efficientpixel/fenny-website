<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD . 'stay_up_forever/config.php';

/**
 * Stay Up Forever: take control of EE's session limit.
 * @author John D Wells <http://johndwells.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD license
 * @link	http://johndwells.com/software/stay-up-forever
 */
class Stay_up_forever_ext {

	var $name			= STAYUPFOREVER_NAME;
	var $version		= STAYUPFOREVER_VER;
	var $settings_exist	= 'y';
	var $docs_url		= 'http://johndwells.com/software/stay-up-forever';

	var $settings		= array();
	var $EE;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
	}
	// END

	
	/**
	 * Activate Extension
	 * @return void
	 */
	public function activate_extension()
	{
		$data = array(
			'class'		=> __CLASS__,
			'settings'	=> serialize($this->settings),
			'priority'	=> 1,
			'version'	=> $this->version,
			'class'		=> __CLASS__,
			'hook'		=> 'sessions_start',
			'method'	=> 'sessions_start',
			'enabled'	=> 'y'
		);
		
		$this->EE->db->insert('extensions', $data);
	}
	// END


	/**
	 * Disable Extension
	 *
	 * @return void
	 */
	public function disable_extension()
	{
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->delete('extensions');
	}
	// END


	/**
	 * Sessions_start hook
	 *
	 * @param 	object	Session object
	 * @return 	object	Modified/unmodified session object
	 */
	function sessions_start($sess)
	{
		$this->_get_settings();
		$sess->cpan_session_len = ($this->settings['cpan_session_len'] > 59) ? $this->settings['cpan_session_len'] : $sess->cpan_session_len;
		$sess->user_session_len = ($this->settings['user_session_len'] > 59) ? $this->settings['user_session_len'] : $sess->user_session_len;
		$sess->session_length = (REQ == 'CP') ? $sess->cpan_session_len : $sess->user_session_len;
		return $sess;
	}
	// END


	/**
	 * Settings
	 *
	 * @return 	void
	 */
	function settings()
	{
		$settings = array();
		
		$settings['cpan_session_len']	= 3600;
		$settings['user_session_len']   = 7200;

		return $settings;
	}
	// END
	

	/**
	 * Update Extension
	 *
	 * @param 	string	String value of current version
	 * @return 	mixed	void on update / false if none
	 */
	public function update_extension($current = '')
	{
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}

		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->update(
					'extensions', 
					array('version' => $this->version)
		);
	}
	// END


	/**
	 * Get settings from db
	 *
	 * @return void
	 */
	protected function _get_settings()
	{
		// if settings are already in session cache, use those
		if (isset($this->EE->session->cache['stayupforever']))
		{
			$this->settings = $this->EE->session->cache['stayupforever'];
			return;
		}

		$this->EE->db
			->select('settings')
			->from('extensions')
			->where(array('enabled' => 'y', 'class' => __CLASS__ ))
			->limit(1);
		$query = $this->EE->db->get();
		
		if ($query->num_rows() > 0)
		{
			$this->settings = unserialize($query->row()->settings);
		}
		
		// now set to session for subsequent calls
		$this->EE->session->cache['stayupforever'] = $this->settings;
	}
	// END

}
// END CLASS
	
/* End of file ext.stay_up_forever.php */ 
/* Location: ./system/expressionengine/third_party/stay_up_forever/ext.stay_up_forever.php */