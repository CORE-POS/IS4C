<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

    This file is part of Fannie.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include('../config.php');
require_once ($FANNIE_ROOT.'src/mysql_connect.php'); // Connect to the DB.
include($FANNIE_ROOT.'src/EanUpc.php');

if (!isset($_REQUEST['save'])){
	$page_title='Fannie - Purchase Orders';
	$header='Purchase Orders';
	include($FANNIE_ROOT.'src/header.html');
}

if (isset($_REQUEST['oid'])){
	$q = "SELECT p.upc,p.quantity,
		d.vendorName,v.brand,v.description,
		v.size,v.units
		FROM PurchaseOrderItems AS p
		LEFT JOIN Vendors AS d ON
		p.vendor_id=d.vendorID
		LEFT JOIN VendorItems AS v ON
		v.upc=p.upc WHERE order_id = "
		.((int)$_REQUEST['oid'])." 
		ORDER BY p.upc";
	$r = $dbc->query($q);
	
	ob_start();
	echo "<table cellpadding=4 cellspacing=0 border=1>";
	echo "<tr><th>UPC</th><th>Cases</th><th>Vendor</th>
		<th>Brand</th><th>Desc</th><th>Size</th>
		<th>Pack</th></tr>";
	$eans = array();
	while($w = $dbc->fetch_row($r)){
		$upc = ltrim($w['upc'],'0');
		if (strlen($upc) > 11 ){
			$upc = Ean13CheckDigit($upc);
			$eans[$upc] = $w['quantity'];
			// scan genius can't mix/match EAN13 and
			// UPC-A. If downloading, stick EANs
			// in a separate file
			if (isset($_REQUEST['save']))
				continue;
		}
		else {
			$upc = UpcACheckDigit($upc);
		}
		printf("<tr><td>%s</td><td>%d</td><td>%s</td>
			<td>%s</td><td>%s</td><td>%s</td><td>%d</td>
			</tr>",$upc,$w['quantity'],
			$w['vendorName'],$w['brand'],$w['description'],
			$w['size'],$w['units']);
	}
	echo "</table>";

	$str = ob_get_contents();
	ob_end_clean();

	if (!isset($_REQUEST['save'])){
		echo $str;
		printf("<a href=purchase.php?save=yes&oid=%d>Download Order</a>",$_REQUEST['oid']);
	}
	else {
		include($FANNIE_ROOT.'src/ReportConvert/HtmlToArray.php');
		include($FANNIE_ROOT.'src/ReportConvert/ArrayToCsv.php');
		$arr = HtmlToArray($str);
		$csv = ArrayToCsv($arr);

		if (count($eans)==0){
			header('Content-Type: application/ms-excel');
			header('Content-Disposition: attachment; filename="purchaseorder.csv"');
			echo $csv;
		}
		else {
			// Create separate UPC and EAN CSVs
			// and zip them up for download
			$upcfn = tempnam(sys_get_temp_dir(),'');
			$fp = fopen($upcfn,'w');
			fwrite($fp,$csv);
			fclose($fp);

			$eanfn = tempnam(sys_get_temp_dir(),'');
			$fp = fopen($eanfn,'w');
			fwrite($fp,"\"EAN\",\"Cases\"\r\n");
			foreach($eans as $k=>$v){
				fwrite($fp,"\"$k\",\"$v\"\r\n");
			}
			fclose($fp);
		
			$zip = new ZipArchive();
			$zfn = tempnam(sys_get_temp_dir(),'');
			$zip->open($zfn, ZipArchive::CREATE);
			$zip->addEmptyDir('PurchaseOrder');
			$zip->addFile($upcfn,'PurchaseOrder/order.upc.csv');
			$zip->addFile($eanfn,'PurchaseOrder/order.ean.csv');
			$zip->close();

			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename="PurchaseOrder.zip"'); 
			header('Content-Transfer-Encoding: binary');
			readfile($zfn);

			unlink($upcfn);
			unlink($eanfn);
			unlink($zfn);
		}
		exit;
	}
}
else {
	$q = "SELECT id,name,stamp FROM PurchaseOrder
		ORDER BY stamp desc";
	$r = $dbc->query($q);
	echo "<table cellspacing=0 cellpadding=4 border=1>";
	while($w = $dbc->fetch_row($r)){
		printf("<tr><td><a href=purchase.php?oid=%d>%s</a>
			</td><td>%s</td></tr>",
			$w['id'],$w['name'],$w['stamp']);
	}
	echo "</table>";
}

include($FANNIE_ROOT.'src/footer.html');
?>
