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
 *  - Input_type.interface.php [modules/import_data/files/input_types/Input_type.interface.php]
 *  - Csv_file.class.php       [modules/import_data/files/input_types/Csv_file.class.php]
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
//require_once 'files/input_types/Xml_test_file.class.php';
// ~~Add your Input Type here~~


class Import_data_CP
{

	var $version = '1.3';

	function Import_data_CP( $switch = TRUE )
	{
		global $IN;
		
		if ($switch)
		{
			switch($IN->GBL('P'))
			{
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