# Import Data into ExpressionEngine #
An ExpressionEngine Module that allows easy import of data into ExpressionEngine

Please also see [the advanced usage guide](http://github.com/designbyfront/Import-Data-into-ExpressionEngine/blob/master/README-advanced.md).

<a name="contents"></a>
## Contents ##
* [Introduction](#introduction)
* [Supported Features](#supportedfeatures)
* [Installation](#installation)
* [Walkthough](#walkthrough)
* [To Do List](#todolist)
* [Troubleshooting](#troubleshooting)




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





<a name="supportedfeatures"></a>
## Supported Features ##
-- [Back to top](#contents)

### Supported Input Types ###
* CSV file

Additional input types can be easily added. Please see [the advanced usage guide](http://github.com/designbyfront/Import-Data-into-ExpressionEngine/blob/master/README-advanced.md#addinginputtype).

### Supported EE Fields ###
* Title [provide string title]
* Category [provide string category]
* Entry Date [provide timestamp or string which can be parsed by `strtotime`]
* Author [provide string username]
* Status [provide string status]

### Supported Custom Field Types ###
* Text input [text]
* Textarea [textarea]
* Drop-down list [select]
* Date field [date]
* Relationship [rel]
* [Playa](http://pixelandtonic.com/playa) [playa]
* [FF Checkbox](http://pixelandtonic.com/fieldframe/docs/ff-checkbox) [ff_checkbox]
* [Wygwam](http://pixelandtonic.com/wygwam) [wygwam]

Additional field types can be easily added. Please see [the advanced usage guide](http://github.com/designbyfront/Import-Data-into-ExpressionEngine/blob/master/README-advanced.md#addingfieldtype).

### Supported Extensions ###
* [Gypsy](http://brandon-kelly.com/gypsy)
* [FieldFrame](http://pixelandtonic.com/fieldframe)
* [Multiple Site Manager](http://expressionengine.com/downloads/details/multiple_site_manager/) (MSM)





<a name="installation"></a>
## Installation ##
-- [Back to top](#contents)

* The directory structure inside the `EE1` directory mimics that of a standard ExpressionEngine 1 installation. Use this to place the files in the correct place.<br />
It will contain:
    * 1 Extension
        * 1 extension file
    * 1 Module
        * 1 English language file
        * 2 module files (MCP, MOD)
        * 10 supporting PHP files
* Install the extension
* Install the Module

__Both__ the extension and module must be installed for the import to work successfully!




<a name="walkthrough"></a>
## Walkthrough ##
-- [Back to top](#contents)

The module will guide you through three setup stages and the submission stage. To access the module, click the "Import Data" link on your modules page.

### Stage 1 ###
In this stage, you are defining where the data is being published and what type it is.

* __Site__ and __Section__ - Select the site and section/weblog that you wish to import into
* __Input File__ - Browse for the input file you wish to upload as the data source
* "_Relationships with existing entries?_" tickbox should be ticked if the data you are inputting is linked / has a relationship to a weblog or data _already_ in ExpressionEngine.
For example, ExpressionEngine Relationship field and Playa
* __Type__ - Select the type of input in the data file [See the "[Supported Features](#supportedfeatures)" section for currently supported types]

![Import - stage one](http://devot-ee.com/images/sized/images/uploads/addons/stage_one-480x266.jpg "Import - stage one")<br />
Images from [Devot:ee listing](http://devot-ee.com/add-ons/import-data-into-expressionengine/)

<br />
### Stage 2 ###
In this stage your data file will be uploaded and you will define the existing relationship (if "_Relationships with existing entries?_" ticked) or review stage one data.

* __Input data field__ - Select the column in the input file which has a relationship.
* __Section__ - Select the section / weblog which the _Input data field_ has a relationship with.
* __Field__ - Select the field which the _Input data field_ has a relationship with.

![Import - stage two](http://devot-ee.com/images/sized/images/uploads/addons/stage_two-480x456.jpg "Import - stage two")<br />
Images from [Devot:ee listing](http://devot-ee.com/add-ons/import-data-into-expressionengine/)

<br />
### Stage 3 ###
In this stage, you now map the fields of your chosen section / weblog to the columns of the input data file.<br />
Simply use the lists to choose the column which corresponds to each field.
For the EE  "_Categories_" field, a multiselect is provided so you can choose more than one column, ie. multiple categories.

An asterisk beside a field means that the entry will not be published if there is no data given for this field.

The "_Unique?_" checkbox allows you to specify that the data in this field is unique to that entry, ie. a unique identifier.
This is used when updating existing entries or submitting multidata (see examples next).

![Import - stage three](http://devot-ee.com/images/sized/images/uploads/addons/stage_three-480x424.jpg "Import - stage three")<br />
Images from [Devot:ee listing](http://devot-ee.com/add-ons/import-data-into-expressionengine/)

Using "_Unique?_", will cause the module to look for an existing entry to update, based on the unique field value, rather than create a new entry.<br />
For example, an item could be imported with CSV file format, where `Item Code` is unique:
<pre>
Item Name, Item Description, Item Code, Item Price
</pre>
and data:
<pre>
Item 1, Item 1 description, 001, 10
Item 2, Item 2 description, 002, 15
Item 3, Item 3 description, 003, 20
</pre>
and then the price updated later with:
<pre>
001, 20
002, 30
003, 40
</pre>

This can also be used to update fields which can hold multiple values, eg. Categories, Playa<br />
For example, an item could be imported with CSV file format, where `Item Code` is unique:
<pre>
Item Name, Item Code, Item Category
</pre>
and data:
<pre>
Item 1, 001, Cat1
Item 1, 002, Cat2
Item 1, 001, Cat3
</pre>
which will have one entry in three categories. The `Item Name` data on lines 2 and 3 is redundant and could be removed (as the module will use the existing title).

<br />
### Stage 4 ###
In this stage, you can review errors, warnings and a list of the rows submitted. For more details on errors and warnings, please consult the [Errors and Warnings list in Toubleshooting](#troubleshooting).<br />
The list of rows submitted shows the `title` field of each row submitted from the input file and and a success or failure notice.

![Import - stage four](http://devot-ee.com/images/sized/images/uploads/addons/stage_four-480x142.jpg "Import - stage four")<br />
Images from [Devot:ee listing](http://devot-ee.com/add-ons/import-data-into-expressionengine/)







<a name="todolist"></a>
## To Do List ##
-- [Back to top](#contents)

This is the short version of the todo list. This will take a while...

* Mulidata custom fields (Playa)
* Delimited data for mulitdata fields (Categories, Playa)
* More end user warnings / notifications
* Upload file field support
* Better code structure
    * <del>Make it easier to add new input types</del>
* Select previously uploaded file
* Extend to EE2




<a name="troubleshooting"></a>
## Troubleshooting ##
-- [Back to top](#contents)

* __Errors and Warnings List__<br />

__Errors__ can occur for the import as a whole (which will stop the import), or on a per row basis (which stop the row import, but continue the overall import).

_Import Errors_ -

* Unknown input type provided
* Invalid input_type object implemented
* Unable to read input file
* Unimplemented fieldtype which is required

_Row Errors_ -

* Row does not contain the correct number of columns to correspond to the fields selected
* Empty data given for 'title' field
* Empty data given for 'author' field
* Selected 'author' does not exist
* Selected 'author' does not have permission to publish or edit this weblog/section
* Selected 'status' is not in status group assigned to weblog/section
* Empty data given for required field

__Warnings__ are significant events which occur during import. The import will continue unhindered, but side-effects may occur.

* Unimplemented fieldtype which is not required
    * In this instance, the field is not required so import can continue. However, data given to import into this field will be ignored as the field type is unknown.

<hr />

* __Categories are not being imported successfully__<br />
Are you using the "_SC Category Select_" fieldtype? This fieldtype will cause the standard category submission to return empty, and so they will not be imported. Disable this fieldtype, import the data and then enable after.<br />
[Thanks to [Paul Bellamy](http://github.com/bellamystudio) for this tip]

<hr />

* __Are you having trouble?__
Please use the [issues page](http://github.com/designbyfront/Import-Data into-ExpressionEngine/issues) or contact us at [info@designbyfront.com](info@designbyfront.com).