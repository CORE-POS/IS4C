<?php
include(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));
AutoLoader::loadMap();
include(realpath(dirname(__FILE__).'/../ini.php'));
include('InstallUtilities.php');
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

<div class="alert"><?php InstallUtilities::checkWritable('../ini.php', False, 'PHP'); ?></div>
<div class="alert"><?php InstallUtilities::checkWritable('../ini-local.php', True, 'PHP'); ?></div>

<form action=scanning.php method=post>
<table id="install" border=0 cellspacing=0 cellpadding=4>
<tr><td style="width:30%;">
<b>Special UPCs</b>:<br />
<p>Special handling modules for UPCs that aren't products (e.g., coupons)</p>
</td><td>
<select multiple size=10 name=SPECIAL_UPC_MODS[]>
<?php
if (isset($_REQUEST['SPECIAL_UPC_MODS'])) $CORE_LOCAL->set('SpecialUpcClasses',$_REQUEST['SPECIAL_UPC_MODS']);

$mods = AutoLoader::listModules('SpecialUPC');

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
InstallUtilities::paramSave('SpecialUpcClasses', $CORE_LOCAL->get('SpecialUpcClasses'));
?>
</select></td></tr>
<tr><td>
<b>House Coupon Prefix</b></td><td>
<?php
if(isset($_REQUEST['HCPREFIX'])) $CORE_LOCAL->set('houseCouponPrefix',$_REQUEST['HCPREFIX'],True);
else if ($CORE_LOCAL->get('houseCouponPrefix') === '') $CORE_LOCAL->set('houseCouponPrefix', '00499999', true);
printf("<input type=text name=HCPREFIX value=\"%s\" />",$CORE_LOCAL->get('houseCouponPrefix'));
InstallUtilities::paramSave('houseCouponPrefix',$CORE_LOCAL->get('houseCouponPrefix'));
?>
<span class='noteTxt'>Set the barcode prefix for houseCoupons.  Should be 8 digits starting with 004. Default is 00499999.</span>
</td></tr>
<tr><td style="width: 30%;">
</td><td>
</td></tr><tr><td>
<b>Coupons &amp; Sales Tax</b>:</td><td>
<?php
if (isset($_REQUEST['COUPONTAX'])) $CORE_LOCAL->set('CouponsAreTaxable',$_REQUEST['COUPONTAX']);
echo '<select name="COUPONTAX">';
if ($CORE_LOCAL->get('CouponsAreTaxable') === 0){
	echo '<option value="1">Tax pre-coupon total</option>';
	echo '<option value="0" selected>Tax post-coupon total</option>';
}
else {
	echo '<option value="1" selected>Tax pre-coupon total</option>';
	echo '<option value="0">Tax post-coupon total</option>';
	$CORE_LOCAL->set('CouponsAreTaxable', 1);
}
echo '</select>';
InstallUtilities::paramSave('CouponsAreTaxable',$CORE_LOCAL->get('CouponsAreTaxable'));
?>
<span class='noteTxt'>Apply sales tax based on item price before any coupons, or
apply sales tax to item price inclusive of coupons.</span>
</td></tr>
<tr><td>
<b>Donation Department</b></td><td>
<?php
if(isset($_REQUEST['DONATIONDEPT'])) $CORE_LOCAL->set('roundUpDept',$_REQUEST['DONATIONDEPT'], true);
else if ($CORE_LOCAL->get('roundUpDept') === '') {
    // try to find a sane default automatically
    $CORE_LOCAL->set('roundUpDept', 701);
    $db = Database::pDataConnect();
    $lookup = $db->query("SELECT dept_no FROM departments WHERE dept_name LIKE '%DONAT%'");
    if ($lookup && $db->num_rows($lookup) > 0) {
        $row = $db->fetch_row($lookup);
        $CORE_LOCAL->set('roundUpDept', $row['dept_no']);
    }
}
printf("<input type=text name=DONATIONDEPT value=\"%s\" />",$CORE_LOCAL->get('roundUpDept'));
InstallUtilities::paramSave('roundUpDept',$CORE_LOCAL->get('roundUpDept'));
?>
<span class='noteTxt'>Set the department number for lines entered via the "round up" donation function.</span>
</td></tr>
<hr />
<p>Discount type modules control how sale prices are calculated.</p></td></tr>
<tr><td>
<b>Number of Discounts</b>:</td><td>
<?php
if (isset($_REQUEST['DT_COUNT']) && is_numeric($_REQUEST['DT_COUNT'])) $CORE_LOCAL->set('DiscountTypeCount',$_REQUEST['DT_COUNT']);
if ($CORE_LOCAL->get("DiscountTypeCount") == "") $CORE_LOCAL->set("DiscountTypeCount",5);
if ($CORE_LOCAL->get("DiscountTypeCount") <= 0) $CORE_LOCAL->set("DiscountTypeCount",1);
printf("<input type=text size=4 name=DT_COUNT value=\"%d\" />",
	$CORE_LOCAL->get('DiscountTypeCount'));
