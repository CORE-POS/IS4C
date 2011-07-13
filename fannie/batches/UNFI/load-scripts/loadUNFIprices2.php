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

/*
	This page merits a little explanation. Here's the basic rundown:
	We take a provided csv file, parse out the info we need,
	calculate margins, and stick it into the database.

	This gets complicated because the csv files can easily be large
	enough to run headlong into PHP's memory limit. To address that,
	I take this approach:

	* split the csv file into 2500 line chunks
	* make a list of these chunks
	* process one of these files
	* redirect back to this page (resetting the memory limit)
	  and process the next file
*/

/* configuration for your module - Important */
include("../../../config.php");
require($FANNIE_ROOT.'src/csv_parser.php');
require($FANNIE_ROOT.'src/mysql_connect.php');

// the column number in the CSV file
// where various information is stored
$SKU = 1;
$BRAND = 2;
$DESCRIPTION = 6;
$QTY = 3;
$SIZE1 = 4;
$UPC = 7;
$CATEGORY = 5;
$WHOLESALE = 9;
$DISCOUNT = 9;

require($FANNIE_ROOT.'batches/UNFI/lib.php');
$VENDOR_ID = getVendorID(basename($_SERVER['SCRIPT_FILENAME']));
if ($VENDOR_ID === False){
	echo "Error: no vendor has this load script";
	exit;
}
$PRICEFILE_USE_SPLITS = True;

/*
	filestoprocess is the array of split files. If it
	hasn't been created yet, this is the first pass
	So the csv is split and a list of splits is built.
	Otherwise, take the existing list from GET
*/
$filestoprocess = array();
$i = 0;
$fp = 0;
if ($PRICEFILE_USE_SPLITS){
	if (!isset($_GET["filestoprocess"])){
		system("split -l 2500 ../tmp/unfi.csv ../tmp/UNFISPLIT");
		$dir = opendir("../tmp");
		while ($current = readdir($dir)){
			if (!strstr($current,"UNFISPLIT"))
				continue;
			$filestoprocess[$i++] = $current;
		}
		
		$delQ = "DELETE FROM vendorItems WHERE vendorID=$VENDOR_ID";
		$delR = $dbc->query($delQ);
	}
	else {
		$filestoprocess = unserialize(base64_decode($_GET["filestoprocess"]));	
	}
}
else {
	$delQ = "DELETE FROM vendorItems WHERE vendorID=$VENDOR_ID";
	$delR = $dbc->query($delQ);
	$filestoprocess[] = "unfi.csv";
}

// remove one split from the list and process that
$current = array_pop($filestoprocess);

$fp = fopen("../tmp/$current",'r');
while(!feof($fp)){
	$line = fgets($fp);
	/* csv parser takes a comma-separated line and returns its elements
	   as an array */
	$data = csv_parser($line);
	if (!is_array($data)) continue;

	if (!isset($data[$UPC])) continue;

	// grab data from appropriate columns
	$sku = $data[$SKU];
	$brand = $data[$BRAND];
	$description = $data[$DESCRIPTION];
	$qty = $data[$QTY];
	$size = $data[$SIZE1];
	$upc = substr($data[$UPC],0,13);
	// zeroes isn't a real item, skip it
	if ($upc == "0000000000000")
		continue;
	$category = $data[$CATEGORY];
	$wholesale = trim($data[$WHOLESALE]);
	$discount = trim($data[$DISCOUNT]);
	// can't process items w/o price (usually promos/samples anyway)
	if (empty($wholesale) or empty($discount))
		continue;

	// syntax fixes. kill apostrophes in text fields,
	// trim $ off amounts as well as commas for the
	// occasional > $1,000 item
	$brand = preg_replace("/\'/","",$brand);
	$description = preg_replace("/\'/","",$description);
	$wholesale = preg_replace("/\\\$/","",$wholesale);
	$wholesale = preg_replace("/,/","",$wholesale);
	$discount = preg_replace("/\\\$/","",$discount);
	$discount = preg_replace("/,/","",$discount);

	// skip the item if prices aren't numeric
	// this will catch the 'label' line in the first CSV split
	// since the splits get returned in file system order,
	// we can't be certain *when* that chunk will come up
	if (!is_numeric($wholesale) or !is_numeric($discount))
		continue;

	// need unit cost, not case cost
	$net_cost = $discount / $qty;

	// set cost in $PRICEFILE_COST_TABLE
	$upQ = "update prodExtra set cost=$net_cost where upc='$upc'";
	$upR = $dbc->query($upQ);
	$upQ = "update products set cost=$net_cost where upc='$upc'";
	$upR = $dbc->query($upQ);
	// end $PRICEFILE_COST_TABLE cost tracking

	$insQ = "INSERT INTO vendorItems (brand,sku,size,upc,units,cost,description,vendorDept,vendorID)
			VALUES ('$brand',$sku,'$size','$upc',$qty,$net_cost,
			'$description',$category,$VENDOR_ID)";
	$insR = $dbc->query($insQ);
	// end general UNFI catalog queries
}
fclose($fp);

