<?php 

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$plugin_info = array(
  	'pi_name' => 'Delete',
	'pi_version' => '2.1',
	'pi_author' => 'Matteo Menapace',
	'pi_author_url' => 'http://www.milocreative.com',
	'pi_description' => 'Allows users to delete their own entries and comments outside of the CP',
	'pi_usage' => Delete::usage()
  );

class Delete
{

	// Default values
	var $type = "entry";
	var $error_invalid_content = "<b>Error:</b> This content does not exist or has already been deleted.<br>";
	var $error_no_permissions = "<b>Error:</b> You do not have permission to delete this.<br>";
	var $message_success = "Content successfully deleted!";
	var $message_failure = "Something went wrong..";

	var $usermessage = "";	
	var $return_data = "";
	
	
	// ----------------------------------------
  	//  Constructor
  	// ----------------------------------------
	function Delete()
	{
		$this->EE =& get_instance();
	}
  	

	// ----------------------------------------
  	//  Generate a delete link  
  	// ----------------------------------------
  	public function link()
  	{

		if ( ! $this->EE->TMPL->fetch_param('id') OR  ! $this->EE->TMPL->fetch_param('template')) return "Invalid delete plugin usage.";

		$type = ($this->EE->TMPL->fetch_param('type')) ? $this->EE->TMPL->fetch_param('type') : $this->type;

		// create a js alert box?
		$alert = "";
	 	if ($this->EE->TMPL->fetch_param('alert') != "false") $alert = " onclick=\"javascript: if (!confirm('Are you sure you want to delete this $type?')) return false;\"";
		
		$link = "<a class='" . $this->EE->TMPL->fetch_param('class') . "' href='" . $this->EE->config->config['site_url'] . $this->EE->TMPL->fetch_param('template') . "/" . 
		$this->EE->TMPL->fetch_param('id') . "' $alert>" . $this->EE->TMPL->tagdata . "</a>";
		
		return $link;
  	}
	
	
	// ----------------------------------------
  	//  Delete an entry  
  	// ----------------------------------------
	function delete_entry()
	{
	 	$entry_id = $this->EE->TMPL->fetch_param('entry_id');

	 	// is entry_id valid?
	 	if(! (is_numeric($entry_id) && is_int($entry_id + 0)) ) 
		{
			$return = ($this->EE->TMPL->fetch_param('error_invalid_content') ? $this->EE->TMPL->fetch_param('error_invalid_content') : $this->error_invalid_content);
			return $return;
		}	

	 	// does this entry exist?
	 	$query = $this->EE->db->query("SELECT * FROM exp_channel_titles WHERE entry_id = $entry_id");
		if ($query->num_rows() < 1) 
		{
			$return = ($this->EE->TMPL->fetch_param('error_invalid_content') ? $this->EE->TMPL->fetch_param('error_invalid_content') : $this->error_invalid_content);
			return $return;
		}
					
	 	$author_id = $query->row('author_id');
	 	$channel_id = $query->row('channel_id');

	 	// check if user has permissions allow to delete this entry (superadmins are always allowed)
	 	if($this->EE->session->userdata('group_id') != 1)
		{ 
			if ( ! $this->EE->session->userdata('can_delete_self_entries') AND ! $this->EE->session->userdata('can_delete_all_entries')) 
			{
				$return = ($this->EE->TMPL->fetch_param('error_no_permissions') ? $this->EE->TMPL->fetch_param('error_no_permissions') : $this->error_no_permissions);
				return $return;
			}
	
			$has_channel_access = false;
			foreach ($this->EE->functions->fetch_assigned_channels() as $key => $val) if ($val == $channel_id) $has_channel_access = true;
			if ($has_channel_access == false) 
			{
				$return = ($this->EE->TMPL->fetch_param('error_no_permissions') ? $this->EE->TMPL->fetch_param('error_no_permissions') : $this->error_no_permissions);
				return $return;
			}
				
			if($this->EE->session->userdata('member_id') == $author_id) 
			{
				if ($this->EE->session->userdata('can_delete_self_entries') == "n")
				{
					$return = ($this->EE->TMPL->fetch_param('error_no_permissions') ? $this->EE->TMPL->fetch_param('error_no_permissions') : $this->error_no_permissions);
					return $return;
				}
			}	 
			else 
			{
				if ($this->EE->session->userdata('can_delete_all_entries') == "n") 
				{
					$return = ($this->EE->TMPL->fetch_param('error_no_permissions') ? $this->EE->TMPL->fetch_param('error_no_permissions') : $this->error_no_permissions);
					return $return;
				}		
			}		 
		}

		// user seems to have permissions to edit this entry, let's do it		
		$deleted = $this->_delete_entry($entry_id);
		if ($deleted == TRUE) $return = ($this->EE->TMPL->fetch_param('message_success') ? $this->EE->TMPL->fetch_param('message_success') : $this->message_success);
		else $return =  $this->message_failure;

		return $return;
	}
	
