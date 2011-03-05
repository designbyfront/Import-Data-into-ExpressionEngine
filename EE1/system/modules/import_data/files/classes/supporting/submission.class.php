<?php
/**
 * Import Data into ExpressionEngine
 *
 * ### EE 1.6 version ###
 *
 *
 * SUBMISSION
 *  - Submits data to EE publish class
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
 * @package  DesignByFront
 * @author   Shann McNicholl
 * @link     http://github.com/designbyfront/Import-Data-into-ExpressionEngine
 * @modified Alistair Brown
 * 
 */
 
require_once PATH_CP.'cp.publish.php'; // Used to create/update entries

class Submission {

	private $site_id;
	private $submission_data = FALSE;

	private $submit_function = 'submit_new_entry';


	function Submission ()
	{
		$this->__construct();
	}


	function __construct($submission_data) {
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


		$PREFS->site_prefs('', $this->site_id);
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
		$_POST = $this->submission_data;
		$pub = new Publish();
		$M = $this->submit_function;

		if (method_exists($pub, $M)) {
			$res = $pub->$M(true); // save/update the entry
			return TRUE;
		}
		return FALSE;

	}

}


/* End of file submission.class.php */
/* Location: ./system/modules/import_data/files/classes/supporting/submission.class.php */