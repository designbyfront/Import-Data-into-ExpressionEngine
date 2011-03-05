<?php
/**
 * Import Data into ExpressionEngine
 *
 * ### EE 1.6 version ###
 *
 *
 * STAGE FOUR
 *
 *
 * Created by Front
 * Useful, memorable and satisfying things for the web.
 * We create amazing online experiences that delight users and help our clients grow.
 *
 * Support
 * Please use the issues page on GitHub: http://github.com/designbyfront/Import-Data-into-ExpressionEngine/issues
 * or email us: info@designbyfront.com
 *
 * License and Attribution
 * This work is licensed under the Creative Commons Attribution-Share Alike 3.0 Unported.
 * As this program is licensed free of charge, there is no warranty for the program, to the extent permitted by applicable law.
 * For more details, please see: http://github.com/designbyfront/Import-Data-into-ExpressionEngine/#readme
 *
 *
 * @package DesignByFront
 * @author  Alistair Brown
 * @author  Shann McNicholl
 * @link    http://github.com/designbyfront/Import-Data-into-ExpressionEngine
 * 
 */

class Stage_four extends Stage {

	protected $lib;
	protected $input_file_obj;

	private $output;
	private $notification;

	private $added_entry_ids;
	private $entry_number;
	private $current_post;
	private $ee_fields_index;
	private $invalid_input;


	function Stage_four ()
	{
		$this->__construct();
	}


	function __construct ()
	{
		global $DSP, $LANG;

		// Set global value for input_loader extension to retain control from EE
		$GLOBALS['input_loader_end_submit_new_form'] = TRUE;

		$this->lib = new Stage_library();
		$this->output = new Results();
		$this->notification = new Results();
		$DSP->title = $LANG->line('import_data_module_name');
		$DSP->crumb = $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=import_data',$LANG->line('import_data_module_name')).$DSP->crumb_item($LANG->line('import_data_stage4'));

		$this->ee_fields_index = parent::get_ee_fields_index_array();
		$this->ee_fields_index['num_ee_fields'] = count($this->ee_fields_index);
		$this->added_entry_ids = array();
		$this->row_number = 0;
		$this->current_post = $_POST;
		$this->invalid_input = FALSE;

		$this->delimiter = array();
		$this->unique = array();
		$this->addition = array();
		$this->field_column_select = array();
		$this->column_field_relation = array();
	}


	public function process_post_data()
	{
		global $LANG;
		// Check for settings file import
		if (isset($this->settings_file)) {
			if (!$this->lib->check_access($this->settings_file))
				throw new Import_data_module_exception($LANG->line('import_data_stage4_settings_file_permission').'<br />['.$this->settings_file.']');
			// Assign settings file data as POST data
			$_POST = json_decode(file_get_contents($this->settings_file), TRUE);
		}
		// Use default processing
		if (!empty($_POST))
			parent::process_post_data();
	} // End process_post_data


	public function get_body()
	{
		global $DSP, $LANG;
		$site_data = $this->lib->parse_input($this->site_select);
		$weblog_data = $this->lib->parse_input($this->weblog_select);

		$body  = $DSP->heading($LANG->line('import_data_stage4_heading'));
		// Call main class function
		$body .= $this->insert_data($site_data[0], $weblog_data[0]);

		if (!isset($this->settings_file)) {
			$body .= $DSP->form_open(array('action'  => 'C=modules'.AMP.'M=import_data'.AMP.'P=export_settings', 
			                                'method'  => 'post',
			                                'name'    => 'entryform',
			                                'id'      => 'entryform',
			                                'enctype' => 'multipart/form-data'),
			                         array());
			$body .= $DSP->input_hidden('referal', 'stage_four');
			$body .= $DSP->input_hidden('data', json_encode($this->current_post));
			$body .= $DSP->input_submit($LANG->line('import_data_form_export_settings'));
			$body .= $DSP->form_close();
		}

		return $body;
	} // End get_body


// ------------------------------------