/* 
	if filestoprocess is empty, stop and print some
	summary info including what files were processed
	(for sanity's sake) and clean up by deleting all
	the splits (this is actually important since the
	next price file might not split into the same
	number of pieces and we process all the splits in
	tmp. So it isn't concurrency-safe, either)

	otherwise, add the current file to the list of
	splits that have already been processed and redirect
	back to this page, passing both lists (files to be done
	and file that are already processed)

	serialize & base64 encoding are used to make the
	arrays URL-safe
*/
if (count($filestoprocess) == 0){
	/* html header, including navbar */
	$page_title = "Done loading items";
	$header = "Done loading items";
	include($FANNIE_ROOT."src/header.html");

	// this stored procedure compensates for items ordered from
	// UNFI under one UPC but sold in-store under a different UPC
	// (mostly bulk items sold by PLU). All it does is update the
	// upcc field in unfi_order for the affected items
	$pluQ1 = "UPDATE unfi_order AS u
		INNER JOIN UnfiToPLU AS p
		ON u.unfi_sku = p.unfi_sku
		SET u.upcc = p.wfc_plu";
	$pluQ2 = "UPDATE prodExtra AS x
		INNER JOIN UnfiToPLU AS p
		ON x.upc=p.wfc_plu
		INNER JOIN unfi_order AS u
		ON u.unfi_sku=p.unfi_sku
		SET x.cost = u.vd_cost / u.pack";
	if ($FANNIE_SERVER_DBMS == "MSSQL"){
		$pluQ1 = "UPDATE unfi_order SET upcc = p.wfc_plu
			FROM unfi_order AS u RIGHT JOIN
			UnfiToPLU AS p ON u.unfi_sku = p.unfi_sku
			WHERE u.unfi_sku IS NOT NULL";
		$pluQ2 = "UPDATE prodExtra
			SET cost = u.vd_cost / u.pack
			FROM UnfiToPLU AS p LEFT JOIN
			unfi_order AS u ON p.unfi_sku = u.unfi_sku
			LEFT JOIN prodExtra AS x
			ON p.wfc_plu = x.upc";
	}
	$dbc->query($pluQ1);
	$dbc->query($pluQ2);

	echo "Finished processing UNFI price file<br />";
	if ($PRICEFILE_USE_SPLITS){
		echo "Files processed:<br />";
		foreach (unserialize(base64_decode($_GET["processed"])) as $p){
			echo $p."<br />";
			unlink("../tmp/$p");
		}
		echo $current."<br />";
		unlink("../tmp/$current");
	}
	else echo "unfi.csv<br />";
	unlink("../tmp/unfi.csv");
	
	echo "<br />";
	echo "<a href=../index.php>UNFI Pricing Home</a>";

	/* html footer */
	include($FANNIE_ROOT."src/footer.html");
}
else {
	$processed = array();
	if (isset($_GET["processed"]))
		$processed = unserialize(base64_decode($_GET["processed"]));
	array_push($processed,$current);

	$sendable_data = base64_encode(serialize($filestoprocess));
	$encoded2 = base64_encode(serialize($processed));
	header("Location: loadUNFIprices2.php?filestoprocess=$sendable_data&processed=$encoded2");

}

?>
