<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
=====================================================

RogEE Email-from-Template
a plug-in for ExpressionEngine 2
by Michael Rog

Please e-mail me with questions, feedback, suggestions, bugs, etc.
>> michael@michaelrog.com
>> http://michaelrog.com/ee

This plugin is compatible with NSM Addon Updater:
>> http://github.com/newism/nsm.addon_updater.ee_addon

=====================================================

*/

$plugin_info = array(
	'pi_name'			=> "RogEE Email-from-Template",
	'pi_version'		=> "1.5.0",
	'pi_author'			=> "Michael Rog",
	'pi_author_url'		=> "http://michaelrog.com/ee",
	'pi_description'	=> "Emails enclosed contents to a provided email address.",
	'pi_usage'			=> Email_from_template::usage()
);

/** ---------------------------------------
/**  Email_from_template class
/** ---------------------------------------*/

class Email_from_template {

	var $return_data = "";

	function Email_from_template($str = '')
	{

	    $this->EE =& get_instance() ;

		// defaults
	    
	    $this->to = $this->EE->config->item('webmaster_email');
	    $this->cc = "";
	    $this->bcc = "";
		$this->from = $this->EE->config->item('webmaster_email');
		$this->subject = "Email-from-Template: ".$this->EE->uri->uri_string();
		$this->echo_tagdata = TRUE;
		$this->append_debug = FALSE;

		// params: fetch / sanitize / validate
		
		$mailtype = (($mailtype = $this->EE->TMPL->fetch_param('mailtype')) == "html") ? "html" : "text";
		
		$from = (($from = $this->EE->TMPL->fetch_param('from')) === FALSE) ? $this->from : $this->EE->security->xss_clean($from);
		$to = (($to = $this->EE->TMPL->fetch_param('to')) === FALSE) ? $this->to : $this->EE->security->xss_clean($to);
		$cc = (($cc = $this->EE->TMPL->fetch_param('cc')) === FALSE) ? FALSE : $this->EE->security->xss_clean($cc);
		$bcc = (($bcc = $this->EE->TMPL->fetch_param('bcc')) === FALSE) ? FALSE : $this->EE->security->xss_clean($bcc);
		
		$subject = (($subject = $this->EE->TMPL->fetch_param('subject')) === FALSE) ? $this->subject : $subject;
		$alt_message = (($alt_message = $this->EE->TMPL->fetch_param('alt_message')) === FALSE) ? FALSE : $this->EE->security->xss_clean($alt_message);
		
		$decode_subject_entities = (strtolower($this->EE->TMPL->fetch_param('decode_subject_entities')) == "no") ? FALSE : TRUE ;
		$decode_message_entities = (strtolower($this->EE->TMPL->fetch_param('decode_message_entities')) == "no") ? FALSE : TRUE ;
		
		$attachments = (($attachments = $this->EE->TMPL->fetch_param('attachments')) === FALSE) ? FALSE : $this->EE->security->xss_clean($attachments);
		
		$echo_tagdata = (strtolower($this->EE->TMPL->fetch_param('echo')) == "no" || strtolower($this->EE->TMPL->fetch_param('echo')) == "off") ? FALSE : TRUE ;
		
		// fetch tag data
    
		if ($str == '')
		{
			$str = $this->EE->TMPL->tagdata ;
		}

		$tagdata = $str;
		
		// assemble and parse template variables
		
		$variables = array();
		
		$single_variables = array(
			'from' => $from,
			'to' => $to,
			'cc' => $cc,
			'bcc' => $bcc,
			'subject' => $subject,
			'ip' => $this->EE->input->ip_address(),
			'httpagent' => $this->EE->input->user_agent(),
			'uri_string' => $this->EE->uri->uri_string()
		);

		$variables[] = $single_variables;

		$message = $this->EE->TMPL->parse_variables($tagdata, $variables) ;
		
		// parse global variables
		
		$subject = $this->EE->TMPL->parse_globals($subject);
		$message = $this->EE->TMPL->parse_globals($message);
		
		// decode HTML entities
		
		if ($decode_subject_entities)
		{
			$this->EE->TMPL->log_item('Decoding HTML entities in subject...');
			$subject = $decode_subject_entities ? html_entity_decode($subject) : $subject;
		}
		
		if ($decode_message_entities)
		{
			$this->EE->TMPL->log_item('Decoding HTML entities in message...');
			$message = $decode_message_entities ? html_entity_decode($message) : $message;
		}

		// mail the message
				
		$this->EE->TMPL->log_item('Sending email from template...');
			
		$this->EE->load->library('email');
		$this->EE->email->initialize() ;

		$this->EE->TMPL->log_item('MAILTYPE: ' . $mailtype);
		$this->EE->email->mailtype = $mailtype;

		$this->EE->TMPL->log_item('FROM: ' . $from);
		$this->EE->email->from($from);

		$this->EE->TMPL->log_item('TO: ' . $to);
		$this->EE->email->to($to); 

		$this->EE->TMPL->log_item('CC: ' . ($cc ? $cc : '(none)'));
		$this->EE->email->cc($cc);
		
		$this->EE->TMPL->log_item('BCC: ' . ($bcc ? $bcc : '(none)'));
		$this->EE->email->bcc($bcc);

		$this->EE->TMPL->log_item('SUBJECT: ' . $subject);
		$this->EE->email->subject($subject);
		
		$this->EE->email->message($message);
		
		if ($alt_message !== FALSE)
		{
			$this->EE->email->set_alt_message($alt_message);	
		}
		
		if ($attachments !== FALSE)
		{
			$this->EE->TMPL->log_item('Adding attachemnts...');
			
			$attachments_array = explode(",", $attachments);
			foreach($attachments_array as $attachment_path)
			{
				$this->EE->TMPL->log_item('Attachment: '.$attachment_path);
				$this->EE->email->attach($attachment_path);
			}
		}
		
		$this->EE->email->Send();

		// more template debugging

		$this->EE->TMPL->log_item('Email sent!');
		
		if (! $echo_tagdata) { $this->EE->TMPL->log_item('Echo is off. Outputting nothing to template.'); }
		else { $this->EE->TMPL->log_item('Echo is on. Repeating message to template.'); }
		
		// return data to template
		
		$this->return_data = ($echo_tagdata) ? $message : "";
		
		if ($this->append_debug)
		{
			$this->return_data .= "<br><hr><br>".$this->EE->email->print_debugger();
		}

	} // END Email_from_template() constructor