InstallUtilities::paramSave('DiscountTypeCount',$CORE_LOCAL->get('DiscountTypeCount'));
?></td></tr><tr><td>
<b>Discount Module Mapping</b>:</td><td>
<?php
if (isset($_REQUEST['DT_MODS'])) $CORE_LOCAL->set('DiscountTypeClasses',$_REQUEST['DT_MODS']);
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
$discounts = AutoLoader::listModules('DiscountType');
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
$tmp_count = 0;
$save = array();
foreach($CORE_LOCAL->get("DiscountTypeClasses") as $r){
	$save[] = $r;
	if ($tmp_count == $CORE_LOCAL->get("DiscountTypeCount")-1)
		break;
	$tmp_count++;
}
InstallUtilities::paramSave('DiscountTypeClasses',$save);
?></td></tr><tr><td colspan=2>
<hr />	<p>Price Methods dictate how item prices are calculated.
	There's some overlap with Discount Types, but <i>generally</i>
	price methods deal with grouped items.</p></td></tr>
<tr><td>
<b>Number of Price Methods</b>:</td><td>
<?php
if (isset($_REQUEST['PM_COUNT']) && is_numeric($_REQUEST['PM_COUNT'])) $CORE_LOCAL->set('PriceMethodCount',$_REQUEST['PM_COUNT']);
if ($CORE_LOCAL->get("PriceMethodCount") == "") $CORE_LOCAL->set("PriceMethodCount",3);
if ($CORE_LOCAL->get("PriceMethodCount") <= 0) $CORE_LOCAL->set("PriceMethodCount",1);
printf("<input type=text size=4 name=PM_COUNT value=\"%d\" />",
	$CORE_LOCAL->get('PriceMethodCount'));
InstallUtilities::paramSave('PriceMethodCount',$CORE_LOCAL->get('PriceMethodCount'));
?>
</td></tr><tr><td>
<b>Price Method Mapping</b>:</td><td>
<?php
if (isset($_REQUEST['PM_MODS'])) $CORE_LOCAL->set('PriceMethodClasses',$_REQUEST['PM_MODS']);
if (!is_array($CORE_LOCAL->get('PriceMethodClasses'))){
	$CORE_LOCAL->set('PriceMethodClasses',
		array(
			'BasicPM',
			'GroupPM',
			'QttyEnforcedGroupPM'
		),True);
}
$pms = AutoLoader::listModules('PriceMethod');
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
$save = array();
$tmp_count = 0;
foreach($CORE_LOCAL->get("PriceMethodClasses") as $r){
	$save[] = $r;
	if ($tmp_count == $CORE_LOCAL->get("PriceMethodCount")-1)
		break;
	$tmp_count++;
}
InstallUtilities::paramSave('PriceMethodClasses',$save);
?></td></tr><tr><td colspan=2>
<hr />	<p>Special Department modules add extra steps to open rings in specific departments.
	Enter department number(s) that each module should apply to.</p>
</td></tr>
<tr><td>
<?php
$sdepts = AutoLoader::listModules('SpecialDept');
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
	$CORE_LOCAL->set('SpecialDeptMap',$sconf);
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
InstallUtilities::confsave('SpecialDeptMap',$saveStr);
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
