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

//ini_set('display_errors','1');
include('../config.php'); 
include('util.php');
include('db.php');
include_once('../classlib2.0/FannieAPI.php');

/**
    @class InstallMembershipPage
    Class for the Membership install and config options
*/
class InstallMembershipPage extends InstallPage {

    protected $title = 'Fannie: Membership Settings';
    protected $header = 'Fannie: Membership Settings';

    public $description = "
    Class for the Membership install and config options page.
    ";

    // This replaces the __construct() in the parent.
    public function __construct() {

        FanniePage::__construct();

        // Link to a file of CSS by using a function.
        $this->add_css_file("../src/style.css");
        $this->add_css_file("../src/javascript/jquery-ui.css");
        $this->add_css_file("../src/css/install.css");

        // Link to a file of JS by using a function.
        $this->add_script("../src/javascript/jquery.js");
        $this->add_script("../src/javascript/jquery-ui.js");

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
        global $FANNIE_URL,
            $FANNIE_EQUITY_DEPARTMENTS,
            $FANNIE_AR_DEPARTMENTS,
            $FANNIE_NAMES_PER_MEM,
            $FANNIE_MEMBER_MODULES,
            $FANNIE_MEMBER_UPC_PREFIX,
            $FANNIE_SERVER,$FANNIE_SERVER_DBMS, $FANNIE_TRANS_DB,$FANNIE_SERVER_USER, $FANNIE_SERVER_PW;

        ob_start();

        echo showInstallTabs("Members");
?>

<form action=InstallMembershipPage.php method=post>
<h1 class="install"><?php echo $this->header; ?></h1>
<?php
if (is_writable('../config.php')){
    echo "<span style=\"color:green;\"><i>config.php</i> is writeable</span>";
}
else {
    echo "<span style=\"color:red;\"><b>Error</b>: config.php is not writeable</span>";
}
?>
<hr />

<p class="ichunk2"><b>Names per membership: </b>
<?php
if (!isset($FANNIE_NAMES_PER_MEM)) $FANNIE_NAMES_PER_MEM = 1;
if (isset($_REQUEST['FANNIE_NAMES_PER_MEM'])) $FANNIE_NAMES_PER_MEM = $_REQUEST['FANNIE_NAMES_PER_MEM'];
confset('FANNIE_NAMES_PER_MEM',$FANNIE_NAMES_PER_MEM);
echo "<input type=text size=3 name=FANNIE_NAMES_PER_MEM value=\"$FANNIE_NAMES_PER_MEM\" />";
?>
</p>

<hr />
<h4 class="install">Equity/Store Charge</h4>
<p class="ichunk2"><b>Equity Department(s): </b>
<?php
if (!isset($FANNIE_EQUITY_DEPARTMENTS)) $FANNIE_EQUITY_DEPARTMENTS = '';
if (isset($_REQUEST['FANNIE_EQUITY_DEPARTMENTS'])) $FANNIE_EQUITY_DEPARTMENTS=$_REQUEST['FANNIE_EQUITY_DEPARTMENTS'];
confset('FANNIE_EQUITY_DEPARTMENTS',"'$FANNIE_EQUITY_DEPARTMENTS'");
printf("<input type=\"text\" name=\"FANNIE_EQUITY_DEPARTMENTS\" value=\"%s\" />",$FANNIE_EQUITY_DEPARTMENTS);
?>
</p>

<p class="ichunk2"><b>Store Charge Department(s): </b>
<?php
if (!isset($FANNIE_AR_DEPARTMENTS)) $FANNIE_AR_DEPARTMENTS = '';
if (isset($_REQUEST['FANNIE_AR_DEPARTMENTS'])) $FANNIE_AR_DEPARTMENTS=$_REQUEST['FANNIE_AR_DEPARTMENTS'];
confset('FANNIE_AR_DEPARTMENTS',"'$FANNIE_AR_DEPARTMENTS'");
printf("<input type=\"text\" name=\"FANNIE_AR_DEPARTMENTS\" value=\"%s\" />",$FANNIE_AR_DEPARTMENTS);
?>
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
<select multiple name="FANNIE_MEMBER_MODULES[]" size="10">
<?php
$dh = opendir("../mem/modules");
$tmp = array();
while(($file = readdir($dh)) !== False){
    if (substr($file,-4) == ".php")
        $tmp[] = substr($file,0,strlen($file)-4);   
}
sort($tmp);
foreach($tmp as $module){
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
<?php
if (!isset($FANNIE_MEMBER_UPC_PREFIX)) $FANNIE_MEMBER_UPC_PREFIX = '';
if (isset($_REQUEST['FANNIE_MEMBER_UPC_PREFIX'])) $FANNIE_MEMBER_UPC_PREFIX=$_REQUEST['FANNIE_MEMBER_UPC_PREFIX'];
confset('FANNIE_MEMBER_UPC_PREFIX',"'$FANNIE_MEMBER_UPC_PREFIX'");
printf("<input type=\"text\" name=\"FANNIE_MEMBER_UPC_PREFIX\" value=\"%s\" />",$FANNIE_MEMBER_UPC_PREFIX);
?>

<hr />
<input type=submit value="Re-run" />
</form>
<?php
$sql = db_test_connect($FANNIE_SERVER,$FANNIE_SERVER_DBMS,
        $FANNIE_TRANS_DB,$FANNIE_SERVER_USER,
        $FANNIE_SERVER_PW);
if (!$sql) {
    echo "<span style='color:red; font-size:1.5em;'>Cannot connect to database to refresh views.</span>";
}
else {
    echo "Refreshing database views ... ";
    $this->recreate_views($sql);
    echo "done.";
}

        return ob_get_clean();

    // body_content
    }

    // rebuild views that depend on ar & equity
    // department definitions
    function recreate_views($con){
        global $FANNIE_TRANS_DB,$FANNIE_OP_DB,$FANNIE_SERVER_DBMS;

        $con->query("DROP VIEW ar_history_today_sum",$FANNIE_TRANS_DB);
        create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'ar_history_today_sum','trans');

        $con->query("DROP VIEW ar_live_balance",$FANNIE_TRANS_DB);
        create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'ar_live_balance','trans');

        $con->query("DROP VIEW stockSumToday",$FANNIE_TRANS_DB);
        create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'stockSumToday','trans');

        $con->query("DROP VIEW equity_live_balance",$FANNIE_TRANS_DB);
        create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'equity_live_balance','trans');

        if ($con->tableExists('newBalanceStockToday_test')) {
            $con->query("DROP VIEW newBalanceStockToday_test",$FANNIE_TRANS_DB);
            create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                    'newBalanceStockToday_test','trans');
        }

        $con->query("DROP VIEW dheader",$FANNIE_TRANS_DB);
        create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'dheader','trans');

        $con->query("DROP VIEW ar_history_today",$FANNIE_TRANS_DB);
        create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'ar_history_today','trans');
    }

// InstallMembershipPage
}

FannieDispatch::conditionalExec(false);

?>
