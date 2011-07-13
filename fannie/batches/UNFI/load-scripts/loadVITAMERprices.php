<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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
$SKU = 0;
$DESCRIPTION = 2;
$SIZE = 1;
$QTY = 8;
$UPC = 7;
$WHOLESALE = 9;

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
		$cleanQ = "delete from vendorItems WHERE vendorID=$VENDOR_ID";
		$dbc->query($cleanQ);
	}
	else {
		$filestoprocess = unserialize(base64_decode($_GET["filestoprocess"]));	
	}
}
else {
	$filestoprocess[] = "unfi.csv";
	$cleanQ = "delete from vendorItems WHERE vendorID=$VENDOR_ID";
	$dbc->query($cleanQ);
}

// remove one split from the list and process that
$current = array_pop($filestoprocess);

$fp = fopen("../tmp/$current",'r');
while(!feof($fp)){
	$line = fgets($fp);
	/* csv parser takes a comma-separated line and returns its elements
	   as an array */
	$data = csv_parser($line);

	if (!isset($data[$UPC])) continue;

	// grab data from appropriate columns
	$upc = str_pad($data[$UPC],13,'0',STR_PAD_LEFT);
	// zeroes isn't a real item, skip it
	if ($upc == "0000000000000" || !is_numeric($upc))
		continue;
	$upc = '0'.substr($upc,0,12);
	$sku = str_replace("-","",$data[$SKU]);
	$brand = 'VITAMER';
	$description = $data[$DESCRIPTION];
	$size = trim($data[$SIZE])." CT";
	$qty = $data[$QTY];
	$wholesale = trim($data[$WHOLESALE]," \$");
	// can't process items w/o price (usually promos/samples anyway)
	if (empty($wholesale))
		continue;

	$net_cost = $wholesale;

	// set cost in $PRICEFILE_COST_TABLE
	$upQ = "update prodExtra set cost=$net_cost where upc='$upc'";
	$upR = $dbc->query($upQ);
	$upQ = "update products set cost=$net_cost where upc='$upc'";
	$upR = $dbc->query($upQ);
	// end $PRICEFILE_COST_TABLE cost tracking

	// if the item doesn't exist in the general vendor catalog table,
	// add it. 
	$insQ = sprintf("INSERT INTO vendorItems (brand,sku,size,upc,units,cost,description,vendorDept,vendorID)
			VALUES (%s,%s,%s,%s,%d,%f,%s,NULL,%d)",$dbc->escape($brand),$dbc->escape($sku),
			$dbc->escape($size),$dbc->escape($upc),$qty,$net_cost,$dbc->escape($description),
			$VENDOR_ID);
	$insR = $dbc->query($insQ);

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
	$header = "Done loading catalog";
	$page_title = "Fannie :: Load Catalog";
	include($FANNIE_ROOT."src/header.html");

	echo "Finished processing Vitamer price file<br />";
	if ($PRICEFILE_USE_SPLITS){
		echo "Files processed:<br />";
		if (isset($_GET['processed'])){
			foreach (unserialize(base64_decode($_GET["processed"])) as $p){
				echo $p."<br />";
				unlink("../tmp/$p");
			}
		}
		echo $current."<br />";
		unlink("../tmp/$current");
	}
	else echo "unfi.csv<br />";
	unlink("../tmp/unfi.csv");
	
	echo "<br />";
	echo "<a href=../index.php>Vendor Pricing Home</a>";

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
	header("Location: loadNPATHprices.php?filestoprocess=$sendable_data&processed=$encoded2");

}

?>
