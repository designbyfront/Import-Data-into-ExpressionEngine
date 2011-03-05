<?php

$L = array(

//----------------------------------------
// Required for MODULES page
//----------------------------------------

	'import_data_module_name'        => 'Import Data',
	'import_data_module_description' => 'Wizard to import external data into ExpressionEngine',

//----------------------------------------

	// STAGE BREADCRUMBS
	'import_data_stage1' => 'Stage 1',
	'import_data_stage2' => 'Stage 2',
	'import_data_stage3' => 'Stage 3',
	'import_data_stage4' => 'Stage 4',

	// STAGE HEADINGS
	'import_data_stage1_heading' => 'Import Data - Stage 1',
	'import_data_stage2_heading' => 'Import Data - Stage 2',
	'import_data_stage3_heading' => 'Import Data - Stage 3',
	'import_data_stage4_heading' => 'Import Data - Stage 4',

	// GENERAL FORM TEXT
	'import_data_site_select'                 => 'Site',
	'import_data_section_select'              => 'Weblog',
	'import_data_input_file'                  => 'Input File',
	'import_data_input_settings'              => 'Settings',
	'import_data_type_select'                 => 'Type',
	'import_data_form_continue'               => 'Continue',
	'import_data_form_publish'                => 'Publish',
	'import_data_form_export_settings'        => 'Export Settings to File',
	'import_data_input_field_select'          => 'Input data field',
	'import_data_has_relationship'            => 'has relationship with',
	'import_data_field_select'                => 'Field',
	'import_data_unique_field'                => 'Unique?',
	'import_data_addition_field'              => 'Disable overwrite behaviour',
	'import_data_delimiter_field'             => 'In field delimiter:',
	'import_data_error_input_type'            => 'Unable to open input file',
	'import_data_error_input_file_permission' => 'Unable to open input file - file not readable',
	'import_data_unknown_input_type'          => 'Unknown input type',
	'import_data_default_select'              => 'Default',
	'import_data_unimplemented_input_type'    => ' data input has not been implemented',
	'import_data_object_implementation'       => 'Input object has not been implemented correctly - missing interface \'Input_type\'',

	'import_data_error_required_data_missing'       => 'Required data is missing - ',
	'import_data_error_selected_site_missing'       => 'Selected site is missing',
	'import_data_error_selected_weblog_missing'     => 'Selected weblog is missing',
	'import_data_error_input_file_location_missing' => 'Location of input file is missing',
	'import_data_error_input_type_missing'          => 'Type of input file is missing',

	// STAGE 1 FORM TEXT
	'import_data_relationship_check'             => 'Relationships with existing entries?',
	'import_data_stage1_uploaded'                => 'Uploaded',
	'import_data_stage1_created'                 => 'Created',
	'import_data_stage1_or_cpaitalised'          => 'OR',
	'import_data_stage1_no_prev_input_upload'    => 'No previously uploaded input files',
	'import_data_stage1_no_prev_settings_upload' => 'No previously uploaded settings files',
	'import_data_stage1_prev_input_upload'       => 'Select an existing input file',
	'import_data_stage1_prev_settings_upload'    => 'Select an existing settings file',

	// STAGE 2 FORM TEXT
	'import_data_stage2_input_error'              => 'The input file has failed to upload or you have not selected a file!',
	'import_data_stage2_input_success'            => 'The input file has been added successfully.',
	'import_data_stage2_settings_success'         => 'The settings file has been added successfully.',
	'import_data_stage2_publish_message'          => 'All necessary data has been provided. Please press the "Publish" button to execute the data import.',
	'import_data_stage2_relationship_y'           => 'You have selected that the given input has relationship(s) to existing data.<br />Detail the relationship(s) below:',
	'import_data_stage2_relationship_n'           => 'You have selected that the given input has <b>no existing relationships</b>.',
	'import_data_stage2_add_relationship_link'    => 'Add another relationship [Javascript required]',

	// STAGE 3 FORM TEXT
	'import_data_stage3_show_relationships_title' => 'Selected Relationships',

	// STAGE 4 SUMMARY TEXT
	'import_data_stage4_missing_fieldtype_1'      => 'Unable to find required field type - please make sure fieldtype \'',
	'import_data_stage4_missing_fieldtype_2'      => '\' has been implemented',
	'import_data_stage4_settings_file_permission' => 'Unable to open settings file - file not readable',
	'import_data_stage4_row_error'                => '<b>Failed</b> on data input for row ',
	'import_data_stage4_missing_data'             => '(data missing for required field)',
	'import_data_stage4_no_title'                 => '(title field empty)',
	'import_data_stage4_invalid_row_structure_1'  => '(invalid row structure - ',
	'import_data_stage4_invalid_row_structure_2'  => ' columns expected, ',
	'import_data_stage4_invalid_row_structure_3'  => ' columns found)',
	'import_data_stage4_unauthorised_author_1'    => '(author \'',
	'import_data_stage4_unauthorised_author_2'    => '\' has not been assigned to post and edit entries in this weblog)',
	'import_data_stage4_missing_author_1'         => '(author \'',
	'import_data_stage4_missing_author_2'         => '\' does not exist)',
	'import_data_stage4_no_author'                => '(author field empty)',
	'import_data_stage4_invalid_status_1'         => 'Status \'',
	'import_data_stage4_invalid_status_2'         => '\' is not in the status group assigned to this weblog',
	'import_data_stage4_submission_success'       => 'Successfully entered data for ',
	'import_data_stage4_submission_failed'        => '<b>Failed</b> to enter data for ',
	'import_data_stage4_submission_object_failed' => '<b>Failed</b> to create a submission object for ',
	'import_data_stage4_row'                      => 'row ',
	'import_data_stage4_of'                       => 'of ',
	'import_data_stage4_summary_heading'          => 'entries posted successfully',
	'import_data_stage4_notifications'            => 'Notification(s)',

	// STAGE 4 NOTIFICATION MESSAGES
	'import_data_stage4_notification_row_1'       => 'Row ',
	'import_data_stage4_notification_row_2'       => ': ',

	'import_data_stage4_notification_url_title_1' => 'The URL title \'',
	'import_data_stage4_notification_url_title_2' => '\' has already been used. EE will automatically change this to a unique value.',

	'import_data_stage4_notification_fieldtype_1' => 'A non-required field (',
	'import_data_stage4_notification_fieldtype_2' => ') of unknown field type \'',
	'import_data_stage4_notification_fieldtype_3' => '\' has been ignored',

	'import_data_stage4_notification_unique_1'    => 'The unique field(s) [',
	'import_data_stage4_notification_unique_2'    => '] have returned more than one corresponding entry. The first returned entry (',
	'import_data_stage4_notification_unique_3'    => ') will be used',

	'import_data_stage4_notification_category_1'  => 'An unknown category \'',
	'import_data_stage4_notification_category_2'  => '\' has not been assigned to this entry',

	// ---------------------------------------------
	// FIELD TYPE NOTIFICATION MESSAGES
	'import_data_stage4_notification_equals_quote'              => ' = \'',
	'import_data_stage4_notification_quote'                     => '\'',
	'import_data_stage4_notification_quote_left_square_bracket' => '\' [',

	// [post_data_rel]
	'import_data_stage4_notification_rel_1' => 'A relationship with an existing entry [',
	'import_data_stage4_notification_rel_2' => '\'] has not been created as the entry cannot be found',

	// [post_data_ngen_file_field]
	'import_data_stage4_notification_ngen_file_field_upload_location_1' => 'Upload location could not be determined from ExpressionEngine settings. Please make sure that upload locations where id',
	'import_data_stage4_notification_ngen_file_field_upload_location_2' => '\' exists',
	'import_data_stage4_notification_ngen_file_field_file_missing'      => 'File location does not exist for file \'',
	'import_data_stage4_notification_ngen_file_field_file_moved'        => 'The import has moved files to \'',
	'import_data_stage4_notification_ngen_file_field_file_not_moved'    => 'The import was unable to move \'',
	'import_data_stage4_notification_ngen_file_field_file_to'           => '\' to \'',
	'import_data_stage4_notification_ngen_file_field_file_specified_by' => '] as specified by the \'',
	'import_data_stage4_notification_ngen_file_field_file_field'        => '\' field',

	// [post_data_playa]
	'import_data_stage4_notification_playa_defined_1' => '<b>Playa</b> is unable to create a relationship for field \'',
	'import_data_stage4_notification_playa_defined_2' => '\' as it has not been defined in Stage 2',
	'import_data_stage4_notification_playa_missing_1' => 'A <b>Playa</b> relationship with an existing entry [',
	'import_data_stage4_notification_playa_missing_2' => '\'] has not been created as the entry cannot be found',


	// ---------------------------------------------
	// $LANG->line('')

	''=>''
);

?>