	private function insert_data ($site_id, $weblog_id)
	{
		global $LANG, $DSP, $DB;

		// Start reading from the data file (error if not readable)
		if ($this->input_file_obj->start_reading_rows() === FALSE)
			throw new Import_data_module_exception($LANG->line('import_data_error_input_type').'<br />['.$this->input_file.']');
		$headers = $this->input_file_obj->read_row();
		$input_column_num = count($headers);
		unset($headers);

		// Get types added by FieldFrame
		$ff_fieldtypes = $this->lib->get_fieldframe_types();
		// Get fields associated with selected weblog
		$weblog_fields = $this->lib->get_weblog_fields($site_id, $weblog_id);

		// Loop through all rows in the data file
		while (($input_row = $this->input_file_obj->read_row()) !== FALSE):
			$this->row_number++;

			// Detect malformed CSV rows and ignore
			if (count($input_row) != $input_column_num) {
				$this->output->log($LANG->line('import_data_stage4_row_error').$this->row_number.' '.$LANG->line('import_data_stage4_invalid_row_structure_1').$input_column_num.$LANG->line('import_data_stage4_invalid_row_structure_2').count($input_row).$LANG->line('import_data_stage4_invalid_row_structure_3'), FALSE);
				continue;
			}

			// Construct row POST call (set defaults)
			$post_data = array();
			$post_data['site_id']    = $site_id;
			$post_data['weblog_id']  = $weblog_id;
			$post_data['title']      = (isset($input_row[$this->field_column_select[$this->ee_fields_index['title_index']]]) ? $input_row[$this->field_column_select[$this->ee_fields_index['title_index']]] : '');
			$post_data['url_title']  = (isset($input_row[$this->field_column_select[$this->ee_fields_index['url_title_index']]]) ? $input_row[$this->field_column_select[$this->ee_fields_index['url_title_index']]] : '');
			$post_data['entry_date'] = (empty($input_row[$this->field_column_select[$this->ee_fields_index['entry_date_index']]]) ? date('Y-m-d H:i A') : date('Y-m-d H:i A', (is_numeric($input_row[$this->field_column_select[$this->ee_fields_index['entry_date_index']]]) ? $input_row[$this->field_column_select[$this->ee_fields_index['entry_date_index']]] : strtotime($input_row[$this->field_column_select[$this->ee_fields_index['entry_date_index']]].' '.date('T')))));
			$post_data['author_id']  = '';
			$post_data['status']     = 'open';
			$post_data['allow_comments'] = 'y';

			// Check url title (if imported) is suitable
			$this->check_url_title($post_data);

			// Check if an entry already exists (using unique tickbox)
			// - These details from this can be used as defaults when updating
			$existing_entry = $this->get_existing_entry($post_data, $input_row, $weblog_fields);
			if (!empty($existing_entry))
				$post_data['entry_id'] = $existing_entry['entry_id'];

			// Default title if empty
			if (!isset($input_row[$this->field_column_select[$this->ee_fields_index['title_index']]]) || (empty($input_row[$this->field_column_select[$this->ee_fields_index['title_index']]]) && $input_row[$this->field_column_select[$this->ee_fields_index['title_index']]] !== 0)) {
				if (!empty($existing_entry)) {
					$post_data['title'] = $existing_entry['title'];
				} else {
					$this->output->log($LANG->line('import_data_stage4_row_error').$this->row_number.' '.$LANG->line('import_data_stage4_no_title'), FALSE);
					continue;
				}
			}

			// Check/Get category (if importing)
			$category_list = $this->get_categories($post_data, $input_row);
			if ($category_list === FALSE)
				continue;
			if (!empty($category_list) && $category_list !== 0)
				$post_data['category'] = $category_list;

			// Check/Get author (if importing)
			$author = $this->get_author($post_data, $input_row, $existing_entry);
			if ($author === FALSE)
				continue;
			if (!empty($author) && $author !== 0)
				$post_data['author_id'] = $author;

			// Check/Get status (if importing)
			$status = $this->get_status($post_data, $input_row, $existing_entry);
			if ($status === FALSE)
				continue;
			if (!empty($status) && $status !== 0)
				$post_data['status'] = $status;

			// For each field, generate get the correct POST data
			foreach ($this->field_column_select as $index => $field_id) {
				// Ignore hard coded EE fields
				if ($index < $this->ee_fields_index['num_ee_fields'])
					continue;
				$post_data = array_merge($post_data, $this->get_field_post_data($index-$this->ee_fields_index['num_ee_fields'], $field_id, $post_data, $weblog_fields, $ff_fieldtypes, $input_row, $existing_entry));
			}

			// Error if invalid input supplied for row
			if ($this->invalid_input) {
					$this->output->log($LANG->line('import_data_stage4_row_error').$this->row_number.' '.$LANG->line('import_data_stage4_missing_data').' - "'.$post_data['title'].'"', FALSE);
					continue;
			}

			// Submit POST data for row to EE
			$this->submit_row($post_data);

		endwhile; // End while reading lines from input file


		$line_count = $this->input_file_obj->stop_reading_rows();
		unset($this->input_file_obj);

		// Output report for user
		$final = '<h2>'.$this->output->get_count().' ('.$LANG->line('import_data_stage4_of').$line_count.') '.$LANG->line('import_data_stage4_summary_heading').'</h2>'."\n\n".$this->output;
		if ($this->notification->get_count() != 0)
			$final .= "\n<br />\n".'<h2>'.$this->notification->get_count().' '.$LANG->line('import_data_stage4_notifications').'</h2>'."\n\n".$this->notification;
		return $final;
	} // End insert_data


