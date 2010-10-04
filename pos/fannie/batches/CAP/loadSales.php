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

include("../../config.php");

require_once($FANNIE_ROOT.'src/mysql_connect.php');
require($FANNIE_ROOT.'src/csv_parser.php');

try {
	$dbc->query("DROP TABLE tempCapPrices");
}
catch(Exception $e){}
$dbc->query("CREATE TABLE tempCapPrices (upc varchar(13), price decimal(10,2))");

$SUB = 1;
$UPC = 2;
$SKU = 3;
$PRICE = 14;

$datastarts = False;

$fp = fopen("tmp/CAP.csv","r");
while(!feof($fp)){
	$line = fgets($fp);
	$data = csv_parser($line);

	if (!$datastarts){
		if (strstr($data[$UPC],'UPC') !== false ) $datastarts = True;
		continue;
	}

	$upc = str_replace("-","",$data[$UPC]);
	$upc = str_replace(" ","",$upc);
	$upc = substr($upc,0,strlen($upc)-1);
	$upc = str_pad($upc,13,"0",STR_PAD_LEFT);

	$lookup = $dbc->query("SELECT upc FROM products WHERE upc='$upc'");
	if ($dbc->num_rows($lookup) == 0){
		if ($data[$SUB] != "BULK") continue;
		if ($data[$SKU] == "direct") continue;
		$sku = $data[$SKU];
		$look2 = $dbc->query("SELECT wfc_plu FROM UnfiToPlu WHERE unfi_sku='$sku'");
		if ($dbc->num_rows($look2) == 0) continue;
		$upc = array_pop($dbc->fetch_row($look2));
	}

	$price = trim($data[$PRICE],"\$");
	$insQ = "INSERT INTO tempCapPrices VALUES ('$upc',$price)";
	$dbc->query($insQ);
}

$page_title = "Fannie - CAP sales";
$header = "Upload Completed";
include($FANNIE_ROOT."src/header.html");

echo "Sales data import complete<p />";
echo "<a href=\"review.php\">Review data &amp; set up sales</a>";

include($FANNIE_ROOT."src/footer.html");

?>
