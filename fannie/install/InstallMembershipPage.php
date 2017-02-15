<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

//ini_set('display_errors','1');
include(dirname(__FILE__) . '/../config.php'); 
if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__) . '/../classlib2.0/FannieAPI.php');
}
if (!function_exists('confset')) {
    include(dirname(__FILE__) . '/util.php');
}
if (!function_exists('dropDeprecatedStructure')) {
    include(dirname(__FILE__) . '/db.php');
}

/**
    @class InstallMembershipPage
    Class for the Membership install and config options
*/
class InstallMembershipPage extends \COREPOS\Fannie\API\InstallPage {

    protected $title = 'Fannie: Membership Settings';
    protected $header = 'Fannie: Membership Settings';

    public $description = "
    Class for the Membership install and config options page.
    ";

    function body_content()
    {
        include(dirname(__FILE__) . '/../config.php');
        ob_start();

        echo showInstallTabs("Members");
?>

<form action=InstallMembershipPage.php method=post>
<?php
echo $this->writeCheck(dirname(__FILE__) . '/../config.php');
?>
<hr />

<p class="ichunk2"><b>Names per membership: </b>
<?php echo installTextField('FANNIE_NAMES_PER_MEM', $FANNIE_NAMES_PER_MEM, 1); ?>
</p>

<hr />
<h4 class="install">Equity/Store Charge</h4>
<p class="ichunk2"><b>Equity Department(s): </b>
<?php echo installTextField('FANNIE_EQUITY_DEPARTMENTS', $FANNIE_EQUITY_DEPARTMENTS, ''); ?>
</p>

<p class="ichunk2"><b>Store Charge Department(s): </b>
<?php echo installTextField('FANNIE_AR_DEPARTMENTS', $FANNIE_AR_DEPARTMENTS, ''); ?>
</p>

<hr />
<h4 class="install">Membership Information Modules</h4>
The Member editing interface displayed after you select a member at:
<br /><a href="<?php echo $FANNIE_URL; ?>mem/MemberSearchPage.php" target="_mem"><?php echo $FANNIE_URL; ?>mem/MemberSearchPage.php</a>
<br />consists of fields grouped in several sections, called modules, listed below.
<br />The enabled (active) ones are selected/highlighted. May initially be none.
<br />
<br /><b>Available Modules</b> <br />
<?php
if (!isset($FANNIE_MEMBER_MODULES)) $FANNIE_MEMBER_MODULES = array('ContactInfo','MemType');
if (isset($_REQUEST['FANNIE_MEMBER_MODULES'])){
    $FANNIE_MEMBER_MODULES = array();
    foreach($_REQUEST['FANNIE_MEMBER_MODULES'] as $m)
        $FANNIE_MEMBER_MODULES[] = $m;
}
$saveStr = 'array(';
foreach($FANNIE_MEMBER_MODULES as $m)
    $saveStr .= '"'.$m.'",';
$saveStr = rtrim($saveStr,",").")";
confset('FANNIE_MEMBER_MODULES',$saveStr);
?>
<select multiple name="FANNIE_MEMBER_MODULES[]" size="10" class="form-control">
<?php
$modules = FannieAPI::listModules('\COREPOS\Fannie\API\member\MemberModule');
sort($modules);
foreach($modules as $module){
    printf("<option %s>%s</option>",(in_array($module,$FANNIE_MEMBER_MODULES)?'selected':''),$module);
}
?>
</select><br />
Click or ctrl-Click or shift-Click to select/deselect modules for enablement.
<br /><br />
<a href="InstallMemModDisplayPage.php">Adjust Module Display Order</a>

<hr />
<h4 class="install">Member Cards</h4>
Member Card UPC Prefix: 
<?php echo installTextField('FANNIE_MEMBER_UPC_PREFIX', $FANNIE_MEMBER_UPC_PREFIX, ''); ?>
<hr />
<h4 class="install">Lane On-Screen Display</h4>
<div id="blueline-input-div">
This controls what is displayed on the upper left of the cashier's screen after a member
is selected.
<?php echo installTextField('FANNIE_BLUELINE_TEMPLATE', $FANNIE_BLUELINE_TEMPLATE, ''); ?>
<a href="" class="btn btn-default btn-xs"
    onclick="$('#blueline-input-div input').focus().val($('#blueline-input-div input').val() + '{{ACCOUNTNO}}'); return false;">
    Account#
</a>
<a href="" class="btn btn-default btn-xs"
    onclick="$('#blueline-input-div input').focus().val($('#blueline-input-div input').val() + '{{ACCOUNTTYPE}}'); return false;">
    Account Type
</a>
<a href="" class="btn btn-default btn-xs"
    onclick="$('#blueline-input-div input').focus().val($('#blueline-input-div input').val() + '{{FIRSTNAME}}'); return false;">
    First Name
</a>
<a href="" class="btn btn-default btn-xs"
    onclick="$('#blueline-input-div input').focus().val($('#blueline-input-div input').val() + '{{LASTNAME}}'); return false;">
    Last Name
</a>
<a href="" class="btn btn-default btn-xs"
    onclick="$('#blueline-input-div input').focus().val($('#blueline-input-div input').val() + '{{FIRSTINITIAL}}'); return false;">
    First Initial
</a>
<a href="" class="btn btn-default btn-xs"
    onclick="$('#blueline-input-div input').focus().val($('#blueline-input-div input').val() + '{{LASTINITIAL}}'); return false;">
    Last Initial
</a>
</div>
<hr />
<h4 class="install">Data Mode</h4>
<div>
Choose how customer data is stored in the database. Using "classic" is highly
recommended in production environments. The "new" mode should not be without
a developer and/or database administrator on hand to help with potential bugs.
<?php
$modes = array(
    1 => 'New',
    0 => 'Classic',
);
echo installSelectField('FANNIE_CUST_SCHEMA', $FANNIE_CUST_SCHEMA, $modes, 0);
?>
<hr />
<p>
    <button type="submit" class="btn btn-default">Save Configuration</button>
</p>
</form>
<?php
$sql = db_test_connect($FANNIE_SERVER,$FANNIE_SERVER_DBMS,
        $FANNIE_TRANS_DB,$FANNIE_SERVER_USER,
        $FANNIE_SERVER_PW);
if (!$sql) {
    echo "<div class='alert alert-danger'>Cannot connect to database to refresh views.</div>";
} else {
    echo "Refreshing database views ... ";
    $this->recreate_views($sql);
    echo "done.";
}

        return ob_get_clean();
    // body_content
    }

    // rebuild views that depend on ar & equity
    // department definitions
    function recreate_views($con)
    {
        $db_name = $this->config->get('TRANS_DB');

        $con->query("DROP VIEW ar_history_today",$db_name);
        $model = new ArHistoryTodayModel($con);
        $model->createIfNeeded($db_name);

        $con->query("DROP VIEW ar_history_today_sum",$db_name);
        $model = new ArHistoryTodaySumModel($con);
        $model->createIfNeeded($db_name);

        $con->query("DROP VIEW ar_live_balance",$db_name);
        $model = new ArLiveBalanceModel($con);
        $model->addExtraDB($this->config->get('OP_DB'));
        $model->createIfNeeded($db_name);

        $con->query("DROP VIEW stockSumToday",$db_name);
        $model = new StockSumTodayModel($con);
        $model->createIfNeeded($db_name);

        $con->query("DROP VIEW equity_live_balance",$db_name);
        $model = new EquityLiveBalanceModel($con);
        $model->addExtraDB($this->config->get('OP_DB'));
        $model->createIfNeeded($db_name);
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
    }

// InstallMembershipPage
}

FannieDispatch::conditionalExec();