	private function check_url_title ($post_data)
	{
		global $DB, $LANG;
		// Generate notification for end user is url title in use and importing, but not using unique
		if (!empty($post_data['url_title']) && $post_data['url_title'] !== 0 && (!isset($this->unique) || empty($this->unique))) {
			$query = 'SELECT *
			          FROM exp_weblog_data wd, exp_weblog_titles wt
			          WHERE wd.entry_id = wt.entry_id
			            AND wd.site_id =   '.$DB->escape_str($post_data['site_id']).'
			            AND wd.weblog_id = '.$DB->escape_str($post_data['weblog_id']).'
			            AND wt.url_title = \''.$DB->escape_str($post_data['url_title']).'\'
			          LIMIT 1';
			$query = $DB->query($query);
			if ($query->num_rows == 1)
				$this->notification->log($LANG->line('import_data_stage4_notification_row_1').$this->row_number.$LANG->line('import_data_stage4_notification_row_2').$LANG->line('import_data_stage4_notification_url_title_1').$post_data['url_title'].$LANG->line('import_data_stage4_notification_url_title_2'));
		}
	} // End check_url_title


	private function get_existing_entry ($post_data, $input_row, $weblog_fields)
	{
		global $DB, $LANG;

		if (count($this->unique) > 0) {
			$query = 'SELECT *
			          FROM exp_weblog_data wd, exp_weblog_titles wt
			          WHERE wd.entry_id = wt.entry_id
			            AND wd.site_id =   '.$DB->escape_str($post_data['site_id']).'
			            AND wd.weblog_id = '.$DB->escape_str($post_data['weblog_id']);

			// Only "title" and "url_title" can be unique of the default EE fields
			$unique_notification = '';
			// Generate SQL using all unique fields
			foreach($this->unique as $unique_column) {
				$indexes = $this->ee_fields_index;
				unset($indexes['num_ee_fields']);
				// If field is EE field
				if (in_array($unique_column, $indexes)) {
					if ($unique_column == $this->ee_fields_index['title_index']) {
						$query .= ' AND wt.title = \''.$DB->escape_str($input_row[$this->field_column_select[$this->ee_fields_index['title_index']]]).'\'';
						$unique_notification .= 'Title = \''.$input_row[$this->field_column_select[$this->ee_fields_index['title_index']]].'\', ';
					} else if ($unique_column == $this->ee_fields_index['url_title_index']) {
						$query .= ' AND wt.url_title = \''.$DB->escape_str($input_row[$this->field_column_select[$this->ee_fields_index['url_title_index']]]).'\'';
						$unique_notification .= 'URL Title = \''.$input_row[$this->field_column_select[$this->ee_fields_index['url_title_index']]].'\', ';
					}
				// Else non-EE field
				} else {
					$query .= ' AND wd.field_id_'.$DB->escape_str($weblog_fields[$unique_column-$this->ee_fields_index['num_ee_fields']]['field_id']).' = \''.$DB->escape_str($input_row[$this->field_column_select[$unique_column]]).'\'';
					$unique_notification .= $weblog_fields[$unique_column-$this->ee_fields_index['num_ee_fields']]['field_label'].' = \''.$input_row[$this->field_column_select[$unique_column]].'\', ';
				}
			}
			$query .= ' LIMIT 2';
			$query = $DB->query($query);
			$existing_entry = $query->result;

			// If result is valid
			if ($existing_entry !== NULL && isset($existing_entry[0])) {
				$existing_entry = $existing_entry[0];

				// If more than one entry returned, notify user (but default to first one)
				if ($query->num_rows > 1)
					$this->notification->log($LANG->line('import_data_stage4_notification_row_1').$this->row_number.$LANG->line('import_data_stage4_notification_row_2').$LANG->line('import_data_stage4_notification_unique_1').substr($unique_notification, 0, -2).$LANG->line('import_data_stage4_notification_unique_2').'#'.$existing_entry['entry_id'].$LANG->line('import_data_stage4_notification_unique_3'));

				return $existing_entry;
			}

		}
		return FALSE;
	} // End get_existing_entry


