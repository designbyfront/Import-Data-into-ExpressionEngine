<?php
/*
 * Field Type
 *
 * ### EE 1.6 version ###
 *
 * Generates correct data structure for field types POST data
 *
 * @package DesignByFront
 * @author  Alistair Brown
 * @link    http://github.com/designbyfront/Import-Data into-ExpressionEngine
 * @since   Version 0.1
 *
 */

class Field_type {

	private $order;
	private $entry_number;
	private $column_index;
	private $site_id;
	private $weblog_id;
	private $field;
	private $value;
	private $existing;
	private $added_ids;
	private $relationships;
	private $addition_override;

	// Mapping of field types to function
	public $supported_types = array(
		'text'            => 'post_data_text',
		'textarea'        => 'post_data_textarea',
		'select'          => 'post_data_select',
		'date'            => 'post_data_date',
		'rel'             => 'post_data_rel',
		'ngen_file_field' => '', //Not implemented
		'playa'           => 'post_data_playa',
		'ff_checkbox'     => 'post_data_ff_checkbox',
		'wygwam'          => 'post_data_wygwam',
		'sl_google_map'   => '', //Not implemented
		'matrix'          => '', //Not implemented
		''                => ''  //Blank
	);

// --- SETTINGS ---------------

	// Fields which support input from a multi-select
	public static $multi_field_types = array(
		'playa'
	);

	// Fields which support input from a multi-select
	public static $delimiter_field_types = array(
		'playa'
	);

	// Fields which can be used as unique
	public static $unique_field_types = array(
		'text',
		'textarea',
		'select',
		'date'
	);

	// Fields which support addition behaviour
	public static $addition_field_types = array(
		'playa'
	);


	/*
	 * @params order             - int corresponding to the number of previous fields executed [Incremental] (not including EE specific fields)
	 * @params entry_number      - int corresponding to the line of the input file currently being processed
	 * @params column_index      - int corresponding to index for the column of current row in the input file
	 *                             -or- array of ints if type in 'multi_field_types' array
	 * @params site_id           - int specifying ExpressionEngine site
	 * @params weblog_id         - int specifying ExpressionEngine weblog
	 * @params field             - associative array containing details about the field
	 * @params value             - string value being put into the field
	 *                             -or- array of strings if type in 'multi_field_types' array
	 *                             -or- array of array of strings if type in 'delimiter_field_types' array
	 * @params existing          - ID of existing full entry if found in database (empty if not found)
	 * @params added_ids         - array of ints which correspond to the entry_id's of recently entered entries (or empty if first entry published)
	 * @params relationships     - associative array  of user defined data relationships [{column_index} => {some_weblog_id}#{some_field_id}] (or empty if not defined in stage 2)
	 * @params addition_override - boolean whether to override default behaviour of overwriting and add to field [only applicable for field types in addition_field_types] (defined in stage 3)
	 */
	public function __construct($order, $entry_number, $column_index, $site_id, $weblog_id, $field, $value, $existing, $added_ids, $relationships, $addition_override) {
		//echo 'Input:<pre>'.print_r(array($order, $site_id, $weblog_id, $field, $value, $existing, $added_ids, $relationships), true).'</pre>';

		$this->order             = $order;
		$this->entry_number      = $entry_number;
		$this->column_index      = $column_index;
		$this->site_id           = $site_id;
		$this->weblog_id         = $weblog_id;
		$this->field             = $field;
		$this->value             = $value;
		$this->existing          = $existing;
		$this->added_ids         = $added_ids;
		$this->relationships     = $relationships;
		$this->addition_override = $addition_override;
	}

	/*
	 * @return associative array containing:
	 *           - mapping from 'post' to formatted array for post data
	 *           - mapping from 'notification' to string or array of strings
	 *         array can be empty
	 */
	public function post_value() {
		if (isset($this->supported_types[$this->field['field_type']]))
			if (method_exists($this, $this->supported_types[$this->field['field_type']]))
				return $this->{$this->supported_types[$this->field['field_type']]}();
		return FALSE;
	}


// ---- Supported Field Type Functions ------

	private function post_data_text() {
		if ($this->value === NULL || $this->value === '')
			if (isset($this->existing['field_id_'.$this->field['field_id']]))
				$this->value = $this->existing['field_id_'.$this->field['field_id']];
			else
				$this->value = '';
		return array('post' => array('field_id_'.$this->field['field_id'] => $this->value, 'field_ft_'.$this->field['field_id'] => $this->field['field_fmt']));
	}



	private function post_data_textarea() {
		return $this->post_data_text();
	}



	private function post_data_select() {
		return $this->post_data_text();
	}



