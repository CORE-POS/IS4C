<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

    This file is part of Fannie.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
$page_title='Fannie - Order Submitted';
$header='Order Submitted';
include('../src/header.html');

include('../src/mysql_connect.php');

$id = $_REQUEST['uid'];
$ln = isset($_REQUEST['ln'])?$_REQUEST['ln']:'';
$fn = isset($_REQUEST['fn'])?$_REQUEST['fn']:'';
$cn = sprintf("%d",$_REQUEST['cardno']);
if (isset($_REQUEST['fullname'])){
	$q = "SELECT firstname,lastname FROM custdata WHERE cardno=$cn AND personnum=1";
	$r = $dbc->query($q);
	$w = $dbc->fetch_row($r);
	$fn = $w[0];
	$ln = $w[1];
}

/* create user, if needed */
if ($id == "NEW"){
	$createQ = sprintf("INSERT INTO SpecialOrderUser (lastname,firstname,card_no)
		VALUES (%s,%s,%d)",$dbc->escape($ln),$dbc->escape($fn),$cn);
	$createR = $dbc->query($createQ);

	$idQ = sprintf("SELECT MAX(id) FROM SpecialOrderUser WHERE
		lastname=%s AND firstname=%s",$dbc->escape($ln),$dbc->escape($fn));
	$idR = $dbc->query($idQ);
	$id = array_pop($dbc->fetch_row($idR));
}
else {
	$id = sprintf("%d",$id);
}

/* update user's contact info */
$upQ = sprintf("UPDATE SpecialOrderUser SET
		street1=%s,
		street2=%s,
		city=%s,
		state=%s,
		zip=%s,
		phone=%s,
		alt_phone=%s,
		email=%s
		WHERE id=%d",
		$dbc->escape($_REQUEST['street1']),
		$dbc->escape($_REQUEST['street2']),
		$dbc->escape($_REQUEST['city']),
		$dbc->escape($_REQUEST['state']),
		$dbc->escape($_REQUEST['zip']),
		$dbc->escape($_REQUEST['ph']),
		$dbc->escape($_REQUEST['ph2']),
		$dbc->escape($_REQUEST['email']),
		$id);
$upR = $dbc->query($upQ);

$fullname = isset($_REQUEST['fullname'])?$_REQUEST['fullname']:$ln.", ".$fn;

$orderQ = sprintf("INSERT INTO SpecialOrder (order_date,uid,status,fullname,order_info,special_instructions,
		superDept) VALUES (%s,%d,0,%s,%s,%s,%d)",$dbc->now(),$id,$dbc->escape($fullname),
		$dbc->escape($_REQUEST['itemdesc']),$dbc->escape($_REQUEST['notes']),$_REQUEST['super']);
$orderR = $dbc->query($orderQ);

echo "<p>Your order has been submitted.</p>";

echo "<a href=view.php>View Open Orders</a>";

include('../src/footer.html');
?>

