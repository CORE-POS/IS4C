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

include('prodFunction.php');
include('../config.php');
include('../src/mysql_connect.php');
$page_title = 'Fannie - Item Maintenance';
$header = 'Item Maintenance';
include('../src/header.html');

?>
<script type="text/javascript" 
	src="<?php echo $FANNIE_URL; ?>src/jquery-1.2.6.min.js">
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
echo "<form action=../item/itemMaint.php method=post>";
if ( isset($FANNIE_COOP_ID) && $FANNIE_COOP_ID == "WEFC_Toronto" ) {
echo "<p>Please use the <a href='itemMaint_WEFC_Toronto.php'>WEFC Toronto Editor</a></p>";
} else {
echo "<input name=upc type=text id=upc> Enter 
<select name=\"ntype\">
<option>UPC</option>
<option>SKU</option>
<option>Brand Prefix</option>
</select> or product name here<br>";

echo "<input name=submit type=submit value=submit> ";
}
echo "</form>";
}

include ('../src/footer.html');

?>
