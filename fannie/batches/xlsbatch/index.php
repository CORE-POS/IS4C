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
/* html header, including navbar */
$page_title = "Fannie -  Sales Batch";
$header = "Upload Batch file";
include($FANNIE_ROOT."src/header.html");
include($FANNIE_ROOT."src/mysql_connect.php");
include($FANNIE_ROOT."src/tmp_dir.php");

$batchtypes = array();
$typesQ = "select batchTypeID,typeDesc from batchType order by batchTypeID";
$typesR = $dbc->query($typesQ);
while ($typesW = $dbc->fetch_array($typesR))
	$batchtypes[$typesW[0]] = $typesW[1];

if (isset($_POST['MAX_FILE_SIZE'])){
	$tmpfile = $_FILES['upload']['tmp_name'];
	$path_parts = pathinfo($_FILES['upload']['name']);
	if ($path_parts['extension'] != "xls"){
		echo "<b>Error: file doesn't seem to be an excel file</b>";
	}
	else {
		$fn = tempnam(sys_get_temp_dir(),"XLB");
		move_uploaded_file($tmpfile, $fn);

		include($FANNIE_ROOT."src/Excel/reader.php");
		$data = new Spreadsheet_Excel_Reader();
		$data->read($fn);

		$sheet = $data->sheets[0];

		$rows = $sheet['numRows'];
		$cols = $sheet['numCols'];

		echo "<form method=post action=index.php>";

		printf("<b>Batch Type: %s <input type=hidden value=%d name=btype /><br />",
			$batchtypes[$_REQUEST['btype']],$_REQUEST['btype']);
		printf("<b>Batch Name: %s <input type=hidden value=\"%s\" name=bname /><br />",
			$_REQUEST['bname'],$_REQUEST['bname']);
		printf("<b>Start Date: %s <input type=hidden value=\"%s\" name=date1 /><br />",
			$_REQUEST['date1'],$_REQUEST['date1']);
		printf("<b>End Date: %s <input type=hidden value=\"%s\" name=date2 /><br />",
			$_REQUEST['date2'],$_REQUEST['date2']);
		printf("<b>Product Identifier</b>: %s <input type=hidden value=\"%s\" name=ftype /><br />",
			$_REQUEST['ftype'],$_REQUEST['ftype']);
		printf("<b>Normalize prices</b>: <input type=checkbox name=normalize /><br />");
		echo "<i>&nbsp;&nbsp;&nbsp;&nbsp;Normalize rounds up to nearest 5 or 9</i><br />";
		echo "<br />";
		printf("<b>Includes check digits</b>: <input type=checkbox name=has_checks /><br />");
		echo "<i>&nbsp;&nbsp;&nbsp;&nbsp;UPCs have check digits</i><br />";
		echo "<br />";
		echo "<b>Data</b> (select appropriate columns)";
		echo "<b>Data</b> (select appropriate columns)";

		echo "<table cellspacing=0 cellpadding=4 border=1>";
		echo "<tr>";
		echo "<th>UPC/LC</th>";
		for($i=1;$i<=$cols;$i++){
			echo "<td><input type=radio name=num value=$i ";
			echo ($i==1)?"checked":"";
			echo " /></td>";
		}
		echo "</tr><tr>";
		echo "<th>Price</th>";
		for($i=1;$i<=$cols;$i++){
			echo "<td><input type=radio name=price value=$i ";
			echo ($i==2)?"checked":"";
			echo " /></td>";
		}
		echo "</tr>";
		for($i=1; $i<=$rows;$i++){
			echo "<tr>";
			echo "<td>&nbsp;</td>";
			for($j=1;$j<=$cols;$j++){
				$dp = "";
				if (isset($sheet['cells'][$i]) && isset($sheet['cells'][$i][$j]))
					$dp = $sheet['cells'][$i][$j];
				echo "<td><input type=hidden name=col{$j}[] value=\"$dp\" />";
				echo (empty($dp)?'&nbsp;':$dp)."</td>";
			}
			echo "</tr>";
		}
		echo "</table>";
		echo "<br />";
		echo "<input type=submit name=makeTheBatch value=\"Create Batch\" />";

		unlink($fn);
	}
}
else if (isset($_REQUEST['makeTheBatch'])){
	$upcCol = $_REQUEST['num'];
	$priceCol = $_REQUEST['price'];

	$upcs = $_REQUEST['col'.$upcCol];
	$prices = $_REQUEST['col'.$priceCol];
	for($i=0;$i<count($upcs);$i++)
		$upcs[$i] = str_replace(" ","",$upcs[$i]);

	$btype = $_REQUEST['btype'];
	$date1 = $dbc->escape($_REQUEST['date1']);
	$date2 = $dbc->escape($_REQUEST['date2']);
	$bname = $dbc->escape($_REQUEST['bname']);

	$dtQ = "SELECT discType FROM batchType WHERE batchTypeID=$btype";
	$dt = array_pop($dbc->fetch_row($dbc->query($dtQ)));

	$insQ = sprintf("INSERT INTO batches (startDate,endDate,batchName,batchType,discounttype,priority)
			VALUES (%s,%s,%s,%d,%d,0)",$date1,$date2,$bname,$btype,$dt);
	$insR = $dbc->query($insQ);

	$idQ = sprintf("SELECT max(batchID) FROM batches WHERE batchName=%s",$bname);
	$id = array_pop($dbc->fetch_row($dbc->query($idQ)));

	$upcChk = $dbc->prepare_statement("SELECT upc FROM products WHERE upc=?");
	for($i=0;$i<count($upcs);$i++){
		if (isset($upcs[$i])){
			$upcs[$i] = str_replace(" ","",$upcs[$i]);	
			$upcs[$i] = str_replace("-","",$upcs[$i]);	
		}
		if (isset($prices[$i])){
			$prices[$i] = trim($prices[$i],' ');
			$prices[$i] = trim($prices[$i],'$');
		}
		if(!is_numeric($upcs[$i])){
			echo "<i>Omitting item. Identifier {$upcs[$i]} isn't a number</i><br />";
			continue;
		}
		elseif(!is_numeric($prices[$i])){
			echo "<i>Omitting item. Price {$prices[$i]} isn't a number</i><br />";
			continue;
		}

		if (isset($_REQUEST['normalize'])){
			$tmp_price = sprintf("%.2f",$prices[$i]);
			while($tmp_price[strlen($tmp_price)-1] != "5"
				&& $tmp_price[strlen($tmp_price)-1] != "9"){
				$tmp_price += 0.01;
			}
			$prices[$i] = $tmp_price;
		}
		
		$upc = ($_REQUEST['ftype']=='UPCs')?str_pad($upcs[$i],13,'0',STR_PAD_LEFT):'LC'.$upcs[$i];
		if (isset($_REQUEST['has_checks']) && $_REQUEST['ftype']=='UPCs')
			$upc = '0'.substr($upc,0,12);

		if ($_REQUEST['ftype'] == 'UPCs'){
			$chkR = $dbc->exec_statement($upcChk, array($upc));
			if ($dbc->num_rows($chkR) ==  0) continue;
		}	

		$q = sprintf("INSERT INTO batchList (upc,batchID,salePrice,active,pricemethod,quantity)
			VALUES(%s,%d,%.2f,0,0,0)",$dbc->escape($upc),$id,$prices[$i]);
		$dbc->query($q);
	}

	echo "Batch created";
}
else {

?>
<script src="../../src/CalendarControl.js"
        language="javascript"></script>
<link rel="stylesheet" type="text/css" href="index.css">
<blockquote style="border:solid 1px black;background:#ddd;padding:4px;">
Use this tool to create a sales batch from an Excel file. Uploaded
files should have a column identifying the product, either by UPC
or likecode, and a column with prices.
</blockquote>
<form enctype="multipart/form-data" action="index.php" method="post">
<table cellspacing=4 cellpadding=4>
<tr><th>Type</th>
<td><select name=btype>
<?php foreach($batchtypes as $k=>$v) printf("<option value=%d>%s</option>",$k,$v); ?>
</select></td>
<th>Start</th><td><input type=text size=10 name=date1 onclick="showCalendarControl(this);" /></td></tr>
<tr><th>Name</th><td><input type=text size=15 name=bname /></td>
<th>End</th><td><input type=text size=10 name=date2 onclick="showCalendarControl(this);" /></td></tr>
<tr><td colspan=4>
<input type="hidden" name="MAX_FILE_SIZE" value="2097152" />
Filename: <input type="file" id="file" name="upload" />
</td></tr>
<tr>
<th>Identifier</th><td><select name=ftype><option>UPCs</option>
<option>Likecodes</option></select></td>
<td colspan=2>
<input type="submit" value="Upload File" />
</td></tr>
</table>
</form>
<?php
}
/* html footer */
include($FANNIE_ROOT."src/footer.html");
?>
