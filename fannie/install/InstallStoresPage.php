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
    @class InstallStoresPage
    Class for the Stores install and config options
*/
class InstallStoresPage extends \COREPOS\Fannie\API\InstallPage {

    protected $title = 'Fannie: Store Settings';
    protected $header = 'Fannie: Store Settings';

    public $description = "
    Class for the Stores install and config options page.
    ";

    public function preprocess()
    {
        global $FANNIE_OP_DB, $FANNIE_STORE_MODE, $FANNIE_STORE_ID;
        $model = new StoresModel(FannieDB::get($FANNIE_OP_DB));
        $posted = false;

        // save info
        if (is_array(FormLib::get('storeID'))) {
            $ids = FormLib::get('storeID');
            $names = FormLib::get('storeName', array());
            $urls = FormLib::get('storeURL', array());
            $hosts = FormLib::get('storeHost', array());
            $drivers = FormLib::get('storeDriver', array());
            $users = FormLib::get('storeUser', array());
            $passwords = FormLib::get('storePass', array());
            $op_dbs = FormLib::get('storeOp', array());
            $trans_dbs = FormLib::get('storeTrans', array());
            $push = FormLib::get('storePush', array());
            $pull = FormLib::get('storePull', array());
            $items = FormLib::get('storeItems', array());

            for($i=0; $i<count($ids); $i++) {
                $model->reset();
                $model->storeID($ids[$i]);
                $model->description( isset($names[$i]) ? $names[$i] : '' );
                $model->dbHost( isset($hosts[$i]) ? $hosts[$i] : '' );
                $model->dbDriver( isset($drivers[$i]) ? $drivers[$i] : '' );
                $model->dbUser( isset($users[$i]) ? $users[$i] : '' );
                $model->dbPassword( isset($passwords[$i]) ? $passwords[$i] : '' );
                $model->opDB( isset($op_dbs[$i]) ? $op_dbs[$i] : '' );
                $model->transDB( isset($trans_dbs[$i]) ? $trans_dbs[$i] : '' );
                $model->push( in_array($ids[$i], $push) ? 1 : 0 );
                $model->pull( in_array($ids[$i], $pull) ? 1 : 0 );
                $model->hasOwnItems( in_array($ids[$i], $items) ? 1 : 0 );
                $model->webServiceUrl(isset($urls[$i]) ? $urls[$i] : '');
                $model->save();
            }

            $posted = true;
        }

        // delete any marked stores
        if (is_array(FormLib::get('storeDelete'))) {
            foreach(FormLib::get('storeDelete') as $id) {
                $model->reset();
                $model->storeID($id);
                $model->delete();
            }

            $posted = true;
        }

        if (FormLib::get('addButton') == 'Add Another Store') {
            $model->reset();
            $model->description('NEW STORE');
            $model->save();

            $posted = true;
        }

        // redirect to self so refreshing the page
        // doesn't repeat HTML POST
        if ($posted) {
            // capture POST of input field
            installTextField('FANNIE_STORE_ID', $FANNIE_STORE_ID, 1);
            installSelectField('FANNIE_STORE_MODE', $FANNIE_STORE_MODE, array('STORE'=>'Single Store', 'HQ'=>'HQ'),'STORE');
            if (FormLib::get('FANNIE_READONLY_JSON', false) !== false) {
                // decode and re-encode to squash whitespace
                $FANNIE_READONLY_JSON = json_encode(json_decode(FormLib::get('FANNIE_READONLY_JSON')));
                confset('FANNIE_READONLY_JSON', "'$FANNIE_READONLY_JSON'");
            }
            $netIDs = FormLib::get('storeNetId');
            $nets = FormLib::get('storeNet');
            $saveStr = 'array(';
            for ($i=0; $i<count($netIDs); $i++) {
                $saveStr .= $netIDs[$i] . '=>array(';
                foreach (explode(',', $nets[$i]) as $net) {
                    $saveStr .= "'" . trim($net) . '\',';
                }
                $saveStr .= '),';
            }
            $saveStr .= ')';
            if (count($netIDs) > 0) {
                confset('FANNIE_STORE_NETS', "$saveStr");
            }
            header('Location: InstallStoresPage.php');
            return false;
        }

        return true;
    }

    /**
      Define any CSS needed
      @return a CSS string
    */
    function css_content(){
        return '
            tr.highlight td {
                background-color: #ffffcc;
            }
        ';
    //css_content()
    }

