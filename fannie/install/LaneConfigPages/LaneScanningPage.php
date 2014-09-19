<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

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
// include's config.php
include('ini.php');
include('../util.php');
include_once('../../classlib2.0/InstallPage.php');

/**
    @class LaneScanningPage
    Class for the Global Lane install and config options Scanning page.
*/
class LaneScanningPage extends InstallPage {

    protected $title = 'CORE:PoS Global Lane Configuration: Scanning';
    protected $header = 'CORE:PoS Global Lane Configuration: Scanning';

    public $description = "
    Class for the Global Lane install and config options Scanning page.
    ";

    // This replaces the __construct() in the parent.
    public function __construct() {

        // To set authentication.
        FanniePage::__construct();

        $SRC = '../../src';
        // Link to a file of CSS by using a function.
        $this->add_css_file("$SRC/style.css");
        $this->add_css_file("$SRC/javascript/jquery-ui.css");
        $this->add_css_file("$SRC/css/install.css");

        // Link to a file of JS by using a function.
        $this->add_script("$SRC/javascript/jquery.js");
        $this->add_script("$SRC/javascript/jquery-ui.js");

    // __construct()
    }

    // If chunks of CSS are going to be added the function has to be
    //  redefined to return them.
    // If this is to override x.css draw_page() needs to load it after the add_css_file
    /**
      Define any CSS needed
      @return A CSS string
    function css_content(){
        $css ="";
        return $css;
    //css_content()
    }
    */

    // If chunks of JS are going to be added the function has to be
    //  redefined to return them.
    /**
      Define any javascript needed
      @return A javascript string
    function javascript_content(){
        $js ="";
        return $js;
    }
    */

    function body_content(){
        global $CORE_LOCAL, $CORE_PATH;

        ob_start();

        echo showLinkToFannie();
        echo showInstallTabsLane("Scanning Options", '');

?>

<form action=LaneScanningPage.php method=post>
<h1 class="install"><?php echo $this->header; ?></h1>

<?php
if (is_writable('../../config.php')){
    echo "<span style=\"color:green;\"><i>config.php</i> is writeable</span>";
}
else {
    echo "<span style=\"color:red;\"><b>Error</b>: config.php is not writeable</span>";
}
?>

<hr />

<h4 class="install">Special UPCs</h4>
<p class="ichunk2 ichunk3">
Special handling modules for UPCs that aren't products (e.g., coupons)
</p>
<b>Special UPCs</b>:
<p class="ichunk">
The enabled ones are highlighted in the multi-select below (initially none).
<br />Click and Ctrl-Click to select/de-select.
</p>
<select multiple size=10 name=SPECIAL_UPC_MODS[]>
<?php
if ($CORE_LOCAL->get('SpecialUpcClasses') === "") $CORE_LOCAL->set('SpecialUpcClasses',array());
if (isset($_REQUEST['SPECIAL_UPC_MODS'])) $CORE_LOCAL->set('SpecialUpcClasses',$_REQUEST['SPECIAL_UPC_MODS']);

$mods = array();
$dh = opendir($CORE_PATH.'lib/Scanning/SpecialUPCs');
while($dh && False !== ($f = readdir($dh))){
    if ($f == "." || $f == "..")
        continue;
    if (substr($f,-4) == ".php")
        $mods[] = rtrim($f,".php");
}

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
// this is different than lane save; uses array type
confsave('SpecialUpcClasses',$CORE_LOCAL->get('SpecialUpcClasses'));
?>
</select><br />
<hr />

<h4 class="install">Discount Types</h4>
<p class="ichunk2 ichunk3">
Discount type modules control how sale prices are calculated.
<br />The numeric code is in the item record; this controls what the code means.
<br />Check the dropdowns for all known Discount Types. Not all may currently be in use.
<br />Decide how many of them you want to use.
<br />Assign a number to (map) each one.
</p>
<b>Number of Discounts in Use</b>:
<?php
if (isset($_REQUEST['DT_COUNT']) && is_numeric($_REQUEST['DT_COUNT'])) $CORE_LOCAL->set('DiscountTypeCount',$_REQUEST['DT_COUNT']);
if ($CORE_LOCAL->get("DiscountTypeCount") == "") $CORE_LOCAL->set("DiscountTypeCount",5);
if ($CORE_LOCAL->get("DiscountTypeCount") <= 0) $CORE_LOCAL->set("DiscountTypeCount",1);
printf("<input type=text size=4 name=DT_COUNT value=\"%d\" />",
    $CORE_LOCAL->get('DiscountTypeCount'));
confsave('DiscountTypeCount',$CORE_LOCAL->get('DiscountTypeCount'));
?>
<br /><b>Discount Module Mapping</b>:<br />
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
        ));
}
$discounts = array();
$dh = opendir($CORE_PATH.'lib/Scanning/DiscountTypes');
while($dh && False !== ($f = readdir($dh))){
    if ($f == "." || $f == "..")
        continue;
    if (substr($f,-4) == ".php"){
        $discounts[] = rtrim($f,".php");
    }
}
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
// this is different than lane save; uses array type
confsave('DiscountTypeClasses',$CORE_LOCAL->get('DiscountTypeClasses'));
?>
<hr />

<h4 class="install">Price Methods</h4>
<p class="ichunk2 ichunk3">
Price Methods dictate how item prices are calculated.
<br />There's some overlap with Discount Types, but <i>generally</i>
price methods deal with grouped ("three for a dollar") items.
<br />The numeric code is in the item record; this controls what the code means.
<br />Check the dropdowns for all known Price Methods. Not all may currently be in use.
<br />Decide how many of them you want to use.
<br />Assign a number to (map) each one.
</p>
<b>Number of Price Methods in Use</b>:
<?php
if (isset($_REQUEST['PM_COUNT']) && is_numeric($_REQUEST['PM_COUNT'])) $CORE_LOCAL->set('PriceMethodCount',$_REQUEST['PM_COUNT']);
if ($CORE_LOCAL->get("PriceMethodCount") == "") $CORE_LOCAL->set("PriceMethodCount",3);
if ($CORE_LOCAL->get("PriceMethodCount") <= 0) $CORE_LOCAL->set("PriceMethodCount",1);
printf("<input type=text size=4 name=PM_COUNT value=\"%d\" />",
    $CORE_LOCAL->get('PriceMethodCount'));
confsave('PriceMethodCount',$CORE_LOCAL->get('PriceMethodCount'));
?>
<br /><b>Price Method Mapping</b>:<br />
<?php
if (isset($_REQUEST['PM_MODS'])) $CORE_LOCAL->set('PriceMethodClasses',$_REQUEST['PM_MODS']);
if (!is_array($CORE_LOCAL->get('PriceMethodClasses'))){
    $CORE_LOCAL->set('PriceMethodClasses',
        array(
            'BasicPM',
            'GroupPM',
            'QttyEnforcedGroupPM'
        ));
}
$pms = array();
$dh = opendir($CORE_PATH.'lib/Scanning/PriceMethods');
while($dh && False !== ($f = readdir($dh))){
    if ($f == "." || $f == "..")
        continue;
    if (substr($f,-4) == ".php"){
        $pms[] = rtrim($f,".php");
    }
}
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
// this is different than lane save; uses array type
confsave('PriceMethodClasses',$CORE_LOCAL->get('PriceMethodClasses'));
?>
<hr />
<input type=submit value="Save Changes" />
</form>

<?php

        return ob_get_clean();

    // body_content
    }

// LaneScanningPage  
}

FannieDispatch::conditionalExec(false);

?>