	private function post_data_date() {
		if ($this->value === NULL || $this->value === '')
			if (isset($this->existing['field_id_'.$this->field['field_id']]))
				$this->value = $this->existing['field_id_'.$this->field['field_id']];
			else
				$this->value = '';
		return array('post' => array('field_id_'.$this->field['field_id'] => date("Y-m-d H:i A", strtotime($this->value))));
	}



	private function post_data_rel() {
		global $DB, $LANG;

		if ($this->value === NULL || $this->value === '')
			return array('post' => array('field_id_'.$this->field['field_id'] => (isset($this->existing['field_id_'.$this->field['field_id']]) ? $this->existing['field_id_'.$this->field['field_id']] : '')));
		if (!isset($this->relationships[$this->column_index]))
			return array();

		$pieces = explode('#', $this->relationships[$this->column_index]);
		if (empty($pieces[1])) {
			$query = 'SELECT entry_id
								FROM exp_weblog_titles
								WHERE site_id = '.$DB->escape_str($this->site_id).'
								AND   weblog_id = '.$DB->escape_str($pieces[0]).'
								AND   title = \''.$DB->escape_str($this->value).'\'
								LIMIT 1';
		} else {
			$query = 'SELECT entry_id
								FROM exp_weblog_data
								WHERE site_id = '.$DB->escape_str($this->site_id).'
								AND   weblog_id = '.$DB->escape_str($pieces[0]).'
								AND   field_id_'.$DB->escape_str($pieces[1]).' = \''.$DB->escape_str($this->value).'\'
								LIMIT 1';
		}
		//echo "<br /><br />\n\n".$query;

		$query = $DB->query($query);
		$existing_entry = $query->result;
		if (empty($existing_entry))
			return array('notification' => $this->format_notification($LANG->line('import_data_stage4_notification_rel_1').(empty($pieces[1]) ? 'title' : 'field_id_'.$pieces[1]).$LANG->line('import_data_stage4_notification_equals_quote').$this->value.$LANG->line('import_data_stage4_notification_rel_2')));
		$existing_entry = $existing_entry[0];
		return array('post' => array('field_id_'.$this->field['field_id'] => $existing_entry['entry_id']));
	}



	private function post_data_ngen_file_field() {
		// TODO

		/*
		Post data looks like:

			["field_id_67"]=>
				array(2) {
					["file_name"]=>
					string(0) ""
					["existing"]=>
					string(0) ""
				}

		 */

	}



	private function post_data_playa() {
		global $DB, $LANG;

/*
 - If given no relationship, send empty
 - If given no value, send existing
 - If given value and not already updated this time, overwrite
 - If given value and already updated this time, keep existing
*/

		$notification = array();

		$previous_entries = array(0 => '');
		preg_match_all('/\[([0-9]+?)\]/', (isset($this->existing['field_id_'.$this->field['field_id']]) ? $this->existing['field_id_'.$this->field['field_id']] : ''), $matches);
		if (!empty($matches[1])) {
			$current_relations = $matches[1];
			// Convert relations into entry IDs
			$query = 'SELECT rel_child_id
								FROM exp_relationships
								WHERE rel_parent_id = '.$this->existing['entry_id'].'
								AND   rel_id IN ('.implode(',', $current_relations).')';
			//echo "<br />\n".$query."<br /><br />\n";
			$query = $DB->query($query);
			$get_previous_entries = $query->result;
			foreach ($get_previous_entries as $get_previous_entry)
				$previous_entries[] = $get_previous_entry['rel_child_id'];
		}


		// If given no relationship, send empty
		if (is_array($this->column_index)) {
			foreach ($this->column_index as $key => $index) {
				if (!isset($this->relationships[$index])) {
					$notification[] = $this->format_notification($LANG->line('import_data_stage4_notification_playa_defined_1').($index+1).$LANG->line('import_data_stage4_notification_playa_defined_2'), TRUE);
					unset($this->column_index[$key]);
					unset($this->value[$key]);
				}
			}
			if (empty($this->column_index))
				return array('post' => array('field_id_'.$this->field['field_id'] => array('old' => '', 'selections' => $previous_entries)), 'notification' => (empty($notification) ? '' : $notification));
		} else {
			if (!isset($this->relationships[$this->column_index]))
				return array('post' => array('field_id_'.$this->field['field_id'] => array('old' => '', 'selections' => $previous_entries)), 'notification' => (empty($notification) ? '' : $notification));
			$this->column_index = array($this->column_index);
		}

		// If given no value, send existing
		$return_existing = TRUE;
		foreach($this->value as $given_value)
			if (!empty($given_value) || $given_value == 0)
				$return_existing = FALSE;
		if ($return_existing)
			return array('post' => array('field_id_'.$this->field['field_id'] => array('old' => '', 'selections' => $previous_entries)), 'notification' => (empty($notification) ? '' : $notification));

		// If given value and not already updated, overwrite previous_entries
		if (isset($this->existing['entry_id']) && !in_array($this->existing['entry_id'], $this->added_ids) && !$this->addition_override)
			$previous_entries = array(0 => '');

		$i = 0;
		foreach ($this->column_index as $key => $index) {
			if (empty($this->value[$i])) {
				$i++;
				continue;
			}

			$pieces = explode('#', $this->relationships[$index]);

			if (!is_array($this->value[$i]))
				$this->value[$i] = array($this->value[$i]);

			$j = 0;
			foreach ($this->value[$i] as $given_value) {
				// If $pieces[1] is 0, we have selected a title
				if ($pieces[1] == 0) {
					$query = 'SELECT entry_id, title as field_id_'.$DB->escape_str($this->field['field_id']).'
										FROM exp_weblog_titles
										WHERE site_id = '.$DB->escape_str($this->site_id).'
										AND   weblog_id = '.$DB->escape_str($pieces[0]).'
										AND   title = \''.$DB->escape_str($given_value).'\'
										LIMIT 1';
				} else {
					$query = 'SELECT entry_id, field_id_'.$DB->escape_str($this->field['field_id']).'
										FROM exp_weblog_data
										WHERE site_id = '.$DB->escape_str($this->site_id).'
										AND   weblog_id = '.$DB->escape_str($pieces[0]).'
										AND   field_id_'.$DB->escape_str($pieces[1]).' = \''.$DB->escape_str($given_value).'\'
										LIMIT 1';
				}
				//echo "<br />\n".$query."<br /><br />\n";
				$query = $DB->query($query);
				$existing_entry = $query->result;
				if (isset($existing_entry[0]['entry_id']))
					$previous_entries[] = $existing_entry[0]['entry_id'];
				else
					$notification[] = $this->format_notification($LANG->line('import_data_stage4_notification_playa_missing_1').(($pieces[1] == 0) ? 'title' : 'field_id_'.$pieces[1]).$LANG->line('import_data_stage4_notification_equals_quote').$given_value.$LANG->line('import_data_stage4_notification_playa_missing_2'));
				$j++;
			}

			$i++;
		}
		$previous_entries = array_unique($previous_entries);

		return array('post' => array('field_id_'.$this->field['field_id'] => array('old' => '', 'selections' => $previous_entries)), 'notification' => $notification);
	}



