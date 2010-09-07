<?php
/*
 * STAGE FOUR
 *
 * ### EE 1.6 version ###
 *
 * Dependencies:
 *  - submission.class.php [modules/import_data/files/classes/submission.class.php]
 *  - field_type.class.php [modules/import_data/files/classes/field_type.class.php]
 *  - ext.input_loader.php [extensions/ext.input_loader.php]
 *
*/

require_once('classes/submission.class.php');
require_once('classes/field_type.class.php');

	global $DSP, $LANG, $DB, $FNS;

	function insert_data($site_id, $weblog_id, $input_data_type, $input_data_location, $unique_columns, $field_column_mapping, $column_field_replationship) {
		//echo "\n<br />1 - Function Memory: ".memory_get_usage(true)."<br /><br />\n\n";
		global $LANG, $DSP, $DB;
		$added_entry_ids = array();

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

		$unique_columns_count = count($unique_columns);

		if ($input_file_obj->start_reading_rows() === FALSE)
			return $LANG->line('import_data_error_input_type').' ('.$input_data_location.')';

		// Discard the headers
		$input_file_obj->read_row();

			// Lookup FF field names
			$query = $DB->query('SELECT fieldtype_id, class FROM exp_ff_fieldtypes');
			$ff_fieldtypes = array();
			foreach ($query->result as $index => $row)
				$ff_fieldtypes['ftype_id_'.$row['fieldtype_id']] = $row['class'];

			// Select normal fields
			$query = $DB->query('SELECT wf.field_id, wf.field_label, wf.field_name, wf.field_required, wf.field_type, wf.field_is_gypsy
													 FROM exp_weblogs wb, exp_field_groups fg, exp_weblog_fields wf
													 WHERE wb.site_id = \''.$DB->escape_str($site_id).'\'
													 AND   wb.site_id = fg.site_id
													 AND   wb.site_id = wf.site_id
													 AND   wb.weblog_id = \''.$DB->escape_str($weblog_id).'\'
													 AND   wb.field_group = fg.group_id
													 AND   wb.field_group = wf.group_id
													 AND   wf.field_is_gypsy = \'n\'
													');
			$weblog_fields = $query->result;
			unset($query);

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
			unset($query);
//echo'<pre>';
//var_dump($weblog_fields);
//var_dump($field_column_mapping);
//echo'</pre>';
//echo "\n<br />2 - Start File Memory: ".memory_get_usage(true)."<br /><br />\n\n";
		$submitted_entries = 0;
		$entry_number = 0;
		while (($input_row = $input_file_obj->read_row()) !== FALSE) {
			// Construct row POST call
			$post_data = array();
			$post_data["site_id"] = $site_id;
			$post_data["weblog_id"] = $weblog_id;
			$post_data["entry_id"] = '';
			$post_data["entry_date"] = date("Y-m-d H:i A");
			//$post_data["status"] = 'open';
			$post_data["allow_comments"] = 'y';
			$post_data["structure_parent"] = '';
			//$post_data["structure_uri"] = '';
			$post_data["structure_template_id"] = '';
			$post_data["title"] = $input_row[$field_column_mapping[0]];
			$post_data["url_title"] = '';

			// Check if already exists (from unique) - details from this can be used as defaults and to update
			$existing_entry = FALSE;
			if ($unique_columns_count > 0) {
				//entry_id, wt.title
				//wt.*, wd.*
				$query = 'SELECT *
									FROM exp_weblog_data wd, exp_weblog_titles wt
									WHERE wd.entry_id = wt.entry_id
									AND   wd.site_id = '.$DB->escape_str($site_id).'
									AND   wd.weblog_id = '.$DB->escape_str($weblog_id);

				foreach($unique_columns as $unique_column) {
					if ($unique_column === "0")
						$query .=  '		AND   wt.title = \''.$DB->escape_str($input_row[$field_column_mapping[0]]).'\'';
					else
						$query .=  '		AND   wd.field_id_'.$DB->escape_str($weblog_fields[$unique_column-1]['field_id']).' = \''.$DB->escape_str($input_row[$field_column_mapping[$unique_column]]).'\'';
				}
				$query .= '				 LIMIT 1';
				//echo $query."<br /><br />\n";
				$query = $DB->query($query);
				$existing_entry = $query->result;
				unset($query);
				if ($existing_entry !== NULL) {
					$existing_entry = $existing_entry[0];
					$post_data["entry_id"] = $existing_entry["entry_id"];
					if ($input_row[$field_column_mapping[0]] === NULL)
						$post_data["title"] = $existing_entry["title"];
				}
			}
			//echo "\n<br />&nbsp;&nbsp;&nbsp;2A - Unique Check Memory: ".memory_get_usage(true)."<br />\n";

			$invalid_input = FALSE;
			foreach ($field_column_mapping as $index => $field_id) {
				// ignore title
				if ($index == 0) continue;
				$index--;

				// Translate ftype_id_X into field type name
				if (substr($weblog_fields[$index]['field_type'], 0, 9) == 'ftype_id_')
					$weblog_fields[$index]['field_type'] = $ff_fieldtypes[$weblog_fields[$index]['field_type']];

				// Create post data from field_type class
				$field_type_object = new Field_Type($index, $field_id, $site_id, $weblog_id, $weblog_fields[$index], $input_row[$field_id], $existing_entry, $added_entry_ids, $column_field_replationship);
				if (($field_post = $field_type_object->post_value()) === FALSE)
					return $LANG->line('import_data_stage4_missing_fieldtype_1').$weblog_fields[$index]['field_type'].$LANG->line('import_data_stage4_missing_fieldtype_2');

				unset($field_type_object);

				if ($weblog_fields[$index]['field_required'] == 'y') {
					// ALSO CHECK IF TITLE NULL SOMEWHERE?
					
					foreach ($field_post as $check_data) {
						if (empty($check_data))
							$invalid_input = TRUE;
					}
				}

				$post_data = array_merge($post_data, $field_post);

				//echo "\$weblog_fields[$index]['field_id'] = '{$weblog_fields[$index]['field_id']}'\n";
				//$post_data['field_id_'.$weblog_fields[$index]['field_id']] = $input_row[$field_id];
			}
			//echo "\n<br />&nbsp;&nbsp;&nbsp;2B - FieldType Memory: ".memory_get_usage(true)."<br />\n";

			if (empty($post_data["title"]))
				$invalid_input = TRUE;

			if ($invalid_input) {
					$entry_number++;
					$output .= '<li>'.$LANG->line('import_data_stage4_missing_data').$entry_number.' - "'.$post_data["title"].'"</li>'."\n";
					continue;
			}

			//echo '<pre>';
			//var_dump($post_data);
			//echo '</pre>';

			// Execute submission through Submission object
			$submission = new Submission($post_data);
			if (!$submission->failure()) {
				if ($submission->save()) {
					$added_entry_ids[] = $GLOBALS['input_loader_entry_id'];
					$output .= '<li>'.$LANG->line('import_data_stage4_submission_success');
					$submitted_entries++;
				} else {
					$output .= '<li>'.$LANG->line('import_data_stage4_submission_failed');
				}
			} else {
				$output .= '<li>'.$LANG->line('import_data_stage4_submission_object_failed');
			}
			unset($submission);
			//echo "\n<br />&nbsp;&nbsp;&nbsp;2C - Submission Memory: ".memory_get_usage(true)."<br />\n";
			$entry_number++;
			$output .= $LANG->line('import_data_stage4_row').$entry_number.' - "'.$post_data["title"].'"</li>'."\n";
			unset($post_data);
			//echo "\n<br />&nbsp;&nbsp;&nbsp;2D - Finished Memory: ".memory_get_usage(true)."<br /><br />\n";
		}
		//echo "\n<br />3 - Memory: ".memory_get_usage(true)."<br /><br />\n\n";
		//var_dump($added_entry_ids);

		$line_count = $input_file_obj->stop_reading_rows();
		unset($input_file_obj);

		return '<h2>'.$submitted_entries.' ('.$LANG->line('import_data_stage4_of').$line_count.') '.$LANG->line('import_data_stage4_summary_heading').'</h2>'."\n\n".'<ul>'.$output.'</ul>';
	}



	$current_post = $_POST;
	$site_data = explode('#', $current_post['site_select']);
	$site_data_hidden = $DSP->input_hidden('site_select', $current_post['site_select']);
	$site_id = $site_data[0];
	$site_name = $site_data[1];

	$weblog_data = explode('#', $current_post['weblog_select']);
	$weblog_data_hidden = $DSP->input_hidden('weblog_select', $current_post['weblog_select']);
	$weblog_id = $weblog_data[0];
	$weblog_name = $weblog_data[1];

	$input_type = $current_post['type_select'];
	$input_type_hidden = $DSP->input_hidden('type_select', $input_type);
	$input_data_location = $current_post['input_file'];
	$input_data_hidden = $DSP->input_hidden('input_file', $input_data_location);

	$unique_columns = $current_post['unique'];
	$field_column_mapping = $current_post['field_column_select'];
	$column_field_replationship = $current_post['column_field_relation'];

	// Set global value for input_loader extension to retain control from EE
	$GLOBALS['input_loader_end_submit_new_form'] = true;

	$DSP->title = $LANG->line('import_data_module_name');
	$DSP->crumb = $DSP->anchor(BASE.
														 AMP.'C=modules'.
														 AMP.'M=import_data',
														 $LANG->line('import_data_module_name'));
		$DSP->crumb .= $DSP->crumb_item($LANG->line('import_data_stage4'));

	// -------------------------------------------------------
	//  Page Heading
	// -------------------------------------------------------
	$r = $DSP->heading($LANG->line('import_data_stage4_heading'));

	//$r .= '<pre>'.print_r($current_post, true).'</pre>';
	$r .= insert_data($site_id, $weblog_id, $input_type, $input_data_location, $unique_columns, $field_column_mapping, $column_field_replationship);

	$DSP->body .= $r;

?>
