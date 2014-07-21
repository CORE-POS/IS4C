<?php
include('../../../config.php');
if (isset($_GET['salesCode1'])){

	$date1 = $_GET['date1'];
	$date2 = $_GET['date2'];
	$salesCode1 = $_GET['salesCode1'];
	$salesCode2 = $_GET['salesCode2'];
	
	$dir = 'DESC';
	if (isset($_GET['dir']))
		$dir = $_GET['dir'];
	$order = 'sum(t.total)';
	if (isset($_GET['order']))
		$order = $_GET['order'];
	$revdir = 'ASC';
	if ($dir == 'ASC')
		$revdir = 'DESC';
		
	if (isset($_GET['excel'])){
		header("Content-Disposition: inline; filename=queryResults.xls");
		header("Content-Description: PHP3 Generated Data");
		header("Content-type: application/vnd.ms-excel; name='excel'");
	}

	if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
	include('../../db.php');
		
	//printf($date1); //listed here for debugging purposes
	//printf($deptEnd); // same as above
	$today = date("F d, Y");	
	//Following lines creates a header for the report, listing sort option chosen, report date, date and department range.
	echo "Report run ";
	echo $today;
	echo "</br>";
	echo "From ";
	print $date1;
	echo " to ";
	print $date2;
	echo "</br>";
	echo "    pCode range: ";
	print $salesCode1;
	echo " to ";	
	print $salesCode2;
	echo "</br>";
	echo "<a href=movement.php?salesCode1=$salesCode1&salesCode2=$salesCode2&date1=$date1&date2=$date2&order=$order&dir=$dir&excel=yes>Save</a> to Excel<br />";
	
	$dlog = DTransactionsModel::selectDlog($date1,$date2);

	$date2a = $date2 . " 23:59:59";
	$date1a = $date1 . " 00:00:00";
	
	$groupBy = "upc";
	$query1 = "description";
	

	$query = $sql->prepare("SELECT DISTINCT t.upc,p.description, SUM(t.quantity),SUM(t.total),
				d.salesCode
			  FROM $dlog as t LEFT JOIN Products as p on t.upc = p.upc
			  LEFT JOIN departments as d on d.dept_no = t.department WHERE 
			  d.salesCode BETWEEN ? AND ?
			  AND tdate BETWEEN ? AND ?' GROUP BY t.upc,p.description,
			  d.salesCode ORDER BY t.upc");
	$result = $sql->execute($query, array($salesCode1, $salesCode2, $date1a, $date2a));
	echo "<table border=1>\n"; //create table
	echo "<tr>";
	echo "<td><a href=movement.php?salesCode1=$salesCode1&salesCode2=$salesCode2&date1=$date1&date2=$date2&order=t.upc&dir=";
	if ($order == "t.upc")
		echo "$revdir>UPC</a></td>";
	else
		echo "ASC>UPC</a></td>";
	echo "<td><a href=movement.php?salesCode1=$salesCode1&salesCode2=$salesCode2&date1=$date1&date2=$date2&order=p.description&dir=";
	if ($order == "p.description")
		echo "$revdir>Description</a></td>";
	else
		echo "ASC>Description</a></td>";
	echo "<td><a href=movement.php?salesCode1=$salesCode1&salesCode2=$salesCode2&date1=$date1&date2=$date2&order=sum(t.quantity)&dir=";
	if ($order == "sum(t.quantity)")
		echo "$revdir>Qty</a></td>";
	else
		echo "DESC>Qty</a></td>";
	echo "<td><a href=movement.php?salesCode1=$salesCode1&salesCode2=$salesCode2&date1=$date1&date2=$date2&order=sum(t.total)&dir=";
	if ($order == "sum(t.total)")
		echo "$revdir>Sales</a></td>";
	else
		echo "DESC>Sales</a></td>";
	echo "<td><a href=movement.php?salesCode1=$salesCode1&salesCode2=$salesCode2&date1=$date1&date2=$date2&order=d.dept_no&dir=";
	if ($order == "d.salesCode")
		echo "$revdir>pCode</a></td>";
	else
		echo "ASC>pCode</a></td>";
	echo "</tr>\n";//create table header
	
	while ($myrow = $sql->fetch_array($result)) //create array from query
		printf("<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>\n",$myrow[0], $myrow[1],$myrow[2],$myrow[3],$myrow[4]);
	//convert row information to strings, enter in table cells
	
	echo "</table>\n";//end table
	
}
else
{
?>
<HTML>
<head>
<title>Query</title>
<link href="<?php echo $FANNIE_URL; ?>src/style.css"
      rel="stylesheet" type="text/css">
<script src="<?php echo $FANNIE_URL; ?>src/javascript/jquery.js"
        language="javascript"></script>
<script src="<?php echo $FANNIE_URL; ?>src/javascript/jquery-ui.js"
        language="javascript"></script>
<link href="<?php echo $FANNIE_URL; ?>src/javascript/jquery-ui.css"
      rel="stylesheet" type="text/css">
</head>
<script type="text/javascript">
$(document).ready(function(){
    $('#date1').datepicker();
    $('#date2').datepicker();
});
</script>
<body>
	<form method = "get" action="movement.php">
	<table border="0" cellspacing="0" cellpadding="5">
		<tr> 
			<td> <p><b>pCode Start</b></p>
			<p><b>pCode End</b></p></td>
			<td> <p>
			<input type=text name=salesCode1 />
			</p>
			<p>
			<input type=text name=salesCode2 />
			</p></td>

			 <td>
			<p><b>Date Start</b> </p>
		         <p><b>Date End</b></p>
		       </td>
		            <td>
		             <p>
		               <input type=text size=25 name=date1 id="date1" />
		               </p>
		               <p>
		                <input type=text size=25 name=date2 id="date2" />
		         </p>
		       </td>

		</tr>
		<tr> 
			<td> <input type=submit name=submit value="Submit"> </td>
			<td> <input type=reset name=reset value="Start Over"> </td>
			<td> <input type=checkbox name=excel /> Excel </td>
			<td>&nbsp;</td>
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
<?php
}
?>




