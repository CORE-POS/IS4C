<?php
include('../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');

$page_title = "Special Order :: Mangement";
$header = "Manage Special Orders";
include($FANNIE_ROOT.'src/header.html');

$status = array(
	0 => "New",
	1 => "Assigned",
	2 => "Ordered",
	3 => "Arrived"
);

$assignments = array();
$q = "SELECT superID,super_name FROM MasterSuperDepts
	GROUP BY superID,super_name ORDER BY superID";
$r = $dbc->query($q);
while($w = $dbc->fetch_row($r))
	$assignments[$w[0]] = $w[1];
$assignments[0] = "No One";

$f1 = (isset($_REQUEST['f1']) && $_REQUEST['f1'] !== '')?(int)$_REQUEST['f1']:'';
$f2 = (isset($_REQUEST['f2']) && $_REQUEST['f2'] !== '')?(int)$_REQUEST['f2']:'';

$filterstring = "";
if ($f1 !== '' && $f2 !== ''){
	$filterstring = sprintf("WHERE status_flag=%d AND sub_status=%d",
		$f1,$f2);
}
else if ($f1 !== ''){
	$filterstring = sprintf("WHERE status_flag=%d",$f1);
}
else if ($f2 !== ''){
	$filterstring = sprintf("WHERE sub_status=%d",$f2);
}

echo "<b>Filters</b>: ";
echo '<select id="f_1" onchange="refilter();">';
echo '<option value="">All</option>';
foreach($status as $k=>$v){
	printf("<option %s value=\"%d\">%s</option>",
		($k===$f1?'selected':''),$k,$v);
}
echo '</select>';
echo '&nbsp;&nbsp;&nbsp;&nbsp;';
echo '<select id="f_2" onchange="refilter();">';
echo '<option value="">All</option>';
foreach($assignments as $k=>$v){
	printf("<option %s value=\"%d\">%s</option>",
		($k===$f2?'selected':''),$k,$v);
}
echo '</select>';
echo '<hr />';

$q = "SELECT min(datetime) as orderDate,p.order_id,sum(total) as value,
	count(*)-1 as items,status_flag,sub_status FROM PendingSpecialOrder as p
	LEFT JOIN SpecialOrderStatus as s ON p.order_id=s.order_id
	$filterstring
	GROUP BY p.order_id,status_flag,sub_status
	ORDER BY min(datetime)";
$r = $dbc->query($q);
$ret = '<table cellspacing="0" cellpadding="4" border="1">
	<tr><th>Order Date</th><th>Order ID</th><th>Value</th>
	<th>Items</th><th>Status</th><th>Assigned To</th></tr>';
while($w = $dbc->fetch_row($r)){
	$ret .= sprintf('<tr><td><a href="view.php?orderID=%d">%s</a></td>
		<td>%d</td><td>%.2f</td>
		<td>%d</td>',$w['order_id'],
		$w['orderDate'],$w['order_id'],
		$w['value'],$w['items']);
	$ret .= '<td><select id="s_status" onchange="updateStatus('.$w['order_id'].');">';
	foreach($status as $k=>$v){
		$ret .= sprintf('<option %s value="%d">%s</option>',
			($w['status_flag']==$k?'selected':''),
			$k,$v);
	}
	$ret .= "</select></td>";
	$ret .= '<td><select id="s_sub" onchange="updateSub('.$w['order_id'].');">';
	foreach($assignments as $k=>$v){
		$ret .= sprintf('<option %s value="%d">%s</option>',
			($w['sub_status']==$k?'selected':''),
			$k,$v);
	}
	$ret .= "</select></td></tr>";
}
$ret .= "</table>";

echo $ret;
?>
<script type="text/javascript">
function refilter(){
	var f1 = $('#f_1').val();
	var f2 = $('#f_2').val();
	
	location = 'clearinghouse.php?f1='+f1+'&f2='+f2;
}
function updateStatus(oid){
	var val = $('#s_status').val();
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
