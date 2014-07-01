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
    @class LaneAdditionalConfigPage
    Class for the Global Lane install and config options AdditionalConfiguration page.
*/
class LaneAdditionalConfigPage extends InstallPage {

    protected $title = 'CORE:PoS Global Lane Configuration: Additional Configuration';
    protected $header = 'CORE:PoS Global Lane Configuration: Additional Configuration';

    public $description = "
    Class for the Global Lane install and config options Additional Configuration page.
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
        global $FANNIE_COUNTRY;

        ob_start();

        echo showLinkToFannie();
        echo showInstallTabsLane("Additional Configuration", '');

?>

<form action=LaneAdditionalConfigPage.php method=post>
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

<h4 class="install">PoS Display</h4>

<p class="ichunk2"><b>Browser only</b>: <select name=BROWSER_ONLY>
<?php
if ($CORE_LOCAL->get('browserOnly')==="") $CORE_LOCAL->set('browserOnly','1');
if (isset($_REQUEST['BROWSER_ONLY'])) $CORE_LOCAL->set('browserOnly',$_REQUEST['BROWSER_ONLY']);
if ($CORE_LOCAL->get('browserOnly') == 1){
    echo "<option value=1 selected>Yes</option>";
    echo "<option value=0>No</option>";
}
else{
    echo "<option value=1>Yes</option>";
    echo "<option value=0 selected>No</option>";
}
echo "</select>";
confsave('browserOnly',$CORE_LOCAL->get('browserOnly'));
?>
<br />
If Yes, the "exit" button on the login screen attempts to close the window.
</p>

<p class="ichunk2"><b>Lock screen on idle</b>: <select name=LOCKSCREEN>
<?php
if ($CORE_LOCAL->get('lockScreen')==="") $CORE_LOCAL->set('lockScreen','1');
if (isset($_REQUEST['LOCKSCREEN'])) $CORE_LOCAL->set('lockScreen',$_REQUEST['LOCKSCREEN']);
if ($CORE_LOCAL->get("lockScreen") == 1){
    echo "<option value=1 selected>Yes</option>";
    echo "<option value=0 >No</option>";
}
else {
    echo "<option value=1>Yes</option>";
    echo "<option value=0 selected>No</option>";
}
echo "</select>";
echo "</p>";
confsave('lockScreen',$CORE_LOCAL->get('lockScreen'));
?>

<p class="ichunk2"><b>Alert Bar</b>:
<?php
if ($CORE_LOCAL->get('alertBar')==="")
    $CORE_LOCAL->set('alertBar','Warning!');
if (isset($_REQUEST['ALERT']))
    $CORE_LOCAL->set('alertBar',$_REQUEST['ALERT']);
printf("<input size=40 type=text name=ALERT value=\"%s\" />",$CORE_LOCAL->get('alertBar'));
echo "<br />Heading on the Alert popup.";
echo "</p>";
confsave('alertBar',"'".$CORE_LOCAL->get('alertBar')."'");
?>

<p class="ichunk2"><b>Footer Modules</b> (left to right):
<br />These display in a row below the lines of items in the transaction.
<?php
$footer_mods = array();
// get current settings
$current_mods = $CORE_LOCAL->get("FooterModules");
// replace w/ form post if needed
// fill in defaults if missing
if (isset($_REQUEST['FOOTER_MODS']))
    $current_mods = $_REQUEST['FOOTER_MODS'];
elseif(!is_array($current_mods) || count($current_mods) != 5){
    $current_mods = array(
    'SavedOrCouldHave',
    'TransPercentDiscount',
    'MemSales',
    'EveryoneSales',
    'MultiTotal'
    );
}
$dh = opendir($CORE_PATH.'lib/FooterBoxes/');
while(False !== ($f = readdir($dh))){
    if ($f == "." || $f == "..")
        continue;
    if (substr($f,-4) == ".php"){
        $footer_mods[] = rtrim($f,".php");
    }
}
for($i=0;$i<5;$i++){
    echo '<br /><select name="FOOTER_MODS[]">';
    foreach($footer_mods as $fm){
        printf('<option %s>%s</option>',
            ($current_mods[$i]==$fm?'selected':''),$fm);
    }
    echo '</select>';
}
$saveStr = "array(";
foreach($current_mods as $m)
    $saveStr .= "'".$m."',";
$saveStr = rtrim($saveStr,",").")";
echo "</p>";
confsave('FooterModules',$saveStr);
?>
<!-- /PoS Display -->
<hr />

<h4 class="install">Store</h4>
<p class="ichunk2"><b>Store</b>:
<?php
if ($CORE_LOCAL->get('store')==="")
    $CORE_LOCAL->set('store','utopia');
if (isset($_REQUEST['STORE']))
    $CORE_LOCAL->set('store',$_REQUEST['STORE']);
printf("<input type=text name=STORE value=\"%s\" />",$CORE_LOCAL->get('store'));
echo "<br />In theory, any hard-coded, store specific sequences should be blocked off based on the store setting.
Actual adherence to this principle in the code is less than perfect.";
echo "</p>";
confsave('store',"'".$CORE_LOCAL->get('store')."'");
?>
<!-- /Misc -->
<hr />

<h4 class="install">Discounts</h4>
<p class="ichunk2"><b>Discounts enabled</b>: <select name=DISCOUNTS>
<?php
if ($CORE_LOCAL->get('discountEnforced')==="") $CORE_LOCAL->set('discountEnforced','1');
if(isset($_REQUEST['DISCOUNTS'])) $CORE_LOCAL->set('discountEnforced',$_REQUEST['DISCOUNTS']);
if ($CORE_LOCAL->get("discountEnforced") == 1){
    echo "<option value=1 selected>Yes</option>";
    echo "<option value=0 >No</option>";
}
else {
    echo "<option value=1>Yes</option>";
    echo "<option value=0 selected>No</option>";
}
echo "</select>";
echo "<br />If Yes, members get a percentage discount as specified in custdata.";
echo "</p>";
confsave('discountEnforced',$CORE_LOCAL->get('discountEnforced'));
?>

<p class="ichunk2"><b>Line Item Discount (member)</b>: 
<?php
if ($CORE_LOCAL->get('LineItemDiscountMem')==="") $CORE_LOCAL->set('LineItemDiscountMem','0');
if(isset($_REQUEST['LD_MEM'])) $CORE_LOCAL->set('LineItemDiscountMem',$_REQUEST['LD_MEM']);
printf("<input type=text name=LD_MEM value=\"%f\" />",$CORE_LOCAL->get('LineItemDiscountMem'));
echo " (percentage; 0.05 =&gt; 5%)";
echo "</p>";
confsave('LineItemDiscountMem',"'".$CORE_LOCAL->get('LineItemDiscountMem')."'");
?>

<p class="ichunk2"><b>Line Item Discount (non-member)</b>: 
<?php
if ($CORE_LOCAL->get('LineItemDiscountNonMem')==="") $CORE_LOCAL->set('LineItemDiscountNonMem','0');
if(isset($_REQUEST['LD_NONMEM'])) $CORE_LOCAL->set('LineItemDiscountNonMem',$_REQUEST['LD_NONMEM']);
printf("<input type=text name=LD_NONMEM value=\"%f\" />",$CORE_LOCAL->get('LineItemDiscountNonMem'));
echo " (percentage; 0.05 =&gt; 5%)";
echo "</p>";
confsave('LineItemDiscountNonMem',"'".$CORE_LOCAL->get('LineItemDiscountNonMem')."'");
?>
<hr />

<h4 class="install">Members</h4>
<p class="ichunk2"><b>Default Non-member #</b>: 
<?php
if ($CORE_LOCAL->get('defaultNonMem')==="")
    $CORE_LOCAL->set('defaultNonMem','11');
if(isset($_REQUEST['NONMEM']))
    $CORE_LOCAL->set('defaultNonMem',$_REQUEST['NONMEM']);
printf("<input type=text name=NONMEM value=\"%s\" />",$CORE_LOCAL->get('defaultNonMem'));
echo "<br />Normally a single account number is used for most if not all non-member
transactions. Specify that account number here.";
echo "</p>";
confsave('defaultNonMem',"'".$CORE_LOCAL->get('defaultNonMem')."'");
?>

<p class="ichunk2"><b>Visiting Member #</b>: 
<?php
if ($CORE_LOCAL->get('visitingMem')==="")
    $CORE_LOCAL->set('visitingMem','9');
if(isset($_REQUEST['VISMEM']))
    $CORE_LOCAL->set('visitingMem',$_REQUEST['VISMEM']);
printf("<input type=text name=VISMEM value=\"%s\" />",$CORE_LOCAL->get('visitingMem'));
echo "<br />This account provides members of other co-ops with member pricing
but no other benefits. Leave blank to disable.";
echo "</p>";
confsave('visitingMem',"'".$CORE_LOCAL->get('visitingMem')."'");
?>

<p class="ichunk2"><b>Show non-member account in searches</b>: <select name=SHOW_NONMEM>
<?php
if ($CORE_LOCAL->get('memlistNonMember')==="")
    $CORE_LOCAL->set('memlistNonMember','0');
if(isset($_REQUEST['SHOW_NONMEM']))
    $CORE_LOCAL->set('memlistNonMember',$_REQUEST['SHOW_NONMEM']);
if ($CORE_LOCAL->get("memlistNonMember") == 1){
    echo "<option value=1 selected>Yes</option>";
    echo "<option value=0 >No</option>";
    echo "</select>";
}
else {
    echo "<option value=1>Yes</option>";
    echo "<option value=0 selected>No</option>";
    echo "</select>";
}
echo "</p>";
confsave('memlistNonMember',$CORE_LOCAL->get('memlistNonMember'));
?>
<hr />

<?php
if (isset($FANNIE_COUNTRY) && $FANNIE_COUNTRY == "CA") {
    $Checks = 'Cheques'; $Check = 'Cheque';
    $checks = 'cheques'; $check = 'cheque';
} else {
    $Checks = 'Checks'; $Check = 'Check';
    $checks = 'checks'; $check = 'check';
}
echo "<h4 class='install'>$Checks</h4>";
echo "<p class='ichunk2'><b>Allow members to write $checks over purchase amount</b>: <select name=OVER>";
if ($CORE_LOCAL->get('cashOverLimit')==="") $CORE_LOCAL->set('cashOverLimit','0');
if(isset($_REQUEST['OVER'])) $CORE_LOCAL->set('cashOverLimit',$_REQUEST['OVER']);
if ($CORE_LOCAL->get("cashOverLimit") == 1){
    echo "<option value=1 selected>Yes</option>";
    echo "<option value=0 >No</option>";
}
else {
    echo "<option value=1>Yes</option>";
    echo "<option value=0 selected>No</option>";
}
echo "</select>";
echo "</p>";
confsave('cashOverLimit',$CORE_LOCAL->get('cashOverLimit'));
?>

<?php
echo "<p class='ichunk2'><b>$Check over limit</b>: ";
if ($CORE_LOCAL->get('dollarOver')==="")
    $CORE_LOCAL->set('dollarOver','0');
if(isset($_REQUEST['OVER_LIMIT']))
    $CORE_LOCAL->set('dollarOver',$_REQUEST['OVER_LIMIT']);
printf("<input type=text size=4 name=OVER_LIMIT value=\"%s\" />",$CORE_LOCAL->Get('dollarOver'));
echo "<br />Maximum amount over purchase. 0 means no limit.";
echo "</p>";
confsave('dollarOver',$CORE_LOCAL->get('dollarOver'));
?>
<hr />

<h4 class="install">Receipts</h4>
<p class="ichunk2"><b>Enable receipts</b>: <select name=PRINT>
<?php
if ($CORE_LOCAL->get('print')==="") $CORE_LOCAL->set('print','1');
if(isset($_REQUEST['PRINT'])) $CORE_LOCAL->set('print',$_REQUEST['PRINT']);
if ($CORE_LOCAL->get("print") == 1){
    echo "<option value=1 selected>Yes</option>";
    echo "<option value=0>No</option>";
}
else {
    echo "<option value=1 >Yes</option>";
    echo "<option value=0 selected>No</option>";
}
echo "</select>";
echo "</p>";
confsave('print',$CORE_LOCAL->get("print"));
?>

<p class="ichunk2"><b>Use new receipt</b>: <select name=NEWRECEIPT>
<?php
if ($CORE_LOCAL->get('newReceipt')==="") $CORE_LOCAL->set('newReceipt','0');
if (isset($_REQUEST['NEWRECEIPT'])) $CORE_LOCAL->set('newReceipt',$_REQUEST['NEWRECEIPT']);
if ($CORE_LOCAL->get("newReceipt") == 1){
    echo "<option value=1 selected>Yes</option>";
    echo "<option value=0>No</option>";
}
else {
    echo "<option value=1 >Yes</option>";
    echo "<option value=0 selected>No</option>";
}
echo "</select>";
echo "<br />The new receipt groups items by category; the old one just lists them in order.";
echo "</p>";
confsave('newReceipt',$CORE_LOCAL->get("newReceipt"));
?>
<hr />

<h4 class="install">Receipt printer</h4>
<p class="ichunk2"><b>Printer port</b>:
<?php
if ($CORE_LOCAL->get('printerPort')==="")
    $CORE_LOCAL->set('printerPort','fake.txt');
if(isset($_REQUEST['PPORT']))
    $CORE_LOCAL->set('printerPort',$_REQUEST['PPORT']);
printf("<input type=text name=PPORT value=\"%s\" />",$CORE_LOCAL->get('printerPort'));
echo "<br />Path to the printer. Common ports are LPT1: (windows) and /dev/lp0 (linux).
Can also print to a text file if it's just a regular file name.";
echo "</p>";
confsave('printerPort',"'".$CORE_LOCAL->get('printerPort')."'");
?>
<hr />

<h4 class="install">Scanner/scale</h4>
<p class="ichunk2"><b>Scanner/scale port</b>:
<?php
if ($CORE_LOCAL->get('scalePort')==="") $CORE_LOCAL->set('scalePort','');
if(isset($_REQUEST['SPORT'])) $CORE_LOCAL->set('scalePort',$_REQUEST['SPORT']);
printf("<input type=text name=SPORT value=\"%s\" />",$CORE_LOCAL->get('scalePort'));
echo "<br />Path to the scanner scale. Common values are COM1 (windows) and /dev/ttyS0 (linux).";
echo "</p>";
confsave('scalePort',"'".$CORE_LOCAL->get('scalePort')."'");
?>

<b>Scanner/scale driver</b>:
<?php
if ($CORE_LOCAL->get('scaleDriver')==="") $CORE_LOCAL->set('scaleDriver','NewMagellan');
if(isset($_REQUEST['SDRIVER'])) $CORE_LOCAL->set('scaleDriver',$_REQUEST['SDRIVER']);
printf("<input type=text name=SDRIVER value=\"%s\" />",$CORE_LOCAL->get('scaleDriver'));
echo '<br />The name of your scale driver. Known good values include "ssd" and "NewMagellan".';
echo "</p>";
confsave('scaleDriver',"'".$CORE_LOCAL->get('scaleDriver')."'");
?>
<hr />

<h4 class="install">Customer-facing display</h4>
<p class="ichunk2 ichunk3">
Touchscreen keys and menus really don't need to appear on
the customer-facing display.
<br />Experimental feature where one
window always shows the item listing. Very alpha.
</p>
<p class="ichunk2"><b>Enable onscreen keys</b>: <select name=SCREENKEYS>
<?php
if ($CORE_LOCAL->get('touchscreen')==="")
    $CORE_LOCAL->set('touchscreen',False);
if(isset($_REQUEST['SCREENKEYS'])){
    $CORE_LOCAL->set('touchscreen',($_REQUEST['SCREENKEYS']==1)?True:False);
}
if ($CORE_LOCAL->get('touchscreen')){
    echo "<option value=1 selected>Yes</option>";
    echo "<option value=0 >No</option>";
    echo "</select>";
    confsave('touchscreen',True);
}
else {
    echo "<option value=1 >Yes</option>";
    echo "<option value=0 selected>No</option>";
    echo "</select>";
    confsave('touchscreen',False);
}
echo "</p>";
?>

<p class="ichunk2"><b>Separate customer display</b>: <select name=CUSTDISPLAY>
<?php
if ($CORE_LOCAL->get('CustomerDisplay')==="")
    $CORE_LOCAL->set('CustomerDisplay','1');
if(isset($_REQUEST['CUSTDISPLAY']))
    $CORE_LOCAL->set('CustomerDisplay',$_REQUEST['CUSTDISPLAY']);
if ($CORE_LOCAL->get('CustomerDisplay')){
    echo "<option value=1 selected>Yes</option>";
    echo "<option value=0 >No</option>";
}
else {
    echo "<option value=1 >Yes</option>";
    echo "<option value=0 selected>No</option>";
}
echo "</select>";
echo "</p>";
confsave('CustomerDisplay',$CORE_LOCAL->get('CustomerDisplay'));
?>
<hr />

<h4 class="install">Paycards</h4>
<p class="ichunk2 ichunk3">
Integrated card processing configuration is included for the sake
of completeness.
<br />The modules themselves require individual configuration, too.
</p>
<p class="ichunk2"><b>Integrated Credit Cards</b>: <select name=INT_CC>
<?php
if ($CORE_LOCAL->get('CCintegarte')==="") $CORE_LOCAL->set('CCintegrate','0');
if(isset($_REQUEST['INT_CC'])) $CORE_LOCAL->set('CCintegrate',$_REQUEST['INT_CC']);
if ($CORE_LOCAL->get('CCintegrate') == 1){
    echo "<option value=1 selected>Yes</option>";
    echo "<option value=0>No</option>";
}
else {
    echo "<option value=1>Yes</option>";
    echo "<option value=0 selected>No</option>";
}
echo "</select>";
echo "</p>";
confsave('CCintegrate',$CORE_LOCAL->get('CCintegrate'));
?>

<p class="ichunk2"><b>Integrated Gift Cards</b>: <select name=INT_GC>
<?php
if ($CORE_LOCAL->get('gcIntegarte')==="") $CORE_LOCAL->set('gcIntegrate','0');
if(isset($_REQUEST['INT_GC'])) $CORE_LOCAL->set('gcIntegrate',$_REQUEST['INT_GC']);
if ($CORE_LOCAL->get('gcIntegrate') == 1){
    echo "<option value=1 selected>Yes</option>";
    echo "<option value=0>No</option>";
}
else {
    echo "<option value=1>Yes</option>";
    echo "<option value=0 selected>No</option>";
}
echo "</select>";
echo "</p>";
confsave('gcIntegrate',$CORE_LOCAL->get('gcIntegrate'));
?>

<p class="ichunk2"><b>Enabled paycard modules</b>:
<br />The enabled ones are highlighted in the multi-select below (initially none).
<br />Click and Ctrl-Click to select/de-select. 
<br />The modules themselves require individual configuration.
<br />
<select multiple size=10 name=PAY_MODS[]>
<?php
if ($CORE_LOCAL->get('RegisteredPaycardClasses')==="") $CORE_LOCAL->set('RegisteredPaycardClasses',array());
if (isset($_REQUEST['PAY_MODS'])) $CORE_LOCAL->set('RegisteredPaycardClasses',$_REQUEST['PAY_MODS']);

$mods = array();
$dh = opendir($CORE_PATH.'plugins/Paycards');
while($dh && False !== ($f = readdir($dh))){
    if ($f == "." || $f == ".." || $f == "BasicCCModule.php")
        continue;
    if (substr($f,-4) == ".php")
        $mods[] = rtrim($f,".php");
}

foreach($mods as $m){
    $selected = "";
    foreach($CORE_LOCAL->get("RegisteredPaycardClasses") as $r){
        if ($r == $m){
            $selected = "selected";
            break;
        }
    }
    echo "<option $selected>$m</option>";
}
// this save is different than the lane version!
confsave('RegisteredPaycardClasses',$CORE_LOCAL->get('RegisteredPaycardClasses'));
echo "</select>";
echo "</p>";
?>

<p class="ichunk2"><b>Signature Required Limit</b>:
<?php
if (isset($_REQUEST['CCSigLimit'])) $CORE_LOCAL->set('CCSigLimit',$_REQUEST['CCSigLimit']);
if ($CORE_LOCAL->get('CCSigLimit')=="") $CORE_LOCAL->set('CCSigLimit',0.00);
printf(" \$<input size=4 type=text name=CCSigLimit value=\"%s\" />",$CORE_LOCAL->get('CCSigLimit'));
echo "<br />Require customer signature if transaction total is more than this amount.";
echo "<br />0 means signature never required.";
echo "</p>";
confsave('CCSigLimit',$CORE_LOCAL->get('CCSigLimit'));
?>

<p class="ichunk2"><b>Signature Capture Device</b>:
<?php
if ($CORE_LOCAL->get('SigCapture')=="")
    $CORE_LOCAL->set('SigCapture','');
if (isset($_REQUEST['SigCapture']))
    $CORE_LOCAL->set('SigCapture',$_REQUEST['SigCapture']);
printf("<input size=4 type=text name=SigCapture value=\"%s\" />",$CORE_LOCAL->get('SigCapture'));
echo "<i> (blank for none)</i>";
echo "</p>";
confsave('SigCapture',"'".$CORE_LOCAL->get('SigCapture')."'");
?>
<hr />

<input type=submit value="Save Changes" />
</form>

<?php

        return ob_get_clean();

    // body_content
    }

// LaneAdditionalConfigurationPage  
}

FannieDispatch::conditionalExec(false);

?>
