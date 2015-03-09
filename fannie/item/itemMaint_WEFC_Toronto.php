<?php
/*******************************************************************************

    Copyright 2005,2009 Whole Foods Community Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
// Another trivial change for git test.
/* --FUNCTIONALITY- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 * Modes:
 * + If called directly: Prompt for upc to edit or create.
 * + If called from here or another script: call editor.
*/
/* --FUNCTIONALITY- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 * These lines grepped in from this listing: !!grep "^/. [0-9]*\." %
*/

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 * 4Jul13   Require admin priv.
 * Changes to the item edit suite:
 * + The changes were initially to support part of our ordering apparatus which
 *    involves:
 *    . import of data from a spreadsheet, which consolidates data from orders
 *      where some same-PLU'd items come from different suppliers.
 *    . editing of product-related tables between loads
 *    . export of data to the ordering system
 * + It also supports the longer description field in productUser and has the
 *    option to compose it for the PoS Product Verification function.
 * + I learned, stumbled into, functionality that suggests how other coops work
 *    with item data and realize now that some of what I added or changed may
 *    not be viewed by others as progress.
 *    I tried to make some features more obvious in hopes they might be used
 *     more at my coop.
 * + I didn't touch the scale items code, which we don't use.
 * + I wasn't able to test the multi-store code because we have only one.
 * -> I didn't change the likecodes handling but haven't tested it.
 * + Others may not want this editor to touch vendorItems, preferring it to
 *    come from vendors, but we do not get machine-readable data from vendors.
 * + Edit tables productUser, vendorItems and a table of per-coop product data.
 * + In edit form, group some inputs into blocks that can be displayed
 *    optionally, controlled by a module-name-like value.
 * + The fieldsets all float and have been made a regular size so they will
 *    will rearrange themselves sort of neatly, a la the membership editor does.
 * $Fannie_Item_Modules = array("Operations","ExtraInfo",
 * + Extend edit to products.size, .unitofmeasure, vendorItems.sku
 * + "Module" blocks for Sale and Multiples (3-for-$1) values.
 * -> JS Calendars for date fields.
 * + Use "module" setting to control whether exisiting multiples code that is
 *    an alternatice to the regular Price is enabled.
 * + The module-control array is not yet part of config.php or an install page,
 *    in part pending seeing how Andy did his modularization of the editor.
 * + Compose, optionally, products.descriptin and productUser.description with
 *    "package" description "200 g" appended.
 * + JS counter for length of products.description, with or without appended
 *    pacakge info.
 * + Regularize some variable names betwee updateItems, insertItem and deleteItem
 * + Use showAsMoney() and saveAsMoney() to prepare money values for display and writing to table.
 *    saveAsMoney() inserts decimals if the value in the form lacks them.
 * + Initial item# prompt extended to make options more obvious and made into a
 *    function that is used throughout.
 * + Extend lane updates to multiple lane-side tables.
*/

require(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}
$dbc = FannieDB::get($FANNIE_OP_DB);
include('prodFunction_WEFC_Toronto.php');
if ( !function_exists('validateUserQuiet') )
    require($FANNIE_ROOT.'auth/login.php');

if ( !validateUserQuiet('admin') ) {
    $redirect = $_SERVER['REQUEST_URI'];
    $url = $FANNIE_URL.'auth/ui/loginform.php';
    header('Location: '.$url.'?redirect='.$redirect);
}
$page_title = 'Fannie - Item Maintanence WEFC_Toronto';
$header = 'Item Maintanence WEFC_Toronto';
include('../src/header.html');

?>
<script type="text/javascript" 
    src="<?php echo $FANNIE_URL; ?>src/javascript/jquery.js">
</script>
<script type"text/javascript" src=ajax.js></script>
<script type="text/javascript">

<!-- This script and many more are available free online at -->
<!-- The JavaScript Source!! http://javascript.internet.com -->
<!-- John Munn  (jrmunn@home.com) -->

 function putFocus(formInst, elementInst) {
  if (document.forms.length > 0) {
   document.forms[formInst].elements[elementInst].focus();
  }
 }

 function setPrice(p){
    document.getElementById('price').value = p;
    scroll(0,0);
 }

<!-- The second number in the "onLoad" command in the body
// tag determines the form's focus. Counting starts with '0'
//  End -->
</script>

<script type"text/javascript" src=wordCount.js></script>

<?php

if(isset($_POST['submit'])){
    $upc = $_POST['upc'];
 
    itemParse($upc);

}elseif(isset($_GET['upc'])){
    $upc = $_GET['upc'];
    itemParse($upc);

}else{

echo "<head><title>Edit Item</title></head>";
echo "<BODY onLoad='putFocus(0,0);'>";
echo "<form action=../item/itemMaint_WEFC_Toronto.php method=post>";
/* Original
echo "<input name=upc type=text id=upc> Enter 
<select name=\"ntype\">
<option>UPC</option>
<option>SKU</option>
<option>Brand Prefix</option>
</select> or product name here<br>";
//EL echo "To add a product enter its UPC or PLU<br>";
echo "<input name=submit type=submit value=submit> ";
*/

echo promptForUPC();

/* 22Feb13 EL Formatting woodshed.
echo "Enter the code for, or words from the description of, an existing product:";
echo "<br />";
//echo "Enter ";
echo "<input name=upc type=text id=upc>";
echo " <input name=submit type=submit value=Go> ";
echo " is a ";
echo "<select name=\"ntype\">
    <option value='UPC'>UPC or PLU</option>
    <option>SKU</option>
    <option>Brand Prefix</option>
</select>";
//echo " or product name here: ";
echo "<br />";
echo "To add a product enter its UPC or PLU or Vendor SKU.";
if ( !empty($upc) )
    echo "<br /> &nbsp; <a href='itemMaint.php?upc=$upc'>Edit $upc again</a>";
echo "<br />";
*/

echo "</form>";
}

include ('../src/footer.html');

?>
