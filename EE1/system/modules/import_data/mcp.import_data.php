<?php
/**
 * Import Data into ExpressionEngine
 *
 * ### EE 1.6 version ###
 *
 * An ExpressionEngine Module that allows easy import of data into ExpressionEngine.
 * Built in import capabilities for CSV files to all base ExpressionEngine and numerous third party fieldtypes.
 * It can be easily extended to allow additional import types and new custom fieldtypes.
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

// Uncomment for debugging
//ini_set('display_errors', 1);
//error_reporting(E_ALL);

// For those using non-unix return characters
ini_set('auto_detect_line_endings', true);

class Import_data_CP
{

	private $version = '2.0';
	private $classes_file_location;

	// Available input types
	public static $input_types = array(
	                                 'Csv_file'        => 'CSV',
	                                 'Remote_csv_file' => 'Remote CSV',
	                                 ''                => ''
	                               );


	function Import_data_CP( $switch = TRUE )
	{
		$this->classes_file_location = $this->get_classes_file_location();
		require_once $this->classes_file_location.'supporting/stage.class.php';
		global $IN;

		try
		{
			if ($switch)
			{
				switch($IN->GBL('P'))
				{
					case 'export_settings'  : $this->export_settings();
						break;
					case 'stage_four'       : $this->stage_four();
						break;
					case 'stage_three'      : $this->stage_three();
						break;
					case 'stage_two'        : $this->stage_two();
						break;
					default                 : $this->stage_one();
						break;
				}
			}
		}
		catch (Import_data_module_exception $e)
		{
			return $e->error_message();
		}
	}


	function get_input_file_upload_location ()
	{
		return Import_data_CP::get_files_location().'upload_input_files/';
	}

	function get_settings_file_upload_location ()
	{
		return Import_data_CP::get_files_location().'upload_settings_files/';
	}

	function get_input_types_file_location ()
	{
		return Import_data_CP::get_files_location().'input_types/';
	}

	function get_classes_file_location ()
	{
		return Import_data_CP::get_files_location().'classes/';
	}

	function get_files_location ()
	{
		return substr(__FILE__, 0, strrpos(__FILE__, '/')).'/files/';
	}


	/* ----------------------------------------
	 *  Stage Four
	 *  - Input the data into EE (printing success and error)
	 * ----------------------------------------
	 */
	function stage_four()
	{
		global $DSP;
		require_once $this->classes_file_location.'stage_four.class.php';
		require_once $this->classes_file_location.'supporting/results.class.php';
		require_once $this->classes_file_location.'supporting/field_type.class.php';
		require_once $this->classes_file_location.'supporting/submission.class.php';
		$stage = new Stage_four();
		$stage->process_post_data();
		$stage->process_post_data(); // intended duplication!
		$stage->check_required_information();
		$stage->validate_input_file();
		$DSP->body = $stage->get_javascript()
		            .$stage->get_body();
	}


	/* ----------------------------------------
	 *  Stage Three
	 *  - Input mappings between the weblog fields and input columns
	 * ----------------------------------------
	 */
	function stage_three()
	{
		global $DSP;
		require_once $this->classes_file_location.'stage_three.class.php';
		require_once $this->classes_file_location.'supporting/field_type.class.php';
		$stage = new Stage_three();
		$stage->process_post_data();
		$stage->check_required_information();
		$stage->validate_input_file();
		$DSP->body = $stage->get_javascript()
		            .$stage->get_body();
	}


	/* ----------------------------------------
	 *  Stage Two
	 *  - Input existing relationships (if relationships exist selected in stage one)
	 * ----------------------------------------
	 */
	function stage_two()
	{
		global $DSP, $LANG;
		require_once $this->classes_file_location.'stage_two.class.php';
		$stage = new Stage_two();
		$stage->process_post_data();
		$stage->locate_file('input',    $this->get_input_file_upload_location());
		$stage->locate_file('settings', $this->get_settings_file_upload_location());
		$stage->process_post_data();
		$stage->validate_input_file();
		$DSP->body = $stage->get_javascript()
		            .$stage->get_body();
	}


	/* ----------------------------------------
	 *  Stage One
	 *  - Input site, weblog, input file, if relationships exist and file type
	 * ----------------------------------------
	 */
	function stage_one()
	{
		global $DSP;
		require_once $this->classes_file_location.'stage_one.class.php';
		$stage = new Stage_one();
		$stage->process_post_data();
		$DSP->body = $stage->get_javascript()
		            .$stage->get_body();
	}


	/* ----------------------------------------
	 *  Export Settings
	 *  - Output the chosen settings as JSON file
	 * ----------------------------------------
	 */
	function export_settings()
	{
		global $DB;
		$settings = (isset($_POST['referal']) && $_POST['referal'] == 'stage_four') ? json_decode(stripslashes($_POST['data']), TRUE) : $_POST;
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


	/* ----------------------------------------
	 *  Module Installer
	 * ----------------------------------------
	 */
	function import_data_module_install()
	{
		global $DB;
		$sql[] = "INSERT INTO exp_modules (module_id, module_name, module_version, has_cp_backend)
		                           VALUES ('', 'Import_data', '$this->version', 'y')";
		foreach ($sql as $query)
		{
			$DB->query($query);
		}
		return TRUE;
	}


	/* ----------------------------------------
	 *  Module De-Installer
	 * ----------------------------------------
	 */
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
		return TRUE;
	}


}


/* End of file mcp.import_data.php */
/* Location: ./system/modules/module_name/mcp.import_data.php */