<html>
<head><title>Product spotlight</title>
<style type="text/css">
tr.one td { background: #ffffff; }
tr.two td { background: #ffffcc; }
td { width: 5em; text-align: center; }
</style>
</head>
<body>
<form action=index.php method=post>
<b>UPC(s)</b>: <input type=text style="width:300px;" name=upcs
value="<?php echo isset($_REQUEST['upcs'])?$_REQUEST['upcs']:'' ?>" />
<input type=submit value="Get Report" />
</form><hr />
<?php
include('../../../config.php');

if (!isset($_REQUEST['upcs'])) return;

include($FANNIE_ROOT.'src/SQLManager.php');
include('../../db.php');

$upcs = explode(' ',$_REQUEST['upcs']);
$where = 'd.upc IN (';
foreach($upcs as $upc){
	if (is_numeric($upc))
		$where .= "'".str_pad($upc,13,'0',STR_PAD_LEFT)."',";
}
$where = rtrim($where,",").")";

$prods = "SELECT upc,description FROM products as d WHERE $where ORDER BY description";
$res = $sql->query($prods);
echo "<h3>Product(s)</h3>";
while($row = $sql->fetch_row($res)){
	echo "<li>".$row[1]."</li>";
}

$this_month = date("F Y");
$last_month = date("F Y",mktime(0,0,0,date('n')-1,1,date("Y")));

$allSalesQ = "SELECT datepart(dw,tdate),datepart(yy,tdate),datepart(mm,tdate),datepart(dd,tdate),
		sum(case when d.trans_status='M' then itemqtty else quantity end) as qty
		FROM dlog_90_view as d
		WHERE $where
		GROUP BY datepart(yy,tdate),datepart(mm,tdate),datepart(dd,tdate),datepart(dw,tdate)
		ORDER BY datepart(yy,tdate),datepart(mm,tdate),datepart(dd,tdate)";
$allSalesR = $sql->query($allSalesQ);
$data = array();
while($row = $sql->fetch_row($allSalesR)){
	$data[] = array(
	'date' => mktime(0,0,0,$row[2],$row[3],$row[1]),
	'day' => $row[0],
	'sales' => $row[4]
	);
}

echo "<h3>Weekly sales</th>";
echo "<table cellspacing=0 cellpadding=4 border=1>
	<tr><th>Week</th><th>S</th><th>M</th><th>T</th>
	<th>W</t><th>T</th><th>F</th><th>S</th>
	<th>Total</th></tr>";
$week = array();
$sum = 0;
$day = 1;
$date = mktime(0,0,0,date('n'),date('j')-90,date('Y'));
$start = $date;
if (date('N',$date) != 7){
	for($i=0;$i<date('N',$date);$i++) $week[]='&nbsp;';
	$start = mktime(0,0,0,date('n'),date('j')-90-date('N',$date),date('Y'));
	$day = date('N',$date)+1;
}
$c = 1;
for($i=0; $i<count($data); $i++){
	if ($start == 0) $start = $date;
	while($date != $data[$i]['date']){
		$week[] = '&nbsp;';
		$day++;
		if ($day == 8){
			printweek($start,$date,$week,$sum);
			$day = 1;
			$week = array();
			$sum = 0;
			$start = 0;
		}
		$date = mktime(0,0,0,date('n',$date),date('j',$date)+1,date('Y',$date));
		if ($start == 0) $start = $date;
	}
	$week[] = sprintf("%.2f",$data[$i]['sales']);
	$sum += $data[$i]['sales'];
	$day++;	

	if ($day == 8){
		printweek($start,$date,$week,$sum);
		$sum = 0;
		$week = array();
		$day = 1;
		$start = 0;
	}
	$date = mktime(0,0,0,date('n',$date),date('j',$date)+1,date('Y',$date));
}

if (count($week) != 0){
	while($day < 8){
		$day++;
		$week[] = '&nbsp;';
		if ($day < 7)
			$date = mktime(0,0,0,date('n',$date),date('j',$date)+1,date('Y',$date));
	}
	printweek($start,$date,$week,$sum);
}
echo "</table>";

function printweek($start,$end,$week,$sum){
	global $c;
	printf("<tr class=%s><td>%s - %s</td>",
		($c==0)?'one':'two',date('n/j',$start),date('n/j',$end));
	foreach($week as $w) echo "<td>$w</td>";
	printf("<td>%.2f</td></tr>",$sum);
	$c = ($c+1)%2;
}

?>
