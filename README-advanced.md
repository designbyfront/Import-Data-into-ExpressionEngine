# Import Data into ExpressionEngine - Advanced #
This guide will detail the ways in which the Import Data Module can be extended.

<a name="contents"></a>
## Contents ##
* [Introduction](#introduction)
* [Adding New Field Types](#addingfieldtype)
* [Adding New Data Input Types](#addinginputtype)

<a name="introduction"></a>
## Introduction ##
-- [Back to top](#contents)

### Support ###

For more information and support, please use the [issues page](http://github.com/designbyfront/Import-Data into-ExpressionEngine/issues) or contact us at [info@designbyfront.com](info@designbyfront.com).

### Created by Front ####

Useful, memorable and satisfying things for the web.
We create amazing online experiences that delight users and help our clients grow.

[Web Design](http://www.designbyfront.com) by Front

### License and Attribution ###

This work is licensed under the Creative Commons Attribution-Share Alike 3.0 Unported.
To view a copy of this license, visit [http://creativecommons.org/licenses/by-sa/3.0/](http://creativecommons.org/licenses/by-sa/3.0/)
or send a letter to `Creative Commons, 171 Second Street, Suite 300, San Francisco, California, 94105, USA`

__No Warranty__<br />
As this program is licensed free of charge, there is no warranty for the program, to the extent permitted by applicable law. Except when otherwise stated in writing the copyright holders and/or other parties provide the program "as is" without warranty of any kind, either expressed or implied, including, but not limited to, the implied warranties of merchantability and fitness for a particular purpose. The entire risk as to the quality and performance of the program is with you. should the program prove defective, you assume the cost of all necessary servicing, repair or correction.
In no event unless required by applicable law or agreed to in writing will any copyright holder, or any other party who may modify and/or redistribute the program as permitted above, be liable to you for damages, including any general, special, incidental or consequential damages arising out of the use or inability to use the program (including but not limited to loss of data or data being rendered inaccurate or losses sustained by you or third parties or a failure of the program to operate with any other programs), even if such holder or other party has been advised of the possibility of such damages.




<a name="addingfieldtype"></a>
## Adding New Field Types ##
-- [Back to top](#contents)

You may want to use field types in your weblogs/sections which are not supported by this module.<br />
To see if a specific field type has already been implemented, please see the [Supported Custom Field Types list](http://github.com/designbyfront/Import-Data-into-ExpressionEngine/blob/master/README.md#supportedfeatures).

If it has not been implemented, you can add it by following the instructions below.<br />
For different field types, the module relies on the `field_type` class, located at:
[system/modules/import_data/files/classes/field_type.class.php](http://github.com/designbyfront/Import-Data-into-ExpressionEngine/blob/master/EE1/system/modules/import_data/files/classes/field_type.class.php)

This class takes in the field type and other data, and constructs the correct POST data needed for ExpressionEngine to handle the input.
The parameters for this class (and so the data which is available to you) are:

* __order__
    * `int` corresponding to the number of previous fields executed [Incremental] (not including EE specific fields)
* __column_index__
    * `int` corresponding to index for the column of current row in the input file
* __site_id__
    * `int` specifying ExpressionEngine site
* __weblog_id__
    * `int` specifying ExpressionEngine weblog
* __field__
    * `associative array` containing details about the field type
        * [field_id, field_label, field_name, field_required, field_type, field_is_gypsy]
* __value__
    * `string` value being put into the field (empty string if no value provided)
* __existing__
    * `associative array` of existing full entry if found in database (empty if not found)
        * This is not an exhaustive list:
        * [entry_id, site_id, weblog_id, field_id_X, field_ft_X, ... author_id, pentry_id, forum_topic_id, ip_address, title, url_title, status, versioning_enabled, view_count_one, view_count_two, view_count_three, view_count_four, allow_comments, allow_trackbacks, sticky, entry_date, dst_enabled, year, month, day, expiration_date, comment_expiration_date, edit_date, recent_comment_date, comment_total, trackback_total, sent_trackbacks, recent_trackback_date]
* __added_ids__
    * `array` of ints which correspond to the entry_id's of recently entered entries (or empty if first entry published)
* __relationships__
    * `associative array` of user defined data relationships [{column_index} => {some_weblog_id}#{some_field_id}] (or empty if not defined in stage 2)
<br />

<br />
__Step 1.__ - Find out the structure of the POST data needed by your required field type
The _date field type_ requires data in the format:
<pre>
Array
(
    [field_id_XX] => 2010-11-14 21:34 PM
)
</pre>
We can see the structure is the field id mapping to a date string which is of the format "Y-m-d H:i A".

<br />
__Step 2.__ - Open `field_type.class.php` and create a new private function

The _date field function_ looks like:
<pre>
private function post_data_date() {
	// Functionality here
}
</pre>

<br />
__Step 3.__ - Implement the functionality to convert the input data into the correct POST format

* First we want to check to see if we have been sent an invalid value:
<pre>
if ($this->value === NULL || $this->value === '') {
	// We were sent an invalid value
}
</pre>

* If we have been sent an invalid value, we can check if we have an existing value we can use instead.<br />
To do this, we need to look in the existing entry data array `$this->existing` for the field we are currently posting to `$this->field['field_id']`<br />
__Note:__ The index used is `'field_id_'.$this->field['field_id']`<br />
_Dont' forget the_ `field_id_` !
<pre>
if (isset($this->existing['field_id_'.$this->field['field_id']])) {
	// There is an existing value for this field
}
</pre>

* If there is an existing value, we want to set it as our current value.<br />
If there is no existing value, we will set our current value to an empty string.<br />
<pre>
$this->value = $this->existing['field_id_'.$this->field['field_id']];
</pre>

* Now that we've got our value (or an the existing value if available), we can construct the POST data. This is an array from the _field id_ to the _field data_.<br />
This is where you would implement the code necessary to format the data correctly for your field type.<br />
Using the date example, as this is a date it needs to be in a certain format.<br />
The _field id_ is created in the same way as we accessed the existing entry data array.<br />
The _field data_ uses `strtotime` to convert from string to timestamp, and then `date` to construct the correctly formatted date.<br />
This array is then returned.
<pre>
array('field_id_'.$this->field['field_id'] => date("Y-m-d H:i A", strtotime($this->value)));
</pre>

* The final _date field function_ looks like:
<pre>
private function post_data_date() {
	if ($this->value === NULL || $this->value === '') {
		if (isset($this->existing['field_id_'.$this->field['field_id']])) {
			$this->value = $this->existing['field_id_'.$this->field['field_id']];
		} else {
			$this->value = '';
		}
	}
	return array('field_id_'.$this->field['field_id'] => date("Y-m-d H:i A", strtotime($this->value)));
}
</pre>


<br />
__Step 4.__ - Add a new mapping in the `$supported_types` array from your field type name to the name of your newly created function in _Step 2_
The _date field function_ is added as a supported type like:
<pre>
private $supported_types = array(
	...
	'select'          => 'post_data_select',
	'date'            => 'post_data_date',
	'rel'             => 'post_data_rel',
	...
);
</pre>

<br />
__Summary__
* Check if `$this->value` if invalid and use the data in `$this->existing` to default
* Return an array with the field name 'field_id_X' mapped to the data





<a name="addinginputtype"></a>
## Adding New Data Input Types ##
-- [Back to top](#contents)

Your data may not come in a format supported by this module.
To see if a specific input type has already been implemented, please see the [Supported Input Types list](http://github.com/designbyfront/Import-Data-into-ExpressionEngine/blob/master/README.md#supportedfeatures).

If it has not been implemented, you can add it by following the instructions below.
The module uses input type classes which implement an interface `Input_type`.
Due to the use of an interface, the class must implement four functions:

* __get_headings()__
    * This function will _open the file_, _read first row_ (which should be headings), _close the file_ and _return array_ of headings<br />
    * Below is the function for `get_headings` in `Csv_file.class.php`
<pre>
public function get_headings() {
	$fh = fopen($this->location, "r");
	if ($fh) {
			$headings = fgetcsv($fh);
			fclose($fh);
			return $headings;
	}
	return $fh;
}
</pre>

* __start_reading_rows()__
    * This function will _setup opening the file_ and _return boolean_ for success
    * Below is the function for `start_reading_rows` in `Csv_file.class.php`
<pre>
public function start_reading_rows() {
	return $this->read_line_handle = fopen($this->location, "r");
}
</pre>

* __stop_reading_rows()__
    * This function will _setup closing the file_ and _return int_ of number of lines output
    * Below is the function for `stop_reading_rows` in `Csv_file.class.php`
<pre>
public function stop_reading_rows() {
	fclose($this->read_line_handle);
	return $this->line_count;
}
</pre>

* __read_row()__
    * This function will _read the next row_ from the file and _return array_ of row data __or__ _return boolean false_ when there no more rows
    * _Note:_ The first call will return the headings - make this call without assigning to a variable to discard.
    * Below is the function for `read_row` in `Csv_file.class.php`
<pre>
public function read_row() {
	if (feof($this->read_line_handle))
		return FALSE;
	$line = fgetcsv($this->read_line_handle);
	if ($line !== FALSE)
		$this->line_count++;
	return $line;
}
</pre>


<br />
__Step 1.__ - Construct your input type class
Use the descriptions of the necessary functions from above and examples of existing input type classes which can be found in [system/modules/import_data/files/input_types/](http://github.com/designbyfront/Import-Data-into-ExpressionEngine/tree/master/EE1/system/modules/import_data/files/input_types/).
Current types are:
* [Csv_file.class.php](http://github.com/designbyfront/Import-Data-into-ExpressionEngine/blob/master/EE1/system/modules/import_data/files/input_types/Csv_file.class.php) - The class currently used for importing CSV file data
* [Xml_test_file.class.php](http://github.com/designbyfront/Import-Data-into-ExpressionEngine/blob/master/EE1/system/modules/import_data/files/input_types/Xml_test_file.class.php) - A test class written to test basic XML file import
    * The above XML importer is __not__ efficient and loads the file as DOM into memory - do not create your class like this or you will likely run out of memory

<br />
__Step 2.__ - Add your new input type PHP class into the input_types directory `system/modules/import_data/files/input_types/`

<br />
__Step 3.__ - Include your new input type PHP class at the top of `system/modules/import_data/mcp.import_data.php`
<pre>
require_once 'files/input_types/My_new_input_type.class.php';
</pre>

<br />
__Step 4.__ - Add your new data type in each stage file in directory `system/modules/import_data/files/`
* Add the new data type name to the `$input_types` array at the top of `stage_one.php`
<pre>
$input_types = array(
	'CSV' => 'CSV',
	'My New Input Type' => 'my_new_input_type',
	'' => ''
);
</pre>

* Add the new data type name and object instantiation to `stage_two.php`, `stage_three.php` and `stage_four.php`<br />
    * Object instantiation occurs in the switch statement, depending on what type has been provided<br />
    * Make sure to assign the object to `$input_file_obj`
<pre>
switch($input_data_type)
{
	case 'CSV' :
		$input_file_obj = new Csv_file($input_data_location);
		break;

	case 'my_new_input_type' :
		$input_file_obj = new My_new_input_type($input_data_location);
		break;

	case 'XML' :
		return $input_data_type.$LANG->line('import_data_unimplemented_input_type');

	default :
		return $LANG->line('import_data_unknown_input_type').' ['.$input_data_type.']';
}
</pre>
