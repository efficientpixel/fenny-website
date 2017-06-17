<div class="rightNav">
	<div style="float: left; width: 100%;">
		<span class="button"><? echo '<a href="'.BASE.AMP.'C=addons_extensions'.AMP.'M=extension_settings'.AMP.'file=libraree" class="submit" >LibrarEE Settings</a>'; ?></span>
	</div>
</div>
<?php 
$this->EE =& get_instance(); 
$site_id = $this->EE->config->item('site_id');
if(!isset($this->EE->config->config['tmpl_file_basepath'])){
	echo "<fieldset style='text-align:center; font-weight:bold;'>No Libraree path has been set, please go to the Libraree extension settings.</fieldset>";
}else{
?>
<table width="100%" border="0">
	<tr>
		<td valign="top">
			<?
		
			if($vars["mailtype"] != "html"){
				echo "<br/><fieldset style='text-align:center;'><b>The default mailtype is 'Plain text', please follow the 'enable HTML emails' instruction on <a href='http://www.libraree.net/docs' >Libraree.net</a> in order to activate HTML in emails.</b></fieldset><br/>";
			}
			
?>
			<h3 class="accordion ui-accordion-header ui-helper-reset ui-state-default ui-corner-all" style="padding-left:6px; cursor:default" ><?php echo $this->EE->lang->line('snippets'); if(!isset($settings["snippets_enabled"])){ echo "<span style='float:right; width:300px;display:block;text-align:right;'>snippets are not synced by LibrarEE</span>";}?></h3>
			<table style="width:100%;float:left;" cellspacing="2" cellpadding="2" border="0" align="center" class="mainTable" id="multee_slugs_tbl">
				<thead>
				<tr>
					<th>Snippet Name</th>
					<th>Database Timestamp</th>
					<th>Snippet Type</th>
					<th>Edit</th>
				</tr>
				</thead>
				<tbody>
				<?php
				$site_id = $this->EE->config->item('site_id');
				
				$q1 = $this->EE->db->query('select site_name from exp_sites where site_id ='.$site_id.';');
				
				foreach($q1->result_array() as $row){
				$sitename = $row['site_name'];
				}
				
				$query = $this->EE->db->query("SELECT * from exp_snippets where site_id=0 OR site_id=".$site_id.";");
				
				if($query->num_rows() > 0){
					foreach($query->result_array() as $row){	
				?>
				<tr>
					<td width="30%"><?php echo $row['snippet_name']; ?></td>
					
					<td width="20%"><?php echo $row['sync_time']; ?></td>
					<td width="20%">
						<?php 
						if($row['site_id']==0)
							{
							echo('Global');
							}
						else
							{
							echo('Local');
							}
						?>
					</td>
					<td width="10%"><a href="<?php echo BASE.AMP.'D=cp'.AMP.'C=design'.AMP.'M=snippets_edit'.AMP.'snippet='.$row['snippet_name']; ?>">Edit</a></td>
				</tr>
				<?
					}
				?>
				</tbody>
				<!--<tfoot>
				<tr class="slug_nav">
					<td colspan="5" align="right"><a href="#" style="background-image:url('expressionengine/third_party/multee_slugs/images/add.png');background-position:right;padding-right:20px;display:block;line-height:20px;background-repeat:no-repeat;" class="slug_add"><?php echo $this->EE->lang->line('opt_create_snippet'); ?></a></td>
				</tr>
				</tfoot>-->
			</table>
			<? } 
		
			?>
			
			<h3 class="accordion ui-accordion-header ui-helper-reset ui-state-default ui-corner-all" style="padding-left:6px; cursor:default"><?php echo $this->EE->lang->line('variables'); if(!isset($settings["global_variables_enabled"])){ echo "<span style='float:right; width:300px;display:block;text-align:right;'>variables are not synced by LibrarEE</span>";}?></h3>
			<table style="width:100%;float:left;" cellspacing="2" cellpadding="2" border="0" align="center" class="mainTable" id="multee_slugs_tbl">
				<thead>
				<tr>
					<th>Variable Name</th>
					<th>Database Timestamp</th>
					<th>Edit</th>
				</tr>
				</thead>
				<tbody>
				<?php
				$site_id = $this->EE->config->item('site_id');
				
				$query = $this->EE->db->query("select * from exp_global_variables where site_id=".$site_id.";");
				
				if($query->num_rows() > 0){
					foreach($query->result_array() as $row){	
				?>
				<tr>
					<td width="*"><?php echo $row['variable_name'];?></td>
					<td width="20%"><?php echo $row['sync_time']; ?></td>
					<td width="10%"><a href="<?php echo BASE.AMP.'D=cp'.AMP.'C=design'.AMP.'M=global_variables_update'.AMP.'variable_id='.$row['variable_id']; ?>">Edit</a></td>
				</tr>
				<?
					}
				
				?>
				</tbody>
				<!--<tfoot>
				<tr class="slug_nav">
					<td colspan="4" align="right"><a href="#" style="background-image:url('expressionengine/third_party/multee_slugs/images/add.png');background-position:right;padding-right:20px;display:block;line-height:20px;background-repeat:no-repeat;" class="slug_add"><?php echo $this->EE->lang->line('opt_create_variable'); ?></a></td>
				</tr>
				</tfoot>-->
			</table>
			<? } ?>
			<h3 class="accordion ui-accordion-header ui-helper-reset ui-state-default ui-corner-all" style="padding-left:6px; cursor:default"><?php echo $this->EE->lang->line('speciality'); if(!isset($settings["message_pages_enabled"])){ echo "<span style='float:right; width:350px;display:block;text-align:right;'>Specialty Templates are not synced by LibrarEE</span>";} ?></h3>
			<table style="width:100%;float:left;" cellspacing="2" cellpadding="2" border="0" align="center" class="mainTable" id="multee_slugs_tbl">
				<thead>
				<tr>
					<th>Variable Name</th>
					<th>Database Timestamp</th>
					<th>Preview</th>
				</tr>
				</thead>
				<tbody>
				<?php
							
				$query = $this->EE->db->query("select * from exp_specialty_templates where site_id=".$site_id.";");
				
				if($query->num_rows() > 0){
					foreach($query->result_array() as $row){	
				?>
				<tr>
					<td width="*">
					<?php echo $row['template_name']; ?>
					</td>
					<td width="20%"><?php echo $row['sync_time'];?></td>
					<td width="10%"><a href="<?php echo BASE.AMP.'D=cp'.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=libraree'.AMP.'method=show_rendered_template'.AMP.'tmpl='.$row['template_name']; ?>">View</a></td>
				</tr>
				<?
					}
			
				?>
				</tbody>
			</table>
			<? } ?>
		</td>
		
	</tr>
</table>
<? } ?>
<div style="clear:both;"></div>