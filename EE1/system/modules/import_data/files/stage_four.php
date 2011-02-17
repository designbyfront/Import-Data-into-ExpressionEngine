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

//echo '<pre>';
//var_dump($_POST);
//echo '</pre>';
//die();

require_once('classes/submission.class.php');
require_once('classes/field_type.class.php');

	global $DSP, $LANG, $DB, $FNS, $log_notifications, $notifications, $notifications_count;

	// insert_data - Takes in all data provides by previous steps. Constructs POST data and POSTs to EE publish entry
	// @param site_id                     int     ID of the site where the entry is to be posted to                    (selected in stage 1)
	// @param weblog_id                   int     ID of the weblog where the entry is to be posted to                  (selected in stage 1)
	// @param input_data_type             string  Type of data file to be processed                                    (selected in stage 1)
	// @param input_data_location         string  Location of data file to be processed                                (uploaded in stage 2)
	// @param unique_columns              array   Fields which have been selected as unique                            (selected in stage 3)
	// @param addition_columns            array   Fields which have been selected to not be overwritten                (selected in stage 3)
	// @param field_column_mapping        array   Mapping of weblog fields to data file columns                        (selected in stage 3)
	// @param column_field_replationship  array   Mapping of relationships between data file column and weblog field   (selected in stage 2)
	function insert_data($site_id, $weblog_id, $input_data_type, $input_data_location, $delimiter_columns, $unique_columns, $addition_columns, $field_column_mapping, $column_field_replationship) {

		//echo "\n<br />1 - Function Memory: ".memory_get_usage(true)."<br /><br />\n\n";
		global $LANG, $DSP, $DB, $log_notifications, $notifications, $notifications_count;
		$added_entry_ids = array(); // Array of entries which have already been added

		// Hard coded EE fields (title, title_url, category, entry_date, author, status)
		$num_ee_special_fields = 6;
		$title_index      = 0;
		$url_title_index  = 1;
		$category_index   = 2;
		$entry_date_index = 3;
		$author_id_index  = 4;
		$status_index     = 5;

		// Select the correct input_type object depending on type selected
		$input_file_obj_return = Import_data_CP::get_input_type_obj($input_data_type, $input_data_location);
		if (!$input_file_obj_return[0])
			return $input_file_obj_return[1];

		// Check that input_type object is an instance of Input_type (and thus implements all necessary functions)
		$input_file_obj = $input_file_obj_return[1];
		if (!($input_file_obj instanceof Input_type))
			return $LANG->line('import_data_object_implementation');

		// Start reading from the data file (error if not readable)
		if ($input_file_obj->start_reading_rows() === FALSE)
			return $LANG->line('import_data_error_input_type').' ['.$input_data_location.']';
		$headers = $input_file_obj->read_row(); // Discard the headers
		$input_column_num = count($headers);
		unset($headers);

		// --- FF Fieldtypes -----------------------------
		// Lookup FF field names
		$query = $DB->query('SHOW tables LIKE \'exp_ff_fieldtypes\''); // Check if installed
		$ff_fieldtypes = array();
		if (!empty($query->result)) {
			$query = $DB->query('SELECT fieldtype_id, class FROM exp_ff_fieldtypes');
			foreach ($query->result as $index => $row)
				$ff_fieldtypes['ftype_id_'.$row['fieldtype_id']] = $row['class'];
		}
		// ------------------------------------------------

		// --- Gypsy --------------------------------------
		// Check if Gypsy is installed
		$query = $DB->query('SELECT class
									 FROM exp_extensions 
									 WHERE class = \'Gypsy\'
									');
		$gypsy_installed = $query->num_rows > 0;
		// ------------------------------------------------

		// Populate array with all fields in the weblog
		// Select normal fields
		$query = $DB->query('SELECT wf.field_id, wf.field_label, wf.field_name, wf.field_required, wf.field_type, wf.field_fmt, '.($gypsy_installed ? 'wf.field_is_gypsy' : '\'n\' as field_is_gypsy').'
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
		unset($query);

		if ($gypsy_installed) {
			// Select gypsy fields
			$query = $DB->query('SELECT wf.gypsy_weblogs, wf.field_id, wf.field_name, wf.field_label, wf.field_required, wf.field_type, wf.field_fmt
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
													 'field_fmt' => $row['field_fmt'],
													 'field_is_gypsy' => 'y');
				}
			}
			unset($query);
		}

//echo'<pre>';
//var_dump($weblog_fields);
//var_dump($field_column_mapping);
//echo'</pre>';
//echo "\n<br />2 - Start File Memory: ".memory_get_usage(true)."<br /><br />\n\n";

		// Initial variable before looking through data file
		$output = '';
		$submitted_entries = 0;
		$entry_number = 0;

		$notifications = '';
		$log_notifications = array();
		$notifications_count = 0;

		$unique_columns_count = count($unique_columns);

		// Loop through all rows in the data file
		while (($input_row = $input_file_obj->read_row()) !== FALSE) {

			// Detect malformed CSV rows and ignore
			if (count($input_row) != $input_column_num) {
				$entry_number++;
				$output .= '<li>'.$LANG->line('import_data_stage4_row_error').$entry_number.' '.$LANG->line('import_data_stage4_invalid_row_structure_1').$input_column_num.$LANG->line('import_data_stage4_invalid_row_structure_2').count($input_row).$LANG->line('import_data_stage4_invalid_row_structure_3').'</li>'."\n";
				continue;
			}

			// Construct row POST call
			$post_data = array();
			$post_data["site_id"] = $site_id;
			$post_data["weblog_id"] = $weblog_id;
			$post_data["title"] = (isset($input_row[$field_column_mapping[$title_index]]) ? $input_row[$field_column_mapping[$title_index]] : '');
			$post_data["url_title"] = (isset($input_row[$field_column_mapping[$url_title_index]]) ? $input_row[$field_column_mapping[$url_title_index]] : '');
			$post_data["category"] = array(); // Calculated later
			$post_data["entry_date"] = (empty($input_row[$field_column_mapping[$entry_date_index]]) ? date("Y-m-d H:i A") : date("Y-m-d H:i A", (is_numeric($input_row[$field_column_mapping[$entry_date_index]]) ? $input_row[$field_column_mapping[$entry_date_index]] : strtotime($input_row[$field_column_mapping[$entry_date_index]].' '.date('T')))));
			$post_data["author_id"] = ''; // Calcaulted later
			$post_data["status"] = 'open'; // Calculated later

			// Other EE fields
			$post_data["allow_comments"] = 'y';
			$post_data["structure_parent"] = '';
			//$post_data["structure_uri"] = '';
			$post_data["structure_template_id"] = '';

			// -----------------------------------------

			// Check if an entry already exists (using unique tickbox)
			// - These details from this can be used as defaults when updating
			$existing_entry = FALSE;
			if ($unique_columns_count > 0) {
				//entry_id, wt.title
				//wt.*, wd.*
				$query = 'SELECT *
								FROM exp_weblog_data wd, exp_weblog_titles wt
								WHERE wd.entry_id = wt.entry_id
								AND   wd.site_id = '.$DB->escape_str($site_id).'
								AND   wd.weblog_id = '.$DB->escape_str($weblog_id);

				$unique_notification = '';
				foreach($unique_columns as $unique_column) {
					if ($unique_column === "0") {
						$query .=  '		AND   wt.title = \''.$DB->escape_str($input_row[$field_column_mapping[$title_index]]).'\'';
						$unique_notification .= 'Title = \''.$input_row[$field_column_mapping[$title_index]].'\', ';
					} else {
						$query .=  '		AND   wd.field_id_'.$DB->escape_str($weblog_fields[$unique_column-1]['field_id']).' = \''.$DB->escape_str($input_row[$field_column_mapping[$unique_column+($num_ee_special_fields-1)]]).'\'';
						$unique_notification .= $weblog_fields[$unique_column-1]['field_label'].' = \''.$input_row[$field_column_mapping[$unique_column+($num_ee_special_fields-1)]].'\', ';
					}
				}

				$query .= '				 LIMIT 2';
				//$query."<br />\n"
				$query = $DB->query($query);
				$existing_entry = $query->result;
				//var_dump($unique_notification);

				if ($existing_entry !== NULL && isset($existing_entry[0])) {
					$existing_entry = $existing_entry[0];
					$post_data["entry_id"] = $existing_entry["entry_id"];

					if ($query->num_rows > 1) {
						$unique_notification =  substr($unique_notification,0,-2);
						log_notification($LANG->line('import_data_stage4_notification_row_1').($entry_number+1).$LANG->line('import_data_stage4_notification_row_2').$LANG->line('import_data_stage4_notification_unique_1').$unique_notification.$LANG->line('import_data_stage4_notification_unique_2').'#'.$existing_entry["entry_id"].$LANG->line('import_data_stage4_notification_unique_3'));
					}

				}
				unset($query);
				unset($unique_notification);
			}
			//echo "\n<br />&nbsp;&nbsp;&nbsp;2A - Unique Check Memory: ".memory_get_usage(true)."<br />\n";

			// -----------------------------------------

			// Default title if empty
			if (!isset($input_row[$field_column_mapping[$title_index]]) || (empty($input_row[$field_column_mapping[$title_index]]) && $input_row[$field_column_mapping[$title_index]] !== 0)) {
				if (isset($existing_entry) && !empty($existing_entry)) {
					$post_data["title"] = $existing_entry["title"];
				} else {
					$entry_number++;
					$output .= '<li>'.$LANG->line('import_data_stage4_row_error').$entry_number.' '.$LANG->line('import_data_stage4_no_title').'</li>'."\n";
					continue;
				}
			}

			// -----------------------------------------

			// Look up the existing categories and groups
			$field_column_mapping[$category_index] = array_values(array_filter($field_column_mapping[$category_index]));
			if (!empty($post_data["entry_id"]) && $post_data["entry_id"] !== 0 && (in_array($post_data["entry_id"], $added_entry_ids) || empty($field_column_mapping[$category_index]) || isset($addition_columns[0]))) {

				$query = 'SELECT cat_id
								FROM exp_category_posts
								WHERE entry_id = '.$DB->escape_str($post_data["entry_id"]);
				$query = $DB->query($query);
				$existing_category_ids = $query->result;
				foreach ($existing_category_ids as $category_id) {
					$post_data["category"][] = $category_id['cat_id'];
				}
			}
			if (!empty($field_column_mapping[$category_index])) {
				// Look up the category group id(s) associated with this weblog
				$query = 'SELECT cat_group
								FROM exp_weblogs
								WHERE weblog_id = '.$DB->escape_str($weblog_id).'
								AND site_id = '.$DB->escape_str($site_id);
				//echo "<br />\n$query<br />\n";
				$query = $DB->query($query);
				$cat_group_ids_raw = $query->result;
				$cat_group_ids = array();
				if ($query->num_rows > 0)
					$cat_group_ids = explode('|', $cat_group_ids_raw[0]['cat_group']);
				// Look up the category id(s) associated with each category group
				foreach($field_column_mapping[$category_index] as $category) {
					if (isset($delimiter_columns[0]) && !empty($delimiter_columns[0]))
						$input_row[$category] = explode($delimiter_columns[0], $input_row[$category]);
					if (!is_array($input_row[$category]))
						$input_row[$category] = array($input_row[$category]);
					foreach ($input_row[$category] as $given_category) {
						$cat_found = FALSE;
						if (!isset($given_category) || (empty($given_category) && $given_category !== 0))
							continue;
						foreach ($cat_group_ids as $cat_group_id) {
							$query = 'SELECT  ct.cat_id
											FROM exp_category_groups cg, exp_categories ct
											WHERE cg.group_id = '.$DB->escape_str($cat_group_id).'
											AND cg.site_id = '.$DB->escape_str($site_id).'
											AND ct.group_id = cg.group_id
											AND ct.site_id = '.$DB->escape_str($site_id).'
											AND ct.cat_name = "'.$DB->escape_str($given_category).'"';
							//echo " - &nbsp;&nbsp;$query<br />\n";
							$query = $DB->query($query);
							$category_id = $query->result;
							if ($query->num_rows > 0) {
								$post_data["category"] = array_unique(array_merge($post_data["category"], array_values($category_id[0])));
								$cat_found = TRUE;
							}
						}
						if (!$cat_found)
							log_notification($LANG->line('import_data_stage4_notification_row_1').($entry_number+1).$LANG->line('import_data_stage4_notification_row_2').$LANG->line('import_data_stage4_notification_category_1').$given_category.$LANG->line('import_data_stage4_notification_category_2'));
					}
				}
			}

			// -----------------------------------------


			// Look up the corresonding id from username
			if (!empty($field_column_mapping[$author_id_index])) {
				if (empty($input_row[$field_column_mapping[$author_id_index]])) {
					if (isset($existing_entry) && !empty($existing_entry)) {
						$post_data["author_id"] = $existing_entry["author_id"];
					} else {
						$entry_number++;
						$output .= '<li>'.$LANG->line('import_data_stage4_row_error').$entry_number.' '.$LANG->line('import_data_stage4_no_author').' - "'.$post_data["title"].'"</li>'."\n";
						continue;
					}
				} else {
					$query = 'SELECT member_id FROM exp_members WHERE username = \''.$DB->escape_str($input_row[$field_column_mapping[$author_id_index]]).'\'';
					$query = $DB->query($query);
					// Author not found
					if ($query->num_rows == 0) {
						$entry_number++;
						$output .= '<li>'.$LANG->line('import_data_stage4_row_error').$entry_number.' '.$LANG->line('import_data_stage4_missing_author_1').$input_row[$field_column_mapping[$author_id_index]].$LANG->line('import_data_stage4_missing_author_2').' - "'.$post_data["title"].'"</li>'."\n";
						continue;
					}
					$weblog_author = $query->result;

					// If not an admin (group 1), check permissions
					if ($weblog_author[0]['member_id'] != 1) {
						$query = 'SELECT me.member_id
										FROM exp_members me, exp_weblog_member_groups wmg
										WHERE me.member_id = '.$DB->escape_str($weblog_author[0]['member_id']).'
										AND   me.group_id = wmg.group_id
										AND   wmg.weblog_id = '.$DB->escape_str($weblog_id);
						$query = $DB->query($query);
						// Author not authorised
						if ($query->num_rows == 0) {
							$entry_number++;
							$output .= '<li>'.$LANG->line('import_data_stage4_row_error').$entry_number.' '.$LANG->line('import_data_stage4_unauthorised_author_1').$input_row[$field_column_mapping[$author_id_index]].$LANG->line('import_data_stage4_unauthorised_author_2').' - "'.$post_data["title"].'"</li>'."\n";
							continue;
						}
					}
					// Author found
					$post_data["author_id"] = $weblog_author[0]['member_id'];
				}
			}

			// -----------------------------------------

			// Look up if status provided is valid
			//if (!empty($field_column_mapping[$status_index])) {
				// If empty, try and get from existing
				if (empty($input_row[$field_column_mapping[$status_index]])) {
					if (isset($existing_entry) && !empty($existing_entry)) {
						$post_data["status"] = $existing_entry["status"];
					}
				// If open or closed, just assign (these are the two defaults and are always valid)
				} else if (strtolower($input_row[$field_column_mapping[$status_index]]) == 'open' || strtolower($input_row[$field_column_mapping[$status_index]]) == 'closed') {
					$post_data["status"] = $input_row[$field_column_mapping[$status_index]];
				// If it's something else, make sure it is valid
				} else {
					$query = 'SELECT st.status_id
								 FROM exp_statuses st, exp_weblogs wb, exp_status_groups sg
								 WHERE wb.weblog_id = '.$DB->escape_str($weblog_id).'
								 AND   wb.site_id = '.$DB->escape_str($site_id).'
								 AND   wb.status_group = sg.group_id
								 AND   st.group_id = sg.group_id
								 AND   st.status = \''.$DB->escape_str($input_row[$field_column_mapping[$status_index]]).'\'';
					$query = $DB->query($query);
					if ($query->num_rows == 0) {
						$entry_number++;
						$output .= '<li>'.$LANG->line('import_data_stage4_row_error').$entry_number.' '.$LANG->line('import_data_stage4_invalid_status_1').$input_row[$field_column_mapping[$status_index]].$LANG->line('import_data_stage4_invalid_status_2').' - "'.$post_data["title"].'"</li>'."\n";
						continue;
					}
					$post_data["status"] = $input_row[$field_column_mapping[$status_index]];
				}
			//}

			// -----------------------------------------

			$invalid_input = FALSE;
			foreach ($field_column_mapping as $index => $field_id) {
				// Ignore hard coded EE fields (title, category, entry_date, author, status)
				if ($index < $num_ee_special_fields) continue;
				$index -= $num_ee_special_fields;

				// Translate ftype_id_X into field type name
				if (substr($weblog_fields[$index]['field_type'], 0, 9) == 'ftype_id_')
					$weblog_fields[$index]['field_type'] = $ff_fieldtypes[$weblog_fields[$index]['field_type']];

				// Create post data from field_type class//
				if (is_array($field_id)) {
					$value = array();
					foreach($field_id as $single_field_id) {
						if (isset($delimiter_columns[$index]) && !empty($delimiter_columns[$index])) {
							$value[] = array_values(array_filter(explode($delimiter_columns[$index], $input_row[$single_field_id])));
						} else {
							$value[] = (isset($input_row[$single_field_id]) ? $input_row[$single_field_id] : '');
						}
					}
				} else {
					$value = (isset($input_row[$field_id]) ? $input_row[$field_id] : '');
				}

				$addition_override = FALSE;
				if (isset($addition_columns[$index+1]))
					$addition_override = TRUE;

				//var_dump($value);
				$field_type_object = new Field_Type($index,
																$entry_number,
																$field_id,
																$site_id,
																$weblog_id,
																$weblog_fields[$index],
																$value,
																$existing_entry,
																$added_entry_ids,
																$column_field_replationship,
																$addition_override);
				$field_post = array();
				if (($field_return = $field_type_object->post_value()) === FALSE && $weblog_fields[$index]['field_required'] == 'y') {
					return $LANG->line('import_data_stage4_missing_fieldtype_1').$weblog_fields[$index]['field_type'].$LANG->line('import_data_stage4_missing_fieldtype_2');
				} else if ($field_return === FALSE) {
					log_notification($LANG->line('import_data_stage4_notification_fieldtype_1').$index.$LANG->line('import_data_stage4_notification_fieldtype_2').$weblog_fields[$index]['field_type'].$LANG->line('import_data_stage4_notification_fieldtype_3'));
					$field_post['field_id_'.$weblog_fields[$index]['field_id']] = '';
				}

				if (isset($field_return['notification']) && !empty($field_return['notification']))
					log_notification($field_return['notification']);
				if (isset($field_return['post']))
					$field_post = $field_return['post'];

				unset($field_type_object);

				if ($weblog_fields[$index]['field_required'] == 'y') {
					if (empty($field_post))
						$invalid_input = TRUE;
					foreach ($field_post as $check_data) {
						if (empty($check_data) && $check_data !== 0)
							$invalid_input = TRUE;
					}
				}

				$post_data = array_merge($post_data, $field_post);

				//echo "\$weblog_fields[$index]['field_id'] = '{$weblog_fields[$index]['field_id']}'\n";
				//$post_data['field_id_'.$weblog_fields[$index]['field_id']] = $input_row[$field_id];
			}
			//echo "\n<br />&nbsp;&nbsp;&nbsp;2B - FieldType Memory: ".memory_get_usage(true)."<br />\n";

			if ($invalid_input) {
					$entry_number++;
					$output .= '<li>'.$LANG->line('import_data_stage4_row_error').$entry_number.' '.$LANG->line('import_data_stage4_missing_data').' - "'.$post_data["title"].'"</li>'."\n";
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

		$final = '<h2>'.$submitted_entries.' ('.$LANG->line('import_data_stage4_of').$line_count.') '.$LANG->line('import_data_stage4_summary_heading').'</h2>'."\n\n".'<ul>'.$output.'</ul>';
		if (!empty($notifications))
			$final .= "\n<br />\n".'<h2>'.$notifications_count.' '.$LANG->line('import_data_stage4_notifications').'</h2>'."\n\n".'<ul>'.$notifications.'</ul>';
		return $final;
	}

	function log_notification($returned_notifications) {
		global $log_notifications, $notifications, $notifications_count;

		if (!is_array($returned_notifications))
			$returned_notifications = array($returned_notifications);

		foreach($returned_notifications as $notification) {
			$notification = '<li>'.$notification.'</li>'."\n";
			if (!isset($log_notifications[sha1($notification)])) {
				$notifications .= $notification;
				$notifications_count++;
				$log_notifications[sha1($notification)] = 1;
			} else {
				$log_notifications[sha1($notification)]++;
			}
		}
	}

	if (isset($_POST['settings_file'])) {
		$current_post = json_decode(file_get_contents($_POST['settings_file']), TRUE);
		$current_post['input_file'] = $_POST['input_file'];
	} else {
		$current_post = $_POST;
	}

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

	$delimiter_columns = (isset($current_post['delimiter']) ? $current_post['delimiter'] : array());
	$unique_columns = (isset($current_post['unique']) ? $current_post['unique'] : array());
	$addition_columns = (isset($current_post['addition']) ? $current_post['addition'] : array());
	$field_column_mapping = (isset($current_post['field_column_select']) ? $current_post['field_column_select'] : array());
	$column_field_replationship = (isset($current_post['column_field_relation']) ? $current_post['column_field_relation'] : array());

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
	$r .= insert_data($site_id, $weblog_id, $input_type, $input_data_location, $delimiter_columns, $unique_columns, $addition_columns, $field_column_mapping, $column_field_replationship);

	if (!isset($_POST['settings_file'])) {
		$r .= $DSP->form_open(
							array(
									'action'		=> 'C=modules'.AMP.'M=import_data'.AMP.'P=export_settings', 
									'method'		=> 'post',
									'name'		=> 'entryform',
									'id'			=> 'entryform',
									'enctype'	=> 'multipart/form-data'
								 ),
							array(
								)
						 );
		$r .= $DSP->input_hidden('referal', 'stage_four');
		$r .= $DSP->input_hidden('data', json_encode($current_post));
		$r .= $DSP->input_submit($LANG->line('import_data_form_export_settings'));
		$r .= $DSP->form_close();
	}

	$DSP->body .= $r;

?>
