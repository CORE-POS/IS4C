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

include(dirname(__FILE__) . '/../../config.php'); 
if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__) . '/../../classlib2.0/FannieAPI.php');
}
if (!function_exists('confset')) {
    include(dirname(__FILE__) . '/../util.php');
}
if (!function_exists('dropDeprecatedStructure')) {
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

    function body_content(){
        //Should this really be done with global?
        //global $FANNIE_URL, $FANNIE_EQUITY_DEPARTMENTS;
        include(dirname(__FILE__) . '/../../config.php'); 
        ob_start();
?>
<?php
echo showInstallTabs("Sample Data", '../');
?>

<form action=InstallSampleDataPage.php method=post>
<?php
echo $this->writeCheck(dirname(__FILE__) . '/../config.php');
?>
<hr />
<div class="well"><em>
<?php
/* First, if this is a request to load a file, do that.
*/
$dbc = new SQLManager($FANNIE_SERVER,
    $FANNIE_SERVER_DBMS,
    $FANNIE_OP_DB,
    $FANNIE_SERVER_USER,
    $FANNIE_SERVER_PW);

if (FormLib::get('employees') !== '') {
    $this->reloadTable($dbc, 'employees');
} elseif (FormLib::get('custdata') !== ''){
    $this->reloadTable($dbc, 'custdata', 'custdataBackup');
} elseif (FormLib::get('memtype') !== ''){
    $this->reloadTable($dbc, 'memtype');
} elseif (FormLib::get('products') !== ''){
    $this->reloadTable($dbc, 'products', 'productBackup');
} elseif (FormLib::get('prod-flags') !== ''){
    $this->reloadTable($dbc, 'prodFlags');
} elseif (FormLib::get('batchType') !== ''){
    $this->reloadTable($dbc, 'batchType');
} elseif (FormLib::get('depts') !== ''){
    $this->reloadTable($dbc, 'departments');
} elseif (FormLib::get('superdepts') !== ''){
    $this->reloadTable($dbc, 'superdepts');
    $this->reloadTable($dbc, 'superDeptNames');
} elseif (FormLib::get('tenders') !== ''){
    $this->reloadTable($dbc, 'tenders');
} elseif (FormLib::get('authentication') !== ''){
    $this->reloadTable($dbc, 'userKnownPrivs');
} elseif (FormLib::get('origin') !== ''){
    $this->reloadTable($dbc, 'originCountry');
    $this->reloadTable($dbc, 'originStateProv');
} elseif (FormLib::get('authGroups') !== ''){
    $this->reloadTable($dbc, 'userGroups');
    $this->reloadTable($dbc, 'userGroupPrivs');
    // give "Administrators" group all permissions
    $dbc->query("INSERT userGroupPrivs SELECT 
            1, auth_class, 'all', 'all'
            FROM userKnownPrivs");
}
?>
</em></div>

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

    private function reloadTable($dbc, $table, $backup_table=false)
    {
        echo 'Loading ' . $table;
        $ready = true;
        if ($backup_table !== false) {
            $backup1 = $dbc->query('TRUNCATE TABLE ' . $dbc->identifierEscape($backup_table));
            $backup2 = $dbc->query('INSERT INTO ' . $dbc->identifierEscape($backup_table) 
                . ' SELECT * FROM ' . $dbc->identifierEscape($table));
            if ($backup1 === false || $backup2 === false) {
                echo _(' - failed to backup current data. Sample data not loaded.');
                $ready = false;
            }
        }

        if ($ready === true) {
            $dbc->query("TRUNCATE TABLE " . $dbc->identifierEscape($table));
            \COREPOS\Fannie\API\data\DataLoad::loadSampleData($dbc, $table);
        }
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
    }
}

FannieDispatch::conditionalExec();

