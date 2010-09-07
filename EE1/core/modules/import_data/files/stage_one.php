<?php
/*
 * STAGE ONE
 *
 * ### EE 1.6 version ###
 *
 *
 * To do:
 *
 *  - Get site_id from session -> set select option to this site id!
 *    Use:
 *       global $SESS;
 *       $SESS->userdata['site_id']
 *
 *  - Select previously uploaded file
 *
*/

	global $DSP, $LANG, $DB, $FNS;

	$input_types = array(
                        'CSV' => 'CSV',
                        //'XML Test' => 'XML Test',
                        '' => ''
                        );

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

		grab_weblog_select();
		nice_weblog_select( $($("select[name=site_select]").get(0)).val() );
		disable_site_select();
		disable_type_select();


		$("select[name=site_select]").change(function() {
			nice_weblog_select( $(this).val() );
		});
		

		$("#entryform").submit(function() {
			enable_site_select();
			enable_type_select();
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
								'name'	=> 'entryform',
								'id'		=> 'entryform'
							 ),
						array(
							)
					 );

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
	foreach($input_types as $id => $input)
		if ($input !== '')
			$type_select .= $DSP->input_select_option($id, $input);
	$type_select .= $DSP->input_select_footer();

	$form_submit = $DSP->input_submit($LANG->line('import_data_form_continue'));

	// -------------------------------------------------------
	//  Table and Table Headers
	// -------------------------------------------------------

	$r .= $DSP->table('', '10', '', '100%')
		 .  $DSP->tr()
		 .  $DSP->table_qcell('tableHeading', array())
		 .  $DSP->tr_c();

	$r .= $DSP->tr()
		 .  $DSP->table_qcell('itemTitle', $LANG->line('import_data_site_select'), '10%')
		 .  $DSP->table_qcell('', $site_select)
		 .  $DSP->tr_c();

	$r .= $DSP->tr()
		 .  $DSP->table_qcell('itemTitle', $LANG->line('import_data_section_select'))
		 .  $DSP->table_qcell('', $weblog_select)
		 .  $DSP->tr_c();

	$r .= $DSP->tr()
		 .  $DSP->table_qcell('itemTitle', $LANG->line('import_data_input_file'))
		 .  $DSP->table_qcell('', '<input type="file" name="input_file" />')
		 .  $DSP->tr_c();

	$r .= $DSP->tr()
		 .  $DSP->table_qcell('', '')
		 .  $DSP->table_qcell('',  $DSP->input_checkbox('relationships\' id=\'relationships', 'y', 0).' <label for="relationships">'.$LANG->line('import_data_relationship_check').'</label>')
		 .  $DSP->tr_c();

	$r .= $DSP->tr()
		 .  $DSP->table_qcell('itemTitle', $LANG->line('import_data_type_select'))
		 .  $DSP->table_qcell('', $type_select)
		 .  $DSP->tr_c();

	$r .= $DSP->tr()
		 .  $DSP->table_qcell('', '')
		 .  $DSP->table_qcell('', '<b>'.$LANG->line('import_data_notes_title').':</b> '.$LANG->line('import_data_notes_data'))
		 .  $DSP->tr_c();

	// ------------------------------------------------------- 
	//  Close Table and Output to $DSP->body 
	// ------------------------------------------------------- 

	$r .= $DSP->table_c();
	$r .= $form_submit;
	$r .= $DSP->form_close();


	$DSP->body .= $r;

?>