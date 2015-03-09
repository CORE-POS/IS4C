<?php
include('../../../config.php');

/*
	read an invoice csv file and parse out the cost of each item
	the cost of each item is updated in prodExtra
	invoice file should be named tmp/invoice.csv
*/
function import_invoice(){
	global $FANNIE_ROOT;
	$INVOICE_FILE = "tmp/invoice.csv";

	// field indexes
	$UPC = 3;
	$COST = 15; // or is it 12?

	// number of beginning lines to ignore
	$SKIP_LINES = 3;

	$fp = fopen($INVOICE_FILE,'r');
	$line_num = 1;

	if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
	include('../../db.php');

    $upQ = $sql->prepare("update prodExtra set cost=? where upc=?");
	while(!feof($fp)){
		$data = fgetcsv($fp);
		if ($line_num > $SKIP_LINES){
			$upc = $data[$UPC];
			$cost = $data[$COST];
			// stop when the shipping line or a non-UPC is hit
			if ($upc == "000000-000000" || !preg_match("/\d\d\d\d\d\d-\d\d\d\d\d\d/",$upc))
				break;
			// just go on to the next item if cost is zero
			if ($cost == 0)
				continue;
			$wupc = UNFItoWFC($upc);
			echo $wupc." ".$cost."<br />";
			$upR = $sql->execute($upQ, array($cost,$wupc));
		}
		$line_num++;
	}
}

/*
	change upcs from UNFI style (6 digit, hyphen, 6 digit)
	to WFC style (13 digit, no check)
*/
function UNFItoWFC($upc){
	list($left,$right) = sscanf($upc,"%d-%d");
	$right = substr($right,0,strlen($right)-1);
	$combined = $left . str_pad($right,5,'0',STR_PAD_LEFT);
	return str_pad($combined,13,'0',STR_PAD_LEFT);
}

?>