	function _delete_entry($entry_id)
	{
		// load the API..
		$this->EE->load->library('api');
		$this->EE->api->instantiate('channel_entries');
		
		$entry_ids = array($entry_id);
		$deleted = $this->EE->api_channel_entries->delete_entry($entry_ids);
		
		return $deleted;
	}
	
	// ----------------------------------------
  	//  Delete a comment
  	// ----------------------------------------
	function delete_comment()
	{		
		$comment_id = $this->EE->TMPL->fetch_param('comment_id');

		// is comment_id valid?
	 	if(! (is_numeric($comment_id) && is_int($comment_id + 0)) )
	 	{
			$return = ($this->EE->TMPL->fetch_param('error_invalid_content') ? $this->EE->TMPL->fetch_param('error_invalid_content') : $this->error_invalid_content);
			return $return;
		}	

		// does this comment exist?
	 	$query = $this->EE->db->query("SELECT * FROM exp_comments WHERE comment_id = $comment_id");
	 	if ($query->num_rows() < 1)
	 	{
			$return = ($this->EE->TMPL->fetch_param('error_invalid_content') ? $this->EE->TMPL->fetch_param('error_invalid_content') : $this->error_invalid_content);
			return $return;
		}	
		
		$author_id = $query->row('author_id');
	 	$channel_id = $query->row('channel_id');
		$entry_id = $query->row('entry_id');

	 	// check if users (not superadmins) have permissions to delete this comments
	 	if($this->EE->session->userdata('group_id') != 1) 
		{ 
			if ( ! $this->EE->session->userdata('can_delete_own_comments') AND ! $this->EE->session->userdata('can_delete_all_comments'))
			{
				$return = ($this->EE->TMPL->fetch_param('error_no_permissions') ? $this->EE->TMPL->fetch_param('error_no_permissions') : $this->error_no_permissions);
				return $return;
			}

			$has_channel_access = false;
			foreach ($this->EE->functions->fetch_assigned_channels() as $key => $val) if ($val == $channel_id) $has_channel_access = true;
			if ($has_channel_access == false) 
			{
				$return = ($this->EE->TMPL->fetch_param('error_no_permissions') ? $this->EE->TMPL->fetch_param('error_no_permissions') : $this->error_no_permissions);
				return $return;
			}	

			if($this->EE->session->userdata('member_id') == $author_id) 
			{
				if ($this->EE->session->userdata('can_delete_own_comments') == "n") 
				{
					$return = ($this->EE->TMPL->fetch_param('error_no_permissions') ? $this->EE->TMPL->fetch_param('error_no_permissions') : $this->error_no_permissions);
					return $return;
				}
			}	 
			else 
			{
				if ($this->EE->session->userdata('can_delete_all_comments') == "n") 
				{
					$return = ($this->EE->TMPL->fetch_param('error_no_permissions') ? $this->EE->TMPL->fetch_param('error_no_permissions') : $this->error_no_permissions);
					return $return;
				}
			}		 
		}
		
		// update entry data
		$query = $this->EE->db->query("SELECT comment_total FROM exp_channel_titles WHERE entry_id = '$entry_id'");
		$this->EE->db->query("UPDATE exp_channel_titles set comment_total = '".($query->row('comment_total') - 1)."' WHERE entry_id = '$entry_id'");
		
		// update user data
		$query = $this->EE->db->query("SELECT total_comments FROM exp_members WHERE member_id = '$author_id'");
		$this->EE->db->query("UPDATE exp_members set total_comments = '".($query->row('total_comments') - 1)."' WHERE member_id = '$author_id'");
		
		// delete actual comment
		$this->EE->db->query("DELETE FROM exp_comments WHERE comment_id = '$comment_id'");

		

		// update stats	
		$this->EE->stats->update_channel_stats($channel_id);
		$this->EE->stats->update_comment_stats($channel_id);

		$return = ($this->EE->TMPL->fetch_param('message_success') ? $this->EE->TMPL->fetch_param('message_success') : $this->message_success);
		return $return;	
	}
	

