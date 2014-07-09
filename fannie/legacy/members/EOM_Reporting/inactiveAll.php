<?php
include('../../../config.php');
include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

if (isset($_GET['excel'])){
	header('Content-Type: application/ms-excel');
	header('Content-Disposition: attachment; filename="InactiveLastMonth.xls"');
	$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];
}

$cached_output = DataCache::getFile("monthly");
if ($cached_output){
	echo $cached_output;
	exit;
}

ob_start();

$months = array(
"Jan"=>"01",
"Feb"=>"02",
"Mar"=>"03",
"Apr"=>"04",
"May"=>"05",
"Jun"=>"06",
"Jul"=>"07",
"Aug"=>"08",
"Sep"=>"09",
"Oct"=>"10",
"Nov"=>"11",
"Dec"=>"12",
);

$query = "select s.cardno,r.mask from
	suspensions as s left join reasoncodes as r
	on s.reasoncode & r.mask <> 0
	where s.memtype2 = 'PC' and
	".$sql->monthdiff($sql->now(),'s.suspDate')." = 1
	order by s.cardno";

echo "<table border=1 cellpadding=4 cellspacing=0>\n";
$headers = array('Mem Num','Overdue A/R','Equity Lapse','NSF Check','Contact Info','Equity Trans','Other');
echo "<tr>";
foreach($headers as $h)
	echo "<th ><font size=2>$h</font></th>";
echo "</tr>";

$backgrounds = array('#ffffcc','#ffffff');
$b = 0;

$result = $sql->query($query);
$curMem=-1;
$firstBuy=0;
$reasons = array("&nbsp;","&nbsp;","&nbsp;","&nbsp;","&nbsp;","&nbsp;");
$rsums = array(0,0,0,0,0,0);
while($row = $sql->fetch_row($result)){
	if ($curMem != $row[0]){
		if ($curMem != -1){
			echo "<tr>";
			echo "<td bgcolor=$backgrounds[$b]>$curMem</td>";
			foreach ($reasons as $r){
				echo "<td bgcolor=$backgrounds[$b] align=center>$r</td>";
			}
			echo "</tr>";
			$b = ($b+1)%2;
		}
		$curMem = $row[0];
		$reasons = array("&nbsp;","&nbsp;","&nbsp;","&nbsp;","&nbsp;","&nbsp;");
	}
	switch($row[1]){
	case '1':
		$reasons[0] = "1";
		$rsums[0] += 1;
		break;
	case '2':
	case '4':
		$reasons[1] = "1";
		$rsums[1] += 1;
		break;
	case '8':
		$reasons[2] = "1";
		$rsums[2] += 1;
		break;
	case '16':
		$reasons[3] = "1";
		$rsums[3] += 1;
		break;
	case '32':
		$reasons[4] = "1";
		$rsums[4] += 1;
		break;
	default:
		$reasons[5] = "1";
		$rsums[5] += 1;
	}
}
echo "<tr>";
echo "<td bgcolor=$backgrounds[$b]>$curMem</td>";
foreach ($reasons as $r){
	echo "<td bgcolor=$backgrounds[$b] align=center>$r</td>";
}
echo "</tr>";
$b = ($b+1)%2;
echo "<tr>";
echo "<th bgcolor=$backgrounds[$b]>Totals</th>";
foreach ($rsums as $r){
	echo "<td bgcolor=$backgrounds[$b] align=center>$r</td>";
}
echo "</tr>";
echo "</table>";

$output = ob_get_contents();
ob_end_clean();
DataCache::putFile('monthly',$output);
echo $output;

?>
