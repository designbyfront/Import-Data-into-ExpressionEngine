## Import Data into ExpressionEngine ##
An ExpressionEngine Module that allows easy import of data into ExpressionEngine<br />
Built in import capabilities are CSV files to all base ExpressionEngine fieldtypes and Playa<br />
It is easily extended to allow additional import types and custom fieldtypes (see **Extending**)

## Installation ##

Use the structure provided to place the files within your current EE installation.<br />
Install the module in your EE control panel

## Adding a new data input type ##

Your data may not come in a format supported by this module.<br />
However, you can create your own data input type easily, just by creating a PHP class.

**Step 1:** Create your new input type PHP class<br />
The class needs to implement `Input_type` and subsequently should provide four methods:<br />
`get_headings()`<br />
&nbsp;&nbsp;&nbsp;open file, read first row (headings), close file, return flat array of headings<br />
`start_reading_rows()`<br />
&nbsp;&nbsp;&nbsp;setup open file, return boolean for success<br />
`stop_reading_rows()`<br />
&nbsp;&nbsp;&nbsp;setup close file, return int of number of lines output<br />
`read_row()`<br />
&nbsp;&nbsp;&nbsp;read next row from file, return flat array of row data, or false when no more rows (first call will return headings)

**Step 2:** Place your new input type PHP class in the `modules/import_data/files/input_types`

**Step 3:** Include your new input type PHP class at the top of `mcp.import_data.php`

**Step 4:** Include your new input type PHP class at the top of `mcp.import_data.php`<br />
 - Eg. `require_once 'files/input_types/My_new_input.class.php';`

**Step 5:** Add your new data type in each stage file<br />
&nbsp;&nbsp;&nbsp;- Add the new data type name to the "$input_types" array at the top of stage_one.php<br />
&nbsp;&nbsp;&nbsp;- Add the new data type name and object instantiation to stage_two.php, stage_three.php and stage_four.php<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;This is in the switch statement in the function defined at the top of each.<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Make sure to assign the object to a variable named `$input_file_obj`

## Adding a field type ##

You may want to use field types in your weblogs/sections which are not supported by this module.<br />
However, you can add your own field types easily, just by creating a PHP function.

**Step 1:** Open the field type class - `field_type.class.php`

**Step 2:** Create a function in this class which will output the POST for your field type.<br />
**Remember:**<br />
&nbsp;&nbsp;&nbsp;- This is an array with the field name 'field_id_X' mapped to the data<br />
&nbsp;&nbsp;&nbsp;- Check is `$this->value` is `NULL` and use the data in `$this->existing` to default

**Step 3:** Add your field type function string to the `$supported_types` array mapping to the function you have just created.


## Support ##

For more information and support, please use the [issues page](http://github.com/designbyfront/Import-Data into-ExpressionEngine/issues) or contact us at info@designbyfront.com

## License and Attribution ##

This work is licensed under the Creative Commons Attribution-Share Alike 3.0 Unported.
To view a copy of this license, visit [http://creativecommons.org/licenses/by-sa/3.0/](http://creativecommons.org/licenses/by-sa/3.0/)
or send a letter to `Creative Commons, 171 Second Street, Suite 300, San Francisco, California, 94105, USA`

**No Warranty**<br />
As this program is licensed free of charge, there is no warranty for the program, to the extent permitted by applicable law. Except when otherwise stated in writing the copyright holders and/or other parties provide the program "as is" without warranty of any kind, either expressed or implied, including, but not limited to, the implied warranties of merchantability and fitness for a particular purpose. The entire risk as to the quality and performance of the program is with you. should the program prove defective, you assume the cost of all necessary servicing, repair or correction.<br />
In no event unless required by applicable law or agreed to in writing will any copyright holder, or any other party who may modify and/or redistribute the program as permitted above, be liable to you for damages, including any general, special, incidental or consequential damages arising out of the use or inability to use the program (including but not limited to loss of data or data being rendered inaccurate or losses sustained by you or third parties or a failure of the program to operate with any other programs), even if such holder or other party has been advised of the possibility of such damages.

## Created by Front ###

Useful, memorable and satisfying things for the web.<br />
We create amazing online experiences that delight users and help our clients grow.

[Web Design](http://www.designbyfront.com) by Front