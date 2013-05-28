<?php
include(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));
AutoLoader::LoadMap();
include(realpath(dirname(__FILE__).'/../ini.php'));
include('util.php');
?>
<html>
<head>
<title>IT CORE Lane Installation: Scanning options</title>
<style type="text/css">
body {
	line-height: 1.5em;
}
</style>
</head>
<body>
<?php include('tabs.php'); ?>
<div id="wrapper">
<h2>IT CORE Lane Installation: Scanning Options</h2>

<div class="alert"><?php check_writeable('../ini.php'); ?></div>
<div class="alert"><?php check_writeable('../ini-local.php'); ?></div>

<form action=scanning.php method=post>
<table id="install" border=0 cellspacing=0 cellpadding=4>
<tr><td style="width:30%;">
<b>Special UPCs</b>:<br />
<p>Special handling modules for UPCs that aren't products (e.g., coupons)</p>
</td><td>
<select multiple size=10 name=SPECIAL_UPC_MODS[]>
<?php
if (isset($_REQUEST['SPECIAL_UPC_MODS'])) $CORE_LOCAL->set('SpecialUpcClasses',$_REQUEST['SPECIAL_UPC_MODS'],True);

$mods = AutoLoader::ListModules('SpecialUPC');

foreach($mods as $m){
	$selected = "";
	foreach($CORE_LOCAL->get("SpecialUpcClasses") as $r){
		if ($r == $m){
			$selected = "selected";
			break;
		}
	}
	echo "<option $selected>$m</option>";
}

$saveStr = "array(";
foreach($CORE_LOCAL->get("SpecialUpcClasses") as $r){
	$saveStr .= "'".$r."',";
}
$saveStr = rtrim($saveStr,",").")";
confsave('SpecialUpcClasses',$saveStr);
?>
</select></td></tr><tr><td colspan=2>
<hr />
<p>Discount type modules control how sale prices are calculated.</p></td></tr>
<tr><td>
<b>Number of Discounts</b>:</td><td>
<?php
if (isset($_REQUEST['DT_COUNT']) && is_numeric($_REQUEST['DT_COUNT'])) $CORE_LOCAL->set('DiscountTypeCount',$_REQUEST['DT_COUNT'],True);
if ($CORE_LOCAL->get("DiscountTypeCount") == "") $CORE_LOCAL->set("DiscountTypeCount",5,True);
if ($CORE_LOCAL->get("DiscountTypeCount") <= 0) $CORE_LOCAL->set("DiscountTypeCount",1,True);
printf("<input type=text size=4 name=DT_COUNT value=\"%d\" />",
	$CORE_LOCAL->get('DiscountTypeCount'));
