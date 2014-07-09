<?php
include('../../../config.php');
include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

if (isset($_GET['excel'])){
	header('Content-Type: application/ms-excel');
	header('Content-Disposition: attachment; filename="newMembersEOM.xls"');
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

$query = "select m.card_no,
	c.FirstName,c.LastName,
	m.street,m.city,m.state,m.zip,
	d.start_date,
	DATE_ADD(d.start_date,INTERVAL 2 YEAR) as endDate,
	s.stockPurchase,
	year(d.start_date),
	month(d.start_date),
	day(d.start_date)
	from meminfo as m
	left join is4c_trans.stockpurchases as s on m.card_no=s.card_no
	left join custdata as c on m.card_no=c.CardNo and c.personNum=1
	left join memDates as d on m.card_no=d.card_no
	where ".$sql>monthdiff($sql->now(),'d.start_date')."=1
	and c.Type='PC'
	order by m.card_no,s.tdate";

echo "<table border=1 cellpadding=0 cellspacing=0>\n";
$headers = array('Mem Num','Name','Address','City',
'State','Zip','Opening date','Ending Date',
'First stock ammount');
echo "<tr>";
foreach($headers as $h)
	echo "<th width=120><font size=2>$h</font></th>";
echo "</tr>";

$backgrounds = array('#ffffcc','#ffffff');
$b = 0;

$result = $sql->query($query);
$curMem=-1;
$curyear = 0;
$curday = 0;
$curmonth = 0;
$stock = 0;
$row = array();
while($t_row = $sql->fetch_row($result)){
	if ($curMem != $t_row[0]) {
		if ($curMem != -1){
			echo "<tr>";
			echo "<td width=120 bgcolor=$backgrounds[$b]>$row[0]</td>";
			echo "<td width=120 bgcolor=$backgrounds[$b]>$row[1] $row[2]</td>";
			echo "<td width=120 bgcolor=$backgrounds[$b]>$row[3]</td>";
			echo "<td width=120 bgcolor=$backgrounds[$b]>$row[4]</td>";
			echo "<td width=120 bgcolor=$backgrounds[$b]>$row[5]</td>";
			echo "<td width=120 bgcolor=$backgrounds[$b]>$row[6]</td>";
			echo "<td width=120 bgcolor=$backgrounds[$b]>$row[7]</td>";
			$temp = explode(" ",$row[8]);
			$temp = explode("-",$temp[0]);
			$fixdate = $temp[1]."/".$temp[2]."/".$temp[0];
			echo "<td width=120 bgcolor=$backgrounds[$b]>$fixdate</td>";
			echo "<td width=120 bgcolor=$backgrounds[$b]>$stock</td>";
			echo "</tr>";
			$b = ($b+1)%2;

			$checkQ = $sql->prepare("select cardno,firstname,lastname from custdata where lastname like ?
				and (firstname like ? or firstname like ?)
				and personnum = 1 and cardno <> ?");
			$checkR = $sql->execute($checkQ, array('%'.$row[2].'%','%'.$row[1].'%', substr($row[1],0,1).'%', $row[0]));
			while($checkW = $sql->fetch_row($checkR)){
				echo "<tr>";
				echo "<td bgcolor=$backgrounds[$b]>&nbsp;</td>";
				echo "<td bgcolor=$backgrounds[$b] colspan=8>Potential match: #$checkW[0] $checkW[1] $checkW[2]</td>";
				echo "</tr>";
				$b = ($b+1)%2;
			}
		}
		$curMem = $t_row[0];
		$row = $t_row;
		$stock = 0;

		$curyear = $t_row[10];
		$curday = $t_row[11];
		$curmonth = $t_row[12];
	}
	if ($t_row[10]==$curyear && $t_row[11]==$curday && $t_row[12]==$curmonth)
		$stock += $t_row[9];

}
echo "<tr>";
echo "<td width=120 bgcolor=$backgrounds[$b]>$row[0]</td>";
echo "<td width=120 bgcolor=$backgrounds[$b]>$row[1] $row[2]</td>";
echo "<td width=120 bgcolor=$backgrounds[$b]>$row[3]</td>";
echo "<td width=120 bgcolor=$backgrounds[$b]>$row[4]</td>";
echo "<td width=120 bgcolor=$backgrounds[$b]>$row[5]</td>";
echo "<td width=120 bgcolor=$backgrounds[$b]>$row[6]</td>";
echo "<td width=120 bgcolor=$backgrounds[$b]>$row[7]</td>";
$temp = explode(" ",$row[8]);
$temp = explode("-",$temp[0]);
$fixdate = $temp[1]."/".$temp[2]."/".$temp[0];
echo "<td width=120 bgcolor=$backgrounds[$b]>$fixdate</td>";
echo "<td width=120 bgcolor=$backgrounds[$b]>$stock</td>";
echo "</tr>";
$b = ($b+1)%2;

$checkQ = $sql->prepare("select cardno,firstname,lastname from custdata where lastname like ?
    and (firstname like ? or firstname like ?)
    and personnum = 1 and cardno <> ?");
$checkR = $sql->execute($checkQ, array('%'.$row[2].'%','%'.$row[1].'%', substr($row[1],0,1).'%', $row[0]));
while($checkW = $sql->fetch_row($checkR)){
	echo "<tr>";
	echo "<td bgcolor=$backgrounds[$b]>&nbsp;</td>";
	echo "<td bgcolor=$backgrounds[$b] colspan=8>Potential match: #$checkW[0] $checkW[1] $checkW[2]</td>";
	echo "</tr>";
	$b = ($b+1)%2;
}
echo "</table>";

$output = ob_get_contents();
ob_end_clean();
DataCache::putFile('monthly',$output);
echo $output;

?>
