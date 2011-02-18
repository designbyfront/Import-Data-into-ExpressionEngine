<?php
/**
 * Import Data into ExpressionEngine
 *
 * ### EE 1.6 version ###
 *
 * An ExpressionEngine Module that allows easy import of data into ExpressionEngine
 * Built in import capabilities are CSV files to all base ExpressionEngine fieldtypes and Playa
 * It is easily extended to allow additional import types and custom fieldtypes
 *
 *
 * Dependencies:
 *
 *  #Input Types
 *  - Input_type.interface.php  [modules/import_data/files/input_types/Input_type.interface.php]
 *  - Csv_file.class.php        [modules/import_data/files/input_types/Csv_file.class.php]
 *  - Remote_csv_file.class.php [modules/import_data/files/input_types/Remote_csv_file.class.php]
 *  ~~Add your Input Type here~~
 *
 *  #Stage Files
 *  - stage_one file   [modules/import_data/files/stage_one.php]
 *  - stage_two file   [modules/import_data/files/stage_two.php]
 *  - stage_three file [modules/import_data/files/stage_three.php]
 *  - stage_four file  [modules/import_data/files/stage_four.php]
 *
 *
 * @package DesignByFront
 * @author  Alistair Brown
 * @author  Shann McNicholl
 * @link    http://github.com/designbyfront/Import-Data into-ExpressionEngine
 * @since   Version 0.1
 * 
 */

// Input Type interface (implemented by all input types)
require_once 'files/input_types/Input_type.interface.php';

// Defined input types:
// Can't use the autoload function as it is used by EE
require_once 'files/input_types/Csv_file.class.php';
require_once 'files/input_types/Remote_csv_file.class.php';
//require_once 'files/input_types/Xml_test_file.class.php';
// ~~Add your Input Type here~~

// For those using non-unix return characters
ini_set('auto_detect_line_endings', true);

class Import_data_CP
{

	var $version = '1.5';

	// Available input types
	var $input_types = array(
	                      'CSV' => 'CSV',
	                      'remote_CSV' => 'Remote CSV',
	                      //'XML' => 'XML',
	                      '' => ''
	                    );

	var $input_file_obj;


	function Import_data_CP( $switch = TRUE )
	{
		global $IN;

		if ($switch)
		{
			switch($IN->GBL('P'))
			{
				case 'export_settings'  : $this->export_settings();
					break;
				case 'stage_four'  : $this->stage_four();
					break;
				case 'stage_three' : $this->stage_three();
					break;
				case 'stage_two'   : $this->stage_two();
					break;
				default            : $this->stage_one();
					break;
			}
		}
	}

	function get_input_type_obj($input_data_type, $input_data_location)
	{
		global $LANG;
		// Implemented input types
		switch($input_data_type)
		{
			case 'CSV' :
				return array(TRUE, new Csv_file($input_data_location));

			case 'remote_CSV' :
				return array(TRUE, new Remote_csv_file($input_data_location));

			case 'XML' :
				return  array(FALSE, $input_data_type.$LANG->line('import_data_unimplemented_input_type'));

			default :
				return  array(FALSE, $LANG->line('import_data_unknown_input_type').' ['.$input_data_type.']');
		}
	}

	function get_input_file_upload_location()
	{
		return substr(__FILE__, 0, strrpos(__FILE__, '/')).'/files/upload_input_files/';
	}

	function get_settings_file_upload_location()
	{
		return substr(__FILE__, 0, strrpos(__FILE__, '/')).'/files/upload_settings_files/';
	}

	// Stage four - Enter the data into EE (printing success and error)
	function stage_four()
	{
		require_once('files/stage_four.php');
	}

	// Stage three - enter mappings between the weblog fields and input columns
	function stage_three()
	{
		require_once('files/stage_three.php');
	}

	// Stage two - enter existing relationships (if relationships exist selected in stage one)
	function stage_two()
	{
		require_once('files/stage_two.php');
	}

	// Stage one - enter site, weblog, input file, if relationships exist and file type
	function stage_one()
	{
		require_once('files/stage_one.php');
	}

	function export_settings()
	{
		global $DB;

		//ob_start();
		if (isset($_POST['referal']) && $_POST['referal'] == 'stage_four') {
			$settings = json_decode(stripslashes($_POST['data']), TRUE);
		} else {
			$settings = $_POST;
		}
		unset($settings['input_file']);

		$site_id = explode('#', $settings['site_select']);
		$weblog_id = explode('#', $settings['weblog_select']);
		$query = $DB->query('SELECT blog_name
									FROM exp_weblogs
									WHERE weblog_id = '.$DB->escape_str($weblog_id[0]).'
									  AND site_id = '.$DB->escape_str($site_id[0]));
		$result = $query->result[0];

		header ('Content-Type: application/octet-stream');
		header ('Content-Disposition: attachment; filename='.$result['blog_name'].'_settings-'.time().'.json');
		echo json_encode($settings);

		exit();
	}



	// ----------------------------------------
	//  Module installer
	// ----------------------------------------

	function import_data_module_install()
	{
		global $DB;

		$sql[] = "INSERT INTO exp_modules (module_id,
																			 module_name,
																			 module_version,
																			 has_cp_backend)
																			 VALUES
																			 ('',
																			 'Import_data',
																			 '$this->version',
																			 'y')";

		foreach ($sql as $query)
		{
			$DB->query($query);
		}
		
		return true;
	}


	// ----------------------------------------
	//  Module de-installer
	// ----------------------------------------

	function import_data_module_deinstall()
	{
		global $DB;

		$query = $DB->query("SELECT module_id
												 FROM exp_modules
												 WHERE module_name = 'Import_data'");

		$sql[] = "DELETE FROM exp_module_member_groups 
							WHERE module_id = '".$query->row['module_id']."'";

		$sql[] = "DELETE FROM exp_modules
							WHERE module_name = 'Import_data'";

		foreach ($sql as $query)
		{
			$DB->query($query);
		}

		return true;
	}



}

/* End of file mcp.import_data.php */
/* Location: ./system/modules/module_name/mcp.import_data.php */