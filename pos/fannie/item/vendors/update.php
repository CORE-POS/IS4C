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
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

// handle the actual file upload
if (isset($_REQUEST['MAX_FILE_SIZE'])){
	$tmpfile = $_FILES['upload']['tmp_name'];
	$fn = $_FILES['upload']['name'];
	move_uploaded_file($tmpfile,'tmp/'.$fn);
	$id = $_REQUEST['vid'];
	header("Location: update.php?vid=$id&filename=$fn&preview=yes");
	return;
}

include('../../config.php');
$page_title = "Fannie : Update Vendor Catalog";
$header = "Update Vendor Catalog";
include($FANNIE_ROOT.'src/header.html');
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'src/csv_parser.php');
?>
<script src="<?php echo $FANNIE_URL; ?>src/jquery-1.2.6.min.js"
	type="text/javascript"></script>
<?php

// no vendor found, shouldn't happen
if (!isset($_REQUEST['vid'])){
	echo "<i>Error: no venodr selected</i>";
	include($FANNIE_ROOT.'src/footer.html');
	return;
}
$vid = $_REQUEST['vid'];

if (isset($_REQUEST['confirm'])){
	$cols = array();
	foreach($_REQUEST['cols'] as $k=>$v){
		switch($v){
		case 'UPC':
			$cols['upc'] = $k;
			break;
		case 'SKU':
			$cols['sku'] = $k;
			break;
		case 'Brand':
			$cols['brand'] = $k;
			break;
		case 'Description':
			$cols['desc'] = $k;
			break;
		case 'Size':
			$cols['size'] = $k;
			break;
		case 'Units':
			$cols['units'] = $k;
			break;
		case 'Cost':
			$cols['cost'] = $k;
			break;
		case 'Dept':
			$cols['dept'] = $k;
			break;
		}	
	}
	
	$fn = $_REQUEST['filename'];
	$vid = $_REQUEST['vid'];

	$dbc->query(sprintf("DELETE FROM vendorItems WHERE vendorID=%d",$vid));		
	$fp = fopen("tmp/$fn","r");
	$count = 0;
	while(!feof($fp)){
		$line = fgets($fp);
		$data = csv_parser($line);

		$upc = "";
		for($i=0;$i<strlen($data[$cols['upc']]);$i++){
			if (is_numeric($data[$cols['upc']][$i]))
				$upc .= $data[$cols['upc']][$i];	
		}
		if (strlen($upc) < 7) continue; 

		$upc = str_pad($upc,13,'0',STR_PAD_LEFT);
		if (strlen($upc) > 13) $upc = substr($upc,-13);
		if (isset($_REQUEST['checkdigits']))	
			$upc = '0'.substr($upc,0,12);

		$sku = $dbc->escape(trim($data[$cols['sku']]));
		$brand = $dbc->escape(trim($data[$cols['brand']]));
		$desc = $dbc->escape(trim($data[$cols['desc']]));
		$size = $dbc->escape(trim($data[$cols['size']]));
		
		$units = trim($data[$cols['units']]);
		$units = (is_numeric($units))?$units:1;

		$cost = trim($data[$cols['cost']]);
		if ($cost[0] == "$") $cost = substr($cost,1);
		$cost = (is_numeric($cost))?$cost:0.00;

		$dept = trim($data[$cols['dept']]);
		$dept = (is_numeric($dept))?$dept:'NULL';

		$insQ = sprintf("INSERT INTO vendorItems (upc,sku,brand,description,
			size,units,cost,vendorDept,vendorID) VALUES ('%s',%s,%s,%s,
			%s,%d,%.2f,%d,%d)",$upc,$sku,$brand,$desc,$size,$units,$cost,
			$dept,$vid);
		$dbc->query($insQ);
		$count++;
	}
	fclose($fp);

	echo "Imported $count products into the vendor catalog";
	echo "<p />";
	echo "<a href=\"index.php\">Back to Vendors</a>";
}
elseif (isset($_REQUEST['preview'])){
	$fn = $_REQUEST['filename'];	
	echo "<form action=update.php method=post>";
	echo "<input type=hidden value=\"$fn\" name=filename />";
	echo "<input type=hidden value=$vid name=vid />";

	$opts = "<option value=\"\"></option>";
	$opts .= "<option>UPC</option>";
	$opts .= "<option>SKU</option>";
	$opts .= "<option>Brand</option>";
	$opts .= "<option>Description</option>";
	$opts .= "<option>Size</option>";
	$opts .= "<option value=Units>Units (case)</option>";
	$opts .= "<option value=Cost>Cost (case)</option>";
	$opts .= "<option value=Dept>Vendor Department</option>";

	echo "<table cellspacing=0 cellpadding=4 border=1>";

	$fp = fopen("tmp/$fn","r");
	$count = 0;
	while(!feof($fp)){
		$line = fgets($fp);
		$data = csv_parser($line);
		
		if ($count == 0){
			echo "<tr>";
			for($i=0;$i<count($data);$i++){	
				echo "<td><select name=cols[]>";
				echo $opts;
				echo "</select></td>";
			}
			echo "</tr>";
		}

		if ($count == 20) break; // limit preview

		echo "<tr>";
		foreach($data as $d){
			echo "<td>$d&nbsp;</td>";
		}
		echo "</tr>";
		
		$count++;
	}
	fclose($fp);
	echo "</table>";
	echo "<br />";
	echo "<input type=submit name=confirm value=\"Update Catalog\">";
	echo " <input type=checkbox name=checkdigits checked /> UPCs include check digits";
	echo "</form>";
}
else {
?>
Upload a list of vendor items in CSV format
<form enctype="multipart/form-data" action="update.php" method="post">
<input type="hidden" name="MAX_FILE_SIZE" value="20971520" />
Filename: <input type="file" id="file" name="upload" />
<input type="submit" value="Upload File" />
<input type="hidden" name=vid value=<?php echo $vid; ?> />
</form>

<?php
}


include($FANNIE_ROOT.'src/footer.html');
?>
