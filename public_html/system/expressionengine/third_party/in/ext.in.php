<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


if ( ! defined('IN_VERSION') )
{
	include( PATH_THIRD . 'in/config.php' );
}

class In_ext
{
	public $settings 		= array();
	public $description		= 'Allows snippets to be dynamically inserted from template files.';
	public $docs_url		= 'http://www.causingeffect.com/software/expressionengine/in';
	public $name			= 'In';
	public $settings_exist	= 'n';
	public $version			= IN_VERSION;

	/**
	 * Constructor
	 *
	 * @param string $settings array or empty string if none exist.
	 */
	public function __construct( $settings = '' )
	{
		$this->EE = get_instance();
		$this->settings = $settings;
	}

	/**
	 * Activate the extension by entering it into the exp_extensions table
	 *
	 * @return void
	 */
	public function activate_extension()
	{
		//settings
		$this->settings = array();

		$hooks = array(
			'template_fetch_template'		=> 'template_pre_parse'
		);

		foreach ( $hooks as $hook => $method )
		{
			//sessions end hook
			$data = array(
				'class'		=> __CLASS__,
				'method'	=> $method,
				'hook'		=> $hook,
				'settings'	=> serialize( $this->settings ),
				'priority'	=> 9,
				'version'	=> $this->version,
				'enabled'	=> 'y'
			);
			$this->EE->db->insert( 'extensions', $data );
		}
	}

	/**
	 * Disables the extension by removing it from the exp_extensions table.
	 *
	 * @return void
	 */
	function disable_extension()
	{
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->delete('extensions');
	}

	/**
	 * Updates the extension by performing any necessary db updates when the extension page is visited.
	 *
	 * @param string $current
	 * @return mixed void on update, false if none
	 */
	function update_extension( $current = '' )
	{
		if ( $current == '' OR $current == $this->version )
		{
			return false;
		}

    /*
		//some of the hooks have changed, so clear out all of the hooks and install them again
		if ( version_compare( $current, '1.7.5', '<' ) )
		{
			$this->disable_extension();
			$this->activate_extension();
		}
    */

		return true;
	}

	/**
	 * Creates global variables from the template pre parse data.
	 *
	 * @param $row
	 * @return string
	 */
	public function template_pre_parse( $row )
	{
        $row['template_data'] = $this->parse_inserts( $row['template_data'] );

        return $row;
	}

  /**
   * @param string $data
   * @return string
   */
  private function parse_inserts( $data )
  {
    //load the class if needed
    if ( ! class_exists( 'In_sert' ) )
    {
      include PATH_THIRD . 'in/libraries/In_sert.php';
    }

    $inserts = new In_sert();
    $data = $inserts->parse( $data );
    unset( $inserts );
    return $data;
  }
}
/* End of file ext.in.php */
/* Location: /system/expressionengine/third_party/in/ext.in.php */