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
interface Input_type
{
	public function get_headings();
	public function start_reading_rows();
	public function stop_reading_rows();
	public function read_row();
}