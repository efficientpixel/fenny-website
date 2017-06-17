<?php $this->EE =& get_instance();

$this->EE->load->helper('librareestring');

// load up any Snippets
$this->EE->db->select('snippet_name, snippet_contents');
$this->EE->db->where('(site_id = '.$this->EE->db->escape_str($this->EE->config->item('site_id')).' OR site_id = 0)');
$fresh = $this->EE->db->get('snippets');

if ($fresh->num_rows() > 0)
{
	$snippets = array();

	foreach ($fresh->result() as $var)
	{
		$snippets[$var->snippet_name] = $var->snippet_contents;
	}

	$this->EE->config->_global_vars = $this->EE->config->_global_vars + $snippets; 

	unset($snippets);
	unset($fresh);
}
				
				
if ( ! class_exists('EE_Template'))
{
	require APPPATH.'libraries/Template.php';
}

$query = $this->EE->db->query("SELECT * from exp_specialty_templates where template_name='".$this->EE->input->get('tmpl')."';");
		
$this->EE->TMPL = new EE_Template;

$this->EE->TMPL->parse_php = TRUE;
		
$this->EE->TMPL->parse($query->row("template_data"), FALSE, $this->EE->config->item('site_id'));

$this->EE->TMPL->final_template = $this->EE->TMPL->parse_globals($this->EE->TMPL->final_template);

?>
<script language="javascript">
$(document).ready(function(){
	<?php
	$actual_length = strlen($this->EE->TMPL->final_template);
	$stripped_length = strlen(strip_tags($this->EE->TMPL->final_template));
		
		if($actual_length != $stripped_length) { ?>
			var content = "<?php echo str_replace("\n","",addslashes($this->EE->TMPL->final_template)); ?>"
		<?php } 
		else { ?>
			var content = "<?php echo str_replace("\n","",nl2br(addslashes($this->EE->TMPL->final_template))); ?>";
		<?php }
	?>
	
	var iframe = document.getElementById("if_prev");
	var doc = iframe.document;
   
   if(iframe.contentDocument) {
   	doc = iframe.contentDocument; // For NS6
   }
   else if(iframe.contentWindow){
   	doc = iframe.contentWindow.document; // For IE5.5 and IE6
   }          
   
   doc.open();
   doc.writeln(content);
   doc.close(); 
});
</script>
			<table style="width:100%;" cellspacing="2" cellpadding="2" border="0" class="mainTable">
				<thead>
				<tr>
					<th class="header">Viewing Parsed Template : <?php echo $this->EE->input->get('tmpl');?></th>
				</tr>
				</thead>
				<tbody>
					<tr>
						<td>
							<iframe id="if_prev" style="width:99%;height:300px;background-color:#fff;"></iframe>
						</td>
					</tr>
					<tr>
						<td><a href="<? echo BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=libraree'; ?>">Back to List</a></td>
					</tr>
				</tbody>
			</table>