<?php
include('../../../config.php');
include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

if (isset($_GET['excel'])){
	header('Content-Type: application/ms-excel');
	header('Content-Disposition: attachment; filename="agedPatronage.xls"');
	$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];
}

$cached_output = DataCache::getFile("monthly");
if ($cached_output){
	echo $cached_output;
	exit;
}

ob_start();

$curMonth = date('n');
$curYear = date('Y');

$lastMonth = $curMonth - 1;
$lastYear = $curYear;
if ($lastMonth == 0){
	$lastMonth = 12;
	$lastYear--;
}

$endDate = $lastYear."-".str_pad($lastMonth,2,"0",STR_PAD_LEFT)."-28";

$dates = array();
array_push($dates,array($lastMonth,$lastYear));

for($i=0;$i<4;$i++){
	$lastMonth--;
	if ($lastMonth == 0){
		$lastMonth = 12;
		$lastYear--;
	}
	array_push($dates,array($lastMonth,$lastYear));
}

$startDate = $lastYear."-".str_pad($lastMonth,2,"0",STR_PAD_LEFT)."-01";

$dlog = DTransactionsModel::selectDlog($startDate,$endDate);

$query = "select 
	m.card_no,
	min(c.FirstName),
	min(c.LastName),
	min(m.city),
	min(m.zip), 
	".$sql->monthdiff($sql->now(),'d.tdate')." as monthsAgo,
	sum(CASE WHEN d.trans_type='T' THEN -1*d.total ELSE 0 END)
	FROM $dlog AS d LEFT JOIN
	meminfo AS m ON d.card_no = m.card_no LEFT JOIN 
	custdata AS c ON d.card_no = c.CardNo AND c.personNum = 1 
	WHERE c.memType = 1 
	GROUP BY m.card_no,".$sql->monthdiff($sql->now(),'d.tdate').",d.trans_num
	ORDER BY m.card_no,".$sql->monthdiff($sql->now(),'d.tdate')." DESC";

$backgrounds = array('#ffffcc','#ffffff');
$b = 0;

echo "<table cellpadding=3 cellspacing=0 border=1><tr>";
echo "<th bgcolor=$backgrounds[$b] colspan=4>Aged Patronage Report</th>";
for ($i=4; $i >=0; $i--){
	echo "<th bgcolor=$backgrounds[$b] width=10px>&nbsp;</th><th bgcolor=$backgrounds[$b] colspan=3>".date("M Y",mktime(0,0,0,$dates[$i][0],1,$dates[$i][1]))."</th>";
}
echo "</tr><tr>";
$b = 1;
echo "<th bgcolor=$backgrounds[$b]>No.</th>";
echo "<th bgcolor=$backgrounds[$b]>Name</th>";
echo "<th bgcolor=$backgrounds[$b]>City</th>";
echo "<th bgcolor=$backgrounds[$b]>Zip</th>";
for ($i=0;$i<5;$i++){
	echo "<th bgcolor=$backgrounds[$b] width=10px>&nbsp;</th>";
	echo "<th bgcolor=$backgrounds[$b]>Sales</th>";
	echo "<th bgcolor=$backgrounds[$b]>Visits</th>";
	echo "<th bgcolor=$backgrounds[$b]>Avg</th>";
}
$b = 0;
echo "</tr>";

$result = $sql->query($query);
$skip = True;
$curNo = -1;
$demo = array('','','');
$data = array(
	array(0,0),
	array(0,0),
	array(0,0),
	array(0,0),
	array(0,0)
);
while($row = $sql->fetch_row($result)){
	if ($curNo != $row[0]){
		if (!$skip){
			echo "<tr>";
			echo "<td bgcolor=$backgrounds[$b]>$curNo</td>";
			foreach($demo as $d) echo "<td bgcolor=$backgrounds[$b]>$d</td>";
			foreach($data as $d){
				echo "<td bgcolor=$backgrounds[$b] width=10px>&nbsp;</td>";
				echo "<td bgcolor=$backgrounds[$b] align=center>$d[0]</td>";
				echo "<td bgcolor=$backgrounds[$b] align=center>$d[1]</td>";
				$avg = 0;
				if ($d[1] != 0) $avg = $d[0]/$d[1];
				echo "<td bgcolor=$backgrounds[$b] align=center>".round($avg,2)."</td>";
			}
			echo "<td bgcolor=$backgrounds[$b] width=10px>&nbsp;</td>";
			for ($i=0;$i<3;$i++) echo "<td bgcolor=$backgrounds[$b] align=center>0</td>";
			echo "</tr>";
			$b = ($b+1)%2;
		}
		$curNo = $row[0];
		$demo[0] = $row[1].' '.$row[2];
		$demo[1] = $row[3];
		$demo[2] = $row[4];
		$skip = False;	
		$data = array(
			array(0,0),
			array(0,0),
			array(0,0),
			array(0,0)
		);
	}
	if (!$skip){
		if ($row[5] == -1) {
			$skip = True;
		}
		else {
			if ($row[5] == -1) echo "WTF";
			$index = 5 - (-1*$row[5]);
			$data[$index][0] += $row[6];
			$data[$index][1] += 1;
		}
	}
}

$output = ob_get_contents();
ob_end_clean();
DataCache::putFile('monthly',$output);
echo $output;

?>
