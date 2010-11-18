<?php
/*
 * Text XML File
 *
 * Takes a Text XML file and outputs each row as correctly formatted data structure
 * Implements 'Input_type'
 *
 * @package DesignByFront
 * @author  Alistair Brown
 * @link    http://github.com/designbyfront/Import-Data into-ExpressionEngine
 * @since   Version 0.1
 *
 *
 * THIS IS NOT THE WAY TO WRITE YOUR XML PARSER
 * THIS IS A QUICK CHECK TO MAKE SURE THAT THE IMPORT DATA MODULE CAN SUPPORT MULTIPLE DATA INPUT OBJECTS
 * DO NOT LOAD IN AN ENTIRE XML FILE INTO MEMORY OR YOU WILL RUN OUT!
 * READ THE XML LINE BY LINE OR SPLIT INTO SMALL FILES AND LOAD ONE BY ONE
 * http://www.techtalkpoint.com/articles/how-to-handle-large-xml-files-in-php/
 *
 */

class Xml_test_file implements Input_type {
	private $location;
	private $xml_object = FALSE;
	private $line_count = -1;

	public function Xml_test_file($location) {
		$this->location = $location;
	}

	public function get_headings() {
		return array('id','name','country');
	}


	public function start_reading_rows() {
		$this->xml_object = simplexml_load_file($this->location);
		echo '<pre>';
		var_dump($this->xml_object);
		echo '</pre>';
		return !($this->xml_object === FALSE);
	}

	public function stop_reading_rows() {
		$this->xml_object = FALSE;
		return $this->line_count;
	}

	// Use start_reading_lines to get file handle
	public function read_row() {
		if ($this->line_count === -1) {
			$this->line_count++;
			return $this->get_headings();
		}

		// Last line - no more - bomb out
		if (!isset($this->xml_object->entry[$this->line_count]))
			return FALSE;

		// Return line
		$line = array($this->xml_object->entry[$this->line_count]->id,
									$this->xml_object->entry[$this->line_count]->name,
									$this->xml_object->entry[$this->line_count]->country);
		$this->line_count++;
		return $line;
	}

}