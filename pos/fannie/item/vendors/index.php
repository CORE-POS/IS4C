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
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include('../../config.php');
$page_title = "Fannie : Manage Vendors";
$header = "Manage Vendors";
include($FANNIE_ROOT.'src/header.html');
include($FANNIE_ROOT.'src/mysql_connect.php');
include('ajax.php');
$vendors = "<option value=\"\">Select a vendor...</option>";
$vendors .= "<option value=\"new\">New vendor...</option>";
$rp = $dbc->query("SELECT * FROM vendors ORDER BY vendorName");
while($rw = $dbc->fetch_row($rp)){
	if (isset($_REQUEST['vid']) && $_REQUEST['vid']==$rw[0])
		$vendors .= "<option selected value=$rw[0]>$rw[1]</option>";
	else
		$vendors .= "<option value=$rw[0]>$rw[1]</option>";
}
?>
<script src="<?php echo $FANNIE_URL; ?>src/jquery-1.2.6.min.js"
	type="text/javascript"></script>
<script src="index.js" type="text/javascript"></script>
<div id="vendorarea">
<select onchange="vendorchange();" id=vendorselect>
<?php echo $vendors; ?>
</select>
</div>
<hr />
<div id="contentarea">
</div>
<?php
echo "<script type=\"text/javascript\">vendorchange();</script>";
include($FANNIE_ROOT.'src/footer.html');
?>
