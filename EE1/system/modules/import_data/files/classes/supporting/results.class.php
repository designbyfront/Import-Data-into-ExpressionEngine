<?php 
/**
 * Import Data into ExpressionEngine
 *
 * ### EE 1.6 version ###
 *
 *
 * RESULTS
 *  - Simple event logging class
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

class Results {

	private $results;
	private $results_count;

	function Results ()
	{
		$this->__construct();
	}


	function __construct ()
	{
		$this->results = array();
		$this->results_count = 0;
	}


	// Log unique values and increment occurance value
	public function log ($returned_results, $success = TRUE)
	{
		if (!is_array($returned_results))
			$returned_results = array($returned_results);

		foreach($returned_results as $result) {
			if (!isset($this->results[$result])) {
				$this->results[$result] = 1;
				if ($success)
					$this->results_count++;
			} else {
				$this->results[$result]++;
			}
		}
	}


	public function get_count ()
	{
		return $this->results_count;
	}

	// Return HTML list of logged values
	public function __toString()
	{
		$result_list = '';
		foreach ($this->results as $result => $occurance)
			$result_list .= '<li>'.$result.'</li>';
		return '<ul>'.$result_list.'</ul>';
	}


}


/* End of file results.class.php */
/* Location: ./system/modules/import_data/files/classes/supporting/results.class.php */