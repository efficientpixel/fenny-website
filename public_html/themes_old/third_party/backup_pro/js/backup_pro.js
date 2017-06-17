$(document).ready(function() {
	
	//settings form
	var backup_type = '';
	if($("#auto_threshold").val() == "custom")
	{
		$("#auto_threshold_custom").show();
	}
		
	if($("#db_backup_method").val() == "mysqldump")
	{
		$("#mysqlcli_command").show();
	}

	if($("#db_restore_method").val() == "mysql")
	{
		$("#mysqldump_command").show();
	}				
	
	var def_assign = "0";
	$("#auto_threshold").change(function(){
		var new_assign = $("#auto_threshold").val();
		if(new_assign == def_assign || new_assign != "custom")
		{
			$("#auto_threshold_custom").hide();
			$("#auto_threshold_custom").val(new_assign);
		}
		else
		{
			$("#auto_threshold_custom").show();
		}
	});	

	var def_assign = "php";
	$("#db_backup_method").change(function(){
		var new_assign = $("#db_backup_method").val();
		if(new_assign == def_assign)
		{
			$("#mysqldump_command").hide();
		}
		else
		{
			$("#mysqldump_command").show();
		}
	});	

	$("#db_restore_method").change(function(){
		var new_assign = $("#db_restore_method").val();
		if(new_assign == def_assign)
		{
			$("#mysqlcli_command").hide();
		}
		else
		{
			$("#mysqlcli_command").show();
		}
	});
	//end settings form

	//check all checkboxes
	$(".toggle_all_db").toggle(
		function(){
			$("input.toggle_db").each(function() {
				this.checked = true;
			});
		}, function (){
			var checked_status = this.checked;
			$("input.toggle_db").each(function() {
				this.checked = false;
			});
		}
	);

	$(".toggle_all_files").toggle(
		function(){
			$("input.toggle_files").each(function() {
				this.checked = true;
			});
		}, function (){
			var checked_status = this.checked;
			$("input.toggle_files").each(function() {
				this.checked = false;
			});
		}
	);
	//end checkboxes
	
	//lil' method to send the backup note to the server
	function bp_save_note(text_div, element)
	{
		var note_text = $(element).val();
		var note_backup = $(element).attr("rel");
		var dataString = "backup="+note_backup+"&note_text="+note_text+ "&" + $.param({ "XID": EE.XID});
		$.ajax({
			type: "POST",
			url: EE.BASE+"&C=addons_modules&M=show_module_cp&module=backup_pro&method=update_backup_note&",
			data: dataString,
			success: function(){

				$(text_div).html(note_text).show();
				$(element).hide();
			},
				error: function(jqXHR, textStatus){
			}
		});
		
	}
	
	//backup note editable
	$(".bp_editable").live("click", function(e) {
		
		var file_id = "#note_"+$(this).attr("rel");
		var note_div = "#note_div_"+$(this).attr("rel");
		var note_html = "#note_div_"+$(this).attr("rel");
		var def_value = $(file_id).val();
		
		//first, prevent using Enter to submit the parent form
		$(file_id).bind("keypress", function(e) {
			  var code = e.keyCode || e.which; 
			  if (code  == 13) 
			  {               
			    e.preventDefault();
			    bp_save_note(note_div, file_id);
			    return false;
			  }
		});	
		
		$(document).keyup(function(e) {
			  if (e.keyCode == 27) { 
					$(note_div).html($(note_html).html()).show();
					$(file_id).val(def_value);
					$(file_id).hide();
			  }   // esc
		});		

		//now do first display
		$(this).hide();
		$(file_id).show();
	});

	$.ajax({
		type: "POST",
		url: EE.BASE+"&C=addons_modules&M=show_module_cp&module=backup_pro&method=l&",
		data: $.param({ "XID": EE.XID}),
		success: function(){

		},
			error: function(jqXHR, textStatus){
		}
	});
	
	//and now all the chosen 
	$("#allowed_access_levels, #db_backup_ignore_table_data, #db_backup_ignore_tables, #cron_notify_member_ids, #backup_missed_schedule_notify_member_ids").chosen({width: "100%"}); 
	
	function clean_bp_errors(backup_type)
	{
		switch(backup_type)
		{
			case 'combined':
				$("#backup_pro_system_error_db_backup_state, #backup_pro_system_error_backup_state_db_backups").hide();
				$("#backup_pro_system_error_file_backup_state").hide();
			break;
			
			case 'file_backup':
				$("#backup_pro_system_error_file_backup_state, #backup_pro_system_error_backup_state_files_backups").hide();
			break;
			
			default:
			case 'db_backup':
				$("#backup_pro_system_error_db_backup_state, #backup_pro_system_error_backup_state_db_backups").hide();
			break;
		}
	}
	
	//progressbar goodies
	$("#_backup_start").live("click", function(e) {
		
		$("#_backup_start_container").hide();
		$("#progress_bar_container").show();
		var kill_progress = false,
		backupProcess = new $.Deferred(), 
		url_base = $('#__url_base').val(),
		proc_url = $('#__backup_proc_url').val(),
		lang_backup_progress_bar_stop = $('#__lang_backup_progress_bar_stop').val();
		
		startBackup(backupProcess);
		backupProcess.progress(onProgressUpdate);
		backupProcess.fail(onBackupError);
		backupProcess.done(onBackupComplete);
		
		
		//Event Methods
		
		function onBackupComplete(data) {
			kill_progress = true;
			$('#progressbar').progressbar('option', 'value', 100);
			$('#active_item').html('');
			$('#total_items').html(data['total_items']);
			$('#active_item').html(data['msg']);
			$('#item_number').html(data['item_number']);
			$('div.heading h2.edit').html(lang_backup_progress_bar_stop);
			document.title = lang_backup_progress_bar_stop;
			$('#breadCrumb li:last').html(lang_backup_progress_bar_stop);
			$('#backup_instructions').hide();
			$("#backup_dashboard_menu").show();
			$("#_backup_download").show();
			var type = $("#__backup_type").val();
			if( type == 'backup_db' )
			{
				backup_type = 'db_backup';
			}
			else
			{
				backup_type = 'file_backup';
			}
			
			clean_bp_errors(backup_type);
		}
		
		function onProgressUpdate(data) {
			if(!data) return;
			progress = Math.floor(data['item_number']/data['total_items']*100);
			$('#progressbar').progressbar('option', 'value', progress);
			$('#total_items').html(data['total_items']);
			$('#active_item').html(data['msg']);
			$('#item_number').html(data['item_number']);
			if(data['total_items'] > 0 && data['item_number'] > 0 && data['item_number'] == data['total_items'])
			{
				$('div.heading h2.edit').html(lang_backup_progress_bar_stop);
				document.title = lang_backup_progress_bar_stop;
				$('#breadCrumb li:last').html(lang_backup_progress_bar_stop);
				$('#backup_instructions').hide();
				$("#backup_dashboard_menu").show();
				$("#_backup_download").show();
			}
		}
		
		function onBackupError(data) {
			kill_progress = true;
			alert('Error encountered. Unable to complete backup');
			console.error(data)
		}

		function startBackup(dfd){
			var kp = kill_progress;
		
			setTimeout(function(){ 
				
			
				$.ajax({
					url: proc_url,
					cache: false,
					dataType: 'html',
					error: function(data){
						dfd.reject(data);
					},
					success: function(data) {
						var _dfd = dfd;
						$.ajax({
							url: url_base+'progress',
							cache: false,
							dataType: 'json',
							error: function(data){
								_dfd.reject(data);
							},
							success: function(data) {
								onBackupComplete(data);
							}
						});
					}
				});
		
				setTimeout(updateLoop, 500);
				dfd.progress(function(){
					progress = $('#progressbar').progressbar('option','value');
		
					if (progress < 100 && !kp) {
						setTimeout(updateLoop, 1000);
					}
				});
			}, 1000);
		
			return dfd;
		}
		
		function updateLoop() {
			var _backupProcess = backupProcess,
				progress;
		
			$.ajax({
				url: url_base+'progress',
				cache: false,
				dataType: 'json',
				error: function(data){
					_backupProcess.reject(data);
				},
				success: function(data) {
					_backupProcess.notify(data)
				}
			});
		
		}	
	});

	//now the testing cron 
	$(".test_cron").click(function (e) {
		
		e.preventDefault();
		var backup_type = $(this).attr("rel");
		var url = $(this).attr("href");
		var link = this;
		
		var image_id = "#animated_" + backup_type;
		$(image_id).show();
		$(link).hide();

		$.ajax({
			url: url,
			context: document.body,
			success: function(xhr){
				alert(" Cron: Complete");
				$(image_id).hide();
				$(link).show();
				clean_bp_errors(backup_type);
			},
			error: function(data, status, errorThrown) {
				alert(" Cron: Failed with status "+ data.status +"\n" +errorThrown );
				$(image_id).hide();
				$(link).show();									
			}
		});			
		
		return false;
		
	});	
	
});