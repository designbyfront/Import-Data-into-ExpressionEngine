<?php
/*
 * STAGE ONE
 *
 * ### EE 1.6 version ###
 *
*/

	global $DSP, $LANG, $DB, $FNS, $SESS;

	// -------------------------------------------------------
	//  HTML Title and Navigation Crumblinks
	// -------------------------------------------------------
	
	$DSP->title = $LANG->line('import_data_module_name');
	$DSP->crumb = $DSP->anchor(BASE.
														 AMP.'C=modules'.
														 AMP.'M=import_data',
														 $LANG->line('import_data_module_name'));
	
	$DSP->crumb .= $DSP->crumb_item($LANG->line('import_data_stage1'));

// jQuery javascript for manipulation of the form select elements (make them dynamic)

$r = '<script type="text/javascript">
	$(document).ready(function(){

		var weblog_list = new Object();

		function grab_weblog_select() {
			$("select[name=weblog_select] option").each(function() {
				weblog_list[$(this).val()] = $(this).text();
			});
		}

		function nice_weblog_select(site_id) {
			site_id = (site_id.split("#"))[0];
			$("select[name=weblog_select] option").each(function() {
				$(this).remove();
			});
			$.each(weblog_list, function(index, value) {
				var piece = value.split(" - ");
				subpiece = piece[0].split("'.$LANG->line('import_data_site_select').' ");
				if (subpiece[1] == site_id)
					$("select[name=weblog_select]").append($("<option></option>").attr("value",index).text(piece[1]));
			});
		}

		function disable_site_select() {
			if ( $("select[name=site_select] option").length == 1 )
				$("select[name=site_select]").attr("disabled","disabled");
		}
		function enable_site_select() {
			if ( $("select[name=site_select] option").length == 1 )
				$("select[name=site_select]").removeAttr("disabled");
		}

		function disable_type_select() {
		//	$("select[name=type_select]").attr("disabled","disabled");
		}
		function enable_type_select() {
		//	$("select[name=type_select]").removeAttr("disabled");
		}

		function preselect_site(site_id) {
			$("select[name=site_select] option").each(function() {
				if (($(this).val().split("#"))[0] == site_id) {
					$(this).attr("selected","selected");
					nice_weblog_select( $("select[name=site_select]").val() );
				}
			});
		}

		grab_weblog_select();
		nice_weblog_select( $($("select[name=site_select]").get(0)).val() );
		disable_site_select();
		disable_type_select();
		preselect_site('.$SESS->userdata['site_id'].');


		$("select[name=site_select]").change(function() {
			nice_weblog_select( $(this).val() );
		});

		$("#entryform").submit(function() {
			enable_site_select();
			enable_type_select();
		});


		function nice_existing_file_select(select_name) {
			var mapping = new Object;
			$("select[name="+select_name+"] option").each(function() {
				var name = $(this).text().split(" - ");
				if (name.length > 1) {
					mapping[$(this).val()] = name[name.length-1].replace(", ", "<br />");
					name.splice(length-1);
					name.join(" - ");
					$(this).text(name[0]);
				} else {
					if (select_name == "settings_select")
						mapping[$(this).val()] = "'.$LANG->line('import_data_stage1_uploaded').': -<br />'.$LANG->line('import_data_stage1_created').': &nbsp;&nbsp;&nbsp;-";
					else
						mapping[$(this).val()] = "'.$LANG->line('import_data_stage1_uploaded').': -";
				}
			});
			return mapping;
		}

		var input_file_select_mapping = nice_existing_file_select("file_select");
		$("select[name=file_select]").change(function() {
			$("#input_file_dates").html(input_file_select_mapping[$(this).val()]);
		});

		var settings_file_select_mapping = nice_existing_file_select("settings_select");
		$("select[name=settings_select]").change(function() {
			var breaks = "";
			if (settings_file_select_mapping[$(this).val()].indexOf("<br />") == -1)
				breaks = "<br /><br />";
			$("#settings_file_dates").html(settings_file_select_mapping[$(this).val()]+breaks);
		});

	});
