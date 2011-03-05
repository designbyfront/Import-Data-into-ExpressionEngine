<?php
/**
 * Import Data into ExpressionEngine
 *
 * ### EE 1.6 version ###
 *
 *
 * STAGE LIBRARY
 *  - Library class containing cross stage functions
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

class Stage_library {

	private $gypsy_installed;
	private $fieldframe_installed;


	function Stage_library ()
	{
		$this->__construct();
	}


	function __construct ()
	{
		$this->gypsy_installed = null;
		$this->fieldframe_installed = null;
	}


	public function get_input_object ($stage)
	{
		global $LANG;

		// Check input file can be accessed
		if (!$this->check_access($stage->input_file))
			throw new Import_data_module_exception($LANG->line('import_data_error_input_file_permission').'<br />['.$stage->input_file.']');

		// Check corresponding class exists and is loaded
		$input_type_file_location = Import_data_CP::get_input_types_file_location();
		require_once $input_type_file_location.'input_type.interface.php';
		$class_file = $input_type_file_location.$stage->type_select.'.class.php';
		if (file_exists($class_file))
			require_once $class_file;

		if (class_exists($stage->type_select))
			$input_file_obj = new $stage->type_select($stage->input_file);
		else
			throw new Import_data_module_exception($LANG->line('import_data_unknown_input_type').' ['.Import_data_CP::$input_types[$stage->type_select].']');

		// Check class has been implemented correctly
		if (!($input_file_obj instanceof Input_type))
			throw new Import_data_module_exception($LANG->line('import_data_object_implementation'));

		return $input_file_obj;
	} // End get_input_object


	public function create_data_table ($attributes, $content, $size = FALSE, $complete_table = TRUE)
	{
		global $DSP;
		// Create table from array using EE display helper
		$table = ($complete_table ? $DSP->table('', '10', '', '100%') : '');
		foreach($content as $index => $value)
			$table .= $this->create_table_row($attributes[$index], $content[$index], (isset($size[$index]) ? $size[$index] :  FALSE));
		return $table.($complete_table ? $DSP->table_c() : '');
	} // End create_data_table


	public function create_table_row ($attributes, $content, $size = FALSE)
	{
		global $DSP;
		// Create table row from array using EE display helper
		$row = $DSP->tr();
		foreach($content as $index => $value) {
			if (empty($size) || (isset($size[$index]) && empty($size[$index]))) {
				$row .= $DSP->table_qcell($attributes[$index], $content[$index]);
			} else {
				$row .= $DSP->table_qcell($attributes[$index], $content[$index], $size[$index]);
			}
		}
		return $row.$DSP->tr_c();
	} // End create_table_row


	public function process_post_data ($stage)
	{
		// Convert POST data to class variables
		foreach ($_POST as $name => $data)
			$stage->$name = $data;
		unset($_POST);
	} // End process_post_data


	public function parse_input ($data)
	{
		// Parse data using default delimiter
		return explode('#', $data);
	} // End parse_input


	public function gypsy_installed ()
	{
		global $DB;
		// check if Gypsy is installed and set boolean
		if ($this->gypsy_installed === null)
		{
			$query = $DB->query('SELECT class FROM exp_extensions WHERE class = \'Gypsy\'');
			$this->gypsy_installed = $query->num_rows > 0;
		}
		return $this->gypsy_installed;
	} // End gypsy_installed


	public function fieldframe_installed ()
	{
		global $DB;
		// check if FieldFrame is installed and set boolean
		if ($this->fieldframe_installed === null)
		{
			$query = $DB->query('SHOW tables LIKE \'exp_ff_fieldtypes\'');
			$this->fieldframe_installed = !empty($query->result);
		}
		return $this->fieldframe_installed;
	} // End fieldframe_installed


	public function get_fieldframe_types ()
	{
		global $DB;
		// Query database for list of FieldFrame types
		$ff_fieldtypes = array();
		if ($this->fieldframe_installed()) {
			$query = $DB->query('SELECT fieldtype_id, class FROM exp_ff_fieldtypes');
			foreach ($query->result as $index => $row)
				$ff_fieldtypes['ftype_id_'.$row['fieldtype_id']] = $row['class'];
		}
		return $ff_fieldtypes;
	} // End get_fieldframe_types


	public function get_weblog_fields ($site_id, $weblog_id)
	{
		global $DB;
		// Query database for list of weblog types
		$query = $DB->query('SELECT wf.field_id, wf.field_label, wf.field_name, wf.field_required, wf.field_type, wf.field_fmt, '.($this->gypsy_installed() ? 'wf.field_is_gypsy' : '\'n\' as field_is_gypsy').($this->fieldframe_installed() ? ', wf.ff_settings' : '').'
		                     FROM exp_weblogs wb, exp_field_groups fg, exp_weblog_fields wf
		                     WHERE wb.site_id = '.$DB->escape_str($site_id).'
		                       AND wb.site_id = fg.site_id
		                       AND wb.site_id = wf.site_id
		                       AND wb.weblog_id = '.$DB->escape_str($weblog_id).'
		                       AND wb.field_group = fg.group_id
		                       AND wb.field_group = wf.group_id '.
		                       ($this->gypsy_installed() ? 'AND   wf.field_is_gypsy = \'n\'' : ''));
		$weblog_fields = $query->result;
		unset($query);

		// If gypsy installed, add these fields also
		if ($this->gypsy_installed()) {
			// Select gypsy fields
			$query = $DB->query('SELECT wf.gypsy_weblogs, wf.field_id, wf.field_name, wf.field_label, wf.field_required, wf.field_type, wf.field_fmt
		                        FROM exp_weblog_fields wf
		                        WHERE wf.site_id = '.$DB->escape_str($site_id).'
		                          AND wf.field_is_gypsy = \'y\'');
			foreach($query->result as $row) {
				$used_by = explode(' ', trim($row['gypsy_weblogs']));
				if (in_array($weblog_id, $used_by)) {
					$weblog_fields[] = array('field_id'      => $row['field_id'],
					                         'field_label'    => $row['field_label'],
					                         'field_name'     => $row['field_name'],
					                         'field_required' => $row['field_required'],
					                         'field_type'     => $row['field_type'],
					                         'field_fmt'      => $row['field_fmt'],
					                         'field_is_gypsy' => 'y');
				}
			}
			unset($query);
		}
		return $weblog_fields;
	} // End get_list_of_fields


	public function check_access ($file_location)
	{
			// Check if file is accessible
			return is_readable($file_location);
	} // End check_access


	public function check_required_information ($stage)
	{
		global $LANG;
		// Check absence of required fields

		if (!isset($stage->site_select)   || empty($stage->site_select))
			throw new Import_data_module_exception($LANG->line('import_data_error_required_data_missing').$LANG->line('import_data_error_selected_site_missing'));

		if (!isset($stage->weblog_select) || empty($stage->weblog_select))
			throw new Import_data_module_exception($LANG->line('import_data_error_required_data_missing').$LANG->line('import_data_error_selected_weblog_missing'));

		if (!isset($stage->input_file)    || empty($stage->input_file))
			throw new Import_data_module_exception($LANG->line('import_data_error_required_data_missing').$LANG->line('import_data_error_input_file_location_missing'));

		if (!isset($stage->type_select)    || empty($stage->type_select))
			throw new Import_data_module_exception($LANG->line('import_data_error_required_data_missing').$LANG->line('import_data_error_input_type_missing'));

		return TRUE;
	} // End check_required_information

}


/* End of file stage_library.class.php */
/* Location: ./system/modules/import_data/files/classes/supporting/stage_library.class.php */