	private function post_data_ff_checkbox() {
		if ($this->value === NULL || $this->value === '')
			if (isset($this->existing['field_id_'.$this->field['field_id']]))
				$this->value = $this->existing['field_id_'.$this->field['field_id']];
			else
				$this->value = '';

		// Convert possible input to boolean
		$positive = array('yes', 'y', 'true',  'on');
		$negative = array('no',  'n', 'false', 'off');
		if (in_array($this->value, $positive))
			$this->value = TRUE;
		else if (in_array($this->value, $negative))
			$this->value = FALSE;
		else
			$this->value = (bool)$this->value;

		return array('post' => array('field_id_'.$this->field['field_id'] => ($this->value ? 'y' : 'n')));
	}



	private function post_data_wygwam() {
		if ($this->value === NULL || $this->value === '')
			$this->value = (isset($this->existing['field_id_'.$this->field['field_id']]) ? $this->existing['field_id_'.$this->field['field_id']] : '');
		$data_array = array('old' => $this->existing['field_id_'.$this->field['field_id']],
												 'new' => $this->value);
		return array('post' => array('field_id_'.$this->field['field_id'] => $data_array));
	}



	private function post_data_sl_google_map() {
		// TODO

		/*
		Post data looks like:

			["field_id_70"]=>
				string(41) "54.592729,-5.928519,1,54.592729,-5.928519"

		That is a string with "{new_long},{new_lat},{zoom_level},{default_long},{default_lat}"

		 */

	}



	private function post_data_matrix() {
		// TODO

		/*
		Post data looks like:

			["field_id_71"]=>
				array(3) {
					["row_order"]=>
					array(2) {
						[0]=>
						string(9) "row_new_0"
						[1]=>
						string(9) "row_new_1"
					}
					["row_new_0"]=>
					array(2) {
						["col_id_1"]=>
						string(15) "test-cell1-row1"
						["col_id_2"]=>
						string(15) "test-cell2-row1"
					}
					["row_new_1"]=>
					array(2) {
						["col_id_1"]=>
						string(15) "test-cell1-row2"
						["col_id_2"]=>
						string(15) "test-cell2-row2"
					}
				}

		 */

	}

	// ---- USEFUL FUNCTIONS ---------------------

	private function format_notification($notification, $global = FALSE) {
		global $LANG;
		return ($global ? '' : $LANG->line('import_data_stage4_notification_row_1').($this->entry_number+1).$LANG->line('import_data_stage4_notification_row_2')).$notification;
	}




}