	private function get_categories ($post_data, $input_row)
	{
		global $DB, $LANG;

		$category_id_list = array();
		$this->field_column_select[$this->ee_fields_index['category_index']] = array_values(array_filter($this->field_column_select[$this->ee_fields_index['category_index']]));

		// IF the entry being imported has previously been during this import (cummulative)
		// OR no categories have been provided for this import
		// OR the user has specified cummulative import
		// THEN grab the existing categories for this entry
		if (!empty($post_data['entry_id']) && (in_array($post_data['entry_id'], $this->added_entry_ids)
		    || empty($this->field_column_select[$this->ee_fields_index['category_index']])
		    || isset($this->addition[$this->ee_fields_index['category_index']]))) {

			$query = 'SELECT cat_id
			          FROM exp_category_posts
			          WHERE entry_id = '.$DB->escape_str($post_data['entry_id']);
			$query = $DB->query($query);
			$existing_category_ids = $query->result;
			foreach ($existing_category_ids as $category_id) {
				$category_id_list[] = $category_id['cat_id'];
			}
		}

		// If categories have been provided for this import
		if (!empty($this->field_column_select[$this->ee_fields_index['category_index']])) {
			// Look up the category group id(s) associated with this weblog
			$query = 'SELECT cat_group
			          FROM exp_weblogs
			          WHERE weblog_id = '.$DB->escape_str($post_data['weblog_id']).'
			            AND site_id = '.$DB->escape_str($post_data['site_id']);
			$query = $DB->query($query);
			$cat_group_ids_raw = $query->result;
			$cat_group_ids = array();
			if ($query->num_rows > 0)
				$cat_group_ids = explode('|', $cat_group_ids_raw[0]['cat_group']);
			// Look up the category id(s) associated with each category group
			foreach($this->field_column_select[$this->ee_fields_index['category_index']] as $category) {
				// If delimited, split on delimiter
				if (isset($this->delimiter[$this->ee_fields_index['category_index']]) && !empty($this->delimiter[$this->ee_fields_index['category_index']]))
					$input_row[$category] = explode($this->delimiter[$this->ee_fields_index['category_index']], $input_row[$category]);
				if (!is_array($input_row[$category]))
					$input_row[$category] = array($input_row[$category]);
				// Check that each category provided is valid for the weblog
				foreach ($input_row[$category] as $given_category) {
					$cat_found = FALSE;
					if (!isset($given_category) || (empty($given_category) && $given_category !== 0))
						continue;
					foreach ($cat_group_ids as $cat_group_id) {
						$query = 'SELECT  ct.cat_id
						          FROM exp_category_groups cg, exp_categories ct
						          WHERE cg.group_id = '.$DB->escape_str($cat_group_id).'
						            AND cg.site_id = '.$DB->escape_str($post_data['site_id']).'
						            AND ct.group_id = cg.group_id
						            AND ct.site_id = '.$DB->escape_str($post_data['site_id']).'
						            AND ct.cat_name = \''.$DB->escape_str($given_category).'\'';
						$query = $DB->query($query);
						$category_id = $query->result;
						if ($query->num_rows > 0) {
							$category_id_list = array_unique(array_merge($category_id_list, array_values($category_id[0])));
							$cat_found = TRUE;
						}
					}
					if (!$cat_found)
						$this->notification->log($LANG->line('import_data_stage4_notification_row_1').$this->row_number.$LANG->line('import_data_stage4_notification_row_2').$LANG->line('import_data_stage4_notification_category_1').$given_category.$LANG->line('import_data_stage4_notification_category_2'));
				}
			}
		}
		return $category_id_list;
	} // End get_categories