</script>';

	// -------------------------------------------------------
	//  Page Heading
	// -------------------------------------------------------
	
	$r .= $DSP->heading($LANG->line('import_data_stage1_heading'));


	$r .= $DSP->form_open(
						array(
								'action'	=> 'C=modules'.AMP.'M=import_data'.AMP.'P=stage_two', 
								'method'	=> 'post',
								'name'		=> 'entryform',
								'id'			=> 'entryform',
								'enctype'	=> 'multipart/form-data'
							 ),
						array(
							)
					 );

	$input_file_upload_location = Import_data_CP::get_input_file_upload_location();
	$files = getDirectoryList($input_file_upload_location);
	if (count($files) == 0) {
		$file_select  = $DSP->input_select_header('file_select\' disabled=\'disabled');
		$file_select .= $DSP->input_select_option('0', $LANG->line('import_data_stage1_no_prev_input_upload'));
	} else {
		$file_select  = $DSP->input_select_header('file_select');
		$file_select .= $DSP->input_select_option('0', $LANG->line('import_data_stage1_prev_input_upload').':');
		foreach($files as $file) {
			$display_name = $file;
			$pieces = explode('-', $file);
			if (count($pieces) > 1 && @date('Y-m-d @ H:i', $pieces[0]) !== FALSE) {
				$timestamp = $pieces[0];
				array_shift($pieces);
				$filename = implode('-', $pieces);
				$display_name = $filename.' - '.$LANG->line('import_data_stage1_uploaded').': '.@date('Y-m-d @ H:i', $timestamp);
			}
			$file_select .= $DSP->input_select_option($file, $display_name);
		}
	}
	$file_select .= $DSP->input_select_footer().'<div style="padding-left: 3px;" id="input_file_dates">'.$LANG->line('import_data_stage1_uploaded').': -</div>';

	$settings_file_upload_location = Import_data_CP::get_settings_file_upload_location();
	$files = getDirectoryList($settings_file_upload_location);
	if (count($files) == 0) {
		$settings_select  = $DSP->input_select_header('settings_select\' disabled=\'disabled');
		$settings_select .= $DSP->input_select_option('0', $LANG->line('import_data_stage1_no_prev_settings_upload'));
	} else {
		$settings_select  = $DSP->input_select_header('settings_select');
		$settings_select .= $DSP->input_select_option('0', $LANG->line('import_data_stage1_prev_settings_upload').':');
		foreach($files as $file) {
			$display_name = $file;
			$pieces = explode('-', $file);
			$count = count($pieces);
			if ($count > 1 && @date('Y-m-d @ H:i', $pieces[0]) !== FALSE) {
				$temp_pieces = $pieces;
				$timestamp = $temp_pieces[0];
				array_shift($temp_pieces);
				$filename = implode('-', $temp_pieces);
				$display_name = $filename.' - '.$LANG->line('import_data_stage1_uploaded').': '.@date('Y-m-d @ H:i', $timestamp);
			}
			if ($count > 2) {
				$bits = explode('.', $pieces[$count-1]);
				if (@date('Y-m-d @ H:i', $bits[0]) !== FALSE)
					$display_name .= ', '.$LANG->line('import_data_stage1_created').': &nbsp;&nbsp;&nbsp;'.@date('Y-m-d @ H:i', $bits[0]);
			}
			$settings_select .= $DSP->input_select_option($file, $display_name);
		}
	}
	$settings_select .= $DSP->input_select_footer().'<div style="padding-left: 3px;" id="settings_file_dates">'.$LANG->line('import_data_stage1_uploaded').': -<br />'.$LANG->line('import_data_stage1_created').': &nbsp;&nbsp;&nbsp;-</div>';

