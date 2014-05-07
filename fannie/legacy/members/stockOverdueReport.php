<?php
include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');

//include($FANNIE_ROOT.'src/functions.php');
if (isset($_GET['excel'])){
	header('Content-Type: application/ms-excel');
	header('Content-Disposition: attachment; filename="stockOverdueReport.xls"');
}
$ALIGN_RIGHT = 1;
$ALIGN_LEFT = 2;
$ALIGN_CENTER = 4;
$TYPE_MONEY = 8;

if (!isset($_GET['excel'])){
?>

<HTML>
<head>
<style type=text/css>
td {
	font-size: .9em;
}
td.left {
	padding-right: 4em;
	text-align: left;
}
td.right {
	padding-left: 4em;
	text-align: right;
}
td.center {
	padding-left: 2em;
	padding-right: 2em;
	text-align: center;
}
</style>
</head>
<BODY BGCOLOR = 'ffffff' ><font size=2> 
<?php
}

if (!isset($_GET['excel']))
	echo "<br /><a href=stockOverdueReport.php?excel=yes>Click here for Excel version</a>";


$balanceQ = "SELECT s.memnum,s.payments,s.enddate,b.balance,
		c.lastname,c.firstname,m.street,
		m.city,m.state,m.zip
		FROM equity_live_balance as s left join
		custdata as c on s.memnum=c.cardno left join
		ar_live_balance as b on s.memnum=b.card_no
		left join meminfo as m on s.memnum=m.card_no
		WHERE c.personnum = 1 and c.type <> 'TERM'
		and s.payments < 100 and
		".$sql->datediff('s.enddate',$sql->now())." < -60
		order by s.memnum";
$balanceR = $sql->query($balanceQ);
$balances = array();
while ($row = $sql->fetch_row($balanceR)){
	$temp = explode(" ",$row[2]);
	$datestr = $temp[0];
	$balances["$row[0]"] = array($row[4].", ".$row[5],str_replace("\n"," ",$row[6]),$row[7],$row[8],$row[9],$row[1],$datestr,$row[3]);
} 

echo tablify($balances,array(0,1,2,3,4,5,6,7,8),array("Account","Name","Address","City","State","Zip",
	     "Current Stock Balance","Stock due date","Current AR Balance"),
	     array($ALIGN_LEFT,$ALIGN_LEFT,$ALIGN_LEFT,$ALIGN_LEFT,$ALIGN_LEFT,$ALIGN_LEFT,
	     $ALIGN_RIGHT|$TYPE_MONEY,$ALIGN_LEFT,$ALIGN_RIGHT|$TYPE_MONEY));


function tablify($data,$col_order,$col_headers,$formatting,$sum_col=-1){
	$sum = 0;
	$ret = "";
	
	$ret .= "<table cellspacing=0 cellpadding=4 border=1><tr>";
	$i = 0;
	foreach ($col_headers as $c){
		while ($formatting[$i] == 0) $i++;
		$ret .= cellify("<u>".$c."</u>",$formatting[$i++]&7);
	}
	$ret .= "</tr>";

	foreach(array_keys($data) as $k){
		$ret .= "<tr>";
		foreach($col_order as $c){
			if($c == 0) $ret .= cellify($k,$formatting[$c]);
			else $ret .= cellify($data[$k][$c-1],$formatting[$c]);

			if ($sum_col != -1 && $c == $sum_col)
				$sum += $data[$k][$c-1];
		}
		$ret .= "</tr>";
	}
	if (count($data) == 0){
		$ret .= "<tr>";
		$ret .= "<td colspan=".count($col_headers)." class=center>";
		$ret .= "No results to report"."</td>";
		$ret .= "</tr>";
	}

	if ($sum_col != -1 && count($data) > 0){
		$ret .= "<tr>";
		foreach($col_order as $c){
			if ($c+1 == $sum_col) $ret .= "<td>Total</td>";
			elseif ($c == $sum_col) $ret .= cellify($sum,$formatting[$c]);
			else $ret .= "<td>&nbsp;</td>";
		}
		$ret .= "</tr>";
	}

	$ret .= "</table>";

	return $ret;
}

function cellify($data,$formatting){
	$ALIGN_RIGHT = 1;
	$ALIGN_LEFT = 2;
	$ALIGN_CENTER = 4;
	$TYPE_MONEY = 8;
	$ret = "";
	if ($formatting & $ALIGN_LEFT) $ret .= "<td class=left>";
	elseif ($formatting & $ALIGN_RIGHT) $ret .= "<td class=right>";
	elseif ($formatting & $ALIGN_CENTER) $ret .= "<td class=center>";

	if ($formatting & $TYPE_MONEY) $ret .= sprintf("%.2f",$data);
	else $ret .= $data;

	$ret .= "</td>";

	return $ret;
}
?>
