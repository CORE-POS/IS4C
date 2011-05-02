<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'item/pricePerOunce.php');

$upc = $_REQUEST['upc'];
switch($_REQUEST['action']){
case 'addVarPricing':
	$dbc->query(sprintf("UPDATE prodExtra SET variable_pricing=1 WHERE upc=%s",$dbc->escape($upc)));
	break;
case 'delVarPricing':
	$dbc->query(sprintf("UPDATE prodExtra SET variable_pricing=0 WHERE upc=%s",$dbc->escape($upc)));
	break;
case 'newPrice':
	$vid = $_REQUEST['vendorID'];
	$bid = $_REQUEST['batchID'];
	$sid = $_REQUEST['superID'];
	$price = $_REQUEST['price'];
	$dbc->query(sprintf("UPDATE vendorSRPs SET srp=%f WHERE upc=%s AND vendorID=%d",
		$price,$dbc->escape($upc),$vid));
	$dbc->query(sprintf("UPDATE batchList SET salePrice=%f WHERE upc=%s AND batchID=%d",
		$price,$dbc->escape($upc),$bid));
	$dbc->query(sprintf("UPDATE shelftags SET normal_price=%f WHERE upc=%s AND id=%d",
		$price,$dbc->escape($upc),$sid));
	echo "Here";
	break;
case 'batchAdd':
	$vid = $_REQUEST['vendorID'];
	$bid = $_REQUEST['batchID'];
	$sid = $_REQUEST['superID'];
	$price = $_REQUEST['price'];
	if ($sid == 99) $sid = 0;

	/* add to batch */
	$batchQ = sprintf("INSERT INTO batchList (upc,batchID,salePrice,active)
		VALUES (%s,%d,%f,0)",$dbc->escape($upc),$bid,$price);
	$batchR = $dbc->query($batchQ);

	/* get shelftag info */
	$infoQ = sprintf("SELECT p.description,v.brand,v.sku,v.size,v.units,b.vendorName
		FROM products AS p LEFT JOIN vendorItems AS v ON p.upc=v.upc AND
		v.vendorID=%d LEFT JOIN vendors AS b ON v.vendorID=b.vendorID
		WHERE p.upc=%s",$vid,$dbc->escape($upc));
	$info = $dbc->fetch_row($dbc->query($infoQ));
	$ppo = pricePerOunce($price,$info['size']);
	
	/* create a shelftag */
	$stQ = sprintf("DELETE FROM shelftags WHERE upc=%s AND id=%d",
		$dbc->escape($upc),$sid);
	$stR = $dbc->query($stQ);
	$addQ = sprintf("INSERT INTO shelftags VALUES (%d,%s,%s,%f,%s,%s,%s,%d,%s,%s)",
		$sid,$dbc->escape($upc),$dbc->escape($info['description']),$price,
		$dbc->escape($info['brand']),$dbc->escape($info['sku']),
		$dbc->escape($info['size']),$info['units'],$dbc->escape($info['vendorName']),
		$dbc->escape($ppo));
	$addR = $dbc->query($addQ);

	break;
case 'batchDel':
	$vid = $_REQUEST['vendorID'];
	$bid = $_REQUEST['batchID'];
	$sid = $_REQUEST['superID'];
	if ($sid == 99) $sid = 0;

	$batchQ = sprintf("DELETE FROM batchList WHERE batchID=%d AND upc=%s",
		$bid,$dbc->escape($upc));
	$batchR = $dbc->query($batchQ);

	$stQ = sprintf("DELETE FROM shelftags WHERE upc=%s AND id=%d",
		$dbc->escape($upc),$sid);
	$stR = $dbc->query($stQ);

	break;
}

?>
