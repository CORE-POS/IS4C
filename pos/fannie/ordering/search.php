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
$page_title='Fannie - Search Special Orders';
$header='Search Special Orders';
include('../src/header.html');

include('../src/mysql_connect.php');
?>
<script type="text/javascript">
$(document).ready(function(){
	$('#memT').click(function(){
		$('#memR').attr('checked',true);
	});

	$('#nameT').click(function(){
		$('#nameR').attr('checked',true);
	});
});
</script>
<form action=search.php method=get>
<table cellspacing=0 cellpadding=4 border=0>
<tr>
	<td><input type=radio name=stype id=memR value=mem /></td>
	<th>By Member #</th>
	<td><input type=text name=memNum id=memT /></td>
</tr>
<tr>
	<td><input type=radio name=stype id=nameR value=name /></td>
	<th>By Last Name</th>
	<td><input type=text name=name id=nameT /></td>
</tr>
<tr>
	<td><input type="submit" name="submit" value="Search" /></td>
</tr>
</table>
</form>
<hr />
<?php

if (isset($_REQUEST['submit'])){

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

	$q = "SELECT order_id,order_date,uid,fullname,superDept,status
		FROM SpecialOrder AS s LEFT JOIN SpecialOrderUser AS u
		ON s.uid=u.id WHERE 1=0 ";
	if (!empty($_REQUEST['memNum']))
		$q .= sprintf(" OR (u.card_no=%d) ",$_REQUEST['memNum']);
	if(!empty($_REQUEST['name'])){
		$q .= sprintf("OR (u.lastname like %s or s.fullname like %s) ",
			$dbc->escape('%'.$_REQUEST['name'].'%'),
			$dbc->escape('%'.$_REQUEST['name'].'%'));
	}
	$q .= " ORDER BY fullname,order_date";

	echo "<table cellspacing=0 cellpadding=4 border=1>";
	echo "<tr><th>Date</th><th>Name</th><th>Dept</th></tr>";
	$r = $dbc->query($q);
	while($w = $dbc->fetch_row($r)){
		printf("<tr><td><a href=order.php?oid=%d&uid=%d>%s</a></td>
			<td>%s</td><td>%s</td></tr>",
			$w['order_id'],$w['uid'],$w['order_date'],
			$w['fullname'],$depts[$w['superDept']]);
	}
	echo "</table>";
}

include('../src/footer.html');
?>

