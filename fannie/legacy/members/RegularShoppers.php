<?php
include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include($FANNIE_ROOT.'src/select_dlog.php');
include('../db.php');

if (isset($_POST["excel"])){
	header('Content-Type: application/ms-excel');
	header('Content-Disposition: attachment; filename="regularShopperReport.xls"');
}	


if (isset($_POST["type"])){

	$memtypeRestrict = "max(memtype) > 0";
	if (isset($_POST["staff"]))
		$memtypeRestrict = "max(d.memtype) = 1";
	$query = "";
	$month = date('n');
	$year = date('Y');
	$startdate = "";
	$enddate = "";
	switch($_POST["type"]){
	case 'month':
		$stamp = mktime(0,0,0,$month,0,$year);
		$startdate = date("Y-m-01",$stamp);
		$enddate = date("Y-m-d",$stamp);
		$dlog = "trans_archive.dbo.dlog".date("Ym",$stamp);
		$query = "select card_no,max(memtype),
			sum(case when trans_type in ('I','D','M') then total else 0 end)
			from $dlog as d
			group by card_no,datepart(yy,tdate),datepart(mm,tdate),datepart(dd,tdate),trans_num
			having $memtypeRestrict and card_no > 0
			order by convert(int,card_no)";	
		break;
	case 'ytd':
		$stamp = mktime(0,0,0,$month,0,$year);
		$enddate = date("Y-m-d",$stamp);
		$stamp = mktime(0,0,0,1,1,$year);
		$startdate = date("Y-m-d",$stamp);
		$dlog = select_dlog($startdate,$enddate);

		$extraRestrict ="or (max(d.memtype) is null and max(c.memtype)";
		if ($memtypeRestrict[strlen($memtypeRestrict)-1] == "1")
			$extraRestrict .= "=1)";
		else
			$extraRestrict .= ">0)";

		$query = "select card_no,case when max(d.memtype) is null then max(c.memtype) else max(d.memtype) end,
			sum(case when trans_type in ('I','D','M') then total else 0 end)
			from $dlog as d left join custdata as c on d.card_no=c.cardno
			where c.personnum=1
			group by card_no,datepart(yy,tdate),datepart(mm,tdate),datepart(dd,tdate),trans_num
			having ($memtypeRestrict $extraRestrict) and card_no > 0
			order by convert(int,card_no)";	
		break;
	case 'last3':
		$stamp = mktime(0,0,0,$month,0,$year);
		$enddate = date("Y-m-d",$stamp);
		for($i=0;$i<3;$i++){
			$month -= 1;
			if ($month == 0){
				$year -= 1;
				$month = 12;
			}
		}
		$stamp = mktime(0,0,0,$month,1,$year);
		$startdate = date("Y-m-d",$stamp);
		
		$dlog = select_dlog($startdate,$enddate);
		$query = "select card_no,max(d.memtype),
			sum(case when trans_type in ('I','D','M') then total else 0 end)
			from $dlog as d 
			group by card_no,datepart(yy,tdate),datepart(mm,tdate),datepart(dd,tdate),trans_num
			having $memtypeRestrict and card_no > 0
			order by convert(int,card_no)";	
		break;
	}
	//echo $query;
	$result = $sql->query($query);

	$curNum = "";
	$curType = "";
	$sum = 0;
	$visits = 0;
	$totals = array(0,0,0);
	$types = array(1=>'Member',3=>'StaffMember');
	echo "Reporting period: $startdate through $enddate";
	echo "<table cellspacing=0 cellpadding=4 border=1>";
	echo "<tr><th>Mem#</th><th>Type</th><th>Num. Trans</th><th>Purchases</th><th>Avg. Purchase</th></tr>";
	while($row=$sql->fetch_row($result)){
		if ($curNum != trim($row[0]) && $curNum != ""){
			echo "<tr>";
			echo "<td>$curNum</td>";
			echo "<td>$types[$curType]</td>";
			echo "<td>$visits</td>";
			echo "<td>$sum</td>";
			echo "<td>".round($sum/$visits,2)."</td>";
			echo "</tr>";
			$curNum = trim($row[0]);
			$curType = $row[1];
			$sum = 0;
			$visits = 0;	
			$totals[0] += 1;
		}
		else if ($curNum != trim($row[0])){
			$curNum = trim($row[0]);
			$curType = $row[1];
			$totals[0] += 1;
		}
		$sum += $row[2];
		$visits += 1;
		$totals[1] += 1;
		$totals[2] += $row[2];
	}
	echo "<tr>";
	echo "<td>$curNum</td>";
	echo "<td>$types[$curType]</td>";
	echo "<td>$visits</td>";
	echo "<td>$sum</td>";
	echo "<td>".round($sum/$visits,2)."</td>";
	echo "</tr>";
	echo "</table>";

	echo "<p />";
	echo "<b>Totals</b>";
	echo "<table cellpadding=4 cellspacing=0 border=1>";
	echo "<tr><th>Unique members</th><td>$totals[0]</td>";
	echo "<tr><th>Transactions</th><td>$totals[1]</td>";
	echo "<tr><th>Avg. Transactions</th><td>".round($totals[1]/$totals[0],2)."</td>";
	echo "<tr><th>Purchases</th><td>".round($totals[2],2)."</td>";
	echo "<tr><th>Avg. Purchase</th><td>".round($totals[2]/$totals[1],2)."</td>";
	echo "</table>";

}
elseif (isset($_POST["type2"])){
	$query = "";
	$month = date('n');
	$year = date('Y');
	$startdate = "";
	$enddate = "";

	$cardnos = array();
	$restrict = "memtype in (1,3)";
	if (isset($_POST["staff"]))
		$restrict = "memtype=1";
	$cardR = $sql->query("select cardno from custdata where type='PC' and $restrict group by cardno");
	while($cardW = $sql->fetch_row($cardR))
		$cardnos["x".trim($cardW[0])] = False;

	$dlog = "";
	switch($_POST["type2"]){
	case 'month':
		$stamp = mktime(0,0,0,$month,0,$year);
		$startdate = date("Y-m-01",$stamp);
		$enddate = date("Y-m-d",$stamp);
		$dlog = "trans_archive.dbo.dlog".date("Ym",$stamp);
		break;
	case 'ytd':
		$stamp = mktime(0,0,0,$month,0,$year);
		$enddate = date("Y-m-d",$stamp);
		$stamp = mktime(0,0,0,1,1,$year);
		$startdate = date("Y-m-d",$stamp);
		$dlog = select_dlog($startdate,$enddate);
		break;
	case 'last3':
		$stamp = mktime(0,0,0,$month,0,$year);
		$enddate = date("Y-m-d",$stamp);
		for($i=0;$i<3;$i++){
			$month -= 1;
			if ($month == 0){
				$year -= 1;
				$month = 12;
			}
		}
		$stamp = mktime(0,0,0,$month,1,$year);
		$startdate = date("Y-m-d",$stamp);
		$dlog = select_dlog($startdate,$enddate);
		break;
	}

	$dataQ = "select d.card_no from $dlog as d group by d.card_no";
	$dataR = $sql->query($dataQ);
	while($dataW = $sql->fetch_row($dataR)){
		$num = trim($dataW[0]);
		if (isset($cardnos["x".$num])) $cardnos["x".$num] = True;
	}

	$idle = array();
	$i = 0;
	//echo var_dump($cardnos);
	foreach($cardnos as $key=>$value){
		if (!$value)	
			$idle[$i++] = substr($key,1);
	}
	sort($idle);
	echo "<b>Memberships with no purchases</b><br />";
	echo "Reporting period: $startdate through $enddate";
	echo "<table cellspacing=0 cellpadding=4 border=1>";
	foreach($idle as $num){
		echo "<tr><td>$num</td></tr>";
	}
	echo "</table>";
	echo "<b>Total</b>: ".count($idle);
}
else {
?>
<form method=post action=RegularShoppers.php>
<b>Regular Customers reports</b>
<table cellspacing=0 cellpadding=4 border=1>
<tr><th valign=top>Time span</th>
<td><input type=radio name=type value=month /> Last month<br />
<input type=radio name=type value=last3 />Last 3 months<br />
<input type=radio name=type value=ytd />Year to date</td></tr>
<tr><th>Ignore staff</th><td><input type=checkbox name=staff checked /></tr>
<tr><th>Excel</th><td><input type=checkbox name=excel /></tr>
</table>
<br />
<input type=submit value="Generate Report" />
</form>
<hr />
<form method=post action=RegularShoppers.php>
<b>Customers NOT purchasing reports</b>
<table cellspacing=0 cellpadding=4 border=1>
<tr><th valign=top>Time span</th>
<td><input type=radio name=type2 value=month /> Last month<br />
<input type=radio name=type2 value=last3 />Last 3 months<br />
<input type=radio name=type2 value=ytd />Year to date</td></tr>
<tr><th>Ignore staff</th><td><input type=checkbox name=staff checked /></tr>
<tr><th>Excel</th><td><input type=checkbox name=excel /></tr>
</table>
<br />
<input type=submit value="Generate Report" />
</form>

<?php
}
?>
