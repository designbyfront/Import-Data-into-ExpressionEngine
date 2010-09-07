<?php

// Used in: stage_two.php, stage_three.php
// Displays the data from the previous submissions

	$r .= $DSP->table('', '10', '', '100%');

	$r .= $DSP->tr()
		 .  $DSP->table_qcell('itemTitle', $LANG->line('import_data_site_select'), '10%')
		 .  $DSP->table_qcell('', $site_name.$site_data_hidden)
		 .  $DSP->tr_c();

	$r .= $DSP->tr()
		 .  $DSP->table_qcell('itemTitle', $LANG->line('import_data_section_select'))
		 .  $DSP->table_qcell('', $weblog_name.$weblog_data_hidden)
		 .  $DSP->tr_c();

	$r .= $DSP->tr()
		 .  $DSP->table_qcell('itemTitle', $LANG->line('import_data_input_file'))
		 .  $DSP->table_qcell('', $input_data_location.$input_data_hidden)
		 .  $DSP->tr_c();

	$r .= $DSP->tr()
		 .  $DSP->table_qcell('itemTitle', $LANG->line('import_data_type_select'))
		 .  $DSP->table_qcell('', $input_type.$input_type_hidden)
		 .  $DSP->tr_c();

	$r .= $DSP->table_c();

?>