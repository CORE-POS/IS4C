<?php
include('../ini.php');
include('util.php');
?>
<html>
<head>
<title>Scanning options</title>
<style type="text/css">
body {
	line-height: 1.5em;
}
</style>
</head>
<body>
<a href="index.php">Necessities</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="extra_config.php">Additional Configuration</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Scanning Options
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="extra_data.php">Sample Data</a>
<form action=scanning.php method=post>
Special handling modules for UPCs that aren't
products (e.g., coupons)<br />
<b>Special UPCs</b>:<br />
<select multiple size=10 name=SPECIAL_UPC_MODS[]>
<?php
if (isset($_REQUEST['SPECIAL_UPC_MODS'])) $IS4C_LOCAL->set('SpecialUpcClasses',$_REQUEST['SPECIAL_UPC_MODS']);

$mods = array();
$dh = opendir('../lib/Scanning/SpecialUPCs');
while(False !== ($f = readdir($dh))){
	if ($f == "." || $f == "..")
		continue;
	if (substr($f,-4) == ".php")
		$mods[] = rtrim($f,".php");
}

foreach($mods as $m){
	$selected = "";
	foreach($IS4C_LOCAL->get("SpecialUpcClasses") as $r){
		if ($r == $m){
			$selected = "selected";
			break;
		}
	}
	echo "<option $selected>$m</option>";
}

$saveStr = "array(";
foreach($IS4C_LOCAL->get("SpecialUpcClasses") as $r){
	$saveStr .= "'".$r."',";
}
$saveStr = rtrim($saveStr,",").")";
confsave('SpecialUpcClasses',$saveStr);
?>
</select><br />
<hr />
Discount type modules control how sale prices
are calculated.<br />
<b>Number of Discounts</b>:
<?php
if (isset($_REQUEST['DT_COUNT']) && is_numeric($_REQUEST['DT_COUNT'])) $IS4C_LOCAL->set('DiscountTypeCount',$_REQUEST['DT_COUNT']);
if ($IS4C_LOCAL->get("DiscountTypeCount") == "") $IS4C_LOCAL->set("DiscountTypeCount",5);
if ($IS4C_LOCAL->get("DiscountTypeCount") <= 0) $IS4C_LOCAL->set("DiscountTypeCount",1);
printf("<input type=text size=4 name=DT_COUNT value=\"%d\" />",
	$IS4C_LOCAL->get('DiscountTypeCount'));
confsave('DiscountTypeCount',$IS4C_LOCAL->get('DiscountTypeCount'));
?>
<br /><b>Discount Module Mapping</b>:<br />
<?php
if (isset($_REQUEST['DT_MODS'])) $IS4C_LOCAL->set('DiscountTypeClasses',$_REQUEST['DT_MODS']);
if (!is_array($IS4C_LOCAL->get('DiscountTypeClasses'))){
	$IS4C_LOCAL->set('DiscountTypeClasses',
		array(
			'NormalPricing',
			'EveryoneSale',
			'MemberSale',
			'CaseDiscount',
			'StaffSale'			
		));
}
$discounts = array();
$dh = opendir('../lib/Scanning/DiscountTypes');
while(False !== ($f = readdir($dh))){
	if ($f == "." || $f == "..")
		continue;
	if (substr($f,-4) == ".php"){
		$discounts[] = rtrim($f,".php");
	}
}
$dt_conf = $IS4C_LOCAL->get("DiscountTypeClasses");
for($i=0;$i<$IS4C_LOCAL->get('DiscountTypeCount');$i++){
	echo "[$i] => ";
	echo "<select name=DT_MODS[]>";
	foreach($discounts as $d) {
		echo "<option";
		if (isset($dt_conf[$i]) && $dt_conf[$i] == $d)
			echo " selected";
		echo ">$d</option>";
	}
	echo "</select><br />";
}
$saveStr = "array(";
$tmp_count = 0;
foreach($IS4C_LOCAL->get("DiscountTypeClasses") as $r){
	$saveStr .= "'".$r."',";
	if ($tmp_count == $IS4C_LOCAL->get("DiscountTypeCount")-1)
		break;
	$tmp_count++;
}
$saveStr = rtrim($saveStr,",").")";
confsave('DiscountTypeClasses',$saveStr);
?>
<hr />
<input type=submit value="Save Changes" />
</form>
</body>
</html>
