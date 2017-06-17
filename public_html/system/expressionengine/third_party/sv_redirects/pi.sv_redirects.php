<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/* Changelog
 1.0.0 - Initial release
 1.0.1 - Addition of strip_html parameter
 1.0.2 - Addition of override_return parameter
*/

$plugin_info = array(
						'pi_name'			=> 'SV Redirects',
						'pi_version'		=> '1.0.1',
						'pi_author'			=> 'Steady Vision',
						'pi_author_url'		=> 'http://www.steadyvision.com/',
						'pi_description'	=> 'Redirect output and display logic.',
						//'pi_usage'			=> Sv_redirects::usage()
					);

class Sv_redirects {
	
	var $EE;
	var $debug = FALSE;
    var $return_data;

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	void
	 */
    function Sv_redirects()
    {
        $this->EE =& get_instance();
    }
	
	function output_message()
	{
		@session_start();
		
		$this->debug = ($this->EE->TMPL->fetch_param('debug') == '') ? FALSE : TRUE;
		$strip_html = ($this->EE->TMPL->fetch_param('strip_html') == 'y') ? TRUE : FALSE;
		
		$tagdata = $this->EE->TMPL->tagdata;
		
		if (isset($_SESSION[__CLASS__]))
		{
			foreach ($_SESSION[__CLASS__]['msg_data'] as $k => $v)
			{
				if ($strip_html)
				{
					$v = strip_tags($v);
				}
				$tagdata = $this->EE->TMPL->swap_var_single('md:'.$k, $v, $tagdata);
			}
			foreach ($_SESSION[__CLASS__]['post_data'] as $k => $v)
			{
				if ($strip_html)
				{
					$v = strip_tags($v);
				}
				$tagdata = $this->EE->TMPL->swap_var_single('pd:'.$k, $v, $tagdata);
			}
			
			$tagdata = $this->EE->TMPL->swap_var_single("sv_post_data_reload", $this->_parse_post_data(), $tagdata);
			
			if ($this->debug)
			{
				if ($strip_html)
				{
					$v = strip_tags($v);
				}
				$tagdata = $this->EE->TMPL->swap_var_single("sv_debug_data", $this->_debug_data(), $tagdata);
			}
			else
			{
				$tagdata = $this->EE->TMPL->swap_var_single("sv_debug_data", '', $tagdata);
			}
		}
		else
		{
			$tagdata = $this->_no_results();
			$this->return_data = $this->_no_results();
		}
		
		if (!$this->debug)
		{
			unset($_SESSION[__CLASS__]);
		}
		
		return $this->return_data = $tagdata;
	}
	
	function _no_results()
	{
		$tagdata  = $this->EE->TMPL->tagdata;
		$tag_name = 'no_message';    
		$pattern  = '#' .LD .'if ' .$tag_name .RD .'(.*?)' .LD .'/if' .RD .'#s';		
		
		if (is_string($tagdata) && is_string($tag_name) && preg_match($pattern, $tagdata, $matches)	)
		{
		  return $matches[1];
		}
		return '';
	}
	
	function override_return()
	{
		$to_where = $this->EE->TMPL->fetch_param('ret');
		
		$html = '<input type="hidden" name="sv_redirects_override" value="'.$to_where.'" />';
		
		return $this->return_data = $html;
	}
	
	function _debug_data()
	{
		$str = '<pre>';
		ob_start();
		print_r($_SESSION[__CLASS__]);
		$dstr = ob_get_clean();
		$dstr = htmlentities($dstr);		
		$str .= $dstr;
		$str .= '</pre>';
		
		return $str;
	}
	
	function _parse_post_data()
	{
		$data = $_SESSION[__CLASS__]['post_data'];
		
		unset($data['XID'], 
			$data['params_id'], 
			$data['sign_mee_up'], 
			$data['password'], 
			$data['current_password'],
			$data['password_confirm']
		);
		
		return $this->_reload_js($data);
	}
	
	function _reload_js($data)
	{
		ob_start();
		?>
      <script type="text/javascript">
	  window.onload = function(){
	  
		  var sv_reload_data = <? echo json_encode($data); ?>;
		 
		function sv_reparse(i,v) {
				var type = '';
				if ($('input[name='+i+']').length)
				{
					type = $('input[name='+i+']').attr('type');
				}
				else if ($('select[name='+i+']').length)
				{
					type = 'select';
				}
				else if ($('textarea[name='+i+']').length)
				{
					type = 'textarea';
				}
				else if ($('select[name="'+i+'[]"]').length)
				{
					i = i+'[]';
					type = 'mselect';
				}
				

				switch ( type ) {
					case 'radio':
						$('input[name='+i+'][value='+v+']').attr('checked', 'checked');
					break;
					case 'textarea':
						$('textarea[name='+i+']').val(v); 
					break;
					case 'select':
						if (v != '0')
							$('select[name='+i+']').val(v);
					break;
					case 'mselect':
						if ($.isArray(v))
						{
							$.each(v, function(a, b){
								$('option[value="'+b+'"]', 'select[name="'+i+'"]').attr('selected', true);
							});
						}
						
					break;
					default:
						$('input[name='+i+']').val(v);
					break;
				}
		}
			
        if (typeof(sv_reload_data) != "undefined") 
		{
			$.each(sv_reload_data, sv_reparse);
			
		}
		 
	 };
	</script>
        <?
		$buffer = ob_get_contents();
        ob_end_clean();
        return $buffer;
	}
	
	/**
	 * Plugin Usage
	 *
	 * @access	public
	 * @return	string	plugin usage text
	 */
	function usage()
	{
		ob_start(); 
		?>
        Available template variables are:
          {sv_post_data_reload} - generated javascript to reload the form with previous data
          {sv_debug_data} - show the data available in the MESSAGE and POST (debug mode must be enabled)
          
          Message Data:
            {md:title}
            {md:heading}
            {md:content}
          Post Data:
          	{pd:post_variable_name}
         
         To view a full list of variables, or to keep the data from clearing between page loads, use the debug feature.
         
		{exp:sv_redirects:output_message debug="y"}
            {if '{md:title}' == 'Error'}
                <strong>{md:content}</strong>
                {sv_post_data_reload}
            {if:else}
                {md:content}
            {/if}
            
            {sv_debug_data}
            
       {/exp:sv_redirects:output_message}
		<?php
		$buffer = ob_get_contents();
	
		ob_end_clean(); 

		return $buffer;
	}

	// --------------------------------------------------------------------

}
// END CLASS

/* End of file pi.sv_redirects.php */
/* Location: ./system/expressionengine/third_party/sv_redirects/pi.sv_redirects.php */