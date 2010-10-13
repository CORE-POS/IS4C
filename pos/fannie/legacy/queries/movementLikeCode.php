<?php
include('../../config.php');

include($FANNIE_ROOT.'src/functions.php');
if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../db.php');

if (isset($_GET['date1'])){

	$date1 = $_GET['date1'];
	$date2 = $_GET['date2'];
	$excel = $_GET['excel'];
	$lc1 = $_GET['likeCode1'];
	$lc2 = $_GET['likeCode2'];

	$sort = "sum(t.total)";
	if (isset($_GET['sort']))
		$sort = $_GET['sort'];
	$dir = "DESC";
	if (isset($_GET['dir'])){
		$dir = $_GET['dir'];
	}
	$otherdir = 'ASC';
	if ($dir == $otherdir)
		$otherdir = 'DESC';

	$dlog = select_dlog($date1,$date2);

	if (isset($_GET['excel'])){
	  header('Content-Type: application/ms-excel');
	  header('Content-Disposition: attachment; filename="movementReport'.$lc1.'-'.$lc2.'.xls"');
	}

	$query = "select
		  u.likecode,l.likeCodeDesc,max(p.department),
		  sum(case when t.trans_status in ('M','V') then t.itemqtty else t.quantity end ) as qty,
		  sum(t.total) from
		  $dlog as t left join upclike as u on u.upc = t.upc
		  left join likecodes as l on u.likecode = l.likecode 
		  left join products as p on t.upc = p.upc
		  where u.upc is not null and u.likecode between $lc1 and $lc2
		  and datediff(dd,t.tdate,'$date1') <= 0 
		  and datediff(dd,t.tdate,'$date2') >= 0
		  group by u.likecode,l.likecodedesc
		  order by $sort $dir";
	//echo $query;
	$result = $sql->query($query);

	// make headers sort links
	$today = date("F d, Y");	
	//Following lines creates a header for the report, listing sort option chosen, report date, date and department range.
	echo "Report summed by ";
	echo "date on ";
	echo "</br>";
	echo $today;
	echo "</br>";
	echo "From ";
	print $date1;
	echo " to ";
	print $date2;
	echo "</br>";

	if (!isset($_GET['excel'])){
		echo "<a href=movementLikeCode.php?date1=$date1&date2=$date2&likeCode1=$lc1&likeCode2=$lc2&sort=$sort&dir=$dir&excel=yes>Save</a> to Excel<br />";
	}

	echo "<table cellpadding=2 cellspacing=0 border=1>";
	echo "<tr>";
	if (!isset($_GET['excel'])){
		if ($sort == "u.likecode"){
			echo "<th><a href=movementLikeCode.php?date1=".$date1."&date2=".$date2."&likeCode1=".$lc1."&likeCode2=".$lc2."&sort=".$sort."&dir=".$otherdir.">Likecode</a></th>";
		}
		else {
			echo "<th><a href=movementLikeCode.php?date1=".$date1."&date2=".$date2."&likeCode1=".$lc1."&likeCode2=".$lc2."&sort=u.likecode&dir=ASC>Likecode</a></th>";
		}
		if ($sort == "l.likeCodeDesc"){
			echo "<th><a href=movementLikeCode.php?date1=".$date1."&date2=".$date2."&likeCode1=".$lc1."&likeCode2=".$lc2."&sort=".$sort."&dir=".$otherdir.">Description</a></th>";
		}
		else {
			echo "<th><a href=movementLikeCode.php?date1=".$date1."&date2=".$date2."&likeCode1=".$lc1."&likeCode2=".$lc2."&sort=l.likeCodeDesc&dir=ASC>Description</a></th>";
		}
		if ($sort == "t.department"){
			echo "<th><a href=movementLikeCode.php?date1=".$date1."&date2=".$date2."&likeCode1=".$lc1."&likeCode2=".$lc2."&sort=".$sort."&dir=".$otherdir.">Dept</a></th>";
		}
		else {
			echo "<th><a href=movementLikeCode.php?date1=".$date1."&date2=".$date2."&likeCode1=".$lc1."&likeCode2=".$lc2."&sort=t.department&dir=ASC>Dept</a></th>";
		}
		if ($sort == "sum(t.quantity)"){
			echo "<th><a href=movementLikeCode.php?date1=".$date1."&date2=".$date2."&likeCode1=".$lc1."&likeCode2=".$lc2."&sort=".$sort."&dir=".$otherdir.">Qty</a></th>";
		}
		else {
			echo "<th><a href=movementLikeCode.php?date1=".$date1."&date2=".$date2."&likeCode1=".$lc1."&likeCode2=".$lc2."&sort=sum(t.quantity)&dir=DESC>Qty</a></th>";
		}
		if ($sort == "sum(t.total)"){
			echo "<th><a href=movementLikeCode.php?date1=".$date1."&date2=".$date2."&likeCode1=".$lc1."&likeCode2=".$lc2."&sort=".$sort."&dir=".$otherdir.">Sales</a></th>";
		}
		else {
			echo "<th><a href=movementLikeCode.php?date1=".$date1."&date2=".$date2."&likeCode1=".$lc1."&likeCode2=".$lc2."&sort=sum(t.total)&dir=DESC>Sales</a></th>";
		}
	}
	else {
		echo "<th>Likecode</th><th>Description</th><th>Qty</th><th>Sales</th>";
	}
	echo "</tr>";
	while ($row = $sql->fetch_array($result)){
		echo "<tr>";
		echo "<td>".$row[0]."</td>";
		echo "<td>".$row[1]."</td>";
		echo "<td>".$row[2]."</td>";
		echo "<td>".$row[3]."</td>";
		echo "<td>".$row[4]."</td>";
		echo "</tr>";	
	}
	echo "</table>";

	return;
}

