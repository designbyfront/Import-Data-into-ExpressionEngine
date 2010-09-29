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
	private $field_id;
	private $site_id;
	private $weblog_id;
	private $field;
	private $value;
	private $existing;
	private $added_ids;
	private $relationships;

	private $supported_types = array(
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
		''                => ''  // Blank
	);

	/*
	 * @params order - int specifying column number
	 * @params field_id - int corresponding to column in the input file
	 * @params site_id - int specifying site
	 * @params field - associative array containing details about the field type
	 * @params value - string value being put into the field
	 * @params existing - associative array of existing data in database (if available)
	 * @params added_ids - flat array of ints representing entry_ids of recently entered entries (if available)
	 * @params relationships - associative array of user defined data relationships [{order} => {weblog}#{field_id}] (if available)
	 */
	public function __construct($order, $field_id, $site_id, $weblog_id, $field, $value, $existing, $added_ids, $relationships) {
		//echo 'Input:<pre>'.print_r(array($order, $site_id, $weblog_id, $field, $value, $existing, $added_ids, $relationships), true).'</pre>';

		$this->order         = $order;
		$this->field_id      = $field_id;
		$this->site_id       = $site_id;
		$this->weblog_id     = $weblog_id;
		$this->field         = $field;
		$this->value         = $value;
		$this->existing      = $existing;
		$this->added_ids     = $added_ids;
		$this->relationships = $relationships;
	}

	public function post_value() {
		if (method_exists($this, $this->supported_types[$this->field['field_type']]))
			return $this->{$this->supported_types[$this->field['field_type']]}();
		return false;
	}


// ---- Supported Field Type Functions ------

	private function post_data_text() {
		if ($this->value === NULL)
			$this->value = $this->existing['field_id_'.$this->field['field_id']];
		return array('field_id_'.$this->field['field_id'] => $this->value);
	}



	private function post_data_textarea() {
		return $this->post_data_text();
	}



	private function post_data_select() {
		return $this->post_data_text();
	}



	private function post_data_date() {
		if ($this->value === NULL)
			$this->value = $this->existing['field_id_'.$this->field['field_id']];
		return array('field_id_'.$this->field['field_id'] => date("Y-m-d H:i A", strtotime($this->value)));
	}



	private function post_data_rel() {
		global $DB;

		if ($this->value === NULL)
			return array('field_id_'.$this->field['field_id'] => $this->existing['field_id_'.$this->field['field_id']]);
		if (!isset($this->relationships[$this->field_id]))
			return array();

		$pieces = explode('#', $this->relationships[$this->field_id]);
		$query = 'SELECT entry_id
							FROM exp_weblog_data
							WHERE site_id = '.$DB->escape_str($this->site_id).'
							AND   weblog_id = '.$DB->escape_str($pieces[0]).'
							AND   field_id_'.$DB->escape_str($pieces[1]).' = \''.$DB->escape_str($this->value).'\'
							LIMIT 1';
		//echo "<br /><br />\n\n".$query;

		$query = $DB->query($query);
		$existing_entry = $query->result;
		$existing_entry = $existing_entry[0];
		return array('field_id_'.$this->field['field_id'] => $existing_entry['entry_id']);
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
		global $DB;

/*
 - If given no relationship, send empty
 - If given no value, send existing
 - If given value and not already updated this time, overwrite
 - If given value and already updated this time, keep existing
*/

		// If given no relationship, send empty
		if (!isset($this->relationships[$this->field_id]))
			return array('field_id_'.$this->field['field_id'] => array());

		$previous_entries = array(0 => '');
		preg_match_all('/\[([0-9]+?)\]/', $this->existing['field_id_'.$this->field['field_id']], $matches);
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

		// If given no value, send existing
		if ($this->value === NULL)
			return array('field_id_'.$this->field['field_id'] => array('old' => '', 'selections' => $previous_entries));

		// If given value and not already updated, overwrite previous_entries
		if (!in_array($this->existing['entry_id'], $this->added_ids))
			$previous_entries = array(0 => '');

		$pieces = explode('#', $this->relationships[$this->field_id]);
		$query = 'SELECT entry_id, field_id_'.$DB->escape_str($this->field['field_id']).'
							FROM exp_weblog_data
							WHERE site_id = '.$DB->escape_str($this->site_id).'
							AND   weblog_id = '.$DB->escape_str($pieces[0]).'
							AND   field_id_'.$DB->escape_str($pieces[1]).' = \''.$DB->escape_str($this->value).'\'
							LIMIT 1';
		//echo "<br />\n".$query."<br /><br />\n";
		$query = $DB->query($query);
		$existing_entry = $query->result;
		$existing_entry = $existing_entry[0];

		$previous_entries[] = $existing_entry['entry_id'];
		$previous_entries = array_unique($previous_entries);

		return array('field_id_'.$this->field['field_id'] => array('old' => '', 'selections' => $previous_entries));
	}



	private function post_data_ff_checkbox() {
		if ($this->value === NULL)
			$this->value = $this->existing['field_id_'.$this->field['field_id']];

		// Convert possible input to boolean
		$positive = array('yes', 'y', 'true',  'on');
		$negative = array('no',  'n', 'false', 'off');
		if (in_array($this->value, $positive))
			$this->value = TRUE;
		else if (in_array($this->value, $negaive))
			$this->value = FALSE;
		else
			$this->value = (bool)$this->value;

		return array('field_id_'.$this->field['field_id'] => ($this->value ? 'y' : 'n'));
	}



	private function post_data_wygwam() {
		if ($this->value === NULL)
			$this->value = $this->existing['field_id_'.$this->field['field_id']];
		$data_array = array('old' => $this->existing['field_id_'.$this->field['field_id']],
												 'new' => $this->value);
		return array('field_id_'.$this->field['field_id'] => $data_array);
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

}