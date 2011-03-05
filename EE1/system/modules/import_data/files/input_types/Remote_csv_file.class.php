<?php
/**
 * Import Data into ExpressionEngine
 *
 * ### EE 1.6 version ###
 *
 *
 * Remove CSV File Input Type
 *  - Takes a remove CSV file, caches it and outputs each row as correctly formatted data structure
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

class Remote_csv_file implements Input_type {

	private $location;
	private $length;
	private $delimiter;
	private $enclosure;

	private $read_line_handle = FALSE;
	private $line_count = -1;


	function Remote_csv_file ($location, $length=0, $delimiter=',', $enclosure='"')
	{
		$this->__construct($location, $length=0, $delimiter=',', $enclosure='"');
	}


	public function __construct ($location, $length=0, $delimiter=',', $enclosure='"')
	{
		// Extract the URL provided in the uploaded input file
		$remote_file_location = file_get_contents($location);
		// Determine the location to cache the remote file (upload_input_files directory)
		$local_file_location = str_replace('input_types', 'upload_input_files', substr(__FILE__, 0, strrpos(__FILE__, '/')).'/'.basename($remote_file_location));
		// Check if already cached (cache is removed at end of import when file is completely read [see line 62])
		if (!file_exists($local_file_location))
			file_put_contents($local_file_location, file_get_contents($remote_file_location));

		$this->location  = $local_file_location;
		$this->length    = $length;
		$this->delimiter = $delimiter;
		$this->enclosure = $enclosure;
	}


	public function get_headings ()
	{
		$fh = fopen($this->location, "r");
		if ($fh) {
				$headings = fgetcsv($fh, $this->length, $this->delimiter, $this->enclosure);
				fclose($fh);
				return $headings;
		}
		return $fh;
	}


	public function start_reading_rows ()
	{
		return $this->read_line_handle = fopen($this->location, "r");
	}


	public function stop_reading_rows ()
	{
		fclose($this->read_line_handle);
		return $this->line_count;
	}


	// Use start_reading_lines to get file handle
	public function read_row ()
	{
		if (feof($this->read_line_handle)) {
			unlink($this->location);
			return FALSE;
		}
		$line = fgetcsv($this->read_line_handle, $this->length, $this->delimiter, $this->enclosure);
		if ($line !== FALSE)
			$this->line_count++;
		return $line;
		//return fgetcsv($this->read_line_handle);
	}

}


/* End of file Remote_csv_file.class.php */
/* Location: ./system/modules/import_data/files/input_types/Remote_csv_file.class.php */