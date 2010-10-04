<?php
include('../src/functions.php');
$page_title = 'Fannie - Reports Module';
$header = 'Item Movement Report';
include ('../src/includes/header.html');
// require_once ('../src/mysql_connect.php');
if ((!isset($_POST['submit'])) && (!isset($_POST['upc'])) && (!isset($_GET['upc']))) {


  echo '<script src="../src/CalendarControl.js" language="javascript"></script>
    <SCRIPT LANGUAGE="JavaScript">
      function putFocus(formInst, elementInst) {
       if (document.forms.length > 0) {
         document.forms[formInst].elements[elementInst].focus();
       }
      }
    </script>';
    

    
    echo '<BODY onLoad="putFocus(0,0);">
  <form method="post" action="itemSales.php">		
  <div id="box">
          <table border="0" cellspacing="3" cellpadding="3">
                  <tr>
                      <input name=upc type=text id=upc> Enter UPC/PLU or product name here<br /><br /><br /></tr>
                  <tr>
                          <td align="right">
                                  <p><b>Date Start</b> </p>
                          <p><b>End</b></p>
                          </td>
                          <td>			
                                  <p><input type=text size=10 name=date1 onfocus="showCalendarControl(this);">&nbsp;&nbsp;*</p>
                                  <p><input type=text size=10 name=date2 onfocus="showCalendarControl(this);">&nbsp;&nbsp;*</p>
                          </td>
                          <td colspan=2>
                                  &nbsp;
                          </td>
                  </tr>
          </table>
          <br />
          <p>* denotes a required field</p>
          <p>If no date is selected, it will default to year-to-date mode.</p><br /><br />
          <input type=submit name=submit value="Submit">
  </div>
  
  </form>
  </body>';

}

if(isset($_POST['submit'])){
	foreach ($_POST AS $key => $value) {
		$$key = $value;
		//echo $key ." : " .  $value"<br>";
	}

	//$order = "ROUND(SUM(t.total),2) DESC";
}else{
      foreach ($_GET AS $key => $value) {
          $$key = $value;
	      //echo $key ." : " .  $value."<br>";
      }
}

echo "<body>";