$lcQ = "select likeCode,likeCodeDesc from likeCodes order by likeCode";
$lcR = $sql->query($lcQ);
$options = "";
while ($lcW = $sql->fetch_array($lcR))
	$options .= "<option value=$lcW[0]>$lcW[0] - $lcW[1]</option>";

?>
<HTML>
<head>
<title>Query</title>
<link href="<?php echo $FANNIE_URL; ?>src/style.css"
      rel="stylesheet" type="text/css">
<script src="<?php echo $FANNIE_URL;?>src/CalendarControl.js"
        language="javascript"></script>
<script type=text/javascript>
function sync(num){
	var val = document.getElementById('select'+num).value;
	document.getElementById('likeCode'+num).value = val;
}
</script>
</head>
<body bgcolor="#FFffff" onload="sync(1); sync(2);">
<div id=logo><img src='../images/newLogo_small.gif'></div>
<div id=main>	
<form method = "get" action="movementLikeCode.php">
	<table border="0" cellspacing="0" cellpadding="5">
		<!--<tr>
			<td bgcolor="#CCFF66"><a href="csvQuery.php"><font color="#CC0000">Click 
here to create Excel Report</font></a></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>-->
		<tr> 
			<td> <p><b>Start</b></p>
			<p><b>End</b></p>
			</td>
			<td><p>
			<select id=select1 onchange=sync(1)><?php echo $options ?></select>
			<input type=text size=4 value=1 name=likeCode1 id=likeCode1  />
			</p>
			<p>
			<select id=select2 onchange=sync(2)><?php echo $options ?></select>
			<input type=text size=4 value=1 name=likeCode2 id=likeCode2 /> 
			</p>
			</td>

			 <td>
			<p><b>Date Start</b> </p>
		         <p><b>End</b></p>
		       </td>
		            <td>
		             <p>
		               <input type=text size=25 name=date1 onfocus="this.value='';showCalendarControl(this);">
		               </p>
		               <p>
		                <input type=text size=25 name=date2 onfocus="this.value='';showCalendarControl(this);">
		         </p>
		       </td>

		</tr>
		<!--<tr>
			<td> Select Dept/Buyer </td>
			<td colspan=3>
				<table width=100%><tr>
					<td><input type=radio name=buyer value=1>Bulk</td>
				       	<td><input type=radio name=buyer value=3>Cool</td>
				      	<td><input type=radio name=buyer value=4>Deli</td>
				      	<td><input type=radio name=buyer value=4>Grocery</td>
				      	<td><input type=radio name=buyer value=5>HBC</td></tr>
				      	<tr><td><input type=radio name=buyer value=6>Produce</td>
				      	<td><input type=radio name=buyer value=7>Marketing</td>
				      	<td><input type=radio name=buyer value=8>Meat</td>
				      	<td><input type=radio name=buyer value=9>Gen Merch</td>
				</tr></table>
			</td>
		</tr>-->
			<td> <input type=submit name=submit value="Submit"> </td>
			<td> <input type=reset name=reset value="Start Over"> </td>
			<td>&nbsp;</td>
			<td> <input type=checkbox name=excel id=excel /> <b>Excel</b> </td>
		</tr>
	</table>
<br>
<br>
<br>
<br>
<br>
<br>
</form>
</body>
</html>