	private function get_author ($post_data, $input_row, $existing_entry)
	{
		global $DB, $LANG;

		// Look up the corresonding id from username
		if (!empty($this->field_column_select[$this->ee_fields_index['author_index']])) {
			if (empty($input_row[$this->field_column_select[$this->ee_fields_index['author_index']]])) {
				if (isset($existing_entry) && !empty($existing_entry)) {
					return $existing_entry['author_id'];
				} else {
					$this->output->log($LANG->line('import_data_stage4_row_error').$this->row_number.' '.$LANG->line('import_data_stage4_no_author').' - "'.$post_data['title'].'"', FALSE);
					return FALSE;
				}
			} else {
				$query = $DB->query('SELECT member_id FROM exp_members WHERE username = \''.$DB->escape_str($input_row[$this->field_column_select[$this->ee_fields_index['author_index']]]).'\'');
				// Author not found
				if ($query->num_rows == 0) {
					$this->output->log($LANG->line('import_data_stage4_row_error').$this->row_number.' '.$LANG->line('import_data_stage4_missing_author_1').$input_row[$this->field_column_select[$this->ee_fields_index['author_index']]].$LANG->line('import_data_stage4_missing_author_2').' - "'.$post_data['title'].'"', FALSE);
					return FALSE;
				}
				$weblog_author = $query->result;

				// If not an admin (group 1), check permissions
				if ($weblog_author[0]['member_id'] != 1) {
					$query = 'SELECT me.member_id
					          FROM exp_members me, exp_weblog_member_groups wmg
					          WHERE me.member_id = '.$DB->escape_str($weblog_author[0]['member_id']).'
					            AND me.group_id = wmg.group_id
					            AND wmg.weblog_id = '.$DB->escape_str($post_data['weblog_id']);
					$query = $DB->query($query);
					// Author not authorised
					if ($query->num_rows == 0) {
						$this->output->log($LANG->line('import_data_stage4_row_error').$this->row_number.' '.$LANG->line('import_data_stage4_unauthorised_author_1').$input_row[$this->field_column_select[$this->ee_fields_index['author_index']]].$LANG->line('import_data_stage4_unauthorised_author_2').' - "'.$post_data['title'].'"', FALSE);
						return FALSE;
					}
				}
				// Author found
				return $weblog_author[0]['member_id'];
			}
		}
	} // End get_author


	private function get_status ($post_data, $input_row, $existing_entry)
	{
		global $DB, $LANG;

		// If empty, try and get from existing
		if (empty($input_row[$this->field_column_select[$this->ee_fields_index['status_index']]])) {
			if (isset($existing_entry) && !empty($existing_entry)) {
				return $existing_entry['status'];
			}
		// If open or closed, just assign (these are the two defaults and are always valid)
		} else if (strtolower($input_row[$this->field_column_select[$this->ee_fields_index['status_index']]]) == 'open'
		         || strtolower($input_row[$this->field_column_select[$this->ee_fields_index['status_index']]]) == 'closed') {
			return $input_row[$this->field_column_select[$this->ee_fields_index['status_index']]];
		// If it's something else, make sure it is valid
		} else {
			$query = 'SELECT st.status_id, st.status
			          FROM exp_statuses st, exp_weblogs wb, exp_status_groups sg
			          WHERE wb.weblog_id = '.$DB->escape_str($post_data['weblog_id']).'
			            AND wb.site_id = '.$DB->escape_str($post_data['site_id']).'
			            AND wb.status_group = sg.group_id
			            AND st.group_id = sg.group_id
			            AND st.status = \''.$DB->escape_str($input_row[$this->field_column_select[$this->ee_fields_index['status_index']]]).'\'
			          LIMIT 1';
			$query = $DB->query($query);
			if ($query->num_rows == 0) {
				$this->notification->log($LANG->line('import_data_stage4_notification_row_1').$this->row_number.$LANG->line('import_data_stage4_notification_row_2').$LANG->line('import_data_stage4_invalid_status_1').$input_row[$this->field_column_select[$this->ee_fields_index['status_index']]].$LANG->line('import_data_stage4_invalid_status_2').' - "'.$post_data['title'].'"', FALSE);
				return '';
			}
			$status = $query->result;
			return $status[0]['status'];
		}
	} // End get_status


