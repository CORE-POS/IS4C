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

include("../../config.php");

require_once($FANNIE_ROOT.'src/mysql_connect.php');
require($FANNIE_ROOT.'src/csv_parser.php');
require($FANNIE_ROOT.'src/tmp_dir.php');

$columns = array('UPC'=>7,'Price'=>23,'SKU'=>8,'Sub'=>6,'A/B/T'=>5);
if (!isset($_REQUEST['col_select'])){
	$tpath = sys_get_temp_dir()."/vendorupload/";
	$fp = fopen($tpath."CAP.csv","r");
	echo '<h3>Select columns</h3>';
	echo '<form action="loadSales.php" method="post">';
	echo '<input type="checkbox" name="rm_cds" checked /> Remove check digits';
	echo '<table cellpadding="4" cellspacing="0" border="1">';
	$width = 0;
	$table = "";
	for($i=0;$i<5;$i++){
		$line = fgets($fp);
		$data = csv_parser($line);
		//$table .= '<tr><td>&nbsp;</td>';
		$j=0;
		foreach($data as $d){
			$table .='<td>'.$d.'</td>';
			$j++;
		}
		if ($j > $width) $width = $j;
		$table .= '</tr>';
	}
	/* old method using radio buttons
	echo '<tr><th>UPC</th>';
	for($i=0;$i<$width;$i++){
		echo '<td><input type="radio" name="upc_col" value="'.$i.'" /></td>';
	}
	echo '</tr>';
	echo '<tr><th>Price</th>';
	for($i=0;$i<$width;$i++){
		echo '<td><input type="radio" name="price_col" value="'.$i.'" /></td>';
	}
	echo '</tr>';
	echo '<tr><th>A/B/TPR</th>';
	for($i=0;$i<$width;$i++){
		echo '<td><input type="radio" name="abt_col" value="'.$i.'" /></td>';
	}
	echo '</tr>';
	echo '<tr><th>SKU</th>';
	for($i=0;$i<$width;$i++){
		echo '<td><input type="radio" name="sku_col" value="'.$i.'" /></td>';
	}
	echo '</tr>';
	echo '<tr><th>Sub</th>';
	for($i=0;$i<$width;$i++){
		echo '<td><input type="radio" name="sub_col" value="'.$i.'" /></td>';
	}
	echo '</tr>';
	*/
	echo '<tr>';
	for ($i=0;$i<$width;$i++){
		echo '<td><select class="columnSelector" name="col_select[]">';
		echo '<option>(ignore)</option>';
		foreach($columns as $label=>$default){
			printf('<option %s>%s</option>',
				($i==$default?'selected':''),$label);
		}
		echo '</td>';
	}
	echo '</tr>';
	$table .= '</table>';
	echo $table;
	echo '<input type="submit" value="Continue" />';
	echo '</form>';
	echo '<script type="text/javascript" src="'.$FANNIE_URL.'src/jquery/jquery.js"></script>';
	echo '<script type="text/javascript">';
?>
	$(document).ready(function(){
		$('.columnSelector').change(function(){
			var myElem = this;
			$('.columnSelector').each(function(i){
				if (this != myElem && $(this).val() == $('*:focus').val())
					$(this).val('(ignore)');
			});
		});
	});
<?php
	echo '</script>';
	exit;
}

try {
	$dbc->query("DROP TABLE tempCapPrices");
}
catch(Exception $e){}
$dbc->query("CREATE TABLE tempCapPrices (upc varchar(13), price decimal(10,2), abtpr varchar(3))");

$SUB = $columns['Sub'];
$UPC = $columns['UPC'];
$SKU = $columns['SKU'];
$PRICE = $columns['Price'];
$ABT = $columns['A/B/T'];
$i=0;
foreach($_REQUEST['col_select'] as $opt){
	switch($opt){
	case 'Sub':
		$SUB = $i; break;	
	case 'UPC':
		$UPC = $i; break;	
	case 'SKU':
		$SKU = $i; break;	
	case 'Price':
		$PRICE = $i; break;	
	case 'A/B/T':
		$ABT = $i; break;	
	}
	$i++;
}

/* old method using radio buttons
$SUB = (isset($_REQUEST['sub_col'])) ? (int)$_REQUEST['sub_col'] : 2;
$UPC = (isset($_REQUEST['upc_col'])) ? (int)$_REQUEST['upc_col'] : 3;
$SKU = (isset($_REQUEST['sku_col'])) ? (int)$_REQUEST['sku_col'] : 4;
$ABT = (isset($_REQUEST['abt_col'])) ? (int)$_REQUEST['abt_col'] : 1;
$PRICE = (isset($_REQUEST['price_col'])) ? (int)$_REQUEST['price_col'] : 4;
*/
$rm_checks = (isset($_REQUEST['rm_cds'])) ? True : False;

$tpath = sys_get_temp_dir()."/vendorupload/";
$fp = fopen($tpath."CAP.csv","r");
$do_skus = $dbc->table_exists("UnfiToPlu");
while(!feof($fp)){
	$line = fgets($fp);
	$data = csv_parser($line);
	if (!is_array($data)) continue;
	if (count($data) < 14) continue;

	$upc = str_replace("-","",$data[$UPC]);
	$upc = str_replace(" ","",$upc);
	if ($rm_checks)
		$upc = substr($upc,0,strlen($upc)-1);
	$upc = str_pad($upc,13,"0",STR_PAD_LEFT);

	$lookup = $dbc->query("SELECT upc FROM products WHERE upc='$upc'");
	if ($dbc->num_rows($lookup) == 0){
		if ($data[$SUB] != "BULK") continue;
		if ($data[$SKU] == "direct") continue;
		if (!$do_skus) continue;
		$sku = $data[$SKU];
		$look2 = $dbc->query("SELECT wfc_plu FROM UnfiToPlu WHERE unfi_sku='$sku'");
		if ($dbc->num_rows($look2) == 0) continue;
		$upc = array_pop($dbc->fetch_row($look2));
	}

	$price = trim($data[$PRICE],"\$");
	$abt = array();
	if (strstr($data[$ABT],"A"))
		$abt[] = "A";
	if (strstr($data[$ABT],"B"))
		$abt[] = "B";
	if (strstr($data[$ABT],"TPR"))
		$abt[] = "TPR";
	foreach($abt as $type){
		$insQ = "INSERT INTO tempCapPrices VALUES ('$upc',$price,'$type')";
		$dbc->query($insQ);
	}
}
fclose($fp);
unlink($tpath."CAP.csv");

$page_title = "Fannie - CAP sales";
$header = "Upload Completed";
include($FANNIE_ROOT."src/header.html");

echo "Sales data import complete<p />";
echo "<a href=\"review.php\">Review data &amp; set up sales</a>";

include($FANNIE_ROOT."src/footer.html");

?>
