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
// includes config.php
include('ini.php');
include('../util.php');
include_once('../../classlib2.0/InstallPage.php');

/**
    @class LaneSecurityPage
    Class for the Global Lane install and config options Security page.
*/
class LaneSecurityPage extends InstallPage {

    protected $title = 'CORE:PoS Global Lane Configuration: Security';
    protected $header = 'CORE:PoS Global Lane Configuration: Security';

    public $description = "
    Class for the Global Lane install and config options Security page.
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
        global $CORE_LOCAL;

        ob_start();

        echo showLinkToFannie();
        echo showInstallTabsLane("Security", '');

?>

<form action=LaneSecurityPage.php method=post>
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

<h4 class="install">Privileges</h4>
<p class="ichunk2 ichunk3" style="line-height: 1.5em;">
Configure the level of privileges required to perform certain operations.
<br />"All" means any cashier can perform the operation.
<br />"Admin only" means "Manager" privileges needed.
<br />See: Fannie Admin &gt; <a href="/IS4C/fannie/admin/Cashiers/CashierIndexPage.php"
target="_cashier">Cashier Management</a>
</p>

<p class="ichunk2"><b>Cancel Transaction</b>: <select name=PRIV_CANCEL>
<?php
if(isset($_REQUEST['PRIV_CANCEL'])) $CORE_LOCAL->set('SecurityCancel',$_REQUEST['PRIV_CANCEL']);
if ($CORE_LOCAL->get("SecurityCancel")=="") $CORE_LOCAL->set("SecurityCancel",20);
if ($CORE_LOCAL->get("SecurityCancel") == 30){
    echo "<option value=30 selected>Admin only</option>";
    echo "<option value=20>All</option>";
}
else {
    echo "<option value=30 >Admin only</option>";
    echo "<option value=20 selected>All</option>";
}
echo "</select>";
echo "</p>";
confsave('SecurityCancel',$CORE_LOCAL->get("SecurityCancel"));
?>

<p class="ichunk2"><b>
Suspend/Resume</b>: <select name=PRIV_SR>
<?php
if(isset($_REQUEST['PRIV_SR'])) $CORE_LOCAL->set('SecuritySR',$_REQUEST['PRIV_SR']);
if ($CORE_LOCAL->get("SecuritySR")=="") $CORE_LOCAL->set("SecuritySR",20);
if ($CORE_LOCAL->get("SecuritySR") == 30){
    echo "<option value=30 selected>Admin only</option>";
    echo "<option value=20>All</option>";
}
else {
    echo "<option value=30 >Admin only</option>";
    echo "<option value=20 selected>All</option>";
}
echo "</select>";
echo "</p>";
confsave('SecuritySR',$CORE_LOCAL->get("SecuritySR"));
?>

<p class="ichunk2"><b>Refund Item</b>: <select name=PRIV_REFUND>
<?php
if(isset($_REQUEST['PRIV_REFUND'])) $CORE_LOCAL->set('SecurityRefund',$_REQUEST['PRIV_REFUND']);
if ($CORE_LOCAL->get("SecurityRefund")=="") $CORE_LOCAL->set("SecurityRefund",20);
if ($CORE_LOCAL->get("SecurityRefund") == 30){
    echo "<option value=30 selected>Admin only</option>";
    echo "<option value=20>All</option>";
}
else {
    echo "<option value=30 >Admin only</option>";
    echo "<option value=20 selected>All</option>";
}
echo "</select>";
echo "</p>";
confsave('SecurityRefund',$CORE_LOCAL->get("SecurityRefund"));
?>
<hr />

<h4 class="install">Limits</h4>
<p class="ichunk2"><b>Void Limit</b>:&nbsp;
<?php
if (isset($_REQUEST['VOIDLIMIT'])) $CORE_LOCAL->set('VoidLimit',$_REQUEST['VOIDLIMIT']);
if ($CORE_LOCAL->get("VoidLimit")=="") $CORE_LOCAL->set("VoidLimit",0);
printf("<input type=text name=VOIDLIMIT value=\"%s\" />",$CORE_LOCAL->get('VoidLimit'));
echo " (in dollars, per transaction. Zero for unlimited).";
echo "</p>";
confsave('VoidLimit',"'".$CORE_LOCAL->get('VoidLimit')."'");
?>

<hr />
<input type=submit value="Save Changes" />
</form>



<?php

        return ob_get_clean();

    // body_content
    }

// LaneSecurityPage  
}

FannieDispatch::conditionalExec(false);

?>
