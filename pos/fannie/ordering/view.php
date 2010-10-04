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
$page_title='Fannie - View Special Orders';
$header='View Special Orders';
include('../src/header.html');

include('../src/mysql_connect.php');
?>
<script type="text/javascript">
$(document).ready(function(){
	$('#st_select').change(refilter);
	$('#dept_select').change(refilter);
});
function refilter(){
	var st = $('#st_select').val();
	var dept = $('#dept_select').val();
	var loc = 'view.php?status='+st;
	if (dept != '')
		loc += '&dept='+dept;
	location = loc;
}
</script>
<?php

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

$st = isset($_REQUEST['status'])?$_REQUEST['status']:0;

$q = sprintf("SELECT order_id,order_date,uid,fullname,superDept
	FROM SpecialOrder WHERE status=%d",$st);
if (isset($_REQUEST['dept']) && !empty($_REQUEST['dept'])){
	$q .= sprintf(" AND superDept=%d",$_REQUEST['dept']);
}
$q .= " ORDER BY order_date";

echo "<select id=st_select>";
foreach($statuses as $k=>$v){
	printf("<option %s value=%d>%s</option>",
		($st==$k?'selected':''),$k,$v);
}
echo "</select>";

echo "&nbsp;&nbsp;&nbsp;";

echo "<select id=dept_select>";
echo "<option value=\"\">Any</option>";
foreach($depts as $k=>$v){
	printf("<option %s value=%d>%s</option>",
		(isset($_REQUEST['dept']) && $_REQUEST['dept']==$k?'selected':''),
		$k,$v);
}
echo "</select>";

echo "<hr />";

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

include('../src/footer.html');
?>

