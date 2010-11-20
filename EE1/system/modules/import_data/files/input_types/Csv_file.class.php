<?php
/*
 * CSV File Input Type
 *
 * Takes a CSV file and outputs each row as correctly formatted data structure
 * Implements 'Input_type'
 *
 * @package DesignByFront
 * @author  Alistair Brown
 * @link    http://github.com/designbyfront/Import-Data into-ExpressionEngine
 * @since   Version 0.1
 *
 */

class Csv_file implements Input_type {
	private $location;
	private $length;
	private $delimiter;
	private $enclosure;

	private $read_line_handle = FALSE;
	private $line_count = -1;

	public function Csv_file($location, $length=0, $delimiter=',', $enclosure='"') {
		$this->location  = $location;
		$this->length    = $length;
		$this->delimiter = $delimiter;
		$this->enclosure = $enclosure;
	}

	public function get_headings() {
		$fh = fopen($this->location, "r");
		if ($fh) {
				$headings = fgetcsv($fh, $this->length, $this->delimiter, $this->enclosure);
				fclose($fh);
				return $headings;
		}
		return $fh;
	}


	public function start_reading_rows() {
		return $this->read_line_handle = fopen($this->location, "r");
	}

	public function stop_reading_rows() {
		fclose($this->read_line_handle);
		return $this->line_count;
	}

	// Use start_reading_lines to get file handle
	public function read_row() {
		if (feof($this->read_line_handle))
			return FALSE;
		$line = fgetcsv($this->read_line_handle, $this->length, $this->delimiter, $this->enclosure);
		if ($line !== FALSE)
			$this->line_count++;
		return $line;
		//return fgetcsv($this->read_line_handle);
	}

}