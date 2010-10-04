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
$page_title='Fannie - View Special Order';
$header='Viewing Special Order';
include('../src/header.html');

include('../src/mysql_connect.php');

$statuses = array(
	0=>'Open',
	10=>'Closed'
);

$depts = array(
	'4'=>'Grocery',
	'2'=>'Cool/Frozen',
	'1'=>'Bulk',
	'5'=>'HBC',
	'8'=>'Meat',
	'9'=>'Gen Merch',
	'3'=>'Deli',
	'6'=>'Produce',
	'0'=>'Unsure'
);

$oid = $_REQUEST['oid'];

$orderQ = sprintf("SELECT order_date,uid,status,fullname,superDept,order_info,special_instructions
	FROM SpecialOrder WHERE order_id=%d",$oid);
$orderR = $dbc->query($orderQ);
$order = $dbc->fetch_row($orderR);

echo "<fieldset><legend>Order Information</legend>";
echo "<table cellpadding=4 cellspacing=0 border=1>";
echo "<tr>";
echo "<th>Date</th><td>".$order['order_date']."</td>";
echo "<th>Status</th><td><select name=status>";
foreach($statuses as $k=>$v){
	printf("<option %s value=%d>%s</option>",
		($k==$order['status']?'selected':''),
		$k,$v);
}
echo "</select></td>";
echo "<th>Dept</th><td><select name=dept>";
foreach($depts as $k=>$v){
	printf("<option %s value=%d>%s</option>",
		($k==$order['superDept']?'selected':''),
		$k,$v);
}
echo "</select></td></tr>";
echo "</table>";
echo "<hr />";
echo "<b>Item to Order</b><br />";
echo "<textarea name=itemdesc rows=3 cols=35>";
echo str_replace("\n","<br />",$order['order_info']);
echo "</textarea>";
echo "<hr />";
echo "<b>Special Instructions / Notes</b><br />";
echo "<textarea name=notes rows=3 cols=35>";
echo str_replace("\n","<br />",$order['special_instructions']);
echo "</textarea>";
echo "</fieldset>";

echo "<fieldset><legend>Contact Information</legend>";
$q = "SELECT s.card_no,c.lastname,c.firstname,
	s.street1,s.street2,s.city,s.state,s.zip,
	s.phone,s.alt_phone,s.email,c.type
	FROM SpecialOrderUser AS s LEFT JOIN
	custdata AS c ON c.cardno=s.card_no AND
	c.personNum=1 WHERE s.id=".$order['uid'];
$r = $dbc->query($q);
$addr = $dbc->fetch_row($r);
if ($addr['card_no'] != 0){
	echo "<p>";
	echo "<b>Member #</b>: ".$addr['card_no']."<br />";
	echo "<b>Primary Account Holder</b>: ".$addr['firstname']." ".$addr['lastname']."<br />";
	$types = array('REG'=>'Non Member','PC'=>'Member','INACT'=>'Inactive',
		'INACT2'=>'Term Pending','TERM'=>'Terminated');
	echo "<b>Current Status</b>: ".$types[$addr['type']]."</p>";
}

echo "<p>";
echo "<b>Mailing Address</b>:<br />";
echo $addr['street1']."<br />";
if (!empty($addr['street2']))
	echo $addr['street2']."<br />";
printf("%s, %s %s</p>",$addr['city'],$addr['state'],$addr['zip']);

echo "<p>";
echo "<b>Primary Phone</b>: ".$addr['phone']."<br />";
if (!empty($addr['alt_phone']))
	echo "<b>Alt. Phone</b>: ".$addr['alt_phone']."<br />";
echo "<b>Email</b>: ".$addr['email'];
echo "</p>";

echo "</fieldset>";

include('../src/footer.html');
?>

