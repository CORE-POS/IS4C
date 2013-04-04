<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'src/select_dlog.php');

if (isset($_GET['date1'])){

	$date1 = $_GET['date1']." 00:00:00";
	$date2 = $_GET['date2']." 23:59:59";
	$dept1 = isset($_REQUEST['dept1'])?$_REQUEST['dept1']:0;
	$dept2 = isset($_REQUEST['dept2'])?$_REQUEST['dept2']:0;
	$super = isset($_REQUEST['super'])?$_REQUEST['super']:'';
	$where = (empty($super))?" t.department BETWEEN $dept1 AND $dept2 ":" s.superID=$super ";
	$where = 't.department BETWEEN ? AND ?';
	$args = array($dept1,$dept2);
	if (!empty($super)){
		$where = 's.superID=?';
		$args = array($super);
	}
	array_unshift($args,$date2.' 23:59:59');
	array_unshift($args,$date1.' 00:00:00');

	$dtrans = select_dtrans($date1,$date2);

	if (isset($_GET['excel'])){
	  header('Content-Type: application/ms-excel');
	  header('Content-Disposition: attachment; filename="movementReport'.$manu.'.xls"');
	}
	$sort = "department";

	$query = $dbc->prepare_statement("select t.upc,p.description,
		  sum(t.quantity) as qty,
		  CASE WHEN t.discounttype IN (2,5) AND memType IN (1,3) THEN unitPrice-memDiscount
		  ELSE unitPrice END as price,
		  t.department,d.dept_name,s.superID
		  from $dtrans as t inner join products as p
		  on p.upc=t.upc
		  left join departments as d on t.department = d.dept_no
		  left join MasterSuperDepts as s on d.dept_no = s.dept_ID
		  left join scaleItems as c on t.upc=c.plu
		  where t.datetime between ? AND ?
		  and trans_status NOT IN ('X','Z','M') and register_no <> 99
		  and emp_no <> 9999 and $where
		  and (t.upc not like '002%' or c.weight=1)
		  group by t.upc,
		  CASE WHEN t.discounttype IN (2,5) and memType IN (1,3) THEN unitPrice-memDiscount
		  ELSE unitPrice END,
		  p.description,t.department,d.dept_name,s.superID
		  order by t.department,t.upc,
		  CASE WHEN t.discounttype IN (2,5) and memType IN (1,3) THEN unitPrice-memDiscount
		  ELSE unitPrice END");
	$result = $dbc->exec_statement($query,$args);

	// make headers sort links
	$today = date("F d, Y");	
	//Following lines creates a header for the report, listing sort option chosen, report date, date and department range.

	$qs = "<a href=index.php?date1={$_REQUEST['date1']}&date2={$_REQUEST['date2']}&dept1=$dept1&depts=$dept2&super=$super";
	if (!isset($_GET['excel'])){
		echo $qs."&sort=$sort&excel=yes>Save</a> to Excel<br />";
	}

	echo $date1." through ".$date2;
	echo "<table cellpadding=2 cellspacing=0 border=1>";
	echo "<tr>";
	printf("<th>UPC</th><th>%s</th><th>Qty</th><th>Price</th>
		<th>%s</th></tr>",
		(isset($_REQUST['excel'])?'Description':$qs."&sort=p.description>Description</a>"),
		(isset($_REQUST['excel'])?'Dept':$qs."&sort=t.department>Dept</a>"));
	echo "</tr>";

	$prevUPC = null;
	$rows = array();	
	while($row = $dbc->fetch_row($result)){
		$line = sprintf("<tr><td>%s</td><td>%s</td><td>%.2f</td>
			<td>%.2f</td><td>%d %s</td></tr>",
			$row['upc'],$row['description'],
			$row['qty'],$row['price'],
			$row['department'],$row['dept_name']);
		if ($row['upc'] == $prevUPC || $prevUPC === null){
			$rows[] = $line;
			$prevUPC = $row['upc'];
		}
		else {
			foreach($rows as $r){
				if (count($rows) > 1)
					echo str_replace("<td>","<td bgcolor=#ffffcc>",$r);
				else
					echo $r;
			}
			$rows = array();
			$prevUPC = null;
		}
	}
	foreach($rows as $r){
		if (count($rows) > 1)
			echo str_replace("<td>","<td bgcolor=#ffffcc>",$r);
		else
			echo $r;
	}
	echo "</table>";

	return;
}

$deptsQ = $dbc->prepare_statement("select dept_no,dept_name from departments order by dept_no");
$deptsR = $dbc->exec_statement($deptsQ);
$deptsList = "";

$deptSubQ = $dbc->prepare_statement("SELECT superID,super_name FROM MasterSuperDepts
		WHERE superID <> 0 
		group by superID,super_name
		ORDER BY superID");
$deptSubR = $dbc->exec_statement($deptSubQ);

$deptSubList = "";
while($deptSubW = $dbc->fetch_array($deptSubR)){
  $deptSubList .=" <option value=$deptSubW[0]>$deptSubW[1]</option>";
}
while ($deptsW = $dbc->fetch_array($deptsR))
  $deptsList .= "<option value=$deptsW[0]>$deptsW[0] $deptsW[1]</option>";

$page_title = "Fannie : Movement By Price";
$header = "Price Point Movement Report";
include($FANNIE_ROOT.'src/header.html');
?>
<script src="../../src/CalendarControl.js"
        type="text/javascript"></script>
<script type="text/javascript">
$(document).ready(function(){
	$('#buyerR:checked').each(function(){
		$('#buyerrow').show();
		$('#deptrow').hide();
	});
	$('#deptR:checked').each(function(){
		$('#deptrow').show();
		$('#buyerrow').hide();
	});
	sync('d1t','d1s');
	sync('d2t','d2s');

	$('#buyerR').click(function(){
		$('#buyerrow').show();
		$('#deptrow').hide();
	});

	$('#deptR').click(function(){
		$('#deptrow').show();
		$('#buyerrow').hide();
	});
});
function sync(id1,id2){
	$('#'+id1).val($('#'+id2).val());
}
</script>
<div id=main>	
<form method = "get" action="index.php">
	<table border="0" cellspacing="0" cellpadding="5">
		<tr><td><input type=radio name=bytype checked id=buyerR><label for="buyerR">By buyer</label>
			<input type=radio name=bytype id=deptR><label for="deptR">By department</label>
		</td></tr>
		<tr id="buyerrow">
			<td>Buyer</td>
			<td><select name=super><option value=""></option>
			<?php echo $deptSubList; ?>
			</select></td>
		</tr>
		<tr id="deptrow" style="display:none;">
			<td>
			<p><b>Dept Start</b></p>
			<p><b>Dept End</b></p>
			</td>
			<td>
			<p><input type=text name=dept1 size=3 id=d1t />
			<select id=d1s onchange="sync('d1t','d1s');">
			<?php echo $deptsList; ?>
			</select></p>
			<p><input type=text name=dept2 size=3 id=d2t />
			<select id=d2s onchange="sync('d2t','d2s');">
			<?php echo $deptsList; ?>
			</select></p>
			</td>
		</tr>
		<tr> 
			 <td>
			<p><b>Date Start</b> </p>
		         <p><b>Date End</b></p>
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
		<tr>
		<td> <input type=submit name=submit value="Submit"> </td>
		<td><input type=checkbox name=excel /> Excel </td>
		<td>&nbsp;</td>
		</tr>
	</table>
</form>
</div>
<?php
include($FANNIE_ROOT.'src/footer.html');
?>