	/** ----------------------------------------
	/**  Plugin Usage
	/** ----------------------------------------*/
	
	function usage()
	{
	
		ob_start(); 
		?>
	
		This plugin emails the enclosed content to a provided email address.
		
		PARAMETERS:
		
		from - sender email address (default: site webmaster)
		to - destination email address (default: site webmaster)
		cc - email addresses to carbon copy
		bcc - email addresses to blind carbon copy
		subject - email subject line (default: template URI)
		mailtype - "text" or "html"
		alt_message - a plain-text fallback for use with HTML emails
		decode_subject_entities - Set to "no" if you don't want to parse the HTML entities in the subject line.
		decode_message_entities - Set to "no" if you don't want to parse the HTML entities in the message text.
		echo - Set to "off" if you don't want to display the tag contents in the template.
		
		VARIABLES:
		
		{to}
		{from}
		{subject}
		{ip}
		{httpagent}
		{uri_string}
		
		EXAMPLE USAGE:
		
		{exp:email_from_template to="admin@ee.com" from="server@ee.com" subject="Hello!" echo="off"}

			This tag content is being viewed at {uri_string}. Sending notification to {to}!

		{/exp:email_from_template}	
	
		USING WITH OTHER PLUGINS AND TAGS:
		
		When you want to email the output of other tags, put Email_from_Template INSIDE the other tag and use parse="inward" on the outer tags.
	
		<?php
		$buffer = ob_get_contents();
		
		ob_end_clean(); 
	
		return $buffer;
	
	} // END usage()

} // END class Email-from-template

/* End of file pi.email-from-template.php */ 
/* Location: ./system/expressionengine/third_party/email-from-template/pi.email-from-template.php */
