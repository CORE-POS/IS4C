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

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	* 21Mar2013 EL Assign all fields on update, not just price, ppu. OK per AT.
	*               This routine will be replaced by Andy's of March 18 soon.

*/

require('../config.php');
require_once($FANNIE_ROOT.'src/mysql_connect.php');

$id = 0;
$upc = $dbc->escape($_REQUEST['upc']);
$description = $dbc->escape($_REQUEST['description']);
$brand = $dbc->escape($_REQUEST['brand']);
$units = $_REQUEST['units'];
if ( $units == '' )
	$units = 'NULL';
$size = $dbc->escape($_REQUEST['size']);
$ppo = $dbc->escape($_REQUEST['ppo']);
$vendor = $dbc->escape($_REQUEST['vendor']);
$sku = $dbc->escape($_REQUEST['sku']);
$price = $_REQUEST['price'];
$id = $_REQUEST['subID'];

$checkUPCQ = "SELECT * FROM shelftags where upc = $upc and id=$id";
$checkUPCR = $dbc->query($checkUPCQ);
$checkUPCN = $dbc->num_rows($checkUPCR);

$insQ = "";
if($checkUPCN == 0){
   $insQ = "INSERT INTO shelftags VALUES($id,$upc,$description,$price,$brand,$sku,$size,$units,$vendor,$ppo)";
}else{
   $insQ = "UPDATE shelftags SET
	 description = $description,
	 normal_price = $price,
	 brand = $brand,
	 sku = $sku,
	 size = $size,
	 units = $units,
	 vendor = $vendor,
	 pricePerUnit=$ppo
	 WHERE upc = $upc AND id=$id";
   //$insQ = "UPDATE shelftags SET normal_price = $price, pricePerUnit='$ppo' WHERE upc = '$upc' and id=$id";
}

$insR = $dbc->query($insQ);
if ( $insR == False ) {
echo "<html>
<head>
</head>
<body>
<p>Failed:<br />
$insQ
</p>
</body>
</html>";
}
else {
echo "
<html>
<head>
<script type='text/javascript'>
window.close();
</script>
</head>
</html>";
}

?>
<!--
<html>
<head>
<script type='text/javascript'>
window.close();
</script>
</head>
</html>
-->