    public function body_content()
    {
        include(dirname(__FILE__) . '/../config.php');
        ob_start();

        echo showInstallTabs('Stores');
        ?>

<form action=InstallStoresPage.php method=post>
<p class="ichunk">Revised 23Apr2014</p>
<?php
echo $this->writeCheck(dirname(__FILE__) . '/../config.php');
?>
<hr />
<h4 class="install">Stores</h4>
<p class="ichunk" style="margin:0.0em 0em 0.4em 0em;">
<?php
$model = new StoresModel(FannieDB::get($FANNIE_OP_DB));
echo '<i>This store is #' . installTextField('FANNIE_STORE_ID', $FANNIE_STORE_ID, 1) . '</i><br />';
echo '<label>Mode</label>';
echo installSelectField('FANNIE_STORE_MODE', $FANNIE_STORE_MODE, array('STORE'=>'Single Store', 'HQ'=>'HQ'),'STORE');

$supportedTypes = \COREPOS\common\sql\Lib::getDrivers();
?>
<table class="table">
<tr>
    <th>Store #</th><th>Description</th>
    <th>Web Services URL</th><th>DB Host</th>
    <th>Driver</th><th>Username</th><th>Password</th>
    <th>Operational DB</th>
    <th>Transaction DB</th>
    <th>Push</th>
    <th>Pull</th>
    <th>Own Items</th>
    <th>Delete Entry</th>
</tr>
<?php foreach($model->find('storeID') as $store) {
    printf('<tr %s>
            <td>%d<input type="hidden" name="storeID[]" value="%d" /></td>
            <td><input type="text" class="form-control" name="storeName[]" value="%s" /></td>
            <td><input type="text" class="form-control" name="storeURL[]" value="%s" /></td>
            <td><input type="text" class="form-control" name="storeHost[]" value="%s" /></td>',
            ($store->dbHost() == $FANNIE_SERVER ? 'class="info"' : ''),
            $store->storeID(), $store->storeID(),
            $store->description(),
            $store->webServiceUrl(),
            $store->dbHost()
    );
    echo '<td><select name="storeDriver[]" class="form-control">';
    foreach($supportedTypes as $key => $label) {
        printf('<option %s value="%s">%s</option>',
            ($store->dbDriver() == $key ? 'selected' : ''),
            $key, $label);
    }
    echo '</select></td>';
    printf('<td><input type="text" class="form-control" name="storeUser[]" value="%s" /></td>
            <td><input type="password" class="form-control" name="storePass[]" value="%s" /></td>
            <td><input type="text" class="form-control" name="storeOp[]" value="%s" /></td>
            <td><input type="text" class="form-control" name="storeTrans[]" value="%s" /></td>
            <td><input type="checkbox" name="storePush[]" value="%d" %s /></td>
            <td><input type="checkbox" name="storePull[]" value="%d" %s /></td>
            <td><input type="checkbox" name="storeItems[]" value="%d" %s /></td>
            <td><input type="checkbox" name="storeDelete[]" value="%d" /></td>
            </tr>',
            $store->dbUser(),
            $store->dbPassword(),
            $store->opDB(),
            $store->transDB(),
            $store->storeID(), ($store->push() ? 'checked' : ''),
            $store->storeID(), ($store->pull() ? 'checked' : ''),
            $store->storeID(), ($store->hasOwnItems() ? 'checked' : ''),
            $store->storeID()
    );

} ?>
</table>
</p>
<hr />
<h4 class="install">Testing Connections</h4>
<p class="ichunk" style="margin:0.0em 0em 0.4em 0em;">
    <ul>
<?php foreach($model->find('storeID') as $store) {
    $test = db_test_connect($store->dbHost(),
                            $store->dbDriver(),
                            $store->transDB(),
                            $store->dbUser(),
                            $store->dbPassword()
                           );
    echo '<li> Store #' . $store->storeID() . ': ' . ($test ? 'Connected' : 'No connection') . '</li>';
} ?>
    </ul>
    <i>Note: it's OK if this store's connection fails as long as it succeeds
       on the "Necessities" tab.</i>
</p>
<hr />
<h4 class="install">Read-only Database Server(s)</h4>
<p class="ichunk" style="margin:0.0em 0em 0.4em 0em;">
Specify one or more database servers that can be used strictly
for read operations. If more than one database is listed, read-only
queries will be load-balanced across them.
<?php
if (!isset($FANNIE_READONLY_JSON) || $FANNIE_READONLY_JSON === 'null') {
    $FANNIE_READONLY_JSON = json_encode(array(array(
        'host' => $FANNIE_SERVER,
        'type' => $FANNIE_SERVER_DBMS,
        'user' => $FANNIE_SERVER_USER,
        'pw' => $FANNIE_SERVER_PW,
    )));
}
confset('FANNIE_READONLY_JSON', "'$FANNIE_READONLY_JSON'");
?>
<textarea rows="10" cols="30" name="FANNIE_READONLY_JSON" class="form-control">
<?php echo \COREPOS\Fannie\API\lib\FannieUI::prettyJSON($FANNIE_READONLY_JSON); ?>
</textarea>
<hr />
<h4 class="install">Store Network(s)</h4>
<p class="ichunk" style="margin:0.0em 0em 0.4em 0em;">
List the network or network(s) in use at each store so clients default to
the correct store (e.g., 192.168.0.0/24)<br />
<?php 
$model->hasOwnItems(1);
foreach($model->find('storeID') as $store) {
    $nets = '';
    if (isset($FANNIE_STORE_NETS) && isset($FANNIE_STORE_NETS[$store->storeID()])) {
        $nets = implode(', ', $FANNIE_STORE_NETS[$store->storeID()]);
    }

    echo 'Store #' . $store->storeID();
    echo '<input type="hidden" name="storeNetId[]" value="' . $store->storeID() . '" />';
    echo '<input type="text" name="storeNet[]" class="form-control" value="'
        . $nets
        . ' " /><br />';
} ?>
</p>
<p>
<button type=submit name="saveButton" value="Save" class="btn btn-default">Save</button>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<button type=submit name="addButton" value="Add Another Store" class="btn btn-default">Add Another Store</button>
</p>
</form>

<?php

        return ob_get_clean();

    // body_content
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
    }

// InstallStoresPage
}

FannieDispatch::conditionalExec();

