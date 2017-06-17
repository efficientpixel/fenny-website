<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Parsemail_ext
{
    public $return_data = false;
    public $EE;

    public $settings       = array();
    public $description    = '';
    public $settings_exist = 'n';
    public $docs_url       = 'http://www.kees-tm.nl/parsemail';


    public function __construct()
    {
        $this->EE =& get_instance();

        $this->EE->load->add_package_path(PATH_THIRD .'parsemail/');
        $this->EE->load->library('parsemail_lib');
        $this->lib = $this->EE->parsemail_lib;

        $this->name = $this->lib->module_name();
        $this->version = $this->lib->version();
    }

    public function activate_extension()
    {
        $hooks = array(
            'email_send',
        );

        $this->EE->db->where('class', __CLASS__);
        $this->EE->db->delete('extensions');

        foreach ($hooks as $hook)
        {
            $data = array(
                'class' => __CLASS__,
                'settings' => '',
                'priority' => 9,
                'version' => $this->version,
                'enabled' => 'y',
                'hook' => $hook,
                'method' => $hook);
            $this->EE->db->insert('exp_extensions', $data);
        }
    }

    public function disable_extension()
    {
        $this->EE->db->where('class', __CLASS__);
        $this->EE->db->delete('extensions');
    }

    public function email_send($data)
    {
        $crowbar = $this->lib->get_crowbar();

        if (!$this->lib->enabled())
        {
            return false;
        }

        // Array elements are references.
        if ($this->lib->debug_enabled())
        {
            echo '<pre>';
            echo '<h2>Original data</h2>';
            print_r($data);
            echo '<hr/>';
        }

        $this->lib->maybe_force_html_email();

        $this->lib->revert_side_effects();

        $this->EE->email->alt_message = $this->lib->maybe_parse($this->EE->email->alt_message);

        /* $this->EE->email->_body = $this->lib->maybe_parse($this->EE->email->_body); */
        $body = $crowbar->get('_body');
        $crowbar->set('_body', $this->lib->maybe_parse($body));
        
        //$this->EE->email->_build_message();
        $crowbar->call('_build_message');

        if ($this->lib->debug_enabled())
        {
            echo '<h2>After parse</h2>';
            print_r($data);
            echo '<hr/>';

            echo '</pre>';
            exit;
        }
    }
}