confsave('DiscountTypeCount',$CORE_LOCAL->get('DiscountTypeCount'));
?></td></tr><tr><td>
<b>Discount Module Mapping</b>:</td><td>
<?php
if (isset($_REQUEST['DT_MODS'])) $CORE_LOCAL->set('DiscountTypeClasses',$_REQUEST['DT_MODS'],True);
if (!is_array($CORE_LOCAL->get('DiscountTypeClasses'))){
	$CORE_LOCAL->set('DiscountTypeClasses',
		array(
			'NormalPricing',
			'EveryoneSale',
			'MemberSale',
			'CaseDiscount',
			'StaffSale'			
		),True);
}
$discounts = AutoLoader::ListModules('DiscountType');
$dt_conf = $CORE_LOCAL->get("DiscountTypeClasses");
for($i=0;$i<$CORE_LOCAL->get('DiscountTypeCount');$i++){
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
foreach($CORE_LOCAL->get("DiscountTypeClasses") as $r){
	$saveStr .= "'".$r."',";
	if ($tmp_count == $CORE_LOCAL->get("DiscountTypeCount")-1)
		break;
	$tmp_count++;
}
$saveStr = rtrim($saveStr,",").")";
confsave('DiscountTypeClasses',$saveStr);
?></td></tr><tr><td colspan=2>
<hr />	<p>Price Methods dictate how item prices are calculated.
	There's some overlap with Discount Types, but <i>generally</i>
	price methods deal with grouped items.</p></td></tr>
<tr><td>
<b>Number of Price Methods</b>:</td><td>
<?php
if (isset($_REQUEST['PM_COUNT']) && is_numeric($_REQUEST['PM_COUNT'])) $CORE_LOCAL->set('PriceMethodCount',$_REQUEST['PM_COUNT'],True);
if ($CORE_LOCAL->get("PriceMethodCount") == "") $CORE_LOCAL->set("PriceMethodCount",3,True);
if ($CORE_LOCAL->get("PriceMethodCount") <= 0) $CORE_LOCAL->set("PriceMethodCount",1,True);
printf("<input type=text size=4 name=PM_COUNT value=\"%d\" />",
	$CORE_LOCAL->get('PriceMethodCount'));
confsave('PriceMethodCount',$CORE_LOCAL->get('PriceMethodCount'));
?>
</td></tr><tr><td>
<b>Price Method Mapping</b>:</td><td>
<?php
if (isset($_REQUEST['PM_MODS'])) $CORE_LOCAL->set('PriceMethodClasses',$_REQUEST['PM_MODS'],True);
if (!is_array($CORE_LOCAL->get('PriceMethodClasses'))){
	$CORE_LOCAL->set('PriceMethodClasses',
		array(
			'BasicPM',
			'GroupPM',
			'QttyEnforcedGroupPM'
		),True);
}
$pms = AutoLoader::ListModules('PriceMethod');
$pm_conf = $CORE_LOCAL->get("PriceMethodClasses");
for($i=0;$i<$CORE_LOCAL->get('PriceMethodCount');$i++){
	echo "[$i] => ";
	echo "<select name=PM_MODS[]>";
	foreach($pms as $p) {
		echo "<option";
		if (isset($pm_conf[$i]) && $pm_conf[$i] == $p)
			echo " selected";
		echo ">$p</option>";
	}
	echo "</select><br />";
}
$saveStr = "array(";
$tmp_count = 0;
foreach($CORE_LOCAL->get("PriceMethodClasses") as $r){
	$saveStr .= "'".$r."',";
	if ($tmp_count == $CORE_LOCAL->get("PriceMethodCount")-1)
		break;
	$tmp_count++;
}
$saveStr = rtrim($saveStr,",").")";
confsave('PriceMethodClasses',$saveStr);
?></td></tr><tr><td colspan=2>
<hr />	<p>Special Department modules add extra steps to open rings in specific departments.
	Enter department number(s) that each module should apply to.</p>
</td></tr>
<tr><td>
<?php
$sdepts = AutoLoader::ListModules('SpecialDept');
$sconf = $CORE_LOCAL->get('SpecialDeptMap');
if (!is_array($sconf)) $sconf = array();
if (isset($_REQUEST['SDEPT_MAP_LIST'])){
	$sconf = array();
	for($i=0;$i<count($_REQUEST['SDEPT_MAP_NAME']);$i++){
		if (!isset($_REQUEST['SDEPT_MAP_LIST'][$i])) continue;
		if (empty($_REQUEST['SDEPT_MAP_LIST'][$i])) continue;

		$class = $_REQUEST['SDEPT_MAP_NAME'][$i];
		$obj = new $class();
		$ids = preg_split('/\D+/',$_REQUEST['SDEPT_MAP_LIST'][$i]);
		foreach($ids as $id)
			$sconf = $obj->register($id,$sconf);
	}
	$CORE_LOCAL->set('SpecialDeptMap',$sconf,True);
}
foreach($sdepts as $sd){
	$list = "";
	foreach($sconf as $id => $mods){
		if (in_array($sd,$mods))
			$list .= $id.', ';
	}
	$list = rtrim($list,', ');
	printf('<tr><td>%s</td><td>
		<input type="text" name="SDEPT_MAP_LIST[]" value="%s" />
		<input type="hidden" name="SDEPT_MAP_NAME[]" value="%s" />
		</td></tr>',
		$sd,$list,$sd);
}
$saveStr = 'array(';
foreach($sconf as $id => $mods){
	if (empty($mods)) continue;
	$saveStr .= $id.'=>array(';
	foreach($mods as $m)
		$saveStr .= '\''.$m.'\',';
	$saveStr = rtrim($saveStr,',').'),';
}
$saveStr = rtrim($saveStr,',').')';
confsave('SpecialDeptMap',$saveStr);
?>
</td></tr>
<tr><td colspan=2>
<hr />
<input type=submit name=scansubmit value="Save Changes" />
</td></tr></table>
</form>
</div> <!--	wrapper -->
</body>
</html>
