<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

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
include('../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');

$page_title = "Special Order :: History";
$header = "Historical Special Orders";
if (isset($_REQUEST['card_no']) && is_numeric($_REQUEST['card_no'])){
	$header = "Past Special Orders for Member #".((int)$_REQUEST['card_no']);
}
include($FANNIE_ROOT.'src/header.html');

$status = array(
	7 => "Completed",
	8 => "Canceled",
	9 => "Inquiry"
);

$assignments = array();
$q = "SELECT superID,super_name FROM MasterSuperDepts
	GROUP BY superID,super_name ORDER BY superID";
$r = $dbc->query($q);
while($w = $dbc->fetch_row($r))
	$assignments[$w[0]] = $w[1];
unset($assignments[0]); 

$suppliers = array('');
$q = "SELECT mixMatch FROM PendingSpecialOrder WHERE trans_type='I'
	GROUP BY mixMatch ORDER BY mixMatch";
$r = $dbc->query($q);
while($w = $dbc->fetch_row($r)){
	$suppliers[] = $w[0];
}

$f1 = (isset($_REQUEST['f1']) && $_REQUEST['f1'] !== '')?(int)$_REQUEST['f1']:'';
$f2 = (isset($_REQUEST['f2']) && $_REQUEST['f2'] !== '')?(int)$_REQUEST['f2']:'';
$f3 = (isset($_REQUEST['f3']) && $_REQUEST['f3'] !== '')?$_REQUEST['f3']:'';

$filterstring = "";
if ($f1 !== ''){
	$filterstring = sprintf("WHERE status_flag=%d",$f1);
}

echo sprintf('<a href="clearinghouse.php%s">Current Orders</a>',
	(isset($_REQUEST['card_no'])?'?card_no='.$_REQUEST['card_no']:'')
);
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
echo "Old Orders";
echo '<p />';

echo "<b>Status</b>: ";
echo '<select id="f_1" onchange="refilter();">';
echo '<option value="">All</option>';
foreach($status as $k=>$v){
	printf("<option %s value=\"%d\">%s</option>",
		($k===$f1?'selected':''),$k,$v);
}
echo '</select>';
echo '&nbsp;&nbsp;&nbsp;&nbsp;';
echo '<b>Buyer</b>: <select id="f_2" onchange="refilter();">';
echo '<option value="">All</option>';
foreach($assignments as $k=>$v){
	printf("<option %s value=\"%d\">%s</option>",
		($k===$f2?'selected':''),$k,$v);
}
echo '</select>';
echo '&nbsp;&nbsp;&nbsp;&nbsp;';
echo '<b>Supplier</b>: <select id="f_3" onchange="refilter();">';
foreach($suppliers as $v){
	printf("<option %s>%s</option>",
		($v===$f3?'selected':''),$v);
}
echo '</select>';
echo '<hr />';

if (isset($_REQUEST['card_no']) && is_numeric($_REQUEST['card_no'])){
	if (empty($filterstring))
		$filterstring .= sprintf("WHERE p.card_no=%d",$_REQUEST['card_no']);
	else
		$filterstring .= sprintf(" AND p.card_no=%d",$_REQUEST['card_no']);
	printf('<input type="hidden" id="cardno" value="%d" />',$_REQUEST['card_no']);
}
$order = isset($_REQUEST['order'])?$_REQUEST['order']:'';
printf('<input type="hidden" id="orderSetting" value="%s" />',$order);
if ($order !== '') $order = base64_decode($order);
else $order = 'min(datetime)';

$q = "SELECT min(datetime) as orderDate,p.order_id,sum(total) as value,
	count(*)-1 as items,status_flag,
	CASE WHEN MAX(p.card_no)=0 THEN MAX(t.last_name) ELSE MAX(c.LastName) END as name,
	MIN(CASE WHEN trans_type='I' THEN charflag ELSE 'ZZZZ' END) as charflag
	FROM CompleteSpecialOrder as p
	LEFT JOIN SpecialOrderStatus as s ON p.order_id=s.order_id
	LEFT JOIN SpecialOrderNotes as n ON n.order_id=p.order_id
	LEFT JOIN custdata AS c ON c.CardNo=p.card_no AND personNum=p.voided
	LEFT JOIN SpecialOrderContact as t on t.card_no=p.order_id
	$filterstring
	GROUP BY p.order_id,status_flag
	HAVING count(*) > 1 OR
	SUM(CASE WHEN notes LIKE '' THEN 0 ELSE 1 END) > 0
	ORDER BY $order";
