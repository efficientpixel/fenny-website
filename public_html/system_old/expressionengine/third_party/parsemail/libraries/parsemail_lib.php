<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Parsemail_lib
{
    public $EE;
    public $enabled;

    public function __construct()
    {
        $this->EE =& get_instance();
        //$this->EE->lang->loadfile('parsemail');
        $this->disabled = 0;
        $this->lib = $this;
        
    }

    public function module_name()
    {
        return 'Parsemail';
    }

    public function version()
    {
        return '1.0.7';
    }

    public function current_uri()
    {
        // Return the current (relative) url.
        return '/' . implode('/', $this->EE->uri->segments);
    }

    public function cleanup_embed_vars($template)
    {
        // Remove leftover (unparsed) variables from the template.

        $template = preg_replace('/'.LD.':([^!]+?)'.RD.'/', '', $template);
        return $template;
    }

    public function embed($template, $embed_vars, $site_id = '')
    {
        // Parse an existing EE template.

        // Allow 'my/template' and array('my', 'template')
        if (is_array($template))
        {
            list($template_group, $template_name) = $template;
        }
        else
        {
            list($template_group, $template_name) = $this->split_template_name($template);
        }

        $template_body = $this->fetch_template($template_group, $template_name, FALSE, $site_id);

        return $this->parse($template_body, $embed_vars);
    }

    public function fetch_template($template_group, $template_name,
                                   $show_default = true, $site_id = '')
    {
        // Template lib required for the embed/parse functions.
        $this->EE->load->library('template');

        $tmpl = new EE_Template();
        return $tmpl->fetch_template($template_group, $template_name,
                                     $show_default, $site_id);
    }

    public function parse($template_body, $embed_vars = array(), $sub_template = false)
    {
        // Parse a template

        // Template lib required for the embed/parse functions.
        $this->EE->load->library('template');

        // Temporarily replace the TMPL, because addons rely on it.
        $tmpl = new EE_Template();

        $orig_tmpl =& $this->EE->TMPL;
        $this->EE->TMPL =& $tmpl;

        /* // Assign variables to the templates. */
        /* // These variables are seen as snippets and therefore parsed early. */
        /* if (isset($this->EE->config->_global_vars)) */
        /* { */
        /*     $orig_global_vars = $this->EE->config->_global_vars; */
        /* } */
        /* else */
        /* { */
        /*     $orig_global_vars = array(); */
        /* } */

        /* $this->EE->config->_global_vars = array(); */
        $vars = array_merge(
            /* (array)$orig_global_vars, */
            $this->prefix_array((array)$embed_vars, 'embed:'),
            $this->prefix_array((array)$embed_vars, ':'),
            $this->prefix_array((array)$embed_vars, ''));
        foreach ($vars as $k => $v)
        {
            $v = strval($v);
            $v = $this->EE->functions->encode_ee_tags($v, true);
            $this->EE->config->_global_vars[$k] = $v;
        }

        $vars = $this->EE->config->_global_vars;
        /* $this->EE->config->_global_vars = array(); */
        $template_body = $tmpl->parse_variables_row($template_body, $vars, false);

        $tmpl->parse($template_body, $sub_template);

        /* // Global vars that were added by other addons */
        /* $new_global_vars = $this->EE->config->_global_vars; */

        /* $this->EE->config->_global_vars = $orig_global_vars; */
        /* $this->EE->config->_global_vars = array_merge( */
        /*     $new_global_vars, */
        /*     $this->EE->config->_global_vars */
        /* ); */
            

        $final_template = $tmpl->final_template;

        // Global variables.
        $final_template = $tmpl->parse_globals($final_template);

        $this->EE->TMPL =& $orig_tmpl;

        return $this->cleanup_embed_vars($final_template);
    }

    public function prefix_array($a, $prefix)
    {
        // Prefix all keys in the array $a with $prefix.
        
        $new = array();
        foreach ($a as $key => $value)
        {
            $new[$prefix . $key] = $value;
        }

        return $new;
    }

    public function debug_enabled()
    {
        return false;
    }

    public function revert_side_effects()
    {
        // Revert side effects caused by Email::_build_message()

        // _build_message callees: _set_boundaries, _write_headers

        // Revert side effects from _write_headers():

        $crowbar = $this->get_crowbar();

        if ($this->EE->email->protocol == 'mail')
        {
            $headers = $crowbar->get('_headers');
            $headers['Subject'] = $crowbar->get('_subject');
            ///$crowbar->set('_headers', $headers);
            $this->EE->email->set_header('Subject', $crowbar->get('_subject'));
            
            /* $encoded_subject = $params['headers']['Subject']; */
            /* $subject = $this->q_decode($encoded_subject); */
            /* $this->EE->email->subject($subject); */
        }

        // Revert side effects from _set_boundaries()
        // nothing to revert.
    }

    public function q_decode($str) 
    {
        // Reverse effects from Email::_prep_q_encoding(str)
        $charset = $this->EE->email->charset;

        if (MB_ENABLED === true)
        {
            return mb_decode_mimeheader($str);
        }
        elseif (extension_loaded('iconv'))
        {
            $output = @iconv_mime_decode(
                $str,
                ICONV_MIME_DECODE_CONTINUE_ON_ERROR,
                $charset);
            if ($output !== FALSE) 
            {
                return $output;
            }
        }
        else
        {
            throw new Exception('help');
        }
    }

    public function reverse_message($body)
    {
        $new_body = $body;

        if ( ! is_php('5.4') && get_magic_quotes_gpc()) 
        {
            $new_body = addslashes($this->_body);
        }

        return $new_body;
    }
    
    

    public function maybe_force_html_email()
    {
        if($this->EE->config->item('parsemail_force_html'))
        {
            $this->EE->email->set_mailtype('html');
        }
    }
    
    public function disabled()
    {
        return $this->disabled > 0;
    }

    public function enabled()
    {
        return !$this->disabled();
    }

    public function disable()
    {
        $this->disabled++;
    }

    public function enable()
    {
        $this->disabled--;
    }

    public function _parse_current($matches)
    {
        $embed_vars = $this->_tmp_embed_vars;
        $sub_template = $this->_tmp_sub_template;
        $template_body = $matches[1];
        return $this->parse($template_body, $embed_vars, $sub_template);
    }

    public function maybe_parse($template_body, $embed_vars = array(), $sub_template = false)
    {
        //if ($this->EE->config->item('parsemail_explicit_tag'))
        if (!$this->EE->config->item('parsemail_always_on'))
        {
            $this->_tmp_embed_vars = $embed_vars;
            $this->_tmp_sub_template = $sub_template;
            $regex = '#\{parsemail\}(.*?)\{/parsemail\}#sm';
            $callback = array($this, '_parse_current');
            return preg_replace_callback($regex, $callback, $template_body);
        }
        else
        {
            $embed_vars['{parsemail}'] = '';
            $embed_vars['{/parsemail}'] = '';
            return $this->parse($template_body, $embed_vars, $sub_template);
        }
    }

    public function get_crowbar()
    {
        $this->EE->load->library('email');
        include_once PATH_THIRD . 'parsemail/classes/Parsemail_email_crowbar.php';
        return new Parsemail_email_crowbar($this->EE->email);
    }
}
