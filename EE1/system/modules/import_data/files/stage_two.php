<?php
/*
 * STAGE Two
 *
 * ### EE 1.6 version ###
 *
 * Dependencies:
 *  - previous_data_table.php [modules/import_data/files/previous_data_table.php]
 *
 */

	global $DSP, $LANG, $DB, $FNS;

	function generate_input_headings($input_data_type, $input_data_location) {
		global $LANG, $DSP;

		switch($input_data_type)
		{
			case 'CSV' :
				$input_file_obj = new Csv_file($input_data_location);
				break;

			//case 'XML Test' :
			//	$input_file_obj = new Xml_test_file($input_data_location);
			//	break;

			case 'XML' :
				return $input_data_type.$LANG->line('import_data_unimplemented_input_type');

			default :
				return $LANG->line('import_data_unknown_input_type').' ['.$input_data_type.']';
		}

		if (!($input_file_obj instanceof Input_type))
			return $LANG->line('import_data_object_implementation');

		$headings = $input_file_obj->get_headings();
		if ($headings === FALSE) {
			$input_header_select = $LANG->line('import_data_error_input_type').' ('.$input_data_location.')';
		} else {
			$input_header_select = $DSP->input_select_header('relation_heading_select[]');
			foreach($headings as $index => $heading)
				$input_header_select .= $DSP->input_select_option($index.'#'.$heading, ++$index.' - '.$heading);
			$input_header_select .= $DSP->input_select_footer();
		}
		return $input_header_select;

	}


	// Deal with file input
	$upload_success = false;
	$target_file = '';
	if ($_FILES['input_file']['name'] != '') {
		// Upload directory will be - {system}/modules/import_data/files/upload_input_files/
		// This directory should have chmod 777 (PHP may not have authority to chmod)
		$upload_location = substr(__FILE__, 0, strrpos(__FILE__, '/')).'/upload_input_files/';
		if (!file_exists($upload_location)) {
			mkdir($upload_location);
			chmod($upload_location, 0777);
		}
		$target_file = $upload_location.time().'-'.basename($_FILES['input_file']['name']);
		$upload_success = move_uploaded_file($_FILES['input_file']['tmp_name'], $target_file);
		chmod($target_file, 0766);
	}

	$site_data = explode('#', $_POST['site_select']);
	$site_data_hidden = $DSP->input_hidden('site_select', $_POST['site_select']);
	$site_id = $site_data[0];
	$site_name = $site_data[1];

	$weblog_data = explode('#', $_POST['weblog_select']);
	$weblog_data_hidden = $DSP->input_hidden('weblog_select', $_POST['weblog_select']);
	$weblog_id = $weblog_data[0];
	$weblog_name = $weblog_data[1];

	$has_replationship = isset($_POST['relationships']) && $_POST['relationships'] == 'y';

	$input_type = $_POST['type_select'];
	$input_type_hidden = $DSP->input_hidden('type_select', $input_type);
	$input_data_location = $target_file;
	$input_data_hidden = $DSP->input_hidden('input_file', $input_data_location);

	// -------------------------------------------------------
	//  HTML Title and Navigation Crumblinks
	// -------------------------------------------------------
	
	$DSP->title = $LANG->line('import_data_module_name');
	$DSP->crumb = $DSP->anchor(BASE.
														 AMP.'C=modules'.
														 AMP.'M=import_data',
														 $LANG->line('import_data_module_name'));
		$DSP->crumb .= $DSP->crumb_item($LANG->line('import_data_stage2'));

$r = '<script type="text/javascript">
	$(document).ready(function(){

		var relationship_index = 0;
		var master_relationship_table;
		var field_list = new Object();

		function copy_dom_chunk() {
			master_relationship_table = $("#relationship_table_0").clone();
		}

		function grab_field_select() {
			$("select[name=relation_field_select[]] option").each(function() {
				field_list[$(this).text()] = $(this).val();
			});
			//console.dir(field_list);
		}

		function nice_field_select(weblog_id, index) {
			weblog_id = (weblog_id.split("#"))[0];
			var specific_field_select = $("select[name=relation_field_select[]]").get(index);
			$(specific_field_select).find("option").each(function() {
				$(this).remove();
			});
			$.each(field_list, function(index, value) {
				var piece = index.split(" - ");
				subpiece = piece[0].split("'.$LANG->line('import_data_section_select').' ");
				if (subpiece[1] == weblog_id)
					$(specific_field_select).append($("<option></option>").attr("value",value).text(piece[1]));
			});
		}

		function add_field_select_listener(index) {
			var relation_weblog_select = $("select[name=relation_weblog_select[]]").get(index);
			nice_field_select($(relation_weblog_select).val(), index);
			$(relation_weblog_select).change(function() {
				//console.log("relation_field_select "+index+" called");
				nice_field_select( $(this).val(), index );
			});
		}

		function add_relationship_link() {
			$("#add_relationship").html( "<a href=\"\">"+$($("#add_relationship").text().split(" [")).get(0)+"</a>" );
			$("#add_relationship a").click(function () {
				var previous_relationship = relationship_index;
				relationship_index++;
				var relationship_table = $(master_relationship_table).clone()
				$(relationship_table).attr("id", "relationship_table_"+relationship_index);

				var relationship_num_title = $(relationship_table).find("td.relationship_num").get(0);
				$(relationship_num_title).text( ($(relationship_num_title).text().split("#"))[0] + "#" + (relationship_index+1) );

				$(relationship_table).insertAfter("#relationship_table_"+previous_relationship);
				add_field_select_listener(relationship_index);
				return false;
			});
		}

		copy_dom_chunk();
		grab_field_select();
		nice_field_select( $($("select[name=relation_weblog_select[]]").get(0)).val() );
		add_field_select_listener(0);
		add_relationship_link();

	});
