<?php
/**
 * Import Data into ExpressionEngine
 *
 * ### EE 1.6 version ###
 *
 *
 * Input Type interface
 *  - All input types must implement this interface. This guarantees that necessary functions have been implemented.
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

interface Input_type
{

	// Function: open file, read first row (headings), close file
	// Return:   flat array of headings
	public function get_headings();


	// Function: open file
	// Return:   boolean
	public function start_reading_rows();


	// Function: close file
	// Return:   int of number of lines output
	public function stop_reading_rows();


	// Function: read next row from file (first call will return headings)
	// Return:   flat array of row data or boolean false when no more rows 
	public function read_row();

}


/* End of file input_type.interface.php */
/* Location: ./system/modules/import_data/files/input_types/input_type.interface.php */