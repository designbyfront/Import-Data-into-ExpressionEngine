<?php
/*
 * Submission
 *
 * ### EE 1.6 version ###
 *
 * Uses the built in EE publish class to submit POST data
 *
 * Dependencies:
 *  - cp.publish.php [PATH_CP/cp.publish.php]
 *   ~~ EE built in publish class
 *
 *
 * @package DesignByFront
 * @author  Shann McNicholl
 * @link    http://github.com/designbyfront/Import-Data into-ExpressionEngine
 * @since   Version 0.1
 *
 * Edited 23/08/2010 - Alistair Brown
 *
 */

require_once PATH_CP.'cp.publish.php'; // Used to create/update entries

class Submission {

	private $site_id;
	private $submission_data = FALSE;

	private $submit_function = 'submit_new_entry';

	public function __construct($submission_data) {
		// Accept array of data
		if (is_array($submission_data)) {
			$this->site_id = $submission_data['site_id'];
			$this->submission_data = $submission_data;
		}
	}

	public function failure() {
		return ($this->submission_data === FALSE);
	}

	/*
	 * save
	 * 
	 * Manages the event data's storage in the DB.
	 *
	 */
	public function save() {

		global $PREFS;
		
 // !*******************************************************************************
 // This sets the proper site ID for the entry we are about to create
 // 
 // Structure also needs fresh $PREFS->ini('site_pages') data in order to update the DB.

		// Uses a chunk of memory
		$PREFS->site_prefs('', $this->site_id);
		//echo "\n<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;2C.1 - Submission (save()) PREFS Memory: ".memory_get_usage(true)."<br />\n";

 // Find unique inputs and search on those, otherwise just create ### HOW DO I DO THIS? - change submit_function ??
		$res = $this->enter_data();

		if(!$res) // Something went wrong
			return FALSE;

		$_POST = array();

		return TRUE;
	}


	private function enter_data() {
		// Uncomment to prevent submission of data when testing
		//return FALSE;

		global $IN;

		// Populate the $IN variable with the POST
		$_POST = $this->submission_data;

		// Use of this code caused memory crash. Publishing seems to be successful without
		// core.system.php ln: 385
		//$IN->fetch_input_data();
		//echo "\n<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;2C.2 - Submission (enter_data()) IN Memory: ".memory_get_usage(true)."<br />\n";

		$pub = new Publish();
		$M = $this->submit_function;

		//echo "\n<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;2C.3 - Submission (enter_data()) Publish Memory: ".memory_get_usage(true)."<br />\n";
		// If there is a method, call it.
		if (method_exists($pub, $M)) {
			$res = $pub->$M(true); // save/update the entry
			return TRUE;
		}
		return FALSE;

	}

}
