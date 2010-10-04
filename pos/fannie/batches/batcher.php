
<html>
<head>
	<title>Fannie - Sales Batcher</title>
	<link rel='stylesheet' href='../src/style.css' type='text/css' />
	<link rel='stylesheet' href='../src/tablesort.css' type='text/css' />
	<style type="text/css">
		div.banner {
		  background: white;
		  font-weight: bold;
		  width: 100%;
		  height: 7em;
		  line-height: 1.1;
		  position: fixed;
		  top: 0px;
		  margin-left: auto;
		  margin-right: auto
		}
		div.banner p {
		  margin: 0; 
		  font-family: Arial, sans-serif;
		  background: #900;
		  border: thin outset #900;
		  color: white
		}
		div.banner h4 {
			line-height: 0.2em;
			margin: 1em
		}
	</style>
	<script type="text/javascript" src="../src/tablesort.js"></script>
	<link rel="stylesheet" href="../src/jquery-ui/development-bundle/themes/smoothness/ui.all.css" />
	<script type="text/javascript" src="../src/jquery-ui/development-bundle/jquery-1.2.6.js"></script>
	<script type="text/javascript" src="../src/jquery-ui/development-bundle/ui/ui.core.js"></script>
	<script type="text/javascript" src="../src/jquery-ui/development-bundle/ui/ui.dialog.js"></script>
	<script language="javascript" type="text/javascript">  
		$(document).ready(function() {   
			$('#dialogTest').addClass('smoothness').dialog({
				autoOpen: false,
				modal: true,
				minHeight: 500,
				minWidth: 500
			});  
			$('#bttn1').click(function() {  
				$('#dialogTest').dialog('open');  
			});
		});  
	</script>  
	<script type="text/javascript">
		$(function() {
			$("#datepicker").datepicker();
		});
	</script>
	

</head>

<body>
	<div id="dialogTest" title="Edit Batch Details">
		<table>
			<tr>
				<td>BatchName:</td><td colspan=2><input type="text" size=30 id="editBatchName" /></td>
			</tr><tr>
				<td>Start Date:</td><td><input type="text" id="datepicker" /></td><td></td>
			</tr><tr>
				<td>End Date:</td><td><input type="text" id="datepicker" /></td><td></td>
			</tr><tr>
				<td colspan=3><input type="submit" value="submit" id="editBatchSubmit" />
		</table>
	</div>


<?php
require_once('../src/mysql_connect.php');
include('control.php');

$batchID = $_GET['batchID'];

// 
// if (!$batchID) {
// 	$query = "SELECT * FROM batches";
// 	$result = mysql_query($query);
// 	
// 	
// 	while ($row = mysql_fetch_assoc) {
// 		echo
// 	}
// }
// 
// 
// 

$query0 = "SELECT * FROM batches WHERE batchID = $batchID";
$result = mysql_query($query0);
$batchInfo = mysql_fetch_assoc($result);

$query1 = "SELECT p.upc as upc, p.description as description, p.normal_price as normalprice, l.salePrice as saleprice FROM batchList l, products p WHERE p.upc = l.upc AND l.batchID = $batchID";
$result1 = mysql_query($query1);
//
//	FLOATING BAR
//
echo "<div class='banner'>\n
	<form action=# method=GET>
	<table border=0>\n<tr>
		<td colspan=3><h4>Add Items: " . ucwords($batchInfo['batchName']) . "</h4></td>
		<td colspan=2>
		<button id=\"bttn1\" value=\"Open the Dialog\">edit batch</button>  
		<!--<font size=-3><a href=\"#\">edit batch</a></font>-->&nbsp;<font size=-3><a href=\"#\">delete batch</a></td></td></tr>\n<tr>
		<td><b>Sale Price: </b><input type=text name=saleprice size=6></td>
		<td><b>UPC: </b><input type=text name=upc></td><input type=hidden name=batchID value=$batchID>
		<td><b>Delete</b><input type=checkbox name=delete value=1>
		<td><input type=submit name=submit value=submit></td>
		<td>&nbsp;&nbsp;&nbsp;Batch #$batchID is currently: $active<b>";
if ($active != 0) { echo "<font color=green size=3>ACTIVE</font>";}
else { echo "<font color=black>OFF</font>";}
//
//	---------------------
//	
//	PRODUCT LISTING
//
echo "</b></td></tr></table></div>";
echo "<br /><br /><br /><br /><br /><br />";
echo "<table id=\"output\" cellpadding=0 cellspacing=0 border=0 class=\"sortable-onload-1 rowstyle-alt colstyle-alt\">\n
	<caption>Generated on " . date('n/j/y \a\t h:i A') . "</caption>\n
		<thead>\n<tr>\n
			<th class=\"sortable-numeric\">UPC</th>\n
			<th class=\"sortable-text\">Description</th>\n
			<th class=\"sortable-currency\">Reg Price</th>\n
			<th class=\"sortable-currency\">Sale Price</th>\n
			<th>Delete</th>
    	</tr>\n</thead>\n<tbody>\n";

while ($row = mysql_fetch_assoc($result1)) {
	$del = "D" . $row['upc'];
	$field = "U" . $row['upc'];
	echo "<tr><td>" . $row['upc'] . "</td>
		<td>" . $row['description'] . "</td>
		<td align=right>" . money_format('%n', $row['normalprice']) . "</td>
		<td align=center><input type=text name=$field field value=" . money_format('%n', $row['saleprice']) . " size=8></td>
		<td align=center><input type=checkbox value=1 name=$del></td></tr>";
}
echo "</table>";

?>