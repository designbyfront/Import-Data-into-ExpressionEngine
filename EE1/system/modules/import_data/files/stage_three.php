<?php
/*
 * STAGE THREE
 *
 * ### EE 1.6 version ###
 *
 * Dependencies:
 *  - previous_data_table.php [modules/import_data/files/previous_data_table.php]
 *  - field_type.class.php [modules/import_data/files/classes/field_type.class.php]
 *
*/

require_once('classes/field_type.class.php');

	global $DSP, $LANG, $DB, $FNS;

	function create_form_list($site_id, $weblog_id, $input_data_type, $input_data_location, $data_relations) {
		global $LANG, $DSP, $DB;
		$multi_field_types = Field_Type::$multi_field_types;
		$unique_field_types = Field_Type::$unique_field_types;
		$delimiter_field_types = Field_Type::$delimiter_field_types;

		$input_file_obj_return = Import_data_CP::get_input_type_obj($input_data_type, $input_data_location);
		if (!$input_file_obj_return[0])
			return $input_file_obj_return[1];

		$input_file_obj = $input_file_obj_return[1];
		if (!($input_file_obj instanceof Input_type))
			return $LANG->line('import_data_object_implementation');

		$headings = $input_file_obj->get_headings();
		if ($headings === FALSE) {
			$form_table = $LANG->line('import_data_error_input_type').' ('.$input_data_location.')';
		} else {
			$form_table = '';

			// Relations - need to know which headings in relation_heading_select
			$input_heading_to_weblog = array();
			$input_heading_to_field = array();
			if (!empty($data_relations['input_heading'])) {
				foreach ($data_relations['input_heading'] as $index => $heading) {
					$heading_pieces = explode('#', $heading);
					$input_heading_to_weblog[$heading_pieces[0]] = $data_relations['weblog'][$index];
					$input_heading_to_field[$heading_pieces[0]] = $data_relations['field'][$index];
				}
			}

			// Lookup FF field names (check it is intalled first)
			$query = $DB->query('SHOW tables LIKE \'exp_ff_fieldtypes\'');
			$ff_fieldtypes = array();
			if (!empty($query->result)) {
				$query = $DB->query('SELECT fieldtype_id, class FROM exp_ff_fieldtypes');
				foreach ($query->result as $index => $row)
					$ff_fieldtypes['ftype_id_'.$row['fieldtype_id']] = $row['class'];
			}

			$unassigned_input_fields = array();
			$relations_table  = $DSP->table('', '10', '', '100%');
			$relations_table .= $DSP->tr()
								  .  $DSP->table_qcell('itemTitle\' colspan=\'2', '<u>'.$LANG->line('import_data_stage3_show_relationships_title').'</u>:', '10%');
			foreach($headings as $index => $heading) {
				if (isset($input_heading_to_weblog[$index]) && isset($input_heading_to_field[$index])) {
					$relations_table .= $DSP->tr()
										  .  $DSP->table_qcell('itemTitle', ($index+1).'. '.$heading, '10%');
					$weblog_pieces = explode('#', $input_heading_to_weblog[$index]);
					$field_pieces = explode('#', $input_heading_to_field[$index]);
					$relation_hidden = $DSP->input_hidden('column_field_relation['.$index.']', $weblog_pieces[0].'#'.$field_pieces[0]);
					$relations_table .= $DSP->table_qcell('', '<b>'.$weblog_pieces[1].'</b>,'.NBS.' <i>'.$field_pieces[1].'</i>'.$relation_hidden); // foreign key text AND hidden field
					$relations_table .= $DSP->tr_c();
				} else {
					$unassigned_input_fields[$index] = $heading;
				}
			}
			$relations_table .= $DSP->table_c();
			$relations_table .= '<hr width="10%" align="left" />';
			if (count($unassigned_input_fields) < count($headings)) {
				$form_table .= $relations_table;
			}

			$field_column_select_populate = $DSP->input_select_option('', '- '.$LANG->line('import_data_default_select').' -', 'y');
			//foreach ($unassigned_input_fields as $index => $heading)
			foreach ($headings as $index => $heading)
				$field_column_select_populate .= $DSP->input_select_option($index, ++$index.' - '.$heading);

			// check if Gypsy is installed and set boolean
			$query = $DB->query('SELECT class
													 FROM exp_extensions 
													 WHERE class = \'Gypsy\'
													');
			$gypsy_installed = $query->num_rows > 0;

			// Select normal fields
			$query = $DB->query('SELECT wf.field_id, wf.field_label, wf.field_name, wf.field_required, wf.field_type, '.($gypsy_installed ? 'wf.field_is_gypsy' : '\'n\' as field_is_gypsy').'
													 FROM exp_weblogs wb, exp_field_groups fg, exp_weblog_fields wf
													 WHERE wb.site_id = \''.$DB->escape_str($site_id).'\'
													 AND   wb.site_id = fg.site_id
													 AND   wb.site_id = wf.site_id
													 AND   wb.weblog_id = \''.$DB->escape_str($weblog_id).'\'
													 AND   wb.field_group = fg.group_id
													 AND   wb.field_group = wf.group_id '.
													 ($gypsy_installed ? 'AND   wf.field_is_gypsy = \'n\'' : '')
													);
			$weblog_fields = $query->result;

			if ($gypsy_installed) {
				// Select gypsy fields
				$query = $DB->query('SELECT wf.gypsy_weblogs, wf.field_id, wf.field_name, wf.field_label, wf.field_required, wf.field_type
														 FROM exp_weblog_fields wf
														 WHERE wf.site_id = \''.$DB->escape_str($site_id).'\'
														 AND   wf.field_is_gypsy = \'y\'
														');
				foreach($query->result as $row) {
					$used_by = explode(' ', trim($row['gypsy_weblogs']));
					if (in_array($weblog_id, $used_by)) {
						$weblog_fields[] = array('field_id' => $row['field_id'],
																		'field_label' => $row['field_label'],
																		'field_name' => $row['field_name'],
																		'field_required' => $row['field_required'],
																		'field_type' => $row['field_type'],
																		'field_is_gypsy' => 'y');
					}
				}
			}

			// Hard code EE fields as they are universal and not "real" fields
			$form_table .= $DSP->table('\' id=\'field_list', '10', '', '100%');
			// title field
			$form_table .= $DSP->tr()
									.  $DSP->table_qcell('', '*', '1%')
									.  $DSP->table_qcell('itemTitle', 'Title', '20%')
									.  $DSP->table_qcell('', '[title] text', '15%')
									.  $DSP->table_qcell('', $DSP->input_select_header('field_column_select[0]').$field_column_select_populate.$DSP->input_select_footer(), '10%')
									.  $DSP->table_qcell('', '--', '10%') // no delimeter input
									.  $DSP->table_qcell('', $DSP->input_checkbox('unique[]\' id=\'unique_title', 0).' <label for="unique_title">'.$LANG->line('import_data_unique_field').'</label>') // unique checkbox
									.  $DSP->tr_c();
			// category field
			$form_table .= $DSP->tr()
									.  $DSP->table_qcell('', '', '1%')
									.  $DSP->table_qcell('itemTitle', 'Category', '20%')
									.  $DSP->table_qcell('', '[category] text', '15%')
									.  $DSP->table_qcell('', $DSP->input_select_header('field_column_select[1][]', 'y', '4', '85%').$field_column_select_populate.$DSP->input_select_footer(), '10%')
									.  $DSP->table_qcell('', '<label for="delimiter_category">'.$LANG->line('import_data_delimiter_field').'</label> '.$DSP->input_text('delimiter[0]', '', '5', '10', 'input', '35px', 'id=\'delimiter_category\''), '10%') // delimeter input
									.  $DSP->table_qcell('', '--') // no unique checkbox
									.  $DSP->tr_c();
			// entry_date field
			$form_table .= $DSP->tr()
									.  $DSP->table_qcell('', '', '1%')
									.  $DSP->table_qcell('itemTitle', 'Entry Date', '20%')
									.  $DSP->table_qcell('', '[entry_date] date', '15%')
									.  $DSP->table_qcell('', $DSP->input_select_header('field_column_select[2]').$field_column_select_populate.$DSP->input_select_footer(), '10%')
									.  $DSP->table_qcell('', '--', '10%') // no delimeter input
									.  $DSP->table_qcell('', '--') // no unique checkbox
									.  $DSP->tr_c();
			// author field
			$form_table .= $DSP->tr()
									.  $DSP->table_qcell('', '', '1%')
									.  $DSP->table_qcell('itemTitle', 'Author', '20%')
									.  $DSP->table_qcell('', '[author] text', '15%')
									.  $DSP->table_qcell('', $DSP->input_select_header('field_column_select[3]').$field_column_select_populate.$DSP->input_select_footer(), '10%')
									.  $DSP->table_qcell('', '--', '10%') // no delimeter input
									.  $DSP->table_qcell('', '--') // no unique checkbox
									.  $DSP->tr_c();
			// status field
			$form_table .= $DSP->tr()
									.  $DSP->table_qcell('', '', '1%')
									.  $DSP->table_qcell('itemTitle', 'Status', '20%')
									.  $DSP->table_qcell('', '[status] text', '15%')
									.  $DSP->table_qcell('', $DSP->input_select_header('field_column_select[4]').$field_column_select_populate.$DSP->input_select_footer(), '10%')
									.  $DSP->table_qcell('', '--', '10%') // no delimeter input
									.  $DSP->table_qcell('', '--') // no unique checkbox
									.  $DSP->tr_c();

			$form_table .= $DSP->tr()
							.  $DSP->table_qcell('itemTitle\' colspan=\'6', '<hr />')
							.  $DSP->tr_c();

			$i = 5;
			foreach($weblog_fields as $index => $row) {
				$field_title = $row['field_label'];
				// Translate ftype_id_X into field type name
				if (substr($row['field_type'], 0, 9) == 'ftype_id_')
					$row['field_type'] = $ff_fieldtypes[$row['field_type']];

				$field_text = '['.$row['field_name'].'] '.$row['field_type'];
				$field_text .= ($row['field_is_gypsy'] == 'y' ? ' (gypsy)' : '');
				$field_required = ($row['field_required'] == 'y' ? '&nbsp;&nbsp;*' : '');
				$unique_checkbox = $DSP->input_checkbox('unique[]\' id=\'unique_'.$index, $index+1);
				$form_table .= $DSP->tr()
										.  $DSP->table_qcell('', $field_required, '1%')
										.  $DSP->table_qcell('itemTitle', $field_title, '20%')
										.  $DSP->table_qcell('', $field_text, '10%');
										if (in_array($row['field_type'], $multi_field_types)) {
											$form_table .= $DSP->table_qcell('', $DSP->input_select_header('field_column_select['.$i.'][]', 'y', '4', '85%').$field_column_select_populate.$DSP->input_select_footer(), '10%'); // Multi-select
										} else {
											$form_table .= $DSP->table_qcell('', $DSP->input_select_header('field_column_select['.$i.']').$field_column_select_populate.$DSP->input_select_footer(), '10%'); // Single select
										}

										if (in_array($row['field_type'], $delimiter_field_types)) {
											$form_table .= $DSP->table_qcell('', '<label for="delimiter_category">'.$LANG->line('import_data_delimiter_field').'</label> '.$DSP->input_text('delimiter['.$index.']', '', '5', '10', 'input', '35px', 'id=\'delimiter_category\''), '10%'); // delimeter input
										} else {
											$form_table .= $DSP->table_qcell('', '--', '10%'); // no delimeter input
										}

										if (in_array($row['field_type'], $unique_field_types)) {
											$form_table .= $DSP->table_qcell('', $unique_checkbox.' <label for="unique_'.$index.'">'.$LANG->line('import_data_unique_field').'</label>'); // unique checkbox
										} else {
											$form_table .= $DSP->table_qcell('', '--'); // no unique checkbox
										}
				$form_table .= $DSP->tr_c();
				$i++;
			}
			$form_table .= $DSP->table_c();

		}
		return $form_table;

	}

	$site_data = explode('#', $_POST['site_select']);
	$site_data_hidden = $DSP->input_hidden('site_select', $_POST['site_select']);
	$site_id = $site_data[0];
	$site_name = $site_data[1];

	$weblog_data = explode('#', $_POST['weblog_select']);
	$weblog_data_hidden = $DSP->input_hidden('weblog_select', $_POST['weblog_select']);
	$weblog_id = $weblog_data[0];
	$weblog_name = $weblog_data[1];

	$input_type = $_POST['type_select'];
	$input_type_hidden = $DSP->input_hidden('type_select', $input_type);
	$input_data_location = $_POST['input_file'];
	$input_data_hidden = $DSP->input_hidden('input_file', $input_data_location);

	$data_relations = array('input_heading' => (isset($_POST['relation_heading_select']) ? $_POST['relation_heading_select'] : ''),
													'weblog'        => (isset($_POST['relation_weblog_select'])  ? $_POST['relation_weblog_select'] : ''),
													'field'         =>  (isset($_POST['relation_field_select'])  ? $_POST['relation_field_select'] : ''));


	$DSP->title = $LANG->line('import_data_module_name');
	$DSP->crumb = $DSP->anchor(BASE.
														 AMP.'C=modules'.
														 AMP.'M=import_data',
														 $LANG->line('import_data_module_name'));
	$DSP->crumb .= $DSP->crumb_item($LANG->line('import_data_stage3'));

	$form_submit = $DSP->input_submit($LANG->line('import_data_form_publish'));

	// -------------------------------------------------------
	//  Page Heading
	// -------------------------------------------------------
	$r  = '<script type="text/javascript">
	$(document).ready(function(){
		$("#field_list").css("border-collapse", "collapse");
		$("#field_list td").css({"padding-bottom": "5px", "padding-top": "5px"});
		$("#field_list tr td:first").css({"padding-left": "10px", "padding-right": "10px", "font-weight": "bold"});
		$("#field_list tr:even").css("background-color", "#EDEDED");
	});
</script>';

	$r .= $DSP->heading($LANG->line('import_data_stage3_heading'));

	$r .= $DSP->qdiv('itemWrapper', $LANG->line('import_data_stage3_input_success'));

	$r .= $DSP->form_open(
						array(
								'action'	=> 'C=modules'.AMP.'M=import_data'.AMP.'P=stage_four', 
								'method'	=> 'post',
								'name'	=> 'entryform',
								'id'		=> 'entryform'
							 ),
						array(
							)
					 );

	require_once('previous_data_table.php');

// Divider table
	$r .= '<hr width="10%" align="left" />';

	$r .= create_form_list($site_id, $weblog_id, $input_type, $input_data_location, $data_relations);

	$r .= $form_submit;
	$r .= $DSP->form_close();

	$DSP->body .= $r;

?>