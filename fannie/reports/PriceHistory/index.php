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

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
	$FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

/* provide a department range and date range to
   get history for all products in those departments
   for that time period AND current price

   provide just a upc to get history for that upc
*/
if (isset($_GET['dept1']) || isset($_GET['upc']) || isset($_GET['manufacturer'])){
  $dept1 = isset($_GET['dept1'])?$_GET['dept1']:'';
  $dept2 = isset($_GET['dept2'])?$_GET['dept2']:'';
  $upc = isset($_GET['upc'])?str_pad($_GET['upc'],13,'0',STR_PAD_LEFT):'';
  $start_date = isset($_GET['date1'])?$_GET['date1']:'';
  $end_date = isset($_GET['date2'])?$_GET['date2']:'';
  $manu = isset($_GET['manufacturer'])?$_GET['manufacturer']:'';
  $mtype = isset($_GET['mtype'])?$_GET['mtype']:'';
  
  if (isset($_GET['excel'])){
    header('Content-Type: application/ms-excel');
    header('Content-Disposition: attachment; filename="PriceHistory.xls"');
  }

  $q = "";
  $args = array();
  if (!isset($_GET['type'])){
    $q = "select h.upc,p.description,price,h.modified from prodPriceHistory
	as h left join products as p on h.upc=p.upc
   	  where h.upc = ?
	  order by h.upc,h.modified desc";
    $args = array($upc);
  }
  else if ($_GET['type'] == 'upc'){
    $q = "select h.upc,p.description,price,h.modified from prodPriceHistory
	as h left join products as p on h.upc=p.upc
   	  where h.upc = ? and h.modified between ? AND ?
	  order by h.upc,h.modified";
    $args = array($upc,$start_date.' 00:00:00',$end_date.' 23:59:59');
  }
  else if ($_GET['type'] == 'department'){
    $q = "select h.upc,p.description,price,h.modified,p.normal_price from prodPriceHistory
	as h left join products as p on h.upc=p.upc
  	  where department between ? and ? and h.modified BETWEEN ? AND ?
	  order by h.upc, h.modified";
    $args = array($dept1,$dept2,$start_date.' 00:00:00',$end_date.' 23:59:59');
    unset($_GET['upc']);
  }
  else {
    if ($mtype == 'upc'){
      $q = "select h.upc,p.description,price,h.modified,p.normal_price from prodPriceHistory
	as h left join products as p on h.upc=p.upc
   	  where h.upc like ? and h.modified BETWEEN ? AND ?
	  order by h.upc,h.modified";
       $args = array('%'.$manu.'%',$start_date.' 00:00:00',$end_date.' 23:59:59');
    }
    else {
      $q = "select p.upc,b.description,p.price,p.modified,b.normal_price
	    from prodPriceHistory as p left join prodExtra as x
	    on p.upc = x.upc left join products as b on
	    p.upc=b.upc where x.manufacturer ? and
	    p.modified between ? AND ?
	    order by p.upc,p.modified";
       $args = array($manu,$start_date.' 00:00:00',$end_date.' 23:59:59');
    }
    unset($_GET['upc']);
  }
  $p = $sql->prepare_statement($q);
  $r = $sql->exec_statement($p,$args);

  echo "<table cellspacing=2 cellpadding=2 border=1>";
  echo "<tr><th>UPC</th><th>Description</th>";
  echo "<th>Price</th><th>Date</th>";
  if (!isset($_GET['upc']))
    echo "<th>Current Price</th>";
  echo "</tr>";

  while ($row = $sql->fetch_array($r)){
	printf("<tr><td>%s</td><td>%s</td><td align=center>%.2f</td><td>%s</td>",
		$row['upc'],$row['description'],$row['price'],
		$row['modified']);
	if (!isset($_GET['upc'])) echo "<td align=center>".$row['normal_price']."</td>";
	echo "</tr>";
  }
  echo "</table>";
}
else {

$deptsQ = $sql->prepare_statement("select dept_no,dept_name from departments order by dept_no");
$deptsR = $sql->exec_statement($deptsQ);
$deptsList = "";
while ($deptsW = $sql->fetch_array($deptsR))
  $deptsList .= "<option value=$deptsW[0]>$deptsW[0] $deptsW[1]</option>";

$page_title = "Price Change Report";
$header = "Fannie : Price Change Report";
include($FANNIE_ROOT.'src/header.html');
?>
<script type=text/javascript src="<?php echo $FANNIE_URL; ?>src/CalendarControl.js">
</script>
<script type=text/javascript>
function showUPC(){
	$('#radioU').attr('checked',true);
	document.getElementById('upcfields').style.display='block';
	document.getElementById('departmentfields').style.display='none';
	document.getElementById('manufacturerfields').style.display='none';
}
function showDept(){
	$('#radioD').attr('checked',true);
	document.getElementById('upcfields').style.display='none';
	document.getElementById('departmentfields').style.display='block';
	document.getElementById('manufacturerfields').style.display='none';
}
function showManu(){
	$('#radioM').attr('checked',true);
	document.getElementById('upcfields').style.display='none';
	document.getElementById('departmentfields').style.display='none';
	document.getElementById('manufacturerfields').style.display='block';
}
$(document).ready(function(){
	showUPC();
	$('#date1').click(function(){showCalendarControl(this);});
	$('#date2').click(function(){showCalendarControl(this);});
	$('#d1s').change(function(){
		$('#dept1').val($('#d1s').val());
	});
	$('#d2s').change(function(){
		$('#dept2').val($('#d2s').val());
	});
});
</script>
<style type=text/css>
#departmentfields{
	display:none;
}
#manufacturerfields{
	display:none;
}
</style>
<body onload=showUPC()>
<form method=get action=index.php>
Type: <input type=radio id=radioU name=type value=upc onclick=showUPC() checked /> UPC 
<input type=radio id=radioD name=type value=department onclick=showDept() /> Department 
<input type=radio id=radioM name=type value=manufacturer onclick=showManu() /> Manufacturer
<br />

<div id=upcfields>
UPC: <input type=text name=upc /><br />
</div>

<div id=departmentfields>
Department Start: <input type=text id=dept1 size=4 name=dept1 />
<select id=d1s><?php echo $deptsList; ?></select><br />
Department End: <input type=text id=dept2 size=4 name=dept2 />
<select id=d2s><?php echo $deptsList; ?></select><br />
</div>

<div id=manufacturerfields>
Manufacturer: <input type=text name=manufacturer /><br />
<input type=radio name=mtype value=upc checked /> UPC prefix 
<input type=radio name=mtype value=name /> Manufacturer name<br />
</div>

Start Date: <input type=text id=date1 name=date1 /><br />
End Date: <input type=text id=date2 name=date2 /><br />
<input type=submit name=Submit /> <input type=checkbox name=excel /> Excel
</form>
<?php
include($FANNIE_ROOT.'src/footer.html');
}
?>
