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

ini_set('display_errors','1');
include(dirname(__FILE__) . '/../../config.php'); 
if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__) . '/../../classlib2.0/FannieAPI.php');
}
if (!function_exists('confset')) {
    include(dirname(__FILE__) . '/../util.php');
}
if (!function_exists('create_if_needed')) {
    include(dirname(__FILE__) . '/../db.php');
}

/**
    @class InstallSampleDataPage
    Class for the SampleData install and config options
*/
class InstallSampleDataPage extends \COREPOS\Fannie\API\InstallPage {

    protected $title = 'Fannie: Sample Data';
    protected $header = 'Fannie: Sample Data';

    public $description = "
    Class for the Sample Data install page.
    ";
    public $themed = true;

    // This replaces the __construct() in the parent.
    public function __construct() {

        // To set authentication.
        FanniePage::__construct();

        // Link to a file of CSS by using a function.
        $this->add_css_file("../../src/style.css");
        $this->add_css_file("../../src/javascript/jquery-ui.css");
        $this->add_css_file("../../src/css/install.css");

        // Link to a file of JS by using a function.
        $this->add_script("../../src/javascript/jquery.js");
        $this->add_script("../../src/javascript/jquery-ui.js");

    // __construct()
    }

    // If chunks of CSS are going to be added the function has to be
    //  redefined to return them.
    // If this is to override x.css draw_page() needs to load it after the add_css_file
    /**
      Define any CSS needed
      @return A CSS string
    */
    function css_content(){
        $css ="
        h4.install {
        margin-bottom: 0.4em;
        }";

        return $css;

    //css_content()
    }

    // If chunks of JS are going to be added the function has to be
    //  redefined to return them.
    /**
      Define any javascript needed
      @return A javascript string
    function javascript_content(){

    }
    */