if (isset($upc)) {
    if (is_numeric($upc)) {
		$upc = str_pad($upc,13,0,STR_PAD_LEFT);
		$queryItem = "SELECT * FROM products WHERE upc = '$upc'";
	} else {
		$queryItem = "SELECT * FROM products WHERE description LIKE '%$upc%' ORDER BY description";
	}
	
	$resultItem = mysql_query($queryItem);
	$num = mysql_num_rows($resultItem);

	if($num == 0) {
		echo "<div id='alert'><p>No item match found, would you like to add this (<a href='../item/itemMaint.php?upc=$upc'>" . $upc . " </a>) item?</p></div>";
	} elseif($num > 1) {
		for($i=0;$i < $num;$i++){
			$rowItem= mysql_fetch_array($resultItem);
			$upc = $rowItem['upc'];
			echo "<a href='itemSales.php?upc=$upc&date1=$date1&date2=$date2'>" . $upc . " </a>- " . $rowItem['description'] . " -- $" .$rowItem['normal_price']. "<br>";
		}	
    } else {
		$today = date("F d, Y");	
		if (empty($date1)) $date1 = date('Y') . '-01-01';
		if (empty($date2)) $date2 = date('Y-m-d');
		//Following lines creates a header for the report, listing sort option chosen, report date, date and department range.
		echo "Report on item ";
		echo $upc;
		echo "</br>";
		echo "Report run on:";
		echo $today;
		echo "</br>";
		echo "From ";
		print $date1;
		echo " to ";
		print $date2;
		echo "</br>";
		echo "</br>";
    
		// Check year in query, match to a dlog table
		$year1 = idate('Y',strtotime($date1));
		$year2 = idate('Y',strtotime($date2));

		if ($year1 != $year2) {
			echo "<div id='alert'><h4>Reporting Error</h4>
				<p>Fannie cannot run reports across multiple years.<br>
				Please retry your query.</p></div>";
			exit();
		}
//		elseif ($year1 == date('Y')) { $table = 'dtransactions'; }
		else { $table = 'dlog_' . $year1; }
 
		$date2a = $date2 . " 23:59:59";
		$date1a = $date1 . " 00:00:00";
       
		if (is_numeric($upc)) {                
		  $query = "SELECT DISTINCT 
				p.upc AS PLU,
				p.description AS Description,
				ROUND(p.normal_price,2) AS Current,
				ROUND(t.unitPrice,2) AS Price,
				d.dept_name AS Dept,
				s.subdept_name AS Subdept,
				SUM(t.quantity) AS Qty,
				ROUND(SUM(t.total),2) AS Total,
				p.scale as Scale
				FROM is4c_log.$table t, is4c_op.products p, is4c_op.subdepts s, is4c_op.departments d
				WHERE t.upc = p.upc AND s.subdept_no = p.subdept AND t.department = d.dept_no 
				AND t.datetime BETWEEN '$date1a' AND '$date2a' 
				AND t.emp_no <> 9999
				AND t.trans_status <> 'X'
				AND t.upc = '$upc'
				GROUP BY CONCAT(t.upc, '-',t.unitprice)
				ORDER BY Price";
		} elseif (!is_numeric($upc)) {
			$query = "SELECT DISTINCT 
				p.upc AS PLU,
				p.description AS Description,
				ROUND(p.normal_price,2) AS Current,
				ROUND(t.unitPrice,2) AS Price,
				d.dept_name AS Dept,
				s.subdept_name AS Subdept,
				SUM(t.quantity) AS Qty,
				ROUND(SUM(t.total),2) AS Total,
				p.scale as Scale
				FROM is4c_log.$table t, is4c_op.products p, is4c_op.subdepts s, is4c_op.departments d
				WHERE t.upc = p.upc AND s.subdept_no = p.subdept AND t.department = d.dept_no 
				AND t.datetime BETWEEN '$date1a' AND '$date2a' 
				AND t.emp_no <> 9999
				AND t.trans_status <> 'X'
				AND t.description LIKE '%$upc%'
				GROUP BY CONCAT(t.upc, '-',t.unitprice)
				ORDER BY Price";
		}
        
 		// echo $query . "<br>";
		$query2 = "SELECT DATEDIFF('$date2a', '$date1a')";
		// echo $query2 . "<br>";
		$result2 = mysql_query($query2,$dbc);
		$row2 = mysql_fetch_row($result2);
		$numdays = $row2[0] + 1;

		$result = mysql_query($query,$dbc);

		echo "<table border=1 cellpadding=3 cellspacing=0 width=100%>";
		echo "<tr><th>UPC</th><th>Desc.</th><th>Dept.</th><th>Subdept.</th><th>Qty * Price</th><th>Total</th><th>Scale</th></tr>";

		if (!$result) {
			$message  = 'Invalid query: ' . mysql_error() . "\n";
			$message .= 'Whole query: ' . $query;
			die($message);
		}

		$total_sold = 0;
		$total_value_sold = 0;
    
		$bg = '#eeeeee'; // Set background color.
		while ($row = mysql_fetch_array($result)) { //create array from query
			if ($row['Scale'] == 0) {$row['Scale'] = 'No';} 
			elseif ($row['Scale'] == 1) {$row['Scale'] = 'Yes';}
			$total_sold = $total_sold + $row['Qty'];
			$total_value_sold = $total_value_sold + $row['Total'];
			$bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee'); // Switch the background color.
			echo '<tr bgcolor="' . $bg . '">';
			echo "<td><a href='../item/itemMaint.php?" . $row['PLU'] . "'>" . $row['PLU'] . "</a></td>
				<td align=left>" . $row['Description'] . "</td>
				<td align=left>" . $row['Dept'] . "</td>
				<td align=left>" . $row['Subdept'] . "</td>
				<td align=center>" . $row['Qty'] . " @ " . money_format('%n',$row['Price']) . "</td>
				<td align=right>" . money_format('%n',$row['Total']) . "</td>
				<td align=center>" . $row['Scale'] . "</td>";
			echo "</tr>";
		}
		
		$avg_sold_per_day = number_format(($total_sold / $numdays),2);
		if ($total_sold != 0) {$avg_price_sold_at = "$" . number_format(($total_value_sold / $total_sold),2);} else {$avg_price_sold_at = 'N/A';}
		echo "</table>\n";//end table
		//end $query
		echo "<p>Number of Days: $numdays </p>
			<p>Total Sold: $total_sold ";
			if ($row['Scale'] == 'Yes') {echo " lbs.</p>";}
			else {echo "</p>";}
		echo "<p>Total Sales: $" . $total_value_sold . "</p>
			<p>An average of $avg_sold_per_day were sold per day.</p>
			<p>The average price was " . $avg_price_sold_at . ".</p>";
		echo "<center><a href='" . $_SERVER['PHP_SELF'] . "'><h4>Start Over</h4></a></center>";
	}
}

include ('../src/includes/footer.html');
?>