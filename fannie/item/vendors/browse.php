<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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

include('../../config.php');
$page_title = "Fannie : Browse Vendor Catalog";
$header = "Browse Vendor Catalog";
include($FANNIE_ROOT.'src/header.html');
include($FANNIE_ROOT.'src/mysql_connect.php');
include('ajax.php');

if (!isset($_REQUEST['vid'])){
	echo "<i>Error: no vendor selected</i>";
	include($FANNIE_ROOT.'src/footer.html');
	return;	
}
$vid = $_REQUEST['vid'];

$cats = "";
$rp = $dbc->query("SELECT deptID,name FROM vendorDepartments
		WHERE vendorID=$vid");
while($rw = $dbc->fetch_row($rp))
	$cats .= "<option value=$rw[0]>$rw[0] $rw[1]</option>";

if ($cats == "") $cats = "<option>All</option>".$cats;
else $cats = "<option value=\"\">Select a department...</option>".$cats;

?>
<script src="<?php echo $FANNIE_URL; ?>src/jquery-1.2.6.min.js"
	type="text/javascript"></script>
<script src="browse.js" type="text/javascript"></script>
<div id="categorydiv">
<select id=categoryselect onchange="catchange();">
<?php echo $cats ?>
</select>
&nbsp;&nbsp;&nbsp;
<select id=brandselect onchange="brandchange();">
<option>Select a department first...</option>
</select>
</div>
<hr />
<div id="contentarea">
<?php if (isset($_REQUEST['did'])){
	showCategoryItems($vid,$_REQUEST['did']);
}
?>
</div>
<?php
echo "<input type=hidden id=vendorID value=$vid />";
echo "<input type=hidden id=urlpath value=\"$FANNIE_URL\" />";
include($FANNIE_ROOT.'src/footer.html');
?>
