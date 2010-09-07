<?php

/*
 * input_loader extension
 * Shann McNicholl @ DesignByFront.com
 *
 * Copyright DesignByFront.com 2010
 *
 * Checks for the existence of a global variable (Defined in $global_var_name)
 * If it is set then an input module is submitting the submit_new_entry form
 * and needs to gain control of the return handle.
 *
 */

if ( ! defined('EXT')) {
	exit('Invalid file request'); }

class input_loader {
	var $settings	 =  array();

	var $name = 'Input control';
	var $version = '0.1';
	var $description = 'Works in conjunction with the an input module';
	var $settings_exist = 'n';
	var $docs_url = 'http://www.designbyfront.com';
	var $global_var_name = 'input_loader_end_submit_new_form'; // Used to determine if the module is currently active (module will set)
	var $global_var_entry_id = 'input_loader_entry_id'; // Used to inform the module of the new entry ID (set by this extension)

	function xml_loader($settings = null) {
		$this->settings = $settings;
	} // __construct


	/*
	 * captureSubmitNewEntryEnd
	 * 
	 * Check for the existence of a global variable ($global_var_name)
	 * If it is set then an input module is submitting the submit_new_entry form
	 * and needs to gain control of the return handle.
	 * 
	 */
	function captureSubmitNewEntryEnd($entry_id = null) {
		global $EXT;

		// Initialise
		$GLOBALS[$this->global_var_entry_id] = null;

		if($entry_id && is_numeric($entry_id)) {
			// Set the global variable
			$GLOBALS[$this->global_var_entry_id] = $entry_id;
		}

		if(array_key_exists($this->global_var_name, $GLOBALS)) {
			$EXT->end_script = true;
		} 

		//return true;
	} // captureSubmitNewEntryEnd




	/* -----------------------------------------------------------------
	 * EE specific functions
	 * 
	 */
	
	function activate_extension() {
		global $DB;
		$DB->query($DB->insert_string('exp_extensions',
																	array(
																				'extension_id' => '',
																				'class'        => __CLASS__,
																				'method'       => "captureSubmitNewEntryEnd",
																				'hook'         => "submit_new_entry_absolute_end",
																				'settings'     => "",
																				'priority'     => 10,
																				'version'      => $this->version,
																				'enabled'      => "y"
																			)));
	} // activate_extension


	function disable_extension() {
		global $DB;
		$DB->query("DELETE FROM exp_extensions WHERE class = '".__CLASS__."'");
	} // disable_extension


	function update_extension($current='') {
		global $DB;
		$DB->query("UPDATE exp_extensions 
								SET version = '".$DB->escape_str($this->version)."' 
								WHERE class = '".__CLASS__."'");
	} // update_extension


	function settings() {
		return $this->settings;
	}


	function settings_form() {
		// Settings not used
	}



} // End class

?>