    function body_content(){
        //Should this really be done with global?
        //global $FANNIE_URL, $FANNIE_EQUITY_DEPARTMENTS;
        include('../../config.php'); 
        ob_start();
?>
<?php
echo showInstallTabs("Sample Data", '../');
?>

<form action=InstallSampleDataPage.php method=post>
<h1 class="install">
    <?php 
    if (!$this->themed) {
        echo "<h1 class='install'>{$this->header}</h1>";
    }
    ?>
</h1>
<?php
if (is_writable('../../config.php')){
    echo "<div class=\"alert alert-success\"><i>config.php</i> is writeable</div>";
}
else {
    echo "<div class=\"alert alert-danger\"><b>Error</b>: config.php is not writeable</div>";
}
?>
<hr />
<div class="well"><em>
<?php
/* First, if this is a request to load a file, do that.
*/
$db = new SQLManager($FANNIE_SERVER,
    $FANNIE_SERVER_DBMS,
    $FANNIE_OP_DB,
    $FANNIE_SERVER_USER,
    $FANNIE_SERVER_PW);

if (isset($_REQUEST['employees'])){
    echo "Loading employees";
    $db->query("TRUNCATE TABLE employees");
    \COREPOS\Fannie\API\data\DataLoad::loadSampleData($db,'employees');  
} elseif (isset($_REQUEST['custdata'])) {
    echo "Loading custdata";
    $backup1 = $db->query('TRUNCATE TABLE custdataBackup');
    $backup2 = $db->query('INSERT INTO custdataBackup SELECT * FROM custdata');
    if ($backup1 === false || $backup2 === false) {
        echo _(' - failed to backup current data. Sample data not loaded.');
    } else {
        $db->query("TRUNCATE TABLE custdata");
        \COREPOS\Fannie\API\data\DataLoad::loadSampleData($db,'custdata');
    }
} elseif(isset($_REQUEST['memtype'])){
    echo "Loading memtype";
    $db->query("TRUNCATE TABLE memtype");
    \COREPOS\Fannie\API\data\DataLoad::loadSampleData($db,'memtype');
} elseif (isset($_REQUEST['products'])) {
    echo "Loading products";
    $backup1 = $db->query('TRUNCATE TABLE productBackup');
    $backup2 = $db->query('INSERT INTO productBackup SELECT * FROM products');
    if ($backup1 === false || $backup2 === false) {
        echo _(' - failed to backup current data. Sample data not loaded.');
    } else {
        $db->query("TRUNCATE TABLE products");
        \COREPOS\Fannie\API\data\DataLoad::loadSampleData($db,'products');
    }
}
elseif(isset($_REQUEST['prod-flags'])){
    echo "Loading product flags";
    $db->query("TRUNCATE TABLE prodFlags");
    \COREPOS\Fannie\API\data\DataLoad::loadSampleData($db,'prodFlags');
}
elseif(isset($_REQUEST['batchType'])){
    echo "Loading batchn types";
    $db->query("TRUNCATE TABLE batchType");
    \COREPOS\Fannie\API\data\DataLoad::loadSampleData($db,'batchType');
}
elseif(isset($_REQUEST['depts'])){
    echo "Loading departments";
    $db->query("TRUNCATE TABLE departments");
    \COREPOS\Fannie\API\data\DataLoad::loadSampleData($db,'departments');
    /* subdepts sample data is of questionable use
    echo "<br />Loading subdepts";
    $db->query("TRUNCATE TABLE subdepts");
    \COREPOS\Fannie\API\data\DataLoad::loadSampleData($db,'subdepts');
    */
}
elseif (isset($_REQUEST['superdepts'])){
    echo "Loading super departments";
    $db->query("TRUNCATE TABLE superdepts");
    \COREPOS\Fannie\API\data\DataLoad::loadSampleData($db,'superdepts');
    $db->query("TRUNCATE TABLE superDeptNames");
    \COREPOS\Fannie\API\data\DataLoad::loadSampleData($db,'superDeptNames');
}
elseif (isset($_REQUEST['tenders'])){
    echo "Loading tenders";
    $db->query("TRUNCATE TABLE tenders");
    \COREPOS\Fannie\API\data\DataLoad::loadSampleData($db,'tenders');
}
elseif (isset($_REQUEST['authentication'])){
    echo "Loading authentication info";
    $db->query("TRUNCATE TABLE userKnownPrivs");
    \COREPOS\Fannie\API\data\DataLoad::loadSampleData($db,'userKnownPrivs');
}
elseif (isset($_REQUEST['origin'])){
    echo "Loading country info";
    $db->query("TRUNCATE TABLE originCountry");
    \COREPOS\Fannie\API\data\DataLoad::loadSampleData($db,'originCountry');
    echo "<br />Loading state/province info";
    $db->query("TRUNCATE TABLE originStateProv");
    \COREPOS\Fannie\API\data\DataLoad::loadSampleData($db,'originStateProv');
} else if (isset($_REQUEST['authGroups'])) {
    echo "Loading authentication groups";
    $db->query("TRUNCATE TABLE userGroups");
    \COREPOS\Fannie\API\data\DataLoad::loadSampleData($db,'userGroups');
    $db->query("TRUNCATE TABLE userGroupPrivs");
    \COREPOS\Fannie\API\data\DataLoad::loadSampleData($db,'userGroupPrivs');
    // give "Administrators" group all permissions
    $db->query("INSERT userGroupPrivs SELECT 
            1, auth_class, 'all', 'all'
            FROM userKnownPrivs");
}
?>
</em></div>

<?php /* Display a list of data that can be loaded.
*/
?>
<p class="ichunk">
Some sample data is available to get a test lane
up and running quickly and to try Fannie functions.
<h3>Keep in mind this data overwrites whatever is currently in the table.</h3>
<br />These utilities populate the server tables.
Then use the <a href="../../sync/SyncIndexPage.php"
target="_sync"><u>Synchronize</u></a>
utilities to populate the lane tables.
</p>
<hr />
<h4 class="install"><?php echo _('Cashiers'); ?></h4>
    This table contains login information for cashiers. The two
    included logins are '56' and '7000'.<br />
    <?php echo $this->loadButton('employees', 'employees', _('Load sample cashiers')); ?>
<hr />
<h4 class="install">Customer Data</h4>
    Customer data is the membership information. Sample data includes
    a bunch of members and default non-member 11.<br />
    <?php echo $this->loadButton('custdata', 'custdata', _('Load sample customers')); ?>
    <br />
    <br />
    Customers are classified into different membership types.<br />
    <?php echo $this->loadButton('memtype', 'memtype', _('Load sample member types')); ?>
<hr />
    <h4 class="install">Products</h4>
    Stuff to sell. There's a lot of sample data. I think this might
    be the Wedge's or at least a snapshot of it.<br />
    <?php echo $this->loadButton('products', 'products', _('Load sample products')); ?>
<hr />
    <h4 class="install">Product Flags</h4>
    Product Flags are a flexible method for identifying custom attributes of items.
    CORE includes a default set of some more common flags.<br />
    <?php echo $this->loadButton('prodFlags', 'prod-flags', _('Load sample product flags')); ?>
<hr />
    <h4 class="install">Batch Types</h4>
    Batches are used for temporary promotional pricing as well as scheduling changes
    in regular retail price. Batches may be organized by type. Sample data includes
    a couple common options.
    <?php echo $this->loadButton('batchType', 'batchType', _('Load sample batch types')); ?>
<hr />
    <h4 class="install">Departments</h4>
    Products get categorized into departments .
    You can also ring amounts directly to a department. Not needed,
    strictly speaking, for a basic lane (Ring up items, total, 
    accept tender, provide change).<br />
    <?php echo $this->loadButton('departments', 'depts', _('Load sample departments')); ?>
<hr />
    <h4 class="install">Super-Department Names <span style="font-weight:400;">and</span> Super-Department Links</h4>
    Super Departments are tags for grouping Departments.
    A Department can have more than one, that is, belong to more than one Super-Department.
    This rudimentary set agrees with the Products sample data.
    Super-Departments can also be used to group the domains of Buyers.
    Use them with e.g. the <a href="../../fannie/item/productList.php">Product List report/tool</a>
    They are also used for grouping shelftags for printing and for grouping data in reports.
    <?php echo $this->loadButton('superdepts', 'superdepts', _('Load sample super departments')); ?>
<hr />
<h4 class="install">Tenders</h4>
    Load all the default tenders into the tenders table.<br />
    <?php echo $this->loadButton('tenders', 'tenders', _('Load sample tenders')); ?>
<hr />
    <h4 class="install">Authentication</h4>
    Load information about currently defined authorization classes<br />
    <?php echo $this->loadButton('userKnownPrivs', 'authentication', _('Load auth classes')); ?>
<br /><br />
    Load default groups<br />
    <?php echo $this->loadButton('userGroups', 'authGroups', _('Load auth groups')); ?>
<hr />
    <h4 class="install">Countries, States, and Provinces</h4>
    Load default place-of-origin information<br />
    <?php echo $this->loadButton('originCountry', 'origin', _('Load origin info')); ?>
<hr />

</form>

<?php

        return ob_get_clean();

    // body_content
    }

    private function loadButton($table_name, $button_name, $label)
    {
        $dbc = $this->connection;
        $chk = $dbc->query('
            SELECT COUNT(*)
            FROM ' . $dbc->identifierEscape($table_name));
        if ($chk === false) {
            return '<div class="alert alert-danger">Table "' . $table_name . '" is 
                missing so sample data cannot be loaded.</div>';
        }
        $count = $dbc->fetchRow($chk);
        if ($count[0] > 0) {
            return '
                <div class="alert alert-warning">
                    This table is not empty. Loading sample data will replace all
                    current data.
                </div>
                <button type="submit" name="' . $button_name . '" value="1"
                    class="btn btn-default"
                    onclick="return confirm(\'Replace current data with sample data?\');"
                    >' . $label . '
                </button>';
        } else {
            return '
                <div class="alert alert-info">
                    This table is currently empty.
                </div>
                <button type="submit" name="' . $button_name . '" value="1"
                    class="btn btn-default"
                    >' . $label . '
                </button>';
        }
    }
}

FannieDispatch::conditionalExec();