</script>';


	// -------------------------------------------------------
	//  Page Heading
	// -------------------------------------------------------
	$r .= $DSP->heading($LANG->line('import_data_stage2_heading'));

	$input_header_select = generate_input_headings($input_type, $input_data_location);

	$query = $DB->query('SELECT weblog_id,blog_name,blog_title
											 FROM exp_weblogs
											 WHERE site_id = \''.$DB->escape_str($site_id).'\'');
	$relation_weblog_select = $DSP->input_select_header('relation_weblog_select[]');
	foreach($query->result as $row)
		$relation_weblog_select .= $DSP->input_select_option($row['weblog_id'].'#'.$row['blog_title'].' ['.$row['blog_name'].', '.$row['weblog_id'].']', $row['blog_title'].' ['.$row['blog_name'].', '.$row['weblog_id'].']');
	$relation_weblog_select .= $DSP->input_select_footer();

	// check if Gypsy is installed and set boolean
	$query = $DB->query('SELECT class
											 FROM exp_extensions 
											 WHERE class = \'Gypsy\'
											');
	$gypsy_installed = $query->num_rows > 0;

	// Select normal fields
	$query = $DB->query('SELECT wb.weblog_id, wf.field_id, wf.field_name, wf.field_label
											 FROM exp_weblogs wb, exp_field_groups fg, exp_weblog_fields wf
											 WHERE wb.site_id = \''.$DB->escape_str($site_id).'\'
											 AND   wb.site_id = fg.site_id
											 AND   wb.site_id = wf.site_id
											 AND   wb.field_group = fg.group_id
											 AND   wb.field_group = wf.group_id '.
											 ($gypsy_installed ? 'AND   wf.field_is_gypsy = \'n\'' : '')
											);
	$relation_field_select = $DSP->input_select_header('relation_field_select[]');
	foreach($query->result as $row)
		$relation_field_select .= $DSP->input_select_option($row['field_id'].'#'.$row['field_label'].' ['.$row['field_name'].']', $LANG->line('import_data_section_select').' '.$row['weblog_id'].' - '.$row['field_label'].' ['.$row['field_name'].']');

	if ($gypsy_installed) {
		// Select gypsy fields
		$query = $DB->query('SELECT wf.gypsy_weblogs, wf.field_id, wf.field_name, wf.field_label
												 FROM exp_weblog_fields wf
												 WHERE wf.site_id = \''.$DB->escape_str($site_id).'\'
												 AND   wf.field_is_gypsy = \'y\'
												');
		foreach($query->result as $row) {
			$used_by = explode(' ', trim($row['gypsy_weblogs']));
			foreach ($used_by as $weblog_id)
				$relation_field_select .= $DSP->input_select_option($row['field_id'].'#'.$row['field_label'].' ['.$row['field_name'].'] (gypsy)', $LANG->line('import_data_section_select').' '.$weblog_id.' - '.$row['field_label'].' ['.$row['field_name'].'] (gypsy)');
		}
	}

	$relation_field_select .= $DSP->input_select_footer();


	$form_submit = $DSP->input_submit($LANG->line('import_data_form_continue'));

	if (!$upload_success) {
		return $DSP->error_message($LANG->line('import_data_stage2_input_error'), 1);
	} else {
		$r .= $DSP->qdiv('itemWrapper', $LANG->line('import_data_stage2_input_success'));

		$r .= $DSP->form_open(
							array(
									'action'	=> 'C=modules'.AMP.'M=import_data'.AMP.'P=stage_three', 
									'method'	=> 'post',
									'name'		=> 'entryform',
									'id'			=> 'entryform'
								 ),
							array(
								)
						 );

		require_once('previous_data_table.php');

		if ($has_replationship) {
			$r .= $DSP->qdiv('itemWrapper', $LANG->line('import_data_stage2_relationship_y'));

			$r .= $DSP->table('\' id=\'relationship_table_0', '10', '', '100%');

			$r .= $DSP->tr()
				 .  $DSP->table_qcell('', '<hr />')
				 .  $DSP->table_qcell('relationship_num\' style=\'font-weight: bold;', 'Relationship #1') //default
				 .  $DSP->tr_c();

			$r .= $DSP->tr()
				 .  $DSP->table_qcell('itemTitle', $LANG->line('import_data_input_field_select'), '10%')
				 .  $DSP->table_qcell('', $input_header_select)
				 .  $DSP->tr_c();

			$r .= $DSP->tr()
				 .  $DSP->table_qcell('style=""', '- '.$LANG->line('import_data_has_relationship').' -')
				 .  $DSP->table_qcell('', '')
				 .  $DSP->tr_c();

			$r .= $DSP->tr()
				 .  $DSP->table_qcell('itemTitle', $LANG->line('import_data_section_select'))
				 .  $DSP->table_qcell('', $relation_weblog_select)
				 .  $DSP->tr_c();

			$r .= $DSP->tr()
				 .  $DSP->table_qcell('itemTitle', '&nbsp; -- '.$LANG->line('import_data_field_select'))
				 .  $DSP->table_qcell('', $relation_field_select)
				 .  $DSP->tr_c();

			$r .= $DSP->table_c();

			$r .= $DSP->qdiv('\' id=\'add_relationship\' colspan=\'2\' style=\'padding-left: 8%; padding-top: 2em; ', $LANG->line('import_data_stage2_add_relationship_link'));

		} else {
			$r .= $DSP->qdiv('itemWrapper', $LANG->line('import_data_stage2_relationship_n'));
		}

		$r .= $form_submit;

		$r .= $DSP->form_close();

	}

	$DSP->body .= $r;

?>