// Set up the selects with all data and in the correct syntax for jQuery to modify

	$query = $DB->query("SELECT site_id,site_label,site_name
											 FROM exp_sites");
	$site_select = $DSP->input_select_header('site_select');
	foreach($query->result as $row)
		$site_select .= $DSP->input_select_option($row['site_id'].'#'.$row['site_label'].' ['.$row['site_name'].']', $row['site_label'].' ['.$row['site_name'].']');
	$site_select .= $DSP->input_select_footer();

	$query = $DB->query("SELECT weblog_id,site_id,blog_name,blog_title
											 FROM exp_weblogs");
	$weblog_select = $DSP->input_select_header('weblog_select');
	foreach($query->result as $row)
		$weblog_select .= $DSP->input_select_option($row['weblog_id'].'#'.$row['blog_title'].' ['.$row['blog_name'].', '.$row['weblog_id'].']', $LANG->line('import_data_site_select').' '.$row['site_id'].' - '.$row['blog_title'].' ['.$row['blog_name'].', '.$row['weblog_id'].']');
	$weblog_select .= $DSP->input_select_footer();

	$type_select = $DSP->input_select_header('type_select');
	foreach($this->input_types as $id => $input)
		if ($input !== '')
			$type_select .= $DSP->input_select_option($id, $input);
	$type_select .= $DSP->input_select_footer();

	$form_submit = $DSP->input_submit($LANG->line('import_data_form_continue'));

	// -------------------------------------------------------
	//  Table and Table Headers
	// -------------------------------------------------------


	$r .= $DSP->table('', '10', '', '600px')
		 .  $DSP->tr()
		 .  $DSP->table_qcell('tableHeading', array())
		 .  $DSP->tr_c();

	$r .= $DSP->tr()
		 .  $DSP->table_qcell('\' colspan=\'4', '<h4 style="margin: 0; font-size: 1.2em;">'.$LANG->line('import_data_input_file').'</h4>')
		 .  $DSP->tr_c();

	$r .= $DSP->tr()
		 .  $DSP->table_qcell('', '')
		 .  $DSP->table_qcell('\' style=\'width: 250px;', '<input type="file" name="input_file" />')
		 .  $DSP->table_qcell('', '<div style="text-align: center; width: 50px;">- '.$LANG->line('import_data_stage1_or_cpaitalised').' -</div>')
		 .  $DSP->table_qcell('\' style=\'padding-left: 30px;', $file_select)
		 .  $DSP->tr_c();

	$r .= $DSP->tr()
		 .  $DSP->table_qcell('', '')
		 .  $DSP->table_qcell('\' colspan=\'3', '<hr />')
		 .  $DSP->tr_c();

	$r .= $DSP->tr()
		 .  $DSP->table_qcell('\' colspan=\'4', '<h4 style="margin: 0; font-size: 1.2em;">'.$LANG->line('import_data_input_settings').'</h4>')
		 .  $DSP->tr_c();

	$r .= $DSP->tr()
		 .  $DSP->table_qcell('', '')
		 .  $DSP->table_qcell('',  $DSP->input_checkbox('relationships\' id=\'relationships', 'y', 0).' <label for="relationships">'.$LANG->line('import_data_relationship_check').'</label>')
		 .  $DSP->table_qcell('\' rowspan=\'4\' valign=\'center\' style=\'text-align: center;', '<div style="height: 60px; width: 1px; border-left: 1px solid #000; margin: 0 auto;"></div><div style="text-align: center; padding: 5px 0;">- '.$LANG->line('import_data_stage1_or_cpaitalised').' -</div><div style="height: 60px; width: 1px; border-left: 1px solid #000; margin: 0 auto;"></div>')
		 .  $DSP->table_qcell('\' rowspan=\'2', '<input type="file" name="settings_file" />')
		 .  $DSP->tr_c();


	$r .= $DSP->tr()
		 .  $DSP->table_qcell('itemTitle', $LANG->line('import_data_site_select'), '10%')
		 .  $DSP->table_qcell('', $site_select)
		 //.  $DSP->table_qcell('', '')
		 //.  $DSP->table_qcell('', '')
		 .  $DSP->tr_c();

	$r .= $DSP->tr()
		 .  $DSP->table_qcell('itemTitle', $LANG->line('import_data_section_select'))
		 .  $DSP->table_qcell('', $weblog_select)
		 //.  $DSP->table_qcell('', '')
		 .  $DSP->table_qcell('', '<div style="width: 75px; height: 1px; border-top: 1px solid #000; float: left; position: relative; top: 8px"></div><div style="text-align: center; padding: 0 5px; float: left;">- '.$LANG->line('import_data_stage1_or_cpaitalised').' -</div><div style="width: 75px; height: 1px; border-top: 1px solid #000; float: left; position: relative; top: 8px"></div>')
		 .  $DSP->tr_c();


	$r .= $DSP->tr()
		 .  $DSP->table_qcell('itemTitle', $LANG->line('import_data_type_select'))
		 .  $DSP->table_qcell('', $type_select)
		 //.  $DSP->table_qcell('', '')
		 .  $DSP->table_qcell('\' style=\'padding-left: 0.5em;', $settings_select)
		 .  $DSP->tr_c();

	// ------------------------------------------------------- 
	//  Close Table and Output to $DSP->body 
	// ------------------------------------------------------- 

	$r .= $DSP->table_c();
	$r .= $form_submit;
	$r .= $DSP->form_close();


	$DSP->body .= $r;



	function getDirectoryList ($directory) {
		$results = array();
		$handler = opendir($directory);
		while ($file = readdir($handler)) {
			if ($file != "." && $file != "..") {
				$results[] = $file;
			}
		}
		closedir($handler);
		return $results;
	}
?>