<?php
include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'src/select_dlog.php');

if (isset($_REQUEST['submit'])){
	$upc = str_pad($_REQUEST['upc'],13,'0',STR_PAD_LEFT);
	$date1 = $_REQUEST['date1'];
	$date2 = $_REQUEST['date2'];
	$dlog = select_dlog($date1,$date2);

	$q = $dbc->prepare_statement("SELECT description FROM products WHERE upc=?");
	$r = $dbc->exec_statement($q,array($upc));
	$w = $dbc->fetch_row($r);
	$description = $w[0];

	$q = $dbc->prepare_statement("SELECT d.card_no,c.lastname,c.firstname,m.street,m.city,m.state,
			m.zip,m.phone,m.email_2,m.email_1,sum(quantity) as qty,
			sum(total) as amt
		FROM $dlog AS d LEFT JOIN custdata AS c
		ON c.cardno=d.card_no AND c.personnum=1
		LEFT JOIN meminfo AS m ON m.card_no=c.cardno
		WHERE d.upc=? AND 
		tdate BETWEEN ? AND ?
		GROUP BY d.card_no,c.firstname,c.lastname,m.street,m.city,
		m.state,m.zip,m.phone,m.email_1,m.email_2
		ORDER BY c.lastname,c.firstname");
	$r = $dbc->exec_statement($q,array($upc,$date1.' 00:00:00',$date2.' 23:59:59'));

	if(isset($_REQUEST['excel'])){
	  header('Content-Type: application/ms-excel');
	  header('Content-Disposition: attachment; filename="recallReport.xls"');
	}

	ob_start();
	
	echo "Purchases for $upc ($description)<br />
		between $date1 and $date2";
	echo "<table cellspacing=0 cellpadding=4 border=1>";
	echo "<tr><th>Mem#</th><th>Name</th><th>Address</th>
		<th>City</th><th>State</th><th>Zip</th>
		<th>Phone</th><th>Alt. Phone</th><th>Email</th>
		<th>Qty</th><th>Amt</th></tr>";
	while($w = $dbc->fetch_row($r)){
		printf("<tr><td>%d</td><td>%s, %s</td>",
			$w[0],$w[1],$w[2]);
		for($i=3;$i<12;$i++)
			printf("<td>%s</td>",(empty($w[$i])?'&nbsp;':$w[$i]));
		echo "</tr>";
	}
	echo "</table>";

	$output = ob_get_contents();
	ob_end_clean();

	if (!isset($_REQUEST['excel'])){
		echo $output;
	}
	else {
		include($FANNIE_ROOT.'src/ReportConvert/HtmlToArray.php');
		//include($FANNIE_ROOT.'src/ReportConvert/ArrayToXls.php');
		include($FANNIE_ROOT.'src/ReportConvert/ArrayToCsv.php');
		$array = HtmlToArray($output);
		//$xls = ArrayToXls($array);
		$xls = ArrayToCsv($array);
		echo $xls;
	}
		
}
else {
	$page_title = "Fannie : Product Purchasers";
	$header = "Product Purchasers";
	include($FANNIE_ROOT.'src/header.html');
	echo '<script src="../../src/CalendarControl.js"
        type="text/javascript"></script>
	<form action=index.php method=get>
	<table><tr>
	<th>UPC</th><td><input type=text name=upc /></td>
	</tr><tr>
	<th>Start date</th><td><input type=text name=date1 onclick="showCalendarControl(this);" /></td>
	</tr><tr>
	<th>End date</th><td><input type=text name=date2 onclick="showCalendarControl(this);" /></td>
	</tr><tr>
	<td><input type=submit name=submit value="Get Report" /></td>
	<td><input type=checkbox name=excel id=excel /><label for=excel>Excel</label></td>
	</tr></table>
	</form>';
	include($FANNIE_ROOT.'src/footer.html');
}

?>
