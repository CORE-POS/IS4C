<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

ini_set('display_errors','1');
include('../config.php'); 
include('util.php');
include('db.php');
$FILEPATH = $FANNIE_ROOT;
?>
<html>
<head>
<title>Fannie: Product Settings</title>
<link rel="stylesheet" href="../src/css/install.css" type="text/css" />
<script type="text/javascript" src="../src/jquery/jquery.js"></script>
</head>
<body>
<?php
echo showInstallTabs("Products");
?>
<form action=prod.php method=post>
<h1>Fannie: Product Settings</h1>
<?php
if (is_writable('../config.php')){
	echo "<span style=\"color:green;\"><i>config.php</i> is writeable</span>";
}
else {
	echo "<span style=\"color:red;\"><b>Error</b>: config.php is not writeable</span>";
}
?>
<br />
<b>Product Information Modules</b> <br />
The product editing interface displayed after you select a product at:
<br /><a href="<?php echo $FANNIE_URL; ?>item/" target="_item"><?php echo $FANNIE_URL; ?>item/</a>
<br />consists of fields grouped in several sections, called modules, listed below.
<br />The enabled (active) ones are selected/highlighted.
<br />
<br /><b>Available Modules</b> <br />
<?php
if (!isset($FANNIE_PRODUCT_MODULES)) $FANNIE_PRODUCT_MODULES = array('BaseItemModule');
if (isset($_REQUEST['FANNIE_PRODUCT_MODULES'])){
	$FANNIE_PRODUCT_MODULES = array();
	foreach($_REQUEST['FANNIE_PRODUCT_MODULES'] as $m)
		$FANNIE_PRODUCT_MODULES[] = $m;
}
$saveStr = 'array(';
foreach($FANNIE_PRODUCT_MODULES as $m)
	$saveStr .= '"'.$m.'",';
$saveStr = rtrim($saveStr,",").")";
confset('FANNIE_PRODUCT_MODULES',$saveStr);
?>
<select multiple name="FANNIE_PRODUCT_MODULES[]" size="10">
<?php
$dh = opendir("../item/modules");
$tmp = array();
while(($file = readdir($dh)) !== False){
	if (substr($file,-4) == ".php")
		$tmp[] = substr($file,0,strlen($file)-4);	
}
sort($tmp);
foreach($tmp as $module){
	printf("<option %s>%s</option>",(in_array($module,$FANNIE_PRODUCT_MODULES)?'selected':''),$module);
}
?>
</select><br />
Click or ctrl-Click or shift-Click to select/deselect modules for enablement.
<hr />
<br />
Default Shelf Tag Layout
<select name=FANNIE_DEFAULT_PDF>
<?php
if (!isset($FANNIE_DEFAULT_PDF)) $FANNIE_DEFAULT_PDF = 'Fannie Standard';
if (isset($_REQUEST['FANNIE_DEFAULT_PDF'])) $FANNIE_DEFAULT_PDF = $_REQUEST['FANNIE_DEFAULT_PDF'];
if (file_exists($FANNIE_ROOT.'admin/labels/scan_layouts.php')){
	include($FANNIE_ROOT.'admin/labels/scan_layouts.php');
	foreach(scan_layouts() as $l){
		if ($l == $FANNIE_DEFAULT_PDF)
			echo "<option selected>$l</option>";
		else
			echo "<option>$l</option>";
	}
}
else {
	echo "<option>No layouts found!</option>";
}
confset('FANNIE_DEFAULT_PDF',"'$FANNIE_DEFAULT_PDF'");
?>
</select>

<hr />
<b>Product Editing</b>
<p style="margin:0.4em 0em 0.4em 0em;"><b>Compose Product Description</b>: 
<?php
if (!isset($FANNIE_COMPOSE_PRODUCT_DESCRIPTION)) $FANNIE_COMPOSE_PRODUCT_DESCRIPTION = 0;
if (isset($_REQUEST['FANNIE_COMPOSE_PRODUCT_DESCRIPTION'])) $FANNIE_COMPOSE_PRODUCT_DESCRIPTION = $_REQUEST['FANNIE_COMPOSE_PRODUCT_DESCRIPTION'];
confset('FANNIE_COMPOSE_PRODUCT_DESCRIPTION',"$FANNIE_COMPOSE_PRODUCT_DESCRIPTION");
echo "<input type=text name=FANNIE_COMPOSE_PRODUCT_DESCRIPTION value=\"$FANNIE_COMPOSE_PRODUCT_DESCRIPTION\" size=1 />";
?>
<br />If 0 products.description, which appears on the receipt, will be used as-is.
<br />If 1 it will be shortened enough hold a "package" description made by
concatenating products.size and products.unitofmeasure so that the whole
string is still 30 or less characters:
<br /> "Eden Seville Orange Marma 500g"
</p>

<p style="margin:0.0em 0em 0.4em 0em;"><b>Compose Long Product Description</b>: 
<?php
if (!isset($FANNIE_COMPOSE_LONG_PRODUCT_DESCRIPTION)) $FANNIE_COMPOSE_LONG_PRODUCT_DESCRIPTION = 0;
if (isset($_REQUEST['FANNIE_COMPOSE_LONG_PRODUCT_DESCRIPTION'])) $FANNIE_COMPOSE_LONG_PRODUCT_DESCRIPTION = $_REQUEST['FANNIE_COMPOSE_LONG_PRODUCT_DESCRIPTION'];
confset('FANNIE_COMPOSE_LONG_PRODUCT_DESCRIPTION',"$FANNIE_COMPOSE_LONG_PRODUCT_DESCRIPTION");
echo "<input type=text name=FANNIE_COMPOSE_LONG_PRODUCT_DESCRIPTION value=\"$FANNIE_COMPOSE_LONG_PRODUCT_DESCRIPTION\" size=1 />";
?>
<br />If 0 productUser.description, which may be used in Product Verification, will be used as-is.
<br />If 1 productUser.brand will be prepended and a "package" description made by
concatenating products.size and products.unitofmeasure will be appended:
<br /> "EDEN | Marmalade, Orange, Seville, Rough-Cut | 500g"<br />
</p>

<hr />
<input type=submit value="Re-run" />
</form>