$r = $dbc->query($q);

$orders = array();
$valid_ids = array();
while($w = $dbc->fetch_row($r)){
	$orders[] = $w;
	$valid_ids[$w['order_id']] = True;
}

if ($f2 !== '' || $f3 !== ''){
	$filter2 = ($f2!==''?sprintf("AND m.superID=%d",$f2):'');
	$filter3 = ($f3!==''?sprintf("AND p.mixMatch=%s",$dbc->escape($f3)):'');
	$q = "SELECT order_id FROM CompleteSpecialOrder AS p
		INNER JOIN MasterSuperDepts AS m ON 
		p.department=m.dept_ID
		WHERE 1=1 $filter2 $filter3
		GROUP BY order_id";
	$r = $dbc->query($q);
	$valid_ids = array();
	while($w = $dbc->fetch_row($r))
		$valid_ids[$w['order_id']] = True;

	if ($f2 !== '' && $f3 === ''){
		$q2 = sprintf("SELECT s.order_id FROM SpecialOrderNotes AS s
				INNER JOIN CompleteSpecialOrder AS c
				ON s.order_id=c.order_id
				WHERE s.superID=%d
				GROUP BY s.order_id",$f2);
		$r2 = $dbc->query($q2);
		while($w2 = $dbc->fetch_row($r2))
			$valid_ids[$w2['order_id']] = True;
	}
}

$ret = '<form id="pdfform" action="tagpdf.php" method="get">';
$ret .= sprintf('<table cellspacing="0" cellpadding="4" border="1">
	<tr>
	<th><a href="" onclick="resort(\'%s\');return false;">Order Date</a></th>
	<th>Order ID</th>
	<th><a href="" onclick="resort(\'%s\');return false;">Name</a></th>
	<th><a href="" onclick="resort(\'%s\');return false;">Value</a></th>
	<th><a href="" onclick="resort(\'%s\');return false;">Items</a></th>
	<th><a href="" onclick="resort(\'%s\');return false;">Status</a></th>
	<th>Arrived</th>',
	base64_encode("min(datetime)"),
	base64_encode("CASE WHEN MAX(p.card_no)=0 THEN MAX(t.last_name) ELSE MAX(c.LastName) END"),
	base64_encode("sum(total)"),
	base64_encode("count(*)-1"),
	base64_encode("status_flag")
);
$ret .= '</tr>';
foreach($orders as $w){
	if (!isset($valid_ids[$w['order_id']])) continue;

	$ret .= sprintf('<tr><td><a href="view.php?orderID=%d">%s</a></td>
		<td>%d</td><td>%s</td><td>%.2f</td>
		<td align=center>%d</td>',$w['order_id'],
		$w['orderDate'],$w['order_id'],
		$w['name'],
		$w['value'],$w['items']);
	$ret .= '<td>'.$status[$w['status_flag']].'</td>';
	$ret .= "<td align=center>".($w['charflag']=='P'?'Yes':'No')."</td>";
	$ret .= "</tr>";
}
$ret .= "</table>";

echo $ret;
?>
<script type="text/javascript">
function refilter(){
	var f1 = $('#f_1').val();
	var f2 = $('#f_2').val();
	var f3 = $('#f_3').val();

	var loc = 'clearinghouse.php?f1='+f1+'&f2='+f2+'&f3='+f3;
	if ($('#cardno').length!=0)
		loc += '&card_no='+$('#cardno').val();
	if ($('#orderSetting').length!=0)
		loc += '&order='+$('#orderSetting').val();
	
	location = loc;
}
function resort(o){
	$('#orderSetting').val(o);
	refilter();
}
function updateStatus(oid,val){
	$.ajax({
	url: 'ajax-calls.php',
	dataType: 'post',
	data: 'action=UpdateStatus&orderID='+oid+'&val='+val,
	cache: false,
	success: function(resp){}
	});
}
function updateSub(oid){
	var val = $('#s_sub').val();
	$.ajax({
	url: 'ajax-calls.php',
	dataType: 'post',
	data: 'action=UpdateSub&orderID='+oid+'&val='+val,
	cache: false,
	success: function(resp){}
	});
}
</script>
<?php
include($FANNIE_ROOT.'src/footer.html');
?>
