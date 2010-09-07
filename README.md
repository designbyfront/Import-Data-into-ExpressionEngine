## Import Data into ExpressionEngine ##
An ExpressionEngine Module that allows easy import of data into ExpressionEngine
Built in import capabilities are CSV files to all base ExpressionEngine fieldtypes and Playa
It is easily extended to allow additional import types and custom fieldtypes (see **Extending**)

## Installation ##

Use the structure provided to place the files within your current EE installation.
Install the module in your EE control panel

## Adding a new data input type ##

Your data may not come in a format supported by this module.
However, you can create your own data input type easily, just by creating a PHP class.

**Step 1:** Create your new input type PHP class:
The class needs to imlepement 'Input_type' and subsequently should provide four methods:
 * `get_headings()`          -> open file, read first row (headings), close file, return flat array of headings
 * `start_reading_rows()`    -> setup open file, return boolean for success
 * `stop_reading_rows()`     -> setup close file, return int of number of lines output
 * `read_row()`              -> read next row from file, return flat array of row data, or false when no more rows (first call will return headings)

**Step 2:** Place your new input type PHP class in the `modules/import_data/files/input_types`

**Step 3:** Include your new input type PHP class at the top of `mcp.import_data.php`

**Step 4:** Include your new input type PHP class at the top of `mcp.import_data.php`
 * Eg. `require_once 'files/input_types/My_new_input.class.php';`

**Step 5:** Add your new data type in each stage file
 * Add the new data type name to the "$input_types" array at the top of stage_one.php
 * Add the new data type name and object instantiation to stage_two.php, stage_three.php and stage_four.php
   This is in the switch statement in the function defined at the top of each.
   Make sure to assign the object to a variable named `$input_file_obj`

## Adding a field type ##

You may want to use field types in your weblogs/sections which are not supported by this module.
However, you can add your own field types easily, just by creating a PHP function.

**Step 1:** Open the field type class - `field_type.class.php`

**Step 2:** Create a function in this class which will output the POST for your field type.
**Remember:**
 * This is an array with the field name 'field_id_X' mapped to the data
 * Check is `$this->value` is `NULL` and use the data in `$this->existing` to default

**Step 3:** Add your field type function string to the `$supported_types` array mapping to the function you have just created.


## Support ##

For more information and support, please use the [issues page](http://github.com/designbyfront/Import-Data into-ExpressionEngine/issues) or contact us at info@designbyfront.com

## Created by Front ###

Useful, memorable and satisfying things for the web
We create amazing online experiences that delight users and help our clients grow.

[Web Design](http://www.designbyfront.com) by Front