<?php
/**
 * Import Data into ExpressionEngine
 *
 * ### EE 1.6 version ###
 *
 *
 * STAGE
 *  - Abstract parent class to all stage classes
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

require_once 'stage_library.class.php';
require_once 'import_data_module_exception.class.php';

abstract class Stage {


	public function get_ee_fields_index_array ()
	{
		return array('title_index'      => 0,
		              'url_title_index'  => 1,
		              'category_index'   => 2,
		              'entry_date_index' => 3,
		              'author_index'     => 4,
		              'status_index'     => 5);
	}


	public function process_post_data ()
	{
		$this->lib->process_post_data($this);
	} // End process_post_data


	public function validate_input_file ()
	{
		$this->input_file_obj = $this->lib->get_input_object($this);
	} // End validate_input_file


	public function check_required_information ()
	{
		$this->lib->check_required_information($this);
	} // End check_required_information


	public function get_javascript ()
	{
		return '';
	} // End get_javascript


	abstract protected function get_body();


}


/* End of file stage.class.php */
/* Location: ./system/modules/import_data/files/classes/supporting/stage.class.php */