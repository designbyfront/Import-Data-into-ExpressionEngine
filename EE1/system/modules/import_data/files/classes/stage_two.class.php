<?php
/**
 * Import Data into ExpressionEngine
 *
 * ### EE 1.6 version ###
 *
 *
 * STAGE TWO
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

class Stage_two extends Stage {

	protected $lib;
	protected $input_file_obj;

	private $upload_success;
	private $files;
	private $input_types;

	public $input_file;


	function Stage_two ()
	{
		$this->__construct();
	}


	function __construct ()
	{
		global $DSP, $LANG;

		$this->lib = new Stage_library();
		$DSP->title = $LANG->line('import_data_module_name');
		$DSP->crumb = $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=import_data', $LANG->line('import_data_module_name')).$DSP->crumb_item($LANG->line('import_data_stage2'));

		$this->upload_success = array();
		$this->files = array();
	}


	public function process_post_data()
	{
		if (isset($this->upload_success['settings']) && $this->upload_success['settings'])
			$_POST = json_decode(file_get_contents($this->files['settings']), TRUE);

		if (!empty($_POST))
			parent::process_post_data();
	} // End process_post_data


	public function locate_file ($type, $upload_location)
	{
		$this->upload_success[$type] = FALSE;
		$target_file = '';
		// If file uploaded
		if (isset($_FILES[$type.'_file']['name']) && $_FILES[$type.'_file']['name'] != '') {
			// Upload directory will be - {system}/modules/import_data/files/upload_input_files/
			// This directory should have chmod 777
			if (!file_exists($upload_location))
				throw new Import_data_module_exception('The upload location does not exist - <i>'.$upload_location.'</i>');
			$target_file = $upload_location.time().'-'.basename($_FILES[$type.'_file']['name']);
			$this->upload_success[$type] = @move_uploaded_file($_FILES[$type.'_file']['tmp_name'], $target_file);
			// PHP will chmod the file as just readable to all (if allowed)
			@chmod($target_file, 0444);
		// Else existing file supplied
		} else if (isset($this->{$type.'_select'}) && !empty($this->{$type.'_select'})) {
			$target_file = $upload_location.$this->{$type.'_select'};
			if (file_exists($target_file))
				$this->upload_success[$type] = TRUE;
		}
		$this->files[$type] = $target_file;
		return $this->upload_success[$type];
	} // End locate_file


	public function validate_input_file ()
	{
		$this->input_file = (isset($this->files['input']) ? $this->files['input'] : null);
		parent::validate_input_file();
	} // End validate_input_file


	public function get_javascript ()
	{
		global $LANG;

		return '<script type="text/javascript">
	$(document).ready(function(){

		var relationship_index = 0;
		var master_relationship_table;
		var field_list = new Object();

		function copy_dom_chunk() {
			master_relationship_table = $("#relationship_table_0").clone();
		}

		function grab_field_select() {
			$("select[name=relation_field_select[]] option").each(function() {
				field_list[$(this).text()] = $(this).val();
			});
			console.dir(field_list);
		}

		function nice_field_select(weblog_id, index) {
			weblog_id = (weblog_id.split("#"))[0];
			var specific_field_select = $("select[name=relation_field_select[]]").get(index);
			$(specific_field_select).find("option").each(function() {
				$(this).remove();
			});
			$.each(field_list, function(index, value) {
				var piece = index.split(" - ");
				subpiece = piece[0].split("'.$LANG->line('import_data_section_select').' ");
				if (subpiece[1] == weblog_id)
					$(specific_field_select).append($("<option></option>").attr("value",value).text(piece[1]));
			});
		}

		function add_field_select_listener(index) {
			var relation_weblog_select = $("select[name=relation_weblog_select[]]").get(index);
			nice_field_select($(relation_weblog_select).val(), index);
			$(relation_weblog_select).change(function() {
				console.log("relation_field_select "+index+" called");
				nice_field_select( $(this).val(), index );
			});
		}

		function add_relationship_link() {
			$("#add_relationship").html( "<a href=\"\">"+$($("#add_relationship").text().split(" [")).get(0)+"</a>" );
			$("#add_relationship a").click(function () {
				var previous_relationship = relationship_index;
				relationship_index++;
				var relationship_table = $(master_relationship_table).clone()
				$(relationship_table).attr("id", "relationship_table_"+relationship_index);

				var relationship_num_title = $(relationship_table).find("td.relationship_num").get(0);
				$(relationship_num_title).text( ($(relationship_num_title).text().split("#"))[0] + "#" + (relationship_index+1) );

				$(relationship_table).insertAfter("#relationship_table_"+previous_relationship);
				add_field_select_listener(relationship_index);
				return false;
			});
		}

		copy_dom_chunk();
		grab_field_select();
		nice_field_select( $($("select[name=relation_weblog_select[]]").get(0)).val() );
		add_field_select_listener(0);
		add_relationship_link();

	});
</script>';
	} // End get_javascript


	public function get_body ()
	{
		global $DSP, $LANG, $DB;

		$body  = $DSP->heading($LANG->line('import_data_stage2_heading'));

		if (!isset($this->upload_success['input']) || !$this->upload_success['input'])
			throw new Import_data_module_exception($LANG->line('import_data_stage2_input_error'));

		// If settings file not provided, present settings form
		if (!isset($this->upload_success['settings']) || !$this->upload_success['settings']) {
			$weblog_data = $this->lib->parse_input($this->weblog_select);
			$site_data = $this->lib->parse_input($this->site_select);
			$form_submit = $DSP->input_submit($LANG->line('import_data_form_continue'));

			$body .= $DSP->qdiv('itemWrapper', $LANG->line('import_data_stage2_input_success'));
			$body .= $DSP->form_open(array('action' => 'C=modules'.AMP.'M=import_data'.AMP.'P=stage_three', 
			                               'method' => 'post',
			                               'name'   => 'entryform',
			                               'id'     => 'entryform'),
			                         array());
			$body .= $this->lib->create_data_table(array(
			                                           array('itemTitle', ''),
			                                           array('itemTitle', ''),
			                                           array('itemTitle', ''),
			                                           array('itemTitle', '')
			                                       ),
			                                       array(
			                                           array($LANG->line('import_data_site_select'),    $site_data[1]         . $DSP->input_hidden('site_select',   $this->site_select)),
			                                           array($LANG->line('import_data_section_select'), $weblog_data[1]       . $DSP->input_hidden('weblog_select', $this->weblog_select)),
			                                           array($LANG->line('import_data_input_file'),     $this->files['input'] . $DSP->input_hidden('input_file',    $this->files['input'])),
			                                           array($LANG->line('import_data_type_select'),    Import_data_CP::$input_types[$this->type_select] . $DSP->input_hidden('type_select',   $this->type_select))
			                                       ),
			                                       array(
			                                           array('10%', '')
			                                       ));

			// If relationships need defined, present the relationships form
			if (isset($this->relationships) && $this->relationships == 'y') {
				$body .= $DSP->qdiv('itemWrapper', $LANG->line('import_data_stage2_relationship_y'));
				$table = $DSP->table('\' id=\'relationship_table_0', '10', '', '100%')
				       . $this->lib->create_table_row(array('', 'relationship_num\' style=\'font-weight: bold;'), array('<hr />', 'Relationship #1'))
				       . $this->lib->create_table_row(array('itemTitle', ''), array($LANG->line('import_data_input_field_select'), $this->retrieve_input_headings($this->type_select, $this->files['input'])), array('10%', ''))
				       . $this->lib->create_table_row(array('style=""', '- '.$LANG->line('import_data_has_relationship').' -'), array('', ''))
				       . $this->lib->create_table_row(array('itemTitle', ''), array($LANG->line('import_data_section_select'), $this->get_relation_weblog_select($site_data[0])))
				       . $this->lib->create_table_row(array('itemTitle', ''), array('&nbsp; -- '.$LANG->line('import_data_field_select'), $this->get_relation_field_select($site_data[0], $weblog_data[0])))
				       . $DSP->table_c();
				$body .= $table.$DSP->qdiv('\' id=\'add_relationship\' style=\'padding-left: 8%; padding-top: 2em; ', $LANG->line('import_data_stage2_add_relationship_link'));
			} else {
				$body .= $DSP->qdiv('itemWrapper', $LANG->line('import_data_stage2_relationship_n'));
			}

		// Else, settings file provided, present publish button
		} else {
			$form_submit = $DSP->input_submit($LANG->line('import_data_form_publish'));
			$body .= $DSP->qdiv('itemWrapper', $LANG->line('import_data_stage2_input_success').'<br /><i>'.$this->files['input'].'</i>');
			$body .= $DSP->qdiv('itemWrapper', $LANG->line('import_data_stage2_settings_success').'<br /><i>'.$this->files['settings'].'</i>');
			$body .= $DSP->qdiv('itemWrapper', '<strong>'.$LANG->line('import_data_stage2_publish_message').'</strong>');
			$body .= $DSP->form_open(array('action' => 'C=modules'.AMP.'M=import_data'.AMP.'P=stage_four', 
			                               'method' => 'post',
			                               'name'   => 'entryform',
			                               'id'     => 'entryform'),
			                         array());
			$body .= $DSP->input_hidden('settings_file', $this->files['settings']).$DSP->input_hidden('input_file', $this->files['input']);
		}
		return $body.$form_submit.$DSP->form_close();

	} // End get_body


// ------------------------------------


	private function get_relation_weblog_select ($site_id)
	{
		global $DSP, $DB;

		// Generate select of all weblogs for site
		$query = $DB->query('SELECT weblog_id, blog_name, blog_title FROM exp_weblogs WHERE site_id = '.$DB->escape_str($site_id));
		$relation_weblog_select = $DSP->input_select_header('relation_weblog_select[]');
		foreach($query->result as $row)
			$relation_weblog_select .= $DSP->input_select_option($row['weblog_id'].'#'.$row['blog_title'].' ['.$row['blog_name'].', '.$row['weblog_id'].']', $row['blog_title'].' ['.$row['blog_name'].', '.$row['weblog_id'].']');
		return $relation_weblog_select.$DSP->input_select_footer();
	} // End get_weblog_select


	private function get_relation_field_select ($site_id, $weblog_id)
	{
		global $DSP, $DB, $LANG;

		// Generate select of all fields for site
		$query = $DB->query('SELECT wb.weblog_id, wf.field_id, wf.field_name, wf.field_label
		                     FROM exp_weblogs wb, exp_field_groups fg, exp_weblog_fields wf
		                     WHERE wb.site_id = '.$DB->escape_str($site_id).'
		                       AND wb.site_id = fg.site_id
		                       AND wb.site_id = wf.site_id
		                       AND wb.field_group = fg.group_id
		                       AND wb.field_group = wf.group_id '.
		                       ($this->lib->gypsy_installed() ? 'AND wf.field_is_gypsy = \'n\'' : ''));
		$relation_field_select = $DSP->input_select_header('relation_field_select[]');
		$last_weblog_id = 0;
		foreach($query->result as $row) {
			if ($last_weblog_id != $row['weblog_id']) {
				$last_weblog_id = $row['weblog_id'];
				$relation_field_select .= $DSP->input_select_option('0#Title [title]', $LANG->line('import_data_section_select').' '.$row['weblog_id'].' - Title [title]');
			}
			$relation_field_select .= $DSP->input_select_option($row['field_id'].'#'.$row['field_label'].' ['.$row['field_name'].']', $LANG->line('import_data_section_select').' '.$row['weblog_id'].' - '.$row['field_label'].' ['.$row['field_name'].']');
		}

		// Check for gypsy fields (if installed)
		if ($this->lib->gypsy_installed()) {
			// Select gypsy fields
			$query = $DB->query('SELECT wf.gypsy_weblogs, wf.field_id, wf.field_name, wf.field_label
			                     FROM exp_weblog_fields wf
			                     WHERE wf.site_id = \''.$DB->escape_str($site_id).'\'
			                       AND wf.field_is_gypsy = \'y\'');
			foreach($query->result as $row) {
				$used_by = explode(' ', trim($row['gypsy_weblogs']));
				foreach ($used_by as $weblog_id)
					$relation_field_select .= $DSP->input_select_option($row['field_id'].'#'.$row['field_label'].' ['.$row['field_name'].'] (gypsy)', $LANG->line('import_data_section_select').' '.$weblog_id.' - '.$row['field_label'].' ['.$row['field_name'].'] (gypsy)');
			}
		}
		return $relation_field_select.$DSP->input_select_footer();
	} // End get_relation_field_select


	private function retrieve_input_headings ($input_data_type, $input_data_location)
	{
		global $LANG, $DSP;

		// Retrieve and create select for input headings
		$headings = $this->input_file_obj->get_headings();
		if ($headings === FALSE) {
			$input_header_select = $LANG->line('import_data_error_input_type').' ('.$input_data_location.')';
		} else {
			$input_header_select = $DSP->input_select_header('relation_heading_select[]');
			foreach($headings as $index => $heading)
				$input_header_select .= $DSP->input_select_option($index.'#'.$heading, ++$index.' - '.$heading);
			$input_header_select .= $DSP->input_select_footer();
		}
		return $input_header_select;
	} // End retrieve_input_headings


}


/* End of file stage_two.class.php */
/* Location: ./system/modules/import_data/files/classes/stage_two.class.php */