<?php
/**
 * Import Data into ExpressionEngine
 *
 * ### EE 1.6 version ###
 *
 *
 * STAGE ONE
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

class Stage_one extends Stage {

	protected $lib;


	function Stage_one ()
	{
		$this->__construct();
	}


	function __construct ()
	{
		global $DSP, $LANG;

		$this->lib = new Stage_library();
		$DSP->title = $LANG->line('import_data_module_name');
		$DSP->crumb = $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=import_data', $LANG->line('import_data_module_name')).$DSP->crumb_item($LANG->line('import_data_stage1'));
	}


	public function get_javascript ()
	{
		global $LANG, $SESS;

		// jQuery javascript for manipulation of the form select elements (make them dynamic)
		return '<script type="text/javascript">
	$(document).ready(function(){

		var weblog_list = new Object();

		function grab_weblog_select() {
			$("select[name=weblog_select] option").each(function() {
				weblog_list[$(this).val()] = $(this).text();
			});
		}

		function nice_weblog_select(site_id) {
			site_id = (site_id.split("#"))[0];
			$("select[name=weblog_select] option").each(function() {
				$(this).remove();
			});
			$.each(weblog_list, function(index, value) {
				var piece = value.split(" - ");
				subpiece = piece[0].split("'.$LANG->line('import_data_site_select').' ");
				if (subpiece[1] == site_id)
					$("select[name=weblog_select]").append($("<option></option>").attr("value",index).text(piece[1]));
			});
		}

		function disable_site_select() {
			if ( $("select[name=site_select] option").length == 1 )
				$("select[name=site_select]").attr("disabled","disabled");
		}
		function enable_site_select() {
			if ( $("select[name=site_select] option").length == 1 )
				$("select[name=site_select]").removeAttr("disabled");
		}

		function disable_type_select() {
		//	$("select[name=type_select]").attr("disabled","disabled");
		}
		function enable_type_select() {
		//	$("select[name=type_select]").removeAttr("disabled");
		}

		function preselect_site(site_id) {
			$("select[name=site_select] option").each(function() {
				if (($(this).val().split("#"))[0] == site_id) {
					$(this).attr("selected","selected");
					nice_weblog_select( $("select[name=site_select]").val() );
				}
			});
		}

		grab_weblog_select();
		nice_weblog_select( $($("select[name=site_select]").get(0)).val() );
		disable_site_select();
		disable_type_select();
		preselect_site('.$SESS->userdata['site_id'].');


		$("select[name=site_select]").change(function() {
			nice_weblog_select( $(this).val() );
		});

		$("#entryform").submit(function() {
			enable_site_select();
			enable_type_select();
		});


		function nice_existing_file_select(select_name) {
			var mapping = new Object;
			$("select[name="+select_name+"] option").each(function(index) {
				var name = $(this).text().split(" - ");
				if (name.length > 1) {
					mapping[$(this).val()] = name[name.length-1].replace(", ", "<br />");
					name.splice(length-1);
					name.join(" - ");
					$(this).text(name[0]);
				} else {
					if (index == 0)
						none = "-";
					else
						none = "unknown";

					if (select_name == "settings_select")
						mapping[$(this).val()] = "'.$LANG->line('import_data_stage1_uploaded').': "+none+"<br />'.$LANG->line('import_data_stage1_created').': &nbsp;&nbsp;&nbsp;"+none;
					else
						mapping[$(this).val()] = "'.$LANG->line('import_data_stage1_uploaded').': "+none;
				}
			});
			return mapping;
		}

		var input_file_select_mapping = nice_existing_file_select("input_select");
		$("select[name=input_select]").change(function() {
			$("#input_file_dates").html(input_file_select_mapping[$(this).val()]);
		});

		var settings_file_select_mapping = nice_existing_file_select("settings_select");
		$("select[name=settings_select]").change(function() {
			var preBreaks = "";
			var postBreaks = "";
			console.log(settings_file_select_mapping[$(this).val()]);
			if (settings_file_select_mapping[$(this).val()].indexOf("<br />") == -1) {
				if (settings_file_select_mapping[$(this).val()].indexOf("Created") != -1) {
					preBreaks = "<br />";
				} else {
					postBreaks = "<br /><br />";
				}
			}
			$("#settings_file_dates").html(preBreaks+settings_file_select_mapping[$(this).val()]+postBreaks);
		});

	});
</script>';
	} // End get_javascript


	public function get_body ()
	{
		global $DSP, $LANG;

		$body  = $DSP->heading($LANG->line('import_data_stage1_heading'));
		$body .= $DSP->form_open(array('action'  => 'C=modules'.AMP.'M=import_data'.AMP.'P=stage_two', 
		                               'method'  => 'post',
		                               'name'    => 'entryform',
		                               'id'      => 'entryform',
		                               'enctype' => 'multipart/form-data'),
		                         array());
		// Create table for form
		$table = $DSP->table('', '10', '', '600px')
		       . $this->lib->create_table_row(array('tableHeading'), array(array()))
		       . $this->lib->create_table_row(array('\' colspan=\'4'), array('<h4 style="margin: 0; font-size: 1.2em;">'.$LANG->line('import_data_input_file').'</h4>'))
		       . $this->lib->create_table_row(array('', '\' style=\'width: 250px;', '', '\' style=\'padding-left: 30px;'),
		                                      array('', '<input type="file" name="input_file" />', '<div style="text-align: center; width: 50px;">- '.$LANG->line('import_data_stage1_or_cpaitalised').' -</div>', $this->get_select_files_location(Import_data_CP::get_input_file_upload_location(), 'input_select', 'input')))
		       . $this->lib->create_table_row(array('', '\' colspan=\'3'), array('', '<hr />'))
		       . $this->lib->create_table_row(array('\' colspan=\'4'), array('<h4 style="margin: 0; font-size: 1.2em;">'.$LANG->line('import_data_input_settings').'</h4>'))
		       . $this->lib->create_table_row(array('', '', '\' rowspan=\'4\' valign=\'center\' style=\'text-align: center;', '\' rowspan=\'2'),
		                                      array('', $DSP->input_checkbox('relationships\' id=\'relationships', 'y', 0).' <label for="relationships">'.$LANG->line('import_data_relationship_check').'</label>', '<div style="height: 60px; width: 1px; border-left: 1px solid #000; margin: 0 auto;"></div><div style="text-align: center; padding: 5px 0;">- '.$LANG->line('import_data_stage1_or_cpaitalised').' -</div><div style="height: 60px; width: 1px; border-left: 1px solid #000; margin: 0 auto;"></div>', '<input type="file" name="settings_file" />'))
		       . $this->lib->create_table_row(array('itemTitle', ''), array($LANG->line('import_data_site_select'), $this->get_site_select()), array('10%', ''))
		       . $this->lib->create_table_row(array('itemTitle', '', ''), array($LANG->line('import_data_section_select'), $this->get_weblog_select(), '<div style="width: 75px; height: 1px; border-top: 1px solid #000; float: left; position: relative; top: 8px"></div><div style="text-align: center; padding: 0 5px; float: left;">- '.$LANG->line('import_data_stage1_or_cpaitalised').' -</div><div style="width: 75px; height: 1px; border-top: 1px solid #000; float: left; position: relative; top: 8px"></div>'))
		       . $this->lib->create_table_row(array('itemTitle', '', '\' style=\'padding-left: 0.5em;'), array($LANG->line('import_data_type_select'), $this->get_type_select(), $this->get_select_files_location(Import_data_CP::get_settings_file_upload_location(), 'settings_select', 'settings')))
		       . $DSP->table_c();
		return $body.$table.$DSP->input_submit($LANG->line('import_data_form_continue')).$DSP->form_close();
	} // End get_body


// ------------------------------------


	private function get_select_files_location ($location, $id, $type)
	{
		global $DSP, $LANG;

		$files = $this->get_directory_list($location);
		// If no files, disable select and have message
		if (count($files) == 0) {
			$file_select  = $DSP->input_select_header($id.'\' disabled=\'disabled');
			$file_select .= $DSP->input_select_option('0', $LANG->line('import_data_stage1_no_prev_'.$type.'_upload'));
		// Else create select for all files
		} else {
			$file_select  = $DSP->input_select_header($id);
			$file_select .= $DSP->input_select_option('0', $LANG->line('import_data_stage1_prev_'.$type.'_upload').':');
			foreach($files as $file) {
				$display_name = $file;
				$pieces = explode('-', $file);
				$count = count($pieces);
				// Attempt to grab uploaded on timestamp from filename
				if ($count > 1 && @date('Y-m-d @ H:i', $pieces[0]) !== FALSE) {
					$temp_pieces = $pieces;
					$timestamp = $temp_pieces[0];
					array_shift($temp_pieces);
					$filename = implode('-', $temp_pieces);
					$display_name = $filename.' - '.$LANG->line('import_data_stage1_uploaded').': '.@date('Y-m-d @ H:i', $timestamp);
				}

				// Attempt to grab created on timestamp from filename
				if ($count > 1) {
					$bits = explode('.', $pieces[$count-1]);
					if (@date('Y-m-d @ H:i', $bits[0]) !== FALSE)
						$display_name .= ($display_name == $file ? ' - ' : ', ').$LANG->line('import_data_stage1_created').': &nbsp;&nbsp;&nbsp;'.@date('Y-m-d @ H:i', $bits[0]);
				}
				$file_select .= $DSP->input_select_option($file, $display_name);
			}
		}
		if ($type != 'input')
			return $file_select.$DSP->input_select_footer().'<div style="padding-left: 3px;" id="'.$type.'_file_dates">'.$LANG->line('import_data_stage1_uploaded').': -<br />'.$LANG->line('import_data_stage1_created').': &nbsp;&nbsp;&nbsp;-</div>';
		else
			return $file_select.$DSP->input_select_footer().'<div style="padding-left: 3px;" id="'.$type.'_file_dates">'.$LANG->line('import_data_stage1_uploaded').': -</div>';
	} // End get_select_files_location


	private function get_directory_list ($directory)
	{
		// Generate array of all files in supplied directory
		$results = array();
		$handler = opendir($directory);
		while ($file = readdir($handler)) {
			if ($file != "." && $file != "..") {
				$results[] = $file;
			}
		}
		closedir($handler);
		return $results;
	} // End get_directory_list


	private function get_site_select ()
	{
		global $DSP, $DB;

		// Create select of sites (special format for javascript)
		$query = $DB->query("SELECT site_id, site_label, site_name FROM exp_sites");
		$site_select = $DSP->input_select_header('site_select');
		foreach($query->result as $row)
			$site_select .= $DSP->input_select_option($row['site_id'].'#'.$row['site_label'].' ['.$row['site_name'].']', $row['site_label'].' ['.$row['site_name'].']');
		return $site_select.$DSP->input_select_footer();
	} // End get_site_select


	private function get_weblog_select ()
	{
		global $DSP, $DB, $LANG;

		// Create select of weblogs (special format for javascript)
		$query = $DB->query("SELECT weblog_id, site_id, blog_name, blog_title FROM exp_weblogs");
		$weblog_select = $DSP->input_select_header('weblog_select');
		foreach($query->result as $row)
			$weblog_select .= $DSP->input_select_option($row['weblog_id'].'#'.$row['blog_title'].' ['.$row['blog_name'].', '.$row['weblog_id'].']', $LANG->line('import_data_site_select').' '.$row['site_id'].' - '.$row['blog_title'].' ['.$row['blog_name'].', '.$row['weblog_id'].']');
		return $weblog_select.$DSP->input_select_footer();
	} // End get_weblog_select


	private function get_type_select ()
	{
		global $DSP;

		// Create select of input file types
		$type_select = $DSP->input_select_header('type_select');
		foreach(Import_data_CP::$input_types as $id => $input)
			if ($input !== '')
				$type_select .= $DSP->input_select_option($id, $input);
		return $type_select.$DSP->input_select_footer();
	} // End get_type_select


}


/* End of file stage_one.class.php */
/* Location: ./system/modules/import_data/files/classes/stage_one.class.php */