  	// ----------------------------------------
  	//  Plugin Usage
  	// ----------------------------------------

	public function usage()
	{
		ob_start(); 
		?>
		
			The delete plugin enables users to delete their own entries and comments from outside of the EE CP.

			You can modify the messages that the plugin outputs by passing them as tag parameters or changing the default values in the config section of pi.delete.php. 


			The plugin has three functions:

			**** GENERATE 'DELETE' LINKS - {exp:delete:link}

			This tag generates a link to the deletion page/template, where you would place one of the other two functions (delete_entry or delete_comment). 

			Parameters:

			 - id (required): id of the entry or comment you want the "delete" link for 
			 - template (required): name of the template containing the {exp:delete:delete_entry} or {exp:delete:delete_comment} plugin code
			 - alert (optional): boolean (true/false). Default: true. If this is set to "true" then an alert box ("Do you really want to ditch this? Yes / No)" will pop up when users click the link
			 - type (optional): you can customize the text displayed in the alert box

			Example: 
			{exp:delete:link 
				id="{entry_id}" 
				type="image" 
				template="images/delete" 
				alert="false"
			}
				Delete
			{/exp:delete:link}


			**** DELETE ENTRIES - {exp:delete:delete_entry}

			You should place this tag in a 'delete entry' template. 
			You can also use AJAX to delete an entry 'under the hood' and fetch the plugin response to display as feedback to users.

			Parameters:

			 - entry_id (required)
			 - error_no_permissions (optional): the text to display when users don't have permisisons to delete something
			 - error_invalid_content (optional): the text displayed when some content doesn't exist or has been deleted
			 - message_success (optional): the text to display on successful deletion

			Example:

			{exp:delete:delete_entry 
				entry_id="{segment_3}"
				error_no_permissions="You're not allowed to delete this image" 
				error_invalid_content="It seems that this image has been already deleted" 
				message_success="Image deleted!"
			}


			**** DELETE COMMENTS - {exp:delete:delete_comment}

			You should place this tag in a 'delete comments' template. 
			You can also use AJAX to delete a comment 'under the hood' and fetch the plugin response to display as feedback to users.

			Parameters:

			 - comment_id (required)	
			 - error_no_permissions (optional): the text to display when users don't have permisisons to delete something
			 - error_invalid_content (optional): the text displayed when some content doesn't exist or has been deleted
			 - message_success (optional): the text to display on successful deletion	

			Example:

			{exp:delete:delete_comment 
				comment_id="{comment_id}"
				error_no_permissions="You're not allowed to delete this comment" 
				error_invalid_content="It seems that this comment has been already deleted" 
				message_success="Comment deleted!"
			}
		
		<?php
		$buffer = ob_get_contents();
		ob_end_clean(); 
		return $buffer;
	}

}

/* End of file pi.delete.php */ 
/* Location: ./system/expressionengine/third_party/delete/pi.delete.php */