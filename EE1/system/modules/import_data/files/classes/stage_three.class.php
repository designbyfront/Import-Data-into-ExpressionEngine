<?php
/**
 * Import Data into ExpressionEngine
 *
 * ### EE 1.6 version ###
 *
 *
 * STAGE THREE
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

class Stage_three extends Stage {

	protected $lib;
	protected $input_file_obj;

	private $ee_fields_index;


	function Stage_three ()
	{
		$this->__construct();
	}


	function __construct ()
	{
		global $DSP, $LANG;

		$this->lib = new Stage_library();
		$DSP->title = $LANG->line('import_data_module_name');
		$DSP->crumb = $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=import_data',$LANG->line('import_data_module_name')).$DSP->crumb_item($LANG->line('import_data_stage3'));

		$this->ee_fields_index = parent::get_ee_fields_index_array();
		$this->ee_fields_index['num_ee_fields'] = count($this->ee_fields_index);
	}


	public function get_javascript ()
	{
		global $LANG;
		return '<script type="text/javascript">
	$(document).ready(function(){
		$("#field_list").css("border-collapse", "collapse");
		$("#field_list td").css({"padding-bottom": "5px", "padding-top": "5px"});
		$("#field_list tr td:first").css({"padding-left": "10px", "padding-right": "10px", "font-weight": "bold"});
		$("#field_list tr:even").css("background-color", "#EDEDED");

		$("#submit_buttons").append("<input type=\"submit\" value=\"'.$LANG->line('import_data_form_export_settings').'\" id=\"export_settings\" />");
		$("#export_settings").click(function() { $("#entryform").attr({action: "'.BASE.'&C=modules&M=import_data&P=export_settings"}); $("#entryform").submit(); setTimeout(function() { $("#entryform").attr({action: "'.BASE.'&C=modules&M=import_data&P=stage_four"}); }, 200); });
	});
</script>';
	} // End get_javascript


	public function get_body ()
	{
		global $DSP, $LANG;
		$headings = $this->input_file_obj->get_headings();
		$site_data = $this->lib->parse_input($this->site_select);
		$weblog_data = $this->lib->parse_input($this->weblog_select);
		$data_relations = array('input_heading' => (isset($this->relation_heading_select) ? $this->relation_heading_select : ''),
		                         'weblog'        => (isset($this->relation_weblog_select)  ? $this->relation_weblog_select : ''),
		                         'field'         => (isset($this->relation_field_select)   ? $this->relation_field_select : ''));

		$body  = $DSP->heading($LANG->line('import_data_stage3_heading'));
		$body .= $DSP->form_open(array('action' => 'C=modules'.AMP.'M=import_data'.AMP.'P=stage_four', 
		                               'method' => 'post',
		                               'name'   => 'entryform',
		                               'id'     => 'entryform'),
		                         array());

		// Create previously input data table
		$previous_table = $this->lib->create_data_table(array(
		                                                    array('itemTitle', ''),
		                                                    array('itemTitle', ''),
		                                                    array('itemTitle', ''),
		                                                    array('itemTitle', '')
		                                                ),
		                                                array(
		                                                    array($LANG->line('import_data_site_select'),    $site_data[1]      . $DSP->input_hidden('site_select',   $this->site_select)),
		                                                    array($LANG->line('import_data_section_select'), $weblog_data[1]    . $DSP->input_hidden('weblog_select', $this->weblog_select)),
		                                                    array($LANG->line('import_data_input_file'),     $this->input_file  . $DSP->input_hidden('input_file',    $this->input_file)),
		                                                    array($LANG->line('import_data_type_select'),    Import_data_CP::$input_types[$this->type_select] . $DSP->input_hidden('type_select',   $this->type_select))
		                                                ),
		                                                array(
		                                                    array('10%', '')
		                                                )).'<hr width="10%" align="left" />';

		$relationship_table_attributes = array(array('itemTitle\' colspan=\'2'));
		$relationship_table_content    = array(array('<u>'.$LANG->line('import_data_stage3_show_relationships_title').'</u>:'));

		// Relations - need to know which headings in relation_heading_select
		$input_heading_to_weblog = array();
		$input_heading_to_field = array();
		if (!empty($data_relations['input_heading'])) {
			foreach ($data_relations['input_heading'] as $index => $heading) {
				$heading_pieces = $this->lib->parse_input($heading);
				$input_heading_to_weblog[$heading_pieces[0]] = $data_relations['weblog'][$index];
				$input_heading_to_field[$heading_pieces[0]] = $data_relations['field'][$index];
			}
		}

		// Create the defined relationships table
		$unassigned_input_fields = array();
		foreach($headings as $index => $heading) {
			if (isset($input_heading_to_weblog[$index]) && isset($input_heading_to_field[$index])) {
				$weblog_pieces = $this->lib->parse_input($input_heading_to_weblog[$index]);
				$field_pieces = $this->lib->parse_input($input_heading_to_field[$index]);
				$relationship_table_attributes[] = array('itemTitle', '');
				$relationship_table_content[]    = array(($index+1).'. '.$heading, '<b>'.$weblog_pieces[1].'</b>,'.NBS.' <i>'.$field_pieces[1].'</i>'.$DSP->input_hidden('column_field_relation['.$index.']', $weblog_pieces[0].'#'.$field_pieces[0]));
			} else {
				$unassigned_input_fields[$index] = $heading;
			}
		}
		$relationship_table = '';
		if (count($relationship_table_content) > 1)
			$relationship_table = $this->lib->create_data_table($relationship_table_attributes, $relationship_table_content, array(array('10%', ''), array('10%', ''))).'<hr width="10%" align="left" />';

		$body .= $previous_table.$relationship_table.$this->create_form($site_data[0], $weblog_data[0], $headings);
		return $body.'<div id="submit_buttons">'.$DSP->input_submit($LANG->line('import_data_form_publish')).'</div>'.$DSP->form_close();
	} // End get_body


// ------------------------------------


	private function create_form($site_id, $weblog_id, $headings) {
		global $LANG, $DSP, $DB;
		$multi_field_types     = Field_Type::$multi_field_types;
		$delimiter_field_types = Field_Type::$delimiter_field_types;
		$unique_field_types    = Field_Type::$unique_field_types;
		$addition_field_types  = Field_Type::$addition_field_types;

		$none = '&nbsp;&nbsp;--';

		if ($headings === FALSE) {
			$table = $LANG->line('import_data_error_input_type').' ('.$this->input_file.')';
		} else {
			$table = '';

			$ff_fieldtypes = $this->lib->get_fieldframe_types();
			$column_select = $this->get_column_select($headings);
			$weblog_fields = $this->lib->get_weblog_fields($site_id, $weblog_id);

			// EE fields at beginning of table (as they are universal)
			$form_table_attributes = array(array('', 'itemTitle', '', '', '', '', ''),
			                               array('', 'itemTitle', '', '', '', '', ''),
			                               array('', 'itemTitle', '', '', '', '', ''),
			                               array('', 'itemTitle', '', '', '', '', ''),
			                               array('', 'itemTitle', '', '', '', '', ''),
			                               array('', 'itemTitle', '', '', '', '', ''),
			                               array('itemTitle\' colspan=\'7'));

			$form_table_content = array(array('*', 'Title',      '[title] text',
			                               $DSP->input_select_header('field_column_select['.$this->ee_fields_index['title_index'].']').$column_select,
			                               $none,
			                               $this->create_unique_checkbox('unique_title', 'unique['.$this->ee_fields_index['title_index'].']', $this->ee_fields_index['title_index']),
			                               $none),
			                            array('',  'Title URL',  '[url_title] text',
			                               $DSP->input_select_header('field_column_select['.$this->ee_fields_index['url_title_index'].']').$column_select,
			                               $none,
			                               $this->create_unique_checkbox('unique_url_title', 'unique['.$this->ee_fields_index['url_title_index'].']', $this->ee_fields_index['url_title_index']),
			                               $none),
			                            array('',  'Category',   '[category] text',
			                               $DSP->input_select_header('field_column_select['.$this->ee_fields_index['category_index'].'][]', 'y', '4', '85%').$column_select,
			                               $this->create_delimter_field('delimiter_category', 'delimiter['.$this->ee_fields_index['category_index'].']'),
			                               $none,
			                               $this->create_disable_overwrite_checkbox('addition_category', 'addition['.$this->ee_fields_index['category_index'].']', $this->ee_fields_index['category_index'])),
			                            array('',  'Entry Date', '[entry_date] date',
			                               $DSP->input_select_header('field_column_select['.$this->ee_fields_index['entry_date_index'].']').$column_select,
			                               $none,
			                               $none,
			                               $none),
			                            array('',  'Author',     '[author] text',
			                               $DSP->input_select_header('field_column_select['.$this->ee_fields_index['author_index'].']').$column_select,
			                               $none,
			                               $none,
			                               $none),
			                            array('',  'Status',     '[status] text',
			                               $DSP->input_select_header('field_column_select['.$this->ee_fields_index['status_index'].']').$column_select,
			                               $none,
			                               $none,
			                               $none),
			                            array('<hr />'));

			// Number of EE fields
			$i = $this->ee_fields_index['num_ee_fields'];
			foreach($weblog_fields as $index => $row) {
				$row_content = array();

				// Translate ftype_id_X into field type name
				if (substr($row['field_type'], 0, 9) == 'ftype_id_')
					$row['field_type'] = $ff_fieldtypes[$row['field_type']];

				// Generate table data for the weblog fields
				$row_content[] = ($row['field_required'] == 'y' ? '&nbsp;&nbsp;*' : '');
				$row_content[] = $row['field_label'];
				$row_content[] = '['.$row['field_name'].'] '.$row['field_type'].($row['field_is_gypsy'] == 'y' ? ' (gypsy)' : '');
				if (in_array($row['field_type'], $multi_field_types))
					$row_content[] = $DSP->input_select_header('field_column_select['.$i.'][]', 'y', '4', '85%').$column_select;
				else
					$row_content[] = $DSP->input_select_header('field_column_select['.$i.']').$column_select;
				$row_content[] = (in_array($row['field_type'], $delimiter_field_types)) ? $this->create_delimter_field('delimiter_'.$i, 'delimiter['.$i.']') : $none;
				$row_content[] = (in_array($row['field_type'], $unique_field_types)) ? $this->create_unique_checkbox('unique_'.$i, 'unique['.$i.']', $i) : $none;
				$row_content[] = (in_array($row['field_type'], $addition_field_types)) ? $this->create_disable_overwrite_checkbox('addition_'.$i, 'addition['.$i.']', $i) : $none;
				$i++;

				$form_table_attributes[] = array('', 'itemTitle', '', '', '', '', '');
				$form_table_content[] = $row_content;
			}

		}
		return $this->lib->create_data_table($form_table_attributes, $form_table_content, array(array('1%', '20%', '15%', '10%', '10%', '10%', '')));
	} // End create_form


	private function get_column_select ($headings)
	{
		global $DSP, $LANG;
		$column_select = $DSP->input_select_option('', '- '.$LANG->line('import_data_default_select').' -', 'y');
		// Create select from input file headings
		foreach ($headings as $index => $heading)
			$column_select .= $DSP->input_select_option($index, ++$index.' - '.$heading);
		return $column_select.$DSP->input_select_footer();
	} // End get_column_select


	private function create_delimter_field ($id, $name)
	{
		global $DSP, $LANG;
		return '<label for="'.$id.'">'.$LANG->line('import_data_delimiter_field').'</label> '.$DSP->input_text($name, '', '5', '10', 'input', '35px', 'id=\''.$id.'\'');
	} // End create_delimter_field


	private function create_unique_checkbox ($id, $name, $value)
	{
		global $DSP, $LANG;
		return $DSP->input_checkbox($name.'\' id=\''.$id, $value).' <label for="'.$id.'">'.$LANG->line('import_data_unique_field').'</label>';
	} // End create_unique_checkbox


	private function create_disable_overwrite_checkbox ($id, $name, $value)
	{
		global $DSP, $LANG;
		return $DSP->input_checkbox($name.'\' id=\''.$id, $value).' <label for="'.$id.'">'.$LANG->line('import_data_addition_field').'</label>';
	} // End create_disable_overwrite_checkbox


}


/* End of file stage_three.class.php */
/* Location: ./system/modules/import_data/files/classes/stage_three.class.php */