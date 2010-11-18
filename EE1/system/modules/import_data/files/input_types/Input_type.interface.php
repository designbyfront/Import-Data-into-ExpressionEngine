<?php
/*
 * Input Type interface
 *
 * All input types must implement this interface. This guarantees that necessary functions have been implemented.
 *
 * @package DesignByFront
 * @author  Alistair Brown
 * @link    http://github.com/designbyfront/Import-Data into-ExpressionEngine
 * @since   Version 0.1
 *
 */
interface Input_type {
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