	private function get_field_post_data ($index, $field_id, $post_data, $weblog_fields, $ff_fieldtypes, $input_row, $existing_entry)
	{
		global $LANG;

		// Translate ftype_id_X into field type name
		if (substr($weblog_fields[$index]['field_type'], 0, 9) == 'ftype_id_')
			$weblog_fields[$index]['field_type'] = $ff_fieldtypes[$weblog_fields[$index]['field_type']];

		// Create post data from field_type class
		if (is_array($field_id)) {
			$value = array();
			foreach($field_id as $single_field_id) {
				if (isset($this->delimiter[$index+$this->ee_fields_index['num_ee_fields']]) && !empty($this->delimiter[$index+$this->ee_fields_index['num_ee_fields']])) {
					$value[] = array_values(array_filter(explode($this->delimiter[$index+$this->ee_fields_index['num_ee_fields']], $input_row[$single_field_id])));
				} else {
					$value[] = (isset($input_row[$single_field_id]) ? $input_row[$single_field_id] : '');
				}
			}
		} else {
			$value = (isset($input_row[$field_id]) ? $input_row[$field_id] : '');
		}

		$field_type_object = new Field_Type($index,
		                                    $this->row_number,
		                                    $field_id,
		                                    $post_data['site_id'],
		                                    $post_data['weblog_id'],
		                                    $weblog_fields[$index],
		                                    $value,
		                                    $existing_entry,
		                                    $this->added_entry_ids,
		                                    $this->column_field_relation,
		                                    isset($this->addition[$index+$this->ee_fields_index['num_ee_fields']]));

		$field_post = array();
		// If field_type class unable to post to field, but field is required
		if (($field_return = $field_type_object->post_value()) === FALSE && $weblog_fields[$index]['field_required'] == 'y') {
			throw new Import_data_module_exception($LANG->line('import_data_stage4_missing_fieldtype_1').$weblog_fields[$index]['field_type'].$LANG->line('import_data_stage4_missing_fieldtype_2'));
		} else if ($field_return === FALSE) {
			$this->notification->log($LANG->line('import_data_stage4_notification_fieldtype_1').$index.$LANG->line('import_data_stage4_notification_fieldtype_2').$weblog_fields[$index]['field_type'].$LANG->line('import_data_stage4_notification_fieldtype_3'));
			$field_post['field_id_'.$weblog_fields[$index]['field_id']] = '';
		}
		unset($field_type_object);

		if (isset($field_return['notification']) && !empty($field_return['notification']))
			$this->notification->log($field_return['notification']);
		if (isset($field_return['post']))
			$field_post = $field_return['post'];

		if ($weblog_fields[$index]['field_required'] == 'y') {
			if (empty($field_post))
				$this->invalid_input = TRUE;
			foreach ($field_post as $check_data) {
				if (empty($check_data) && $check_data !== 0)
					$this->invalid_input = TRUE;
			}
		}

		return $field_post;
	} // End get_field_post_data


	private function submit_row ($post_data)
	{
		global $LANG;

		// Execute submission through Submission object
		$submission = new Submission($post_data);
		if (!$submission->failure()) {
			if ($submission->save()) {
				$this->added_entry_ids[] = $GLOBALS['input_loader_entry_id'];
				$this->output->log($LANG->line('import_data_stage4_submission_success').$LANG->line('import_data_stage4_row').$this->row_number.' - "'.$post_data['title'].'"');
			} else {
				$this->output->log($LANG->line('import_data_stage4_submission_failed').$LANG->line('import_data_stage4_row').$this->row_number.' - "'.$post_data['title'].'"', FALSE);
			}
		} else {
			$this->output->log($LANG->line('import_data_stage4_submission_object_failed').$LANG->line('import_data_stage4_row').$this->row_number.' - "'.$post_data['title'].'"', FALSE);
		}
	} // End submit_row



}


