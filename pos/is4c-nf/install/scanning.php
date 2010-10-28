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
<input type=submit value="Save Changes" />
</form>
</body>
</html>
