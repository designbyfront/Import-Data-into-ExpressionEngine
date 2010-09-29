<?php
/*
 * STAGE THREE
 *
 * ### EE 1.6 version ###
 *
 * Dependencies:
 *  - previous_data_table.php [modules/import_data/files/previous_data_table.php]
 *
 *
 * To do:
 *  - Allow control over entry_date, status, allow_comments of entry
 *
*/

	global $DSP, $LANG, $DB, $FNS;

	function create_form_list($site_id, $weblog_id, $input_data_type, $input_data_location, $data_relations) {
		global $LANG, $DSP, $DB;

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

			$field_column_select = $DSP->input_select_header('field_column_select[]');
			$field_column_select .= $DSP->input_select_option('', '- '.$LANG->line('import_data_default_select').' -');

			//foreach ($unassigned_input_fields as $index => $heading)
			foreach ($headings as $index => $heading)
				$field_column_select .= $DSP->input_select_option($index, ++$index.' - '.$heading);
			$field_column_select .= $DSP->input_select_footer();

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

			$form_table .= $DSP->table('', '10', '', '100%');
			$form_table .= $DSP->tr()
									.  $DSP->table_qcell('', '*', '1%')
									.  $DSP->table_qcell('itemTitle', 'Title', '20%')
									.  $DSP->table_qcell('', '[title] text', '15%');
			$form_table .= $DSP->table_qcell('', $field_column_select, '10%');
			$form_table .= $DSP->table_qcell('', $DSP->input_checkbox('unique[]\' id=\'unique_title', 0).' <label for="unique_title">'.$LANG->line('import_data_unique_field').'</label>') // unique checkbox
									.  $DSP->tr_c();
			foreach($weblog_fields as $index => $row) {
				$field_title = $row['field_label'];

				// Translate ftype_id_X into field type name
				if (substr($row['field_type'], 0, 9) == 'ftype_id_')
					$row['field_type'] = $ff_fieldtypes[$row['field_type']];

				$field_text = '['.$row['field_name'].'] '.$row['field_type'];
				$field_text .= ($row['field_is_gypsy'] == 'y' ? ' (gypsy)' : '');
				$field_required = ($row['field_required'] == 'y' ? '*' : '');
				$unique_checkbox = $DSP->input_checkbox('unique[]\' id=\'unique_'.$index, $index+1);
				$form_table .= $DSP->tr()
										.  $DSP->table_qcell('', $field_required, '1%')
										.  $DSP->table_qcell('itemTitle', $field_title, '20%')
										.  $DSP->table_qcell('', $field_text, '10%')
										.  $DSP->table_qcell('', $field_column_select, '10%')
										.  $DSP->table_qcell('', $unique_checkbox.' <label for="unique_'.$index.'">'.$LANG->line('import_data_unique_field').'</label>') // unique checkbox
										.  $DSP->tr_c();
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

	$form_submit = $DSP->input_submit($LANG->line('import_data_form_continue'));

	// -------------------------------------------------------
	//  Page Heading
	// -------------------------------------------------------
	$r  = $DSP->heading($LANG->line('import_data_stage3_heading'));

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