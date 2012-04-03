<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op, Duluth, MN

    This file is part of Fannie.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
include('../../config.php');

$page_title = "Fannie :: Product Tools";
$header = "Import Products";

include($FANNIE_ROOT.'src/header.html');

include($FANNIE_ROOT.'src/csv_parser.php');
include($FANNIE_ROOT.'src/tmp_dir.php');
if (isset($_REQUEST['MAX_FILE_SIZE']) ){
	// save new file
	$tmpfile = $_FILES['upload']['tmp_name'];
	$path_parts = pathinfo($_FILES['upload']['name']);
	$outfile = tempnam(sys_get_temp_dir(),"MIC");
	move_uploaded_file($tmpfile, $outfile);

	echo '<form action="prod.php" method="post">';

	echo '<i>Preview: Select which columns contain desired information</i><br />';
	echo '<input type="checkbox" name="skip" /> First row contains headers (omit it)<br />';
	echo '<input type="checkbox" name="checks" /> UPCs contain check digits<br />';

	$preview = array();
	$fp = fopen($outfile,"r");
	while( ($line = fgets($fp)) !== False && count($preview) < 5)
		$preview[] = csv_parser($line);
	fclose($fp);

	echo '<table cellspacing="0" cellpadding="4" border="1">';
	echo '<tr>';
	echo '<th>UPC</th>';
	for($i=0;$i<count($preview[0]);$i++)
		echo '<td><input type="radio" name="upc" value="'.$i.($i==0?'" checked':'"').' /></td>';
	echo '</tr>';
	echo '<tr>';
	echo '<th>Description</th>';
	for($i=0;$i<count($preview[0]);$i++)
		echo '<td><input type="radio" name="desc" value="'.$i.($i==1?'" checked':'"').' /></td>';
	echo '</tr>';
	echo '<tr>';
	echo '<th>Price</th>';
	for($i=0;$i<count($preview[0]);$i++)
		echo '<td><input type="radio" name="price" value="'.$i.($i==2?'" checked':'"').' /></td>';
	echo '</tr>';
	echo '<tr>';
	echo '<th>Dept#</th>';
	for($i=0;$i<count($preview[0]);$i++)
		echo '<td><input type="radio" name="dept" value="'.$i.'" /></td>';
	echo '</tr>';
	foreach($preview as $p){
		echo '<tr><td>&nbsp;</td>';
		foreach($p as $entry) echo '<td>'.$entry.'</td>';
		echo '</tr>';
	}
	echo '</table><br />';
	printf('<input type="hidden" name="ufile" value="%s" />',base64_encode($outfile));
	echo '<input type="submit" value="Import Data" name="importbutton" />';
	echo '</form>';
}
else if (isset($_REQUEST['importbutton'])){
	include($FANNIE_ROOT.'src/mysql_connect.php');
	$defaults_table = array();
	$defQ = "SELECT dept_no,dept_tax,dept_fs,dept_discount FROM departments";
	$defR = $dbc->query($defQ);
	while($defW = $dbc->fetch_row($defR)){
		$defaults_table[$defW['dept_no']] = array(
			'tax' => $defW['dept_tax'],
			'fs' => $defW['dept_fs'],
			'discount' => $defW['dept_discount']
		);
	}

	$upc_index = $_REQUEST['upc'];
	$desc_index = $_REQUEST['desc'];
	$price_index = $_REQUEST['price'];
	$dept_index = isset($_REQUEST['dept'])?$_REQUEST['dept']:False;
	$skip_one = isset($_REQUEST['skip'])?True:False;

	$filename = base64_decode($_REQUEST['ufile']);

	$fp = fopen($filename,'r');
	echo "Results: <br />";
	while( ($line = fgets($fp)) !== False ){
		// skip header row
		if ($skip_one){
			$skip_one = False;
			continue;
		}

		$line = csv_parser($line);

		// get info from file and member-type default settings
		// if applicable
		$upc = $line[$upc_index];
		$desc = $line[$desc_index];
		$price =  $line[$price_index];	
		$dept = ($dept_index !== False) ? $line[$dept_index] : 0;
		$tax = 0;
		$fs = 0;
		$discount = 1;
		if ($dept_index !== False){
			if (isset($defaults_table[$dept]['tax']))
				$tax = $defaults_table[$dept]['tax'];
			if (isset($defaults_table[$dept]['discount']))
				$discount = $defaults_table[$dept]['discount'];
			if (isset($defaults_table[$dept]['fs']))
				$fs = $defaults_table[$dept]['fs'];
		}

		// upc cleanup
		$upc = str_replace(" ","",$upc);
		$upc = str_replace("-","",$upc);
		if (isset($_REQUEST['checks']))
			$upc = substr($upc,0,strlen($upc)-1);
		$upc = str_pad($upc,13,'0',STR_PAD_LEFT);

		if (strlen($desc) > 35) $desc = substr($desc,0,35);		


		$dbc->query("DELETE FROM products WHERE upc=".$dbc->escape($upc));

		$insQ = sprintf("INSERT INTO products (upc,description,normal_price,
			pricemethod,groupprice,quantity,special_price,specialpricemethod,
			specialgroupprice,specialquantity,start_date,end_date,department,
			size,tax,foodstamp,scale,scaleprice,mixmatchcode,modified,advertised,
			tareweight,discount,discounttype,unitofmeasure,wicable,qttyEnforced,
			idEnforced,cost,inUse,numflag,subdept,deposit,local) VALUES
			(%s,%s,%.2f,0,.0,0,.0,0,.0,0,'1900-01-01','1900-01-01',%d,'',%d,%d,0,.0,
			'',%s,1,.0,%d,0,'',0,0,0,.0,1,0,0,.0,0)",$dbc->escape($upc),
			$dbc->escape($desc),$price,$dept,$tax,$fs,$dbc->now(),$discount);
		$dbc->query($insQ);
	}
	echo "Loaded requested products";
	fclose($fp);
	unlink($filename);
}
else {
?>
Upload a CSV file containing product UPCs, descriptions, prices,
and optional department numbers
<form enctype="multipart/form-data" action="prod.php" method="post">
<input type="hidden" name="MAX_FILE_SIZE" value="20971520" />
Filename: <input type="file" id="file" name="upload" />
<input type="submit" value="Upload File" />
<?php
}
include($FANNIE_ROOT.'src/footer.html');
?>
