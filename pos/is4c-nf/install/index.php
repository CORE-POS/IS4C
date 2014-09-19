<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

    DHermann test
*********************************************************************************/

ini_set('display_errors','1');

include(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));
AutoLoader::loadMap();
if (file_exists('../ini.php'))
    include('../ini.php');
include('InstallUtilities.php');
?>
<html>
<head>
<title>IT CORE Lane Installation: Necessities</title>
<style type="text/css">
body {
    line-height: 1.5em;
}
</style>
<script type="text/javascript" src="../js/jquery.js"></script>
</head>
<body>
<?php include('tabs.php'); ?>
<div id="wrapper">
<h2>IT CORE Lane Installation: Necessities</h2>

<form action=index.php method=post>

<div class="alert"><?php InstallUtilities::checkWritable('../ini.php', False, 'PHP'); ?></div>
<div class="alert"><?php InstallUtilities::checkWritable('../ini-local.php', True, 'PHP'); ?></div>

PHP is running as: <?php echo InstallUtilities::whoami(); ?><br />
<?php
if (!function_exists("socket_create")){
    echo '<b>Warning</b>: PHP socket extension is not enabled. NewMagellan will not work quite right';
}
?>
<br />
<table id="install" border=0 cellspacing=0 cellpadding=4>
<?php 
if (is_array($CORE_LOCAL->get('LaneMap'))) {
    $my_ips = MiscLib::getAllIPs();
    $map = $CORE_LOCAL->get('LaneMap');
    $register_id_is_mapped = false;
    $store_id_is_mapped = false;
    foreach ($my_ips as $ip) {
        if (!isset($map[$ip])) {
            continue;
        }
        if (!is_array($map[$ip])) {
            echo '<tr><td colspan="3">Error: invalid entry for ' . $ip . '</td></tr>';
        } elseif (!isset($map[$ip]['register_id'])) {
            echo '<tr><td colspan="3">Error: missing register_id for ' . $ip . '</td></tr>';
        } elseif (!isset($map[$ip]['store_id'])) {
            echo '<tr><td colspan="3">Error: missing store_id for ' . $ip . '</td></tr>';
        } else {
            if ($CORE_LOCAL->get('store_id') === '') {
                // no store_id set. assign based on IP
                $CORE_LOCAL->set('store_id', $map[$ip]['store_id']);
                $store_id_is_mapped = true;
            } else if ($CORE_LOCAL->get('store_id') != $map[$ip]['store_id']) {
                echo '<tr><td colspan="3">Warning: store_id is set to ' 
                    . $CORE_LOCAL->get('store_id') . '. Based on IP ' . $ip
                    . ' it should be set to ' . $map[$ip]['store_id'] . '</td></tr>';
            } else {
                $store_id_is_mapped = true;
            }
            if ($CORE_LOCAL->get('laneno') === '') {
                // no store_id set. assign based on IP
                $CORE_LOCAL->set('laneno', $map[$ip]['register_id']);
                $register_id_is_mapped = true;
            } else if ($CORE_LOCAL->get('laneno') != $map[$ip]['register_id']) {
                echo '<tr><td colspan="3">Warning: register_id is set to ' 
                    . $CORE_LOCAL->get('laneno') . '. Based on IP ' . $ip
                    . ' it should be set to ' . $map[$ip]['register_id'] . '</td></tr>';
            } else {
                // map entry matches
                // should maybe delete ini.php entry if it exists?
                $register_id_is_mapped = true;
            }

            // use first matching IP
            break;
        }
    }
}
?>
<tr>
    <td style="width:30%;">Lane number:</td>
    <?php if ($CORE_LOCAL->get('laneno') !== '' && $CORE_LOCAL->get('laneno') == 0) { ?>
    <td>0 (Zero)</td>
    <?php } elseif ($register_id_is_mapped) { ?>
    <td><?php echo $CORE_LOCAL->get('laneno'); ?> (assigned by IP; cannot be edited)</td>
    <?php } else { ?>
    <td><?php echo InstallUtilities::installTextField('laneno', 99, InstallUtilities::INI_SETTING, false); ?></td>
    <?php } ?>
</tr>
<tr>
    <td>Store number:</td>
    <?php if ($CORE_LOCAL->get('store_id') !== '' && $CORE_LOCAL->get('store_id') == 0) { ?>
    <td>0 (Zero)</td>
    <?php } elseif ($store_id_is_mapped) { ?>
    <td><?php echo $CORE_LOCAL->get('store_id'); ?> (assigned by IP; cannot be edited)</td>
    <?php } else { ?>
    <td><?php echo InstallUtilities::installTextField('store_id', 1, InstallUtilities::INI_SETTING, false); ?></td>
    <?php } ?>
</tr>
<?php if ($CORE_LOCAL->get('laneno') === '' || $CORE_LOCAL->get('laneno') != 0) { ?>
<tr>
    <td colspan=2 class="tblheader">
    <h3>Database set up</h3>
    </td>
</tr>
<tr>
    <td>Lane database host: </td>
    <td><?php echo InstallUtilities::installTextField('localhost', '127.0.0.1', InstallUtilities::INI_SETTING); ?></td>
</tr>
<tr>
    <td>Lane database type:</td>
    <td>
    <?php
    $db_opts = array('mysql'=>'MySQL','mssql'=>'SQL Server',
        'pdomysql'=>'MySQL (PDO)','pdomssql'=>'SQL Server (PDO)',
        'pdolite' => 'SQLite (PDO)');
    echo InstallUtilities::installSelectField('DBMS', $db_opts, 'mysql', InstallUtilities::INI_SETTING);
    ?>
    </td>
</tr>
<tr>
    <td>Lane user name:</td>
    <td><?php echo InstallUtilities::installTextField('localUser', 'root', InstallUtilities::INI_SETTING); ?></td>
</tr>
<tr>
    <td>Lane password:</td>
    <td>
    <?php
    echo InstallUtilities::installTextField('localPass', '', InstallUtilities::INI_SETTING, true, array('type'=>'password'));
    ?>
    </td>
</tr>
<tr>
    <td>Lane operational DB:</td>
    <td><?php echo InstallUtilities::installTextField('pDatabase', 'opdata', InstallUtilities::INI_SETTING); ?></td>
</tr>
<tr>
    <td colspan=2>
<div class="noteTxt">
Testing operational DB Connection:
<?php
$gotDBs = 0;
if ($CORE_LOCAL->get("DBMS") == "mysql")
    $val = ini_set('mysql.connect_timeout',5);

$sql = InstallUtilities::dbTestConnect($CORE_LOCAL->get('localhost'),
        $CORE_LOCAL->get('DBMS'),
        $CORE_LOCAL->get('pDatabase'),
        $CORE_LOCAL->get('localUser'),
        $CORE_LOCAL->get('localPass'));
if ($sql === False) {
    echo "<span class='fail'>Failed</span>";
    echo '<div class="db_hints" style="margin-left:25px;">';
    if (!function_exists('socket_create')){
        echo '<i>Try enabling PHP\'s socket extension in php.ini for better diagnostics</i>';
    }
    elseif (@MiscLib::pingport($CORE_LOCAL->get('localhost'),$CORE_LOCAL->get('DBMS'))){
        echo '<i>Database found at '.$CORE_LOCAL->get('localhost').'. Verify username and password
            and/or database account permissions.</i>';
    }
    else {
        echo '<i>Database does not appear to be listening for connections on '
            .$CORE_LOCAL->get('localhost').'. Verify host is correct, database is running and
            firewall is allowing connections.</i>';
    }
    echo '</div>';
} else {
    echo "<span class='success'>Succeeded</span><br />";
    //echo "<textarea rows=3 cols=80>";
    $opErrors = create_op_dbs($sql,$CORE_LOCAL->get('DBMS'));
    $gotDBs++;
    if (!empty($opErrors)){
        echo '<div class="db_create_errors" style="border: solid 1px red;padding:5px;">';
        echo 'There were some errors creating operational DB structure';
        echo '<ul style="margin-top:2px;">';
        foreach($opErrors as $error){
            echo '<li>';    
            echo 'Error on structure <b>'.$error['struct'].'</b>. ';
            printf('<a href="" onclick="$(\'#eDetails%s\').toggle();return false;">Details</a>',
                $error['struct']);
            printf('<ul style="display:none;" id="eDetails%s">',$error['struct']);
            echo '<li>Query: <pre>'.$error['query'].'</pre></li>';
            echo '<li>Error Message: '.$error['details'].'</li>';
            echo '</ul>';
            echo '</li>';
        }
        echo '</div>';
    }
    //echo "</textarea>";
}
?>
</div> <!-- noteTxt -->
</td></tr>
<tr>
    <td>Lane transaction DB:</td>
    <td><?php echo InstallUtilities::installTextField('tDatabase', 'translog', InstallUtilities::INI_SETTING); ?></td>
</tr>
<tr>
    <td colspan=2>
<div class="noteTxt">
Testing transactional DB connection:
<?php
$sql = InstallUtilities::dbTestConnect($CORE_LOCAL->get('localhost'),
        $CORE_LOCAL->get('DBMS'),
        $CORE_LOCAL->get('tDatabase'),
        $CORE_LOCAL->get('localUser'),
        $CORE_LOCAL->get('localPass'));
if ($sql === False ) {
    echo "<span class='fail'>Failed</span>";
    echo '<div class="db_hints" style="margin-left:25px;">';
    echo '<i>If both connections failed, see above. If just this one
        is failing, it\'s probably an issue of database user 
        permissions.</i>';
    echo '</div>';
} else {
    echo "<span class='success'>Succeeded</span><br />";
    //echo "<textarea rows=3 cols=80>";
    

    /* Re-do tax rates here so changes affect the subsequent
     * ltt* view builds. 
     */
    if (isset($_REQUEST['TAX_RATE']) && $sql->table_exists('taxrates')){
        $queries = array();
        for($i=0; $i<count($_REQUEST['TAX_RATE']); $i++){
            $rate = $_REQUEST['TAX_RATE'][$i];
            $desc = $_REQUEST['TAX_DESC'][$i];
            if(is_numeric($rate)){
                $desc = str_replace(" ","",$desc);
                $queries[] = sprintf("INSERT INTO taxrates VALUES 
                    (%d,%f,'%s')",$i+1,$rate,$desc);
            }
            else if ($rate != ""){
                echo "<br /><b>Error</b>: the given
                    tax rate, $rate, doesn't seem to
                    be a number.";
            }
            $sql->query("TRUNCATE TABLE taxrates");
            foreach($queries as $q)
                $sql->query($q);
        }
    }

    $transErrors = create_trans_dbs($sql,$CORE_LOCAL->get('DBMS'));
    $gotDBs++;
    if (!empty($transErrors)){
        echo '<div class="db_create_errors" style="border: solid 1px red;padding:5px;">';
        echo 'There were some errors creating transactional DB structure';
        echo '<ul style="margin-top:2px;">';
        foreach($transErrors as $error){
            echo '<li>';    
            echo 'Error on structure <b>'.$error['struct'].'</b>. ';
            printf('<a href="" onclick="$(\'#eDetails%s\').toggle();return false;">Details</a>',
                $error['struct']);
            printf('<ul style="display:none;" id="eDetails%s">',$error['struct']);
            echo '<li>Query: <pre>'.$error['query'].'</pre></li>';
            echo '<li>Error Message: '.$error['details'].'</li>';
            echo '</ul>';
            echo '</li>';
        }
        echo '</div>';
    }
    //echo "</textarea>";
}
?>
</div> <!-- noteTxt -->
</td>
</tr>
<?php } else { $gotDBs=2; } // end local lane db config that does not apply on lane#0 / server ?> 
<tr>
    <td>Server database host: </td>
    <td><?php echo InstallUtilities::installTextField('mServer', '127.0.0.1'); ?></td>
</tr>
<tr>
    <td>Server database type:</td>
    <td>
    <?php
    $db_opts = array('mysql'=>'MySQL','mssql'=>'SQL Server',
        'pdomysql'=>'MySQL (PDO)','pdomssql'=>'SQL Server (PDO)');
    echo InstallUtilities::installSelectField('mDBMS', $db_opts, 'mysql');
    ?>
    </td>
</tr>
<tr>
    <td>Server user name:</td>
    <td><?php echo InstallUtilities::installTextField('mUser', 'root'); ?></td>
</tr>
<tr>
    <td>Server password:</td>
    <td>
    <?php
    echo InstallUtilities::installTextField('mPass', '', InstallUtilities::EITHER_SETTING, true, array('type'=>'password'));
    ?>
    </td>
</tr>
<tr>
    <td>Server database name:</td>
    <td><?php echo InstallUtilities::installTextField('mDatabase', 'core_trans'); ?></td>
</tr>
<tr>
    <td colspan=2>
<div class="noteTxt">
Testing server connection:
<?php
$sql = InstallUtilities::dbTestConnect($CORE_LOCAL->get('mServer'),
        $CORE_LOCAL->get('mDBMS'),
        $CORE_LOCAL->get('mDatabase'),
        $CORE_LOCAL->get('mUser'),
        $CORE_LOCAL->get('mPass'));
if ($sql === False){
    echo "<span class='fail'>Failed</span>";
    echo '<div class="db_hints" style="margin-left:25px;width:350px;">';
    if (!function_exists('socket_create')){
        echo '<i>Try enabling PHP\'s socket extension in php.ini for better diagnostics</i>';
    }
    elseif (@MiscLib::pingport($CORE_LOCAL->get('mServer'),$CORE_LOCAL->get('DBMS'))){
        echo '<i>Database found at '.$CORE_LOCAL->get('mServer').'. Verify username and password
            and/or database account permissions.</i>';
    }
    else {
        echo '<i>Database does not appear to be listening for connections on '
            .$CORE_LOCAL->get('mServer').'. Verify host is correct, database is running and
            firewall is allowing connections.</i>';
    }
    echo '</div>';
}
else {
    echo "<span class='success'>Succeeded</span><br />";
    //echo "<textarea rows=3 cols=80>";
    $sErrors = create_min_server($sql,$CORE_LOCAL->get('mDBMS'));
    if (!empty($sErrors)){
        echo '<div class="db_create_errors" style="border: solid 1px red;padding:5px;">';
        echo 'There were some errors creating transactional DB structure';
        echo '<ul style="margin-top:2px;">';
        foreach($sErrors as $error){
            echo '<li>';    
            echo 'Error on structure <b>'.$error['struct'].'</b>. ';
            printf('<a href="" onclick="$(\'#eDetails%s\').toggle();return false;">Details</a>',
                $error['struct']);
            printf('<ul style="display:none;" id="eDetails%s">',$error['struct']);
            echo '<li>Query: <pre>'.$error['query'].'</pre></li>';
            echo '<li>Error Message: '.$error['details'].'</li>';
            echo '</ul>';
            echo '</li>';
        }
        echo '</div>';
    }
    //echo "</textarea>";
}
?>
</div>  <!-- noteTxt -->
</td></tr><tr><td colspan=2 class="tblHeader">
<h3>Tax</h3></td></tr>
<tr><td colspan=2>
<p><i>Provided tax rates are used to create database views. As such,
descriptions should be DB-legal syntax (e.g., no spaces). A rate of
0% with ID 0 is automatically included. Enter exact values - e.g.,
0.05 to represent 5%.</i></p></td></tr>
<tr><td colspan=2>
<?php
$rates = array();
if ($gotDBs == 2) {
    $sql = new SQLManager($CORE_LOCAL->get('localhost'),
            $CORE_LOCAL->get('DBMS'),
            $CORE_LOCAL->get('tDatabase'),
            $CORE_LOCAL->get('localUser'),
            $CORE_LOCAL->get('localPass'));
    if ($CORE_LOCAL->get('laneno') == 0 && $CORE_LOCAL->get('laneno') !== '') {
        // server-side rate table is in op database
        $sql = new SQLManager($CORE_LOCAL->get('localhost'),
                $CORE_LOCAL->get('DBMS'),
                $CORE_LOCAL->get('pDatabase'),
                $CORE_LOCAL->get('localUser'),
                $CORE_LOCAL->get('localPass'));
    }
    if ($sql->table_exists('taxrates')) {
        $ratesR = $sql->query("SELECT id,rate,description FROM taxrates ORDER BY id");
        while($row=$sql->fetch_row($ratesR))
            $rates[] = array($row[0],$row[1],$row[2]);
    }
}
echo "<table><tr><th>ID</th><th>Rate</th><th>Description</th></tr>";
foreach($rates as $rate){
    printf("<tr><td>%d</td><td><input type=text name=TAX_RATE[] value=\"%f\" /></td>
        <td><input type=text name=TAX_DESC[] value=\"%s\" /></td></tr>",
        $rate[0],$rate[1],$rate[2]);
}
printf("<tr><td>(Add)</td><td><input type=text name=TAX_RATE[] value=\"\" /></td>
    <td><input type=text name=TAX_DESC[] value=\"\" /></td></tr></table>");
?>
</td></tr><tr><td colspan=2 class="submitBtn">
<input type=submit value="Save &amp; Re-run installation checks" />
</form>
</td></tr>
</table>
</div> <!--    wrapper -->
<?php

function create_op_dbs($db,$type){
    global $CORE_LOCAL;
    $name = $CORE_LOCAL->get('pDatabase');
    $errors = array();

    if ($CORE_LOCAL->get('laneno') == 0) {
        $errors[] = array(
            'struct' => 'No structures created for lane #0',
            'query' => 'None',
            'details' => 'Zero is reserved for server',
        );

        return $errors;
    }
    
    InstallUtilities::createIfNeeded($db, $type, $name, 'couponcodes', 'op', $errors);
    $chk = $db->query('SELECT Code FROM couponcodes', $name);
    if (!$db->fetch_row($chk)){
        InstallUtilities::loadSampleData($db,'couponcodes');
    }
    else {
        $db->end_query($chk);
    }

    InstallUtilities::createIfNeeded($db, $type, $name, 'custdata', 'op', $errors);

    InstallUtilities::createIfNeeded($db, $type, $name, 'memtype', 'op', $errors);

    InstallUtilities::createIfNeeded($db, $type, $name, 'memberCards', 'op', $errors);

    InstallUtilities::createIfNeeded($db, $type, $name, 'custPreferences', 'op', $errors);

    $cardsViewQ = "CREATE VIEW memberCardsView AS 
        SELECT ".$db->concat("'".$CORE_LOCAL->get('memberUpcPrefix')."'",'c.CardNo','')." as upc, 
        c.CardNo as card_no FROM custdata c";
    if (!$db->table_exists('memberCardsView',$name)){
        InstallUtilities::dbStructureModify($db,'memberCardsView',$cardsViewQ,$errors);
    }
    
    InstallUtilities::createIfNeeded($db, $type, $name, 'departments', 'op', $errors);

    InstallUtilities::createIfNeeded($db, $type, $name, 'employees', 'op', $errors);

    InstallUtilities::createIfNeeded($db, $type, $name, 'globalvalues', 'op', $errors);
    $chk = $db->query('SELECT CashierNo FROM globalvalues', $name);
    if ($db->num_rows($chk) != 1){
        $db->query('TRUNCATE TABLE globalvalues');
        InstallUtilities::loadSampleData($db,'globalvalues');
    }

    InstallUtilities::createIfNeeded($db, $type, $name, 'drawerowner', 'op', $errors);
    $chk = $db->query('SELECT drawer_no FROM drawerowner', $name);
    if ($db->num_rows($chk) == 0){
        $db->query('INSERT INTO drawerowner (drawer_no) VALUES (1)', $name);
        $db->query('INSERT INTO drawerowner (drawer_no) VALUES (2)', $name);
    }

    InstallUtilities::createIfNeeded($db, $type, $name, 'products', 'op', $errors);

    InstallUtilities::createIfNeeded($db, $type, $name, 'dateRestrict', 'op', $errors);

    InstallUtilities::createIfNeeded($db, $type, $name, 'tenders', 'op', $errors);
    $chk = $db->query('SELECT TenderID FROM tenders', $name);
    if ($db->num_rows($chk) == 0){
        InstallUtilities::loadSampleData($db,'tenders');
    }

    InstallUtilities::createIfNeeded($db, $type, $name, 'subdepts', 'op', $errors);

    InstallUtilities::createIfNeeded($db, $type, $name, 'MasterSuperDepts', 'op', $errors);

    InstallUtilities::createIfNeeded($db, $type, $name, 'customReceipt', 'op', $errors);

    InstallUtilities::createIfNeeded($db, $type, $name, 'custReceiptMessage', 'op', $errors);

    InstallUtilities::createIfNeeded($db, $type, $name, 'disableCoupon', 'op', $errors);

    InstallUtilities::createIfNeeded($db, $type, $name, 'houseCoupons', 'op', $errors);

    InstallUtilities::createIfNeeded($db, $type, $name, 'houseVirtualCoupons', 'op', $errors);

    InstallUtilities::createIfNeeded($db, $type, $name, 'houseCouponItems', 'op', $errors);

    InstallUtilities::createIfNeeded($db, $type, $name, 'autoCoupons', 'op', $errors);

    InstallUtilities::createIfNeeded($db, $type, $name, 'ShrinkReasons', 'op', $errors);

    /**
      @deprecated 3Jan14
      Only used in PrehLib::chargeOk()
      Not really necessary to have a dedicated view
    */
    //InstallUtilities::createIfNeeded($db, $type, $name, 'memchargebalance', 'op', $errors);

    InstallUtilities::createIfNeeded($db, $type, $name, 'unpaid_ar_today', 'op', $errors);

    // Update lane_config structure if needed
    if ($db->table_exists('lane_config', $name)){
        $def = $db->table_definition('lane_config', $name);
        if (!isset($def['keycode']) || !isset($def['value']))
            $db->query('DROP TABLE lane_config', $name);
    }
    InstallUtilities::createIfNeeded($db, $type, $name, 'lane_config', 'op', $errors);

    InstallUtilities::createIfNeeded($db, $type, $name, 'parameters', 'op', $errors);
    $chk = $db->query('SELECT param_key FROM parameters', $name);
    if (!$db->fetch_row($chk)) {
        InstallUtilities::loadSampleData($db, 'parameters');
    } else {
        $db->end_query($chk);
    }
    
    return $errors;
}

function create_trans_dbs($db,$type){
    global $CORE_LOCAL;
    $name = $CORE_LOCAL->get('tDatabase');
    $errors = array();

    if ($CORE_LOCAL->get('laneno') == 0) {
        $errors[] = array(
            'struct' => 'No structures created for lane #0',
            'query' => 'None',
            'details' => 'Zero is reserved for server',
        );

        return $errors;
    }
    
    InstallUtilities::createIfNeeded($db, $type, $name, 'dtransactions', 'trans', $errors);

    InstallUtilities::createIfNeeded($db, $type, $name, 'localtrans', 'trans', $errors);

    InstallUtilities::createIfNeeded($db, $type, $name, 'localtransarchive', 'trans', $errors);

    /**
    @deprecated
    Replaced by localtranstoday TABLE
    InstallUtilities::createIfNeeded($db, $type, $name, 'localtrans_today', 'trans', $errors);
    */

    InstallUtilities::createIfNeeded($db, $type, $name, 'suspended', 'trans', $errors);

    InstallUtilities::createIfNeeded($db, $type, $name, 'localtemptrans', 'trans', $errors);

    InstallUtilities::createIfNeeded($db, $type, $name, 'taxrates', 'trans', $errors);

    InstallUtilities::createIfNeeded($db, $type, $name, 'localtranstoday', 'trans', $errors);

    InstallUtilities::createIfNeeded($db, $type, $name, 'memdiscountadd', 'trans', $errors);

    InstallUtilities::createIfNeeded($db, $type, $name, 'memdiscountremove', 'trans', $errors);

    InstallUtilities::createIfNeeded($db, $type, $name, 'screendisplay', 'trans', $errors);

    InstallUtilities::createIfNeeded($db, $type, $name, 'staffdiscountadd', 'trans', $errors);

    InstallUtilities::createIfNeeded($db, $type, $name, 'staffdiscountremove', 'trans', $errors);

    /**
     @deprecated 10Mar14 by Andy
     View layer isn't necessary; can query suspended table directly
    InstallUtilities::createIfNeeded($db, $type, $name, 'suspendedtoday', 'trans', $errors);
    */

    InstallUtilities::createIfNeeded($db, $type, $name, 'couponApplied', 'trans', $errors);

    /* lttsummary, lttsubtotals, and subtotals
     * always get rebuilt to account for tax rate
     * changes */
    include('buildLTTViews.php');
    $errors = buildLTTViews($db,$type,$errors);

    InstallUtilities::createIfNeeded($db, $type, $name, 'taxView', 'trans', $errors);

    $lttR = "CREATE view ltt_receipt as 
        select
        l.description as description,
        case 
            when voided = 5 
                then 'Discount'
            when trans_status = 'M'
                then 'Mbr special'
            when trans_status = 'S'
                then 'Staff special'
            when unitPrice = 0.01
                then ''
            when scale <> 0 and quantity <> 0 
                then ".$db->concat('quantity', "' @ '", 'unitPrice','')."
            when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1
                then ".$db->concat('volume', "' / '", 'unitPrice','')."
            when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1
                then ".$db->concat('quantity', "' @ '", 'volume', "' /'", 'unitPrice','')."
            when abs(itemQtty) > 1 and discounttype = 3
                then ".$db->concat('ItemQtty', "' / '", 'unitPrice','')."
            when abs(itemQtty) > 1
                then ".$db->concat('quantity', "' @ '", 'unitPrice','')."
            when matched > 0
                then '1 w/ vol adj'
            else ''
        end
        as comment,
        total,
        case 
            when trans_status = 'V' 
                then 'VD'
            when trans_status = 'R'
                then 'RF'
            when tax = 1 and foodstamp <> 0
                then 'TF'
            when tax = 1 and foodstamp = 0
                then 'T' 
            when tax = 0 and foodstamp <> 0
                then 'F'
            WHEN (tax > 1 and foodstamp <> 0)
                THEN ".$db->concat('SUBSTR(t.description,1,1)',"'F'",'')."
            WHEN (tax > 1 and foodstamp = 0)
                THEN SUBSTR(t.description,1,1)
            when tax = 0 and foodstamp = 0
                then '' 
        end
        as Status,
        trans_type,
        unitPrice,
        voided,
        CASE 
            WHEN upc = 'DISCOUNT' THEN (
            SELECT MAX(trans_id) FROM localtemptrans WHERE voided=3
            )-1
            WHEN trans_type = 'T' THEN trans_id+99999    
            ELSE trans_id
        END AS trans_id
        from localtemptrans as l
        left join taxrates as t
        on l.tax = t.id
        where voided <> 5 and UPC <> 'TAX'
        AND trans_type <> 'L'";
    if($type == 'mssql'){
        $lttR = "CREATE view ltt_receipt as 
            select
            l.description,
            case 
                when voided = 5 
                    then 'Discount'
                when trans_status = 'M'
                    then 'Mbr special'
                when trans_status = 'S'
                    then 'Staff special'
                when unitPrice = 0.01
                    then ''
                when scale <> 0 and quantity <> 0 
                    then quantity+ ' @ '+ unitPrice
                when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1
                    then volume+ ' /'+ unitPrice
                when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1
                    then Quantity+ ' @ '+Volume+ ' /'+ unitPrice
                when abs(itemQtty) > 1 and discounttype = 3
                    then ItemQtty+ ' /'+ UnitPrice
                when abs(itemQtty) > 1
                    then quantity+' @ '+unitPrice
                when matched > 0
                    then '1 w/ vol adj'
                else ''
            end
            as comment,
            total,
            case 
                when trans_status = 'V' 
                    then 'VD'
                when trans_status = 'R'
                    then 'RF'
                when tax = 1 and foodstamp <> 0
                    then 'TF'
                when tax = 1 and foodstamp = 0
                    then 'T' 
                WHEN (tax > 1 and foodstamp <> 0)
                    THEN LEFT(t.description,1)+'F'
                WHEN (tax > 1 and foodstamp = 0)
                    THEN LEFT(t.description,1)
                when tax = 0 and foodstamp <> 0
                    then 'F'
                when tax = 0 and foodstamp = 0
                    then '' 
            end
            as Status,
            trans_type,
            unitPrice,
            trans_id
            CASE 
                WHEN upc = 'DISCOUNT' THEN (
                SELECT MAX(trans_id) FROM localtemptrans WHERE voided=3
                )-1
                WHEN trans_type = 'T' THEN trans_id+99999    
                ELSE trans_id
            END AS trans_id
            from localtemptrans as l
            left join taxrates as t
            on l.tax = t.id
            where voided <> 5 and UPC <> 'TAX'
            AND trans_type <> 'L'
            order by trans_id";
    }
    InstallUtilities::dbStructureModify($db,'ltt_receipt','DROP VIEW ltt_receipt',$errors);
    if(!$db->table_exists('ltt_receipt',$name)){
        InstallUtilities::dbStructureModify($db,'ltt_receipt',$lttR,$errors);
    }

    $rV = "CREATE view receipt as
        select
        case 
            when trans_type = 'T'
                then     ".$db->concat( "SUBSTR(".$db->concat('UPPER(TRIM(description))','space(44)','').", 1, 44)" 
                    , "right(".$db->concat( 'space(8)', 'FORMAT(-1 * total, 2)','').", 8)" 
                    , "right(".$db->concat( 'space(4)', 'status','').", 4)",'')."
            when voided = 3 
                then     ".$db->concat("SUBSTR(".$db->concat('description', 'space(30)','').", 1, 30)"
                    , 'space(9)'
                    , "'TOTAL'"
                    , 'right('.$db->concat( 'space(8)', 'FORMAT(unitPrice, 2)','').', 8)','')."
            when voided = 2
                then     description
            when voided = 4
                then     description
            when voided = 6
                then     description
            when voided = 7 or voided = 17
                then     ".$db->concat("SUBSTR(".$db->concat('description', 'space(30)','').", 1, 30)"
                    , 'space(14)'
                    , 'right('.$db->concat( 'space(8)', 'FORMAT(unitPrice, 2)','').', 8)'
                    , 'right('.$db->concat( 'space(4)', 'status','').', 4)','')."
            else
                ".$db->concat("SUBSTR(".$db->concat('description', 'space(30)','').", 1, 30)"
                , "' '" 
                , "SUBSTR(".$db->concat('comment', 'space(13)','').", 1, 13)"
                , 'right('.$db->concat('space(8)', 'FORMAT(total, 2)','').', 8)'
                , 'right('.$db->concat('space(4)', 'status','').', 4)','')."
        end
        as linetoprint
        from ltt_receipt
        order by trans_id";
    if($type == 'mssql'){
        $rV = "CREATE  view receipt as
        select top 100 percent
        case 
            when trans_type = 'T'
                then     right((space(44) + upper(rtrim(Description))), 44) 
                    + right((space(8) + convert(varchar, (-1 * Total))), 8) 
                    + right((space(4) + status), 4)
            when voided = 3 
                then     left(Description + space(30), 30) 
                    + space(9) 
                    + 'TOTAL' 
                    + right(space(8) + convert(varchar, UnitPrice), 8)
            when voided = 2
                then     description
            when voided = 4
                then     description
            when voided = 6
                then     description
            when voided = 7 or voided = 17
                then     left(Description + space(30), 30) 
                    + space(14) 
                    + right(space(8) + convert(varchar, UnitPrice), 8) 
                    + right(space(4) + status, 4)
            when sequence < 1000
                then     description
            else
                left(Description + space(30), 30)
                + ' ' 
                + left(Comment + space(13), 13) 
                + right(space(8) + convert(varchar, Total), 8) 
                + right(space(4) + status, 4)
        end
        as linetoprint,
        sequence
        from ltt_receipt
        order by sequence";
    }
    elseif($type == 'pdolite'){
        $rV = str_replace('right(','str_right(',$rV);
        $rV = str_replace('FORMAT(','ROUND(',$rV);
    }

    if(!$db->table_exists('receipt',$name)){
        InstallUtilities::dbStructureModify($db,'receipt',$rV,$errors);
    }

    $rpheader = "CREATE VIEW rp_receipt_header AS
        select
        min(datetime) as dateTimeStamp,
        card_no as memberID,
        register_no,
        emp_no,
        trans_no,
        CAST(sum(case when discounttype = 1 then discount else 0 end) AS decimal(10,2)) as discountTTL,
        CAST(sum(case when discounttype = 2 then memDiscount else 0 end) AS decimal(10,2)) as memSpecial,
        case when (min(datetime) is null) then 0 else
            sum(CASE WHEN discounttype = 4 THEN memDiscount ELSE 0 END)
        end as staffSpecial,
        CAST(sum(case when upc = '0000000008005' then total else 0 end) AS decimal(10,2)) as couponTotal,
        CAST(sum(case when upc = 'MEMCOUPON' then unitPrice else 0 end) AS decimal(10,2)) as memCoupon,
        abs(sum(case when trans_subtype = 'MI' or trans_subtype = 'CX' then total else 0 end)) as chargeTotal,
        sum(case when upc = 'Discount' then total else 0 end) as transDiscount,
        sum(case when trans_type = 'T' then -1 * total else 0 end) as tenderTotal
        from localtranstoday
        WHERE trans_type <> 'L'
        AND datetime >= CURRENT_DATE
        group by register_no, emp_no, trans_no, card_no";
    if($type == 'mssql'){
        $rpheader = "CREATE view rp_receipt_header as
        select
        min(datetime) as dateTimeStamp,
        card_no as memberID,
        register_no,
        emp_no,
        trans_no,
        convert(numeric(10,2), sum(case when discounttype = 1 then discount else 0 end)) as discountTTL,
        convert(numeric(10,2), sum(case when discounttype = 2 then memDiscount else 0 end)) as memSpecial,
        case when (min(datetime) is null) then 0 else
            sum(CASE WHEN discounttype = 4 THEN memDiscount ELSE 0 END)
        end as staffSpecial,
        convert(numeric(10,2), sum(case when upc = '0000000008005' then total else 0 end)) as couponTotal,
        convert(numeric(10,2), sum(case when upc = 'MEMCOUPON' then unitPrice else 0 end)) as memCoupon,
        abs(sum(case when trans_subtype = 'MI' or trans_subtype = 'CX' then total else 0 end)) as chargeTotal,
        sum(case when upc = 'Discount' then total else 0 end) as transDiscount,
        sum(case when trans_type = 'T' then -1 * total else 0 end) as tenderTotal
        from localtranstoday
        WHERE trans_type <> 'L'
        AND datetime >= CURRENT_DATE
        group by register_no, emp_no, trans_no, card_no";
    }
    if(!$db->table_exists('rp_receipt_header',$name)){
        InstallUtilities::dbStructureModify($db,'rp_receipt_header',$rpheader,$errors);
    }

    $rplttR = "CREATE view rp_ltt_receipt as 
        select
        register_no,
        emp_no,
        trans_no,
        l.description as description,
        case 
            when voided = 5 
                then 'Discount'
            when trans_status = 'M'
                then 'Mbr special'
            when trans_status = 'S'
                then 'Staff special'
            when unitPrice = 0.01
                then ''
            when scale <> 0 and quantity <> 0 
                then ".$db->concat('quantity', "' @ '", 'unitPrice','')."
            when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1
                then ".$db->concat('volume', "' / '", 'unitPrice','')."
            when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1
                then ".$db->concat('quantity', "' @ '", 'volume', "' /'", 'unitPrice','')."
            when abs(itemQtty) > 1 and discounttype = 3
                then ".$db->concat('ItemQtty', "' / '", 'unitPrice','')."
            when abs(itemQtty) > 1
                then ".$db->concat('quantity', "' @ '", 'unitPrice','')."
            when matched > 0
                then '1 w/ vol adj'
            else ''
        end
        as comment,
        total,
        case 
            when trans_status = 'V' 
                then 'VD'
            when trans_status = 'R'
                then 'RF'
            WHEN (tax = 1 and foodstamp <> 0)
                THEN 'TF'
            WHEN (tax = 1 and foodstamp = 0)
                THEN 'T' 
            WHEN (tax > 1 and foodstamp <> 0)
                THEN ".$db->concat('SUBSTR(t.description,1,1)',"'F'",'')."
            WHEN (tax > 1 and foodstamp = 0)
                THEN SUBSTR(t.description,1,1)
            when tax = 0 and foodstamp <> 0
                then 'F'
            when tax = 0 and foodstamp = 0
                then '' 
        end
        as Status,
        trans_type,
        unitPrice,
        voided,
        trans_id
        from localtranstoday as l
        left join taxrates as t
        on l.tax = t.id
        where voided <> 5 and UPC <> 'TAX' and UPC <> 'DISCOUNT'
        AND trans_type <> 'L'
        AND datetime >= CURRENT_DATE
        order by emp_no, trans_no, trans_id";
    if($type == 'mssql'){
        $rplttR = "CREATE view rp_ltt_receipt as 
            select
            register_no,
            emp_no,
            trans_no,
            description,
            case 
                when voided = 5 
                    then 'Discount'
                when trans_status = 'M'
                    then 'Mbr special'
                when trans_status = 'S'
                    then 'Staff special'
                when unitPrice = 0.01
                    then ''
                when scale <> 0 and quantity <> 0 
                    then quantity+ ' @ '+ unitPrice
                when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1
                    then volume+ ' /'+ unitPrice
                when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1
                    then Quantity+ ' @ '+ Volume+ ' /'+ unitPrice
                when abs(itemQtty) > 1 and discounttype = 3
                    then ItemQtty+' /'+ UnitPrice
                when abs(itemQtty) > 1
                    then quantity+ ' @ '+ unitPrice
                when matched > 0
                    then '1 w/ vol adj'
                else ''
            end
            as comment,
            total,
            case 
                when trans_status = 'V' 
                    then 'VD'
                when trans_status = 'R'
                    then 'RF'
                WHEN (tax = 1 and foodstamp <> 0)
                    THEN 'TF'
                WHEN (tax = 1 and foodstamp = 0)
                    THEN 'T' 
                WHEN (tax > 1 and foodstamp <> 0)
                    THEN LEFT(t.description,1)+'F'
                WHEN (tax > 1 and foodstamp = 0)
                    THEN LEFT(t.description,1)
                when tax = 0 and foodstamp <> 0
                    then 'F'
                when tax = 0 and foodstamp = 0
                    then '' 
            end
            as Status,
            trans_type,
            unitPrice,
            voided,
            trans_id
            from localtranstoday as l
            left join taxrates as t
            on l.tax = t.id
            where voided <> 5 and UPC <> 'TAX' and UPC <> 'DISCOUNT'
            AND trans_type <> 'L'
            AND datetime >= CURRENT_DATE
            order by emp_no, trans_no, trans_id";
    }
    InstallUtilities::dbStructureModify($db,'rp_ltt_receipt','DROP VIEW rp_ltt_receipt',$errors);
    if(!$db->table_exists('rp_ltt_receipt',$name)){
        InstallUtilities::dbStructureModify($db,'rp_ltt_receipt',$rplttR,$errors);
    }

    $rprV = "CREATE view rp_receipt  as
        select
        register_no,
        emp_no,
        trans_no,
        case 
            when trans_type = 'T'
                then     ".$db->concat( "SUBSTR(".$db->concat('UPPER(TRIM(description))','space(44)','').", 1, 44)" 
                    , "right(".$db->concat( 'space(8)', 'FORMAT(-1 * total, 2)','').", 8)" 
                    , "right(".$db->concat( 'space(4)', 'status','').", 4)",'')."
            when voided = 3 
                then     ".$db->concat("SUBSTR(".$db->concat('description', 'space(30)','').", 1, 30)"
                    , 'space(9)'
                    , "'TOTAL'"
                    , 'right('.$db->concat( 'space(8)', 'FORMAT(unitPrice, 2)','').', 8)','')."
            when voided = 2
                then     description
            when voided = 4
                then     description
            when voided = 6
                then     description
            when voided = 7 or voided = 17
                then     ".$db->concat("SUBSTR(".$db->concat('description', 'space(30)','').", 1, 30)"
                    , 'space(14)'
                    , 'right('.$db->concat( 'space(8)', 'FORMAT(unitPrice, 2)','').', 8)'
                    , 'right('.$db->concat( 'space(4)', 'status','').', 4)','')."
            else
                ".$db->concat("SUBSTR(".$db->concat('description', 'space(30)','').", 1, 30)"
                , "' '" 
                , "SUBSTR(".$db->concat('comment', 'space(13)','').", 1, 13)"
                , 'right('.$db->concat('space(8)', 'FORMAT(total, 2)','').', 8)'
                , 'right('.$db->concat('space(4)', 'status','').', 4)','')."
        end
        as linetoprint,
        trans_id
        from rp_ltt_receipt";
    if($type == 'mssql'){
        $rprV = "CREATE view rp_receipt  as
        select
        register_no,
        emp_no,
        trans_no,
        case 
            when trans_type = 'T'
                then     right((space(44) + upper(rtrim(Description))), 44) 
                    + right((space(8) + convert(varchar, (-1 * Total))), 8) 
                    + right((space(4) + status), 4)
            when voided = 3 
                then     left(Description + space(30), 30) 
                    + space(9) 
                    + 'TOTAL' 
                    + right(space(8) + convert(varchar, UnitPrice), 8)
            when voided = 2
                then     description
            when voided = 4
                then     description
            when voided = 6
                then     description
            when voided = 7 or voided = 17
                then     left(Description + space(30), 30) 
                    + space(14) 
                    + right(space(8) + convert(varchar, UnitPrice), 8) 
                    + right(space(4) + status, 4)
            else
                left(Description + space(30), 30)
                + ' ' 
                + left(Comment + space(13), 13) 
                + right(space(8) + convert(varchar, Total), 8) 
                + right(space(4) + status, 4)
        end
        as linetoprint,
        trans_id
        from rp_ltt_receipt";
    }
    elseif($type == 'pdolite'){
        $rprV = str_replace('right(','str_right(',$rprV);
        $rprV = str_replace('FORMAT(','ROUND(',$rprV);
    }
    if(!$db->table_exists('rp_receipt',$name)){
        InstallUtilities::dbStructureModify($db,'rp_receipt',$rprV,$errors);
    }

    InstallUtilities::createIfNeeded($db, $type, $name, 'PaycardTransactions', 'trans', $errors);

    InstallUtilities::createIfNeeded($db, $type, $name, 'efsnetRequest', 'trans', $errors);

    InstallUtilities::createIfNeeded($db, $type, $name, 'efsnetRequestMod', 'trans', $errors);

    InstallUtilities::createIfNeeded($db, $type, $name, 'efsnetResponse', 'trans', $errors);

    InstallUtilities::createIfNeeded($db, $type, $name, 'efsnetTokens', 'trans', $errors);

    $ccV = "CREATE view ccReceiptView 
        AS 
        select
          (case r.mode
            when 'tender' then 'Credit Card Purchase'
            when 'retail_sale' then 'Credit Card Purchase'
            when 'Credit_Sale' then 'Credit Card Purchase'
            when 'retail_alone_credit' then 'Credit Card Refund'
            when 'Credit_Return' then 'Credit Card Refund'
            when 'refund' then 'Credit Card Refund'
            else ''
          end) as tranType,
          (case r.mode 
            when 'refund' then -1*r.amount
            else r.amount
          end) as amount,
          r.PAN,
          (case r.manual when 1 then 'Manual' else 'Swiped' end) as entryMethod,
          r.issuer,
          r.name,
          s.xResultMessage,
          s.xApprovalNumber, 
          s.xTransactionID, 
          r.date, r.cashierNo, r.laneNo, r.transNo, r.transID, r.datetime,
          0 as sortorder
        from efsnetRequest r
        join efsnetResponse s
          on s.date=r.date
          and s.cashierNo=r.cashierNo
          and s.laneNo=r.laneNo
          and s.transNo=r.transNo
          and s.transID=r.transID
        where s.validResponse=1 and 
        (s.xResultMessage like '%APPROVE%' or s.xResultMessage like '%PENDING%')

        union all

        select
          (case r.mode
            when 'tender' then 'Credit Card Purchase CANCELED'
            when 'retail_sale' then 'Credit Card Purchase CANCELLED'
            when 'Credit_Sale' then 'Credit Card Purchase CANCELLED'
            when 'retail_alone_credit' then 'Credit Card Refund CANCELLED'
            when 'Credit_Return' then 'Credit Card Refund CANCELLED'
            when 'refund' then 'Credit Card Refund CANCELED'
            else ''
          end) as tranType,
          (case r.mode when 'refund' then r.amount else -1*r.amount end) as amount,  
          r.PAN,
          (case r.manual when 1 then 'Manual' else 'Swiped' end) as entryMethod,
          r.issuer,
          r.name,
          s.xResultMessage,
          s.xApprovalNumber,
          s.xTransactionID,
          r.date, r.cashierNo, r.laneNo, r.transNo, r.transID, r.datetime,
          1 as sortorder
        from efsnetRequestMod m
        join efsnetRequest r
          on r.date=m.date
          and r.cashierNo=m.cashierNo
          and r.laneNo=m.laneNo
          and r.transNo=m.transNo
          and r.transID=m.transID
        join efsnetResponse s
          on s.date=r.date
          and s.cashierNo=r.cashierNo
          and s.laneNo=r.laneNo
          and s.transNo=r.transNo
          and s.transID=r.transID
        where s.validResponse=1 and (s.xResultMessage like '%APPROVE%')
          and m.validResponse=1 and 
          (m.xResponseCode=0 or m.xResultMessage like '%APPROVE%')
          and m.mode='void'";
    if(!$db->table_exists('ccReceiptView',$name)){
        InstallUtilities::dbStructureModify($db,'ccReceiptView',$ccV,$errors);
    }

    $sigCaptureTable = "CREATE TABLE CapturedSignature (
        tdate datetime,
        emp_no int,
        register_no int,
        trans_no int,
        trans_id int,
        filetype char(3),
        filecontents blob)";
    if($type == "mssql"){
        $sigCaptureTable = str_replace("blob","image",$sigCaptureTable);
    }
    if (!$db->table_exists("CapturedSignature")){
        InstallUtilities::dbStructureModify($db,'CapturedSignature',$sigCaptureTable,$errors);
    }

    $lttG = "CREATE  view ltt_grouped as
    select     upc,description,trans_type,trans_subtype,sum(itemQtty)as itemqtty,
        discounttype,volume,
        trans_status,
        case when voided=1 then 0 else voided end as voided,
        department,sum(quantity) as quantity,matched,min(trans_id) as trans_id,
        scale,
        sum(unitprice) as unitprice, 
        CAST(sum(total) AS decimal(10,2)) as total,
        sum(regPrice) as regPrice,tax,foodstamp,charflag,
        case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end as grouper
    from localtemptrans
    where description not like '** YOU SAVED %' and trans_status = 'M'
    group by upc,description,trans_type,trans_subtype,discounttype,volume,
        trans_status,
        department,scale,case when voided=1 then 0 else voided end,
        matched,tax,foodstamp,charflag,
        case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end

    union all

    select     upc,case when numflag=1 then ".$db->concat('description',"'*'",'')." else description end as description,
        trans_type,trans_subtype,sum(itemQtty)as itemqtty,discounttype,volume,
        trans_status,
        case when voided=1 then 0 else voided end as voided,
        department,sum(quantity) as quantity,matched,min(trans_id) as trans_id,
        scale,unitprice,CAST(sum(total) AS decimal(10,2)) as total,regPrice,tax,foodstamp,charflag,
        case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end as grouper
    from localtemptrans
    where description not like '** YOU SAVED %' and trans_status !='M'
    AND trans_type <> 'L'
    group by upc,description,trans_type,trans_subtype,discounttype,volume,
        trans_status,
        department,scale,case when voided=1 then 0 else voided end,
        unitprice,regPrice,matched,tax,foodstamp,charflag,
        case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end

    union all

    select     upc,
        case when discounttype=1 then
        ".$db->concat("' > you saved \$'",'CAST(CAST(sum(quantity*regprice-quantity*unitprice) AS decimal(10,2)) AS char(20))',"'  <'",'')."
        when discounttype=2 then
        ".$db->concat("' > you saved \$'",'CAST(CAST(sum(quantity*regprice-quantity*unitprice) AS decimal(10,2)) AS char(20))',"'  Member Special <'",'')."
        end as description,
        trans_type,'0' as trans_subtype,0 as itemQtty,discounttype,volume,
        'D' as trans_status,
        2 as voided,
        department,0 as quantity,matched,min(trans_id)+1 as trans_id,
        scale,0 as unitprice,
        0 as total,
        0 as regPrice,0 as tax,0 as foodstamp,charflag,
        case when trans_status='d' or scale=1 then trans_id else scale end as grouper
    from localtemptrans
    where description not like '** YOU SAVED %' and (discounttype=1 or discounttype=2)
    AND trans_type <> 'L'
    group by upc,description,trans_type,trans_subtype,discounttype,volume,
        department,scale,matched,
        case when trans_status='d' or scale=1 then trans_id else scale end
    having CAST(sum(quantity*regprice-quantity*unitprice) AS decimal(10,2))<>0";
    if($type == 'mssql'){
        $lttG = "CREATE   view ltt_grouped as
        select     upc,description,trans_type,trans_subtype,sum(itemQtty)as itemqtty,
            discounttype,volume,
            trans_status,
            case when voided=1 then 0 else voided end as voided,
            department,sum(quantity) as quantity,matched,min(trans_id) as trans_id,
            scale,
            sum(unitprice) as unitprice, 
            sum(total) as total,
            sum(regPrice) as regPrice,tax,foodstamp,charflag,
            case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end as grouper
        from localtemptrans
        where description not like '** YOU SAVED %' and trans_status = 'M'
        group by upc,description,trans_type,trans_subtype,discounttype,volume,
            trans_status,
            department,scale,case when voided=1 then 0 else voided end,
            matched,tax,foodstamp,charflag,
            case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end

        union all

        select     upc,case when numflag=1 then description+'*' else description end as description,
            trans_type,trans_subtype,sum(itemQtty)as itemqtty,discounttype,volume,
            trans_status,
            case when voided=1 then 0 else voided end as voided,
            department,sum(quantity) as quantity,matched,min(trans_id) as trans_id,
            scale,unitprice,sum(total) as total,regPrice,tax,foodstamp,charflag,
            case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end as grouper
        from localtemptrans
        where description not like '** YOU SAVED %' and trans_status !='M'
        AND trans_type <> 'L'
        group by upc,description,trans_type,trans_subtype,discounttype,volume,
            trans_status,
            department,scale,case when voided=1 then 0 else voided end,
            unitprice,regPrice,matched,tax,foodstamp,charflag,
            case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end

        union all

        select     upc,
            case when discounttype=1 then
            ' > you saved $'+convert(varchar(20),convert(money,sum(quantity*regprice-quantity*unitprice)))+'  <'
            when discounttype=2 then
            ' > you saved $'+convert(varchar(20),convert(money,sum(quantity*regprice-quantity*unitprice)))+'  Member Special <'
            end as description,
            trans_type,'0' as trans_subtype,0 as itemQtty,discounttype,volume,
            'D' as trans_status,
            2 as voided,
            department,0 as quantity,matched,min(trans_id)+1 as trans_id,
            scale,0 as unitprice,
            0 as total,
            0 as regPrice,0 as tax,0 as foodstamp,charflag,
            case when trans_status='d' or scale=1 then trans_id else scale end as grouper
        from localtemptrans
        where description not like '** YOU SAVED %' and (discounttype=1 or discounttype=2)
        AND trans_type <> 'L'
        group by upc,description,trans_type,trans_subtype,discounttype,volume,
            department,scale,matched,
            case when trans_status='d' or scale=1 then trans_id else scale end
        having convert(money,sum(quantity*regprice-quantity*unitprice))<>0";
    }
    InstallUtilities::dbStructureModify($db,'ltt_grouped','DROP VIEW ltt_grouped',$errors);
    if(!$db->table_exists('ltt_grouped',$name)){
        InstallUtilities::dbStructureModify($db,'ltt_grouped',$lttG,$errors);
    }


    $lttreorderG = "CREATE   view ltt_receipt_reorder_g as
    select 
    l.description as description,
    case 
        when voided = 5 
            then 'Discount'
        when trans_status = 'M'
            then 'Mbr special'
        when trans_status = 'S'
            then 'Staff special'
        when unitPrice = 0.01
            then ''
        when charflag = 'SO'
            then ''
        when scale <> 0 and quantity <> 0 
            then ".$db->concat('CAST(quantity AS char)',"' @ '",'CAST(unitPrice AS char)','')."
        when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1
            then ".$db->concat('CAST(volume AS char)',"' / '",'CAST(unitPrice AS char)','')."
        when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1
            then ".$db->concat('CAST(quantity AS char)',"' @ '",'CAST(volume AS char)',"' /'",'CAST(unitPrice AS char)','')."
        when abs(itemQtty) > 1 and discounttype = 3
            then ".$db->concat('CAST(ItemQtty AS char)',"' / '",'CAST(unitPrice AS char)','')."
        when abs(itemQtty) > 1
            then ".$db->concat('CAST(quantity AS char)',"' @ '",'CAST(unitPrice AS char)','')."
        when matched > 0
            then '1 w/ vol adj'
        else ''
    end
    as comment,
    total,
    case 
        when trans_status = 'V' 
            then 'VD'
        when trans_status = 'R'
            then 'RF'
        when tax = 1 and foodstamp <> 0
            then 'TF'
        when tax = 1 and foodstamp = 0
            then 'T' 
        WHEN (tax > 1 and foodstamp <> 0)
            THEN ".$db->concat('SUBSTR(t.description,1,1)',"'F'",'')."
        WHEN (tax > 1 and foodstamp = 0)
            THEN SUBSTR(t.description,1,1)
        when tax = 0 and foodstamp <> 0
            then 'F'
        when tax = 0 and foodstamp = 0
            then '' 
    end
    as status,
    case when trans_subtype='CM' or voided in (10,17)
        then 'CM' else trans_type
    end
    as trans_type,
    unitPrice,
    voided,
    trans_id + 1000 as sequence,
    department,
    upc,
    trans_subtype
    from ltt_grouped as l
    left join taxrates as t
    on l.tax = t.id
    where voided <> 5 and UPC <> 'TAX' and UPC <> 'DISCOUNT'
    AND trans_type <> 'L'
    and not (trans_status='M' and total=CAST('0.00' AS decimal(10,2)))

    union

    select
    '  ' as description,
    ' ' as comment,
    0 as total,
    ' ' as Status,
    ' ' as trans_type,
    0 as unitPrice,
    0 as voided,
    999 as sequence,
    '' as department,
    '' as upc,
    '' as trans_subtype";

    if($type == 'mssql'){
        $lttreorderG = "CREATE view ltt_receipt_reorder_g as
        select top 100 percent
        l.description,
        case 
            when voided = 5 
                then 'Discount'
            when trans_status = 'M'
                then 'Mbr special'
            when trans_status = 'S'
                then 'Staff special'
            when unitPrice = 0.01
                then ''
            when scale <> 0 and quantity <> 0 
                then convert(varchar, quantity) + ' @ ' + convert(varchar, unitPrice)
            when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1
                then convert(varchar, volume) + ' /' + convert(varchar, unitPrice)
            when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1
                then convert(varchar, Quantity) + ' @ ' + convert(varchar, Volume) + ' /' + convert(varchar, unitPrice)
            when abs(itemQtty) > 1 and discounttype = 3
                then convert(varchar,ItemQtty) + ' /' + convert(varchar, UnitPrice)
            when abs(itemQtty) > 1
                then convert(varchar, quantity) + ' @ ' + convert(varchar, unitPrice)    
            when matched > 0
                then '1 w/ vol adj'
            else ''
        end
        as comment,
        total,
        case 
            when trans_status = 'V' 
                then 'VD'
            when trans_status = 'R'
                then 'RF'
            WHEN (tax = 1 and foodstamp <> 0)
                THEN 'TF'
            WHEN (tax = 1 and foodstamp = 0)
                THEN 'T' 
            WHEN (tax > 1 and foodstamp <> 0)
                THEN LEFT(t.description,1)+'F'
            WHEN (tax > 1 and foodstamp = 0)
                THEN LEFT(t.description,1)
            when tax = 0 and foodstamp <> 0
                then 'F'
            when tax = 0 and foodstamp = 0
                then '' 
        end
        as Status,
        case when trans_subtype='CM' or voided in (10,17)
            then 'CM' else trans_type
        end
        as trans_type,
        unitPrice,
        voided,
        trans_id + 1000 as sequence,
        department,
        upc,
        trans_subtype
        from ltt_grouped as l
        left join taxrates as t
        on l.tax = t.id
        where voided <> 5 and UPC <> 'TAX' and UPC <> 'DISCOUNT'
        AND trans_type <> 'L'
        and not (trans_status='M' and total=convert(money,'0.00'))

        union

        select
        '  ' as description,
        ' ' as comment,
        0 as total,
        ' ' as Status,
        ' ' as trans_type,
        0 as unitPrice,
        0 as voided,
        999 as sequence,
        '' as department,
        '' as upc,
        '' as trans_subtype";
    }
    InstallUtilities::dbStructureModify($db,'ltt_receipt_reorder_g','DROP VIEW ltt_receipt_reorder_g',$errors);
    if(!$db->table_exists('ltt_receipt_reorder_g',$name)){
        InstallUtilities::dbStructureModify($db,'ltt_receipt_reorder_g',$lttreorderG,$errors);
    }

    $reorderG = "CREATE   view receipt_reorder_g as
        select 
        case 
            when trans_type = 'T' 
                then     
                    case when trans_subtype = 'CP' and upc<>'0'
                    then    ".$db->concat(
                        "SUBSTR(".$db->concat('description','space(30)','').",1,30)",
                        "' '",
                        "SUBSTR(".$db->concat('comment','space(12)','').",1,12)",
                        "right(".$db->concat('space(8)','CAST(total AS char)','').",8)",
                        "right(".$db->concat('space(4)','status','').",4)",'')." 
                    else     ".$db->concat( 
                        "right(".$db->concat('space(44)','upper(description)','').",44)", 
                        "right(".$db->concat('space(8)','CAST((-1*total) AS char)','').",8)",
                        "right(".$db->concat('space(4)','status','').",4)",'')." 
                    end 
            when voided = 3 
                then     ".$db->concat( 
                    "SUBSTR(".$db->concat('description','space(30)','').",1,30)",
                    "space(9)", 
                    "'TOTAL'", 
                    "right(".$db->concat('space(8)','CAST(unitPrice AS char)','').",8)",'')."
            when voided = 2
                then     description
            when voided = 4
                then     description
            when voided = 6
                then     description
            when voided = 7 or voided = 17
                then     ".$db->concat(
                    "SUBSTR(".$db->concat('description','space(30)','').",1,30)",
                    "space(14)", 
                    "right(".$db->concat('space(8)','CAST(unitPrice AS char)','').",8)",
                    "right(".$db->concat('space(4)','status','').",4)",'')." 
            when sequence < 1000
                then     description
            else
                ".$db->concat(
                    "SUBSTR(".$db->concat('description','space(30)','').",1,30)",
                    "' '",
                    "SUBSTR(".$db->concat('comment','space(12)','').",1,12)",
                    "right(".$db->concat('space(8)','CAST(total AS char)','').",8)",
                    "right(".$db->concat('space(4)','status','').",4)",'')." 
            end as linetoprint,
        sequence,
        department,
        super_name as dept_name,
        trans_type,
        upc
        from ltt_receipt_reorder_g r
        left outer join ".$CORE_LOCAL->get('pDatabase').".MasterSuperDepts d on r.department=d.dept_ID
        where r.total<>0 or r.unitPrice=0
        order by sequence";
    
    if($type == 'mssql'){
        $reorderG = "CREATE view receipt_reorder_g as
        select top 100 percent
        case 
            when trans_type = 'T' 
                then     
                    case when trans_subtype = 'CP' and upc<>'0'
                    then    left(Description + space(30), 30)
                        + ' ' 
                        + left(Comment + space(12), 12) 
                        + right(space(8) + convert(varchar, Total), 8) 
                        + right(space(4) + status, 4) 
                    else     right((space(44) + upper(rtrim(Description))), 44) 
                        + right((space(8) + convert(varchar, (-1 * Total))), 8) 
                        + right((space(4) + status), 4) 
                    end 
            when voided = 3 
                then     left(Description + space(30), 30) 
                    + space(9) 
                    + 'TOTAL' 
                    + right(space(8) + convert(varchar, UnitPrice), 8)
            when voided = 2
                then     description
            when voided = 4
                then     description
            when voided = 6
                then     description
            when voided = 7 or voided = 17
                then     left(Description + space(30), 30) 
                    + space(14) 
                    + right(space(8) + convert(varchar, UnitPrice), 8) 
                    + right(space(4) + status, 4)
            when sequence < 1000
                then     description
            else
                left(Description + space(30), 30)
                + ' ' 
                + left(Comment + space(12), 12) 
                + right(space(8) + convert(varchar, Total), 8) 
                + right(space(4) + status, 4)
            end
            as linetoprint,
            sequence,
            department,
            dept_name,
            trans_type,
            upc
            from ltt_receipt_reorder_g r
            left outer join ".$CORE_LOCAL->get('pDatabase')."dbo.MasterSuperDepts
                   d on r.department=d.dept_ID
            where r.total<>0 or r.unitprice=0
            order by sequence";
    }
    elseif($type == 'pdolite'){
        $reorderG = str_replace('right(','str_right(',$reorderG);
    }
    if(!$db->table_exists('receipt_reorder_g',$name)){
        InstallUtilities::dbStructureModify($db,'receipt_reorder_g',$reorderG,$errors);
    }


    $unionsG = "CREATE view receipt_reorder_unions_g as
    select linetoprint,
    sequence,dept_name,1 as ordered,upc
    from receipt_reorder_g
    where (department<>0 or trans_type IN ('CM','I'))
    and linetoprint not like 'member discount%'

    union all

    select replace(replace(replace(r1.linetoprint,'** T',' = t'),' **',' = '),'W','w') as linetoprint,
    r1.sequence,r2.dept_name,1 as ordered,r2.upc
    from receipt_reorder_g as r1 join receipt_reorder_g as r2 on r1.sequence+1=r2.sequence
    where r1.linetoprint like '** T%' and r2.dept_name is not null and r1.linetoprint<>'** Tare Weight 0 **'

    union all

    select
    ".$db->concat(
    "SUBSTR(".$db->concat("'** '","trim(CAST(percentDiscount AS char))","'% Discount Applied **'",'space(30)','').",1,30)",
    "' '", 
    "space(13)",
    "right(".$db->concat('space(8)',"CAST((-1*transDiscount) AS char)",'').",8)",
    "space(4)",'')." as linetoprint,
    0 as sequence,null as dept_name,2 as ordered,
    '' as upc
    from subtotals
    where percentDiscount<>0

    union all

    select linetoprint,sequence,null as dept_name,2 as ordered,upc
    from receipt_reorder_g
    where linetoprint like 'member discount%'

    union all

    select 
    ".$db->concat(
    "right(".$db->concat('space(44)',"'SUBTOTAL'",'').",44)",
    "right(".$db->concat('space(8)',"CAST(round(l.runningTotal-s.taxTotal-l.tenderTotal,2) AS char)",'').",8)",
    "space(4)",'')." as linetoprint,1 as sequence,null as dept_name,3 as ordered,'' as upc
    from lttsummary as l, subtotals as s

    union all

    select 
    ".$db->concat(
    "right(".$db->concat('space(44)',"'TAX'",'').",44)",
    "right(".$db->concat('space(8)',"CAST(round(taxTotal,2) AS char)",'').",8)", 
    "space(4)",'')." as linetoprint,
    2 as sequence,null as dept_name,3 as ordered,'' as upc
    from subtotals

    union all

    select 
    ".$db->concat(
    "right(".$db->concat('space(44)',"'TOTAL'",'').",44)",
    "right(".$db->concat('space(8)',"CAST(runningTotal-tenderTotal AS char)",'').",8)", 
    "space(4)",'')." as linetoprint,3 as sequence,null as dept_name,3 as ordered,'' as upc
    from lttsummary

    union all

    select linetoprint,sequence,dept_name,4 as ordered,upc
    from receipt_reorder_g
    where (trans_type='T' and department = 0)
    or (department = 0 and trans_type NOT IN ('CM','I')
    and linetoprint NOT LIKE '** %'
    and linetoprint NOT LIKE 'Subtotal%') 

    union all

    select 
    ".$db->concat(
    "right(".$db->concat('space(44)',"'CURRENT AMOUNT DUE'",'').",44)",
    "right(".$db->concat('space(8)',"CAST(runningTotal-transDiscount AS char)",'').",8)", 
    "space(4)",'')." as linetoprint,
    5 as sequence,
    null as dept_name,
    5 as ordered,'' as upc
    from subtotals where runningTotal <> 0 ";

    if($type == 'mssql'){
        $unionsG = "CREATE view receipt_reorder_unions_g as
        select linetoprint,
        sequence,dept_name,1 as ordered,upc
        from receipt_reorder_g
        where (department<>0 or trans_type IN ('CM','I'))
        and linetoprint not like 'member discount%'

        union all

        select replace(replace(replace(r1.linetoprint,'** T',' = T'),' **',' = '),'W','w') as linetoprint,
        r1.[sequence],r2.dept_name,1 as ordered,r2.upc
        from receipt_reorder_g r1 join receipt_reorder_g r2 on r1.[sequence]+1=r2.[sequence]
        where r1.linetoprint like '** T%' and r2.dept_name is not null and r1.linetoprint<>'** Tare Weight 0 **'

        union all

        select
        left('** '+rtrim(convert(char,percentdiscount))+'% Discount Applied **' + space(30), 30)
        + ' ' 
        + left('' + space(13), 13) 
        + right(space(8) + convert(varchar, (-1*transDiscount)), 8) 
        + right(space(4) + '', 4),
        0 as sequence,null as dept_name,2 as ordered,
        '' as upc
        from subtotals
        where percentdiscount<>0

        union all

        select linetoprint,sequence,null as dept_name,2 as ordered,upc
        from receipt_reorder_g
        where linetoprint like 'member discount%'

        union all

        select 
        right((space(44) + upper(rtrim('SUBTOTAL'))), 44) 
        + right((space(8) + convert(varchar,round(l.runningTotal-s.taxTotal-l.tenderTotal,2))),8)
        + right((space(4) + ''), 4) as linetoprint,1 as sequence,null as dept_name,3 as ordered,'' as upc
        from lttsummary as l, subtotals as s

        union all

        select 
        right((space(44) + upper(rtrim('TAX'))), 44) 
        + right((space(8) + convert(varchar,round(taxtotal,2))), 8) 
        + right((space(4) + ''), 4) as linetoprint,
        2 as sequence,null as dept_name,3 as ordered,'' as upc
        from subtotals

        union all

        select 
        right((space(44) + upper(rtrim('TOTAL'))), 44) 
        + right((space(8) +convert(varchar,runningtotal-tendertotal)),8)
        + right((space(4) + ''), 4) as linetoprint,3 as sequence,null as dept_name,3 as ordered,'' as upc
        from lttsummary

        union all

        select linetoprint,sequence,dept_name,4 as ordered,upc
        from receipt_reorder_g
        where (trans_type='T' and department = 0)
        or (department = 0 and trans_type NOT IN ('CM','I') and linetoprint like '%Coupon%')

        union all

        select 
        right((space(44) + upper(rtrim('Current Amount Due'))), 44) 
        +right((space(8) + convert(varchar,subtotal)),8)
        + right((space(4) + ''), 4) as linetoprint,
        5 as sequence,
        null as dept_name,
        5 as ordered,'' as upc
        from subtotals where runningtotal <> 0 ";
    }
    elseif($type == 'pdolite'){
        $unionsG = str_replace('right(','str_right(',$unionsG);
    }
    InstallUtilities::dbStructureModify($db,'receipt_reorder_unions_g','DROP VIEW receipt_reorder_unions_g',$errors);
    if(!$db->table_exists('receipt_reorder_unions_g',$name)){
        InstallUtilities::dbStructureModify($db,'receipt_reorder_unions_g',$unionsG,$errors);
    }

    $rplttG = "CREATE     view rp_ltt_grouped as
        select     register_no,emp_no,trans_no,card_no,
            upc,description,trans_type,trans_subtype,sum(itemQtty)as itemqtty,
            discounttype,volume,
            trans_status,
            case when voided=1 then 0 else voided end as voided,
            department,sum(quantity) as quantity,matched,min(trans_id) as trans_id,
            scale,
            sum(unitprice) as unitprice, 
            CAST(sum(total) AS decimal(10,2)) as total,
            sum(regPrice) as regPrice,tax,foodstamp,
            case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end as grouper
        from localtranstoday
        where description not like '** YOU SAVED %' and trans_status = 'M'
        AND datetime >= CURRENT_DATE
        group by register_no,emp_no,trans_no,card_no,
            upc,description,trans_type,trans_subtype,discounttype,volume,
            trans_status,
            department,scale,case when voided=1 then 0 else voided end,
            matched,tax,foodstamp,
            case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end

        union all

        select     register_no,emp_no,trans_no,card_no,
            upc,case when numflag=1 then ".$db->concat('description',"'*'",'')." else description end as description,
            trans_type,trans_subtype,sum(itemQtty)as itemqtty,discounttype,volume,
            trans_status,
            case when voided=1 then 0 else voided end as voided,
            department,sum(quantity) as quantity,matched,min(trans_id) as trans_id,
            scale,unitprice,CAST(sum(total) AS decimal(10,2)) as total,regPrice,tax,foodstamp,
            case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end as grouper
        from localtranstoday
        where description not like '** YOU SAVED %' and trans_status !='M'
        AND datetime >= CURRENT_DATE
        AND trans_type <> 'L'
        group by register_no,emp_no,trans_no,card_no,
            upc,description,trans_type,trans_subtype,discounttype,volume,
            trans_status,
            department,scale,case when voided=1 then 0 else voided end,
            unitprice,regPrice,matched,tax,foodstamp,
            case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end

        union all

        select     register_no,emp_no,trans_no,card_no,
            upc,
            case when discounttype=1 then
            ".$db->concat("' > you saved \$'",'CAST(CAST(sum(quantity*regprice-quantity*unitprice) AS decimal(10,2)) AS char(20))',"'  <'",'')."
            when discounttype=2 then
            ".$db->concat("' > you saved \$'",'CAST(CAST(sum(quantity*regprice-quantity*unitprice) AS decimal(10,2)) AS char(20))',"'  Member Special <'",'')."
            end as description,
            trans_type,'0' as trans_subtype,0 as itemQtty,discounttype,volume,
            'D' as trans_status,
            2 as voided,
            department,0 as quantity,matched,min(trans_id)+1 as trans_id,
            scale,0 as unitprice,
            0 as total,
            0 as regPrice,0 as tax,0 as foodstamp,
            case when trans_status='d' or scale=1 then trans_id else scale end as grouper
        from localtranstoday
        where description not like '** YOU SAVED %' and (discounttype=1 or discounttype=2)
        AND datetime >= CURRENT_DATE
        AND trans_type <> 'L'
        group by register_no,emp_no,trans_no,card_no,
            upc,description,trans_type,trans_subtype,discounttype,volume,
            department,scale,matched,
            case when trans_status='d' or scale=1 then trans_id else scale end
        having CAST(sum(quantity*regprice-quantity*unitprice) AS decimal(10,2))<>0";
    if($type == 'mssql'){
        $rplttG = "CREATE      view rp_ltt_grouped as
        select     register_no,emp_no,trans_no,card_no,
            upc,description,trans_type,trans_subtype,sum(itemQtty)as itemqtty,
            discounttype,volume,
            trans_status,
            case when voided=1 then 0 else voided end as voided,
            department,sum(quantity) as quantity,matched,min(trans_id) as trans_id,
            scale,
            sum(unitprice) as unitprice, 
            sum(total) as total,
            sum(regPrice) as regPrice,tax,foodstamp,
            case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end as grouper
        from localtranstoday
        where description not like '** YOU SAVED %' and trans_status = 'M'
        AND datetime >= CURRENT_DATE
        group by register_no,emp_no,trans_no,card_no,
            upc,description,trans_type,trans_subtype,discounttype,volume,
            trans_status,
            department,scale,case when voided=1 then 0 else voided end,
            matched,tax,foodstamp,
            case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end

        union all

        select     register_no,emp_no,trans_no,card_no,
            upc,case when numflag=1 then description+'*' else description end as description,
            trans_type,trans_subtype,sum(itemQtty)as itemqtty,discounttype,volume,
            trans_status,
            case when voided=1 then 0 else voided end as voided,
            department,sum(quantity) as quantity,matched,min(trans_id) as trans_id,
            scale,unitprice,sum(total) as total,regPrice,tax,foodstamp,
            case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end as grouper
        from localtranstoday
        where description not like '** YOU SAVED %' and trans_status !='M'
        AND datetime >= CURRENT_DATE
        AND trans_type <> 'L'
        group by register_no,emp_no,trans_no,card_no,
            upc,description,trans_type,trans_subtype,discounttype,volume,
            trans_status,
            department,scale,case when voided=1 then 0 else voided end,
            unitprice,regPrice,matched,tax,foodstamp,
            case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end

        union all

        select     register_no,emp_no,trans_no,card_no,
            upc,
            case when discounttype=1 then
            ' > YOU SAVED $'+convert(varchar(20),convert(money,sum(quantity*regprice-quantity*unitprice)))+'  <'
            when discounttype=2 then
            ' > YOU SAVED $'+convert(varchar(20),convert(money,sum(quantity*regprice-quantity*unitprice)))+'  Member Special <'
            end as description,
            trans_type,'0' as trans_subtype,0 as itemQtty,discounttype,volume,
            'D' as trans_status,
            2 as voided,
            department,0 as quantity,matched,min(trans_id)+1 as trans_id,
            scale,0 as unitprice,
            0 as total,
            0 as regPrice,0 as tax,0 as foodstamp,
            case when trans_status='d' or scale=1 then trans_id else scale end as grouper
        from localtranstoday
        where description not like '** YOU SAVED %' and (discounttype=1 or discounttype=2)
        AND datetime >= CURRENT_DATE
        AND trans_type <> 'L'
        group by register_no,emp_no,trans_no,card_no,
            upc,description,trans_type,trans_subtype,discounttype,volume,
            department,scale,matched,
            case when trans_status='d' or scale=1 then trans_id else scale end
        having convert(money,sum(quantity*regprice-quantity*unitprice))<>0";
    }    
    InstallUtilities::dbStructureModify($db,'rp_ltt_grouped','DROP VIEW rp_ltt_grouped',$errors);
    if(!$db->table_exists('rp_ltt_grouped',$name)){
        InstallUtilities::dbStructureModify($db,'rp_ltt_grouped',$rplttG,$errors);
    }

    $rpreorderG = "CREATE    view rp_ltt_receipt_reorder_g as
        select 
        register_no,emp_no,trans_no,card_no,
        l.description as description,
        case 
            when voided = 5 
                then 'Discount'
            when trans_status = 'M'
                then 'Mbr special'
            when trans_status = 'S'
                then 'Staff special'
            when unitPrice = 0.01
                then ''
            when scale <> 0 and quantity <> 0 
                then ".$db->concat('CAST(quantity AS char)',"' @ '",'CAST(unitPrice AS char)','')."
            when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1
                then ".$db->concat('CAST(volume AS char)',"' / '",'CAST(unitPrice AS char)','')."
            when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1
                then ".$db->concat('CAST(quantity AS char)',"' @ '",'CAST(volume AS char)',"' /'",'CAST(unitPrice AS char)','')."
            when abs(itemQtty) > 1 and discounttype = 3
                then ".$db->concat('CAST(ItemQtty AS char)',"' / '",'CAST(unitPrice AS char)','')."
            when abs(itemQtty) > 1
                then ".$db->concat('CAST(quantity AS char)',"' @ '",'CAST(unitPrice AS char)','')."
            when matched > 0
                then '1 w/ vol adj'
            else ''
        end
        as comment,
        total,
        case 
            when trans_status = 'V' 
                then 'VD'
            when trans_status = 'R'
                then 'RF'
            WHEN (tax = 1 and foodstamp <> 0)
                THEN 'TF'
            WHEN (tax = 1 and foodstamp = 0)
                THEN 'T' 
            WHEN (tax > 1 and foodstamp <> 0)
                THEN ".$db->concat('SUBSTR(t.description,1,1)',"'F'",'')."
            WHEN (tax > 1 and foodstamp = 0)
                THEN SUBSTR(t.description,1,1)
            when tax = 0 and foodstamp <> 0
                then 'F'
            when tax = 0 and foodstamp = 0
                then '' 
        end
        as status,
        trans_type,
        unitPrice,
        voided,
        trans_id + 1000 as sequence,
        department,
        upc,
        trans_subtype
        from rp_ltt_grouped as l
        left join taxrates as t
        on l.tax=t.id
        where voided <> 5 and UPC <> 'TAX' and UPC <> 'DISCOUNT'
        AND trans_type <> 'L'
        and not (trans_status='M' and total=CAST('0.00' AS decimal))

        union

        select
        0 as register_no, 0 as emp_no,0 as trans_no,0 as card_no,
        '  ' as description,
        ' ' as comment,
        0 as total,
        ' ' as Status,
        ' ' as trans_type,
        0 as unitPrice,
        0 as voided,
        999 as sequence,
        '' as department,
        '' as upc,
        '' as trans_subtype";
    if($type == 'mssql'){
        $rpreorderG = "CREATE     view rp_ltt_receipt_reorder_g as
        select top 100 percent
        register_no,emp_no,trans_no,card_no,
        l.description,
        case 
            when voided = 5 
                then 'Discount'
            when trans_status = 'M'
                then 'Mbr special'
            when trans_status = 'S'
                then 'Staff special'
            when unitPrice = 0.01
                then ''
            when scale <> 0 and quantity <> 0 
                then convert(varchar, quantity) + ' @ ' + convert(varchar, unitPrice)
            when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1
                then convert(varchar, volume) + ' /' + convert(varchar, unitPrice)
            when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1
                then convert(varchar, Quantity) + ' @ ' + convert(varchar, Volume) + ' /' + convert(varchar, unitPrice)
            when abs(itemQtty) > 1 and discounttype = 3
                then convert(varchar,ItemQtty) + ' /' + convert(varchar, UnitPrice)
            when abs(itemQtty) > 1
                then convert(varchar, quantity) + ' @ ' + convert(varchar, unitPrice)    
            when matched > 0
                then '1 w/ vol adj'
            else ''
        end
        as comment,
        total,
        case 
            when trans_status = 'V' 
                then 'VD'
            when trans_status = 'R'
                then 'RF'
            WHEN (tax = 1 and foodstamp <> 0)
                THEN 'TF'
            WHEN (tax = 1 and foodstamp = 0)
                THEN 'T' 
            WHEN (tax > 1 and foodstamp <> 0)
                THEN LEFT(t.description,1)+'F'
            WHEN (tax > 1 and foodstamp = 0)
                THEN LEFT(t.description,1)
            when tax = 0 and foodstamp <> 0
                then 'F'
            when tax = 0 and foodstamp = 0
                then '' 
        end
        as Status,
        trans_type,
        unitPrice,
        voided,
        trans_id + 1000 as sequence,
        department,
        upc,
        trans_subtype
        from rp_ltt_grouped as l
        left join taxrates as t
        on l.tax=t.id
        where voided <> 5 and UPC <> 'TAX' and UPC <> 'DISCOUNT'
        AND trans_type <> 'L'
        and not (trans_status='M' and total=convert(money,'0.00'))

        union

        select
        0 as register_no, 0 as emp_no,0 as trans_no,0 as card_no,
        '  ' as description,
        ' ' as comment,
        0 as total,
        ' ' as Status,
        ' ' as trans_type,
        0 as unitPrice,
        0 as voided,
        999 as sequence,
        '' as department,
        '' as upc,
        '' as trans_subtype";
    }    
    InstallUtilities::dbStructureModify($db,'rp_ltt_receipt_reorder_g','DROP VIEW rp_ltt_receipt_reorder_g',$errors);
    if(!$db->table_exists("rp_ltt_receipt_reorder_g",$name)){
        InstallUtilities::dbStructureModify($db,'rp_ltt_receipt_reorder_g',$rpreorderG,$errors);
    }
    
    $rpG = "CREATE    view rp_receipt_reorder_g as
        select 
        register_no,emp_no,trans_no,card_no,
        case 
            when trans_type = 'T' 
                then     
                    case when trans_subtype = 'CP' and upc<>'0'
                    then    ".$db->concat(
                        "SUBSTR(".$db->concat('description','space(30)','').",1,30)",
                        "' '",
                        "SUBSTR(".$db->concat('comment','space(12)','').",1,12)",
                        "right(".$db->concat('space(8)','CAST(total AS char)','').",8)",
                        "right(".$db->concat('space(4)','status','').",4)",'')." 
                    else     ".$db->concat( 
                        "right(".$db->concat('space(44)','upper(description)','').",44)", 
                        "right(".$db->concat('space(8)','CAST((-1*total) AS char)','').",8)",
                        "right(".$db->concat('space(4)','status','').",4)",'')." 
                    end 
            when voided = 3 
                then     ".$db->concat( 
                    "SUBSTR(".$db->concat('description','space(30)','').",1,30)",
                    "space(9)", 
                    "'TOTAL'", 
                    "right(".$db->concat('space(8)','CAST(unitPrice AS char)','').",8)",'')."
            when voided = 2
                then     description
            when voided = 4
                then     description
            when voided = 6
                then     description
            when voided = 7 or voided = 17
                then     ".$db->concat(
                    "SUBSTR(".$db->concat('description','space(30)','').",1,30)",
                    "space(14)", 
                    "right(".$db->concat('space(8)','CAST(unitPrice AS char)','').",8)",
                    "right(".$db->concat('space(4)','status','').",4)",'')." 
            when sequence < 1000
                then     description
            else
                ".$db->concat(
                    "SUBSTR(".$db->concat('description','space(30)','').",1,30)",
                    "' '",
                    "SUBSTR(".$db->concat('comment','space(12)','').",1,12)",
                    "right(".$db->concat('space(8)','CAST(total AS char)','').",8)",
                    "right(".$db->concat('space(4)','status','').",4)",'')." 
        end
        as linetoprint,
        sequence,
        department,
        super_name as dept_name,
        case when trans_subtype='CM' or voided in (10,17)
            then 'CM' else trans_type
        end
        as trans_type,
        upc

        from rp_ltt_receipt_reorder_g r
        left outer join ".$CORE_LOCAL->get('pDatabase').".MasterSuperDepts d 
        on r.department=d.dept_ID
        where r.total<>0 or r.unitPrice=0
        order by register_no,emp_no,trans_no,card_no,sequence";
    if($type == 'mssql'){
        $rpG = "CREATE     view rp_receipt_reorder_g as
        select top 100 percent
        register_no,emp_no,trans_no,card_no,
        case 
            when trans_type = 'T' 
                then     
                    case when trans_subtype = 'CP' and upc<>'0'
                    then    left(Description + space(30), 30)
                        + ' ' 
                        + left(Comment + space(12), 12) 
                        + right(space(8) + convert(varchar, Total), 8) 
                        + right(space(4) + status, 4) 
                    else     right((space(44) + upper(rtrim(Description))), 44) 
                        + right((space(8) + convert(varchar, (-1 * Total))), 8) 
                        + right((space(4) + status), 4) 
                    end 
            when voided = 3 
                then     left(Description + space(30), 30) 
                    + space(9) 
                    + 'TOTAL' 
                    + right(space(8) + convert(varchar, UnitPrice), 8)
            when voided = 2
                then     description
            when voided = 4
                then     description
            when voided = 6
                then     description
            when voided = 7 or voided = 17
                then     left(Description + space(30), 30) 
                    + space(14) 
                    + right(space(8) + convert(varchar, UnitPrice), 8) 
                    + right(space(4) + status, 4)
            when sequence < 1000
                then     description
            else
                left(Description + space(30), 30)
                + ' ' 
                + left(Comment + space(12), 12) 
                + right(space(8) + convert(varchar, Total), 8) 
                + right(space(4) + status, 4)
        end
        as linetoprint,
        sequence,
        department,
        dept_name,
        case when trans_subtype='CM' or voided in (10,17)
            then 'CM' else trans_type
        end
        as trans_type,
        upc

        from rp_ltt_receipt_reorder_g r
        left outer join ".$CORE_LOCAL->get('pDatabase').".dbo.MasterSuperDepts d 
        on r.department=d.dept_ID
        where r.total<>0 or r.unitprice=0
        order by register_no,emp_no,trans_no,card_no,sequence";
    }
    elseif($type == 'pdolite'){
        $rpG = str_replace('right(','str_right(',$rpG);
    }
    if(!$db->table_exists('rp_receipt_reorder_g',$name)){
        InstallUtilities::dbStructureModify($db,'rp_receipt_reorder_g',$rpG,$errors);
    }

    $rpunionsG = "CREATE     view rp_receipt_reorder_unions_g as
        select linetoprint,
        emp_no,register_no,trans_no,
        sequence,dept_name,1 as ordered,upc
        from rp_receipt_reorder_g
        where (department<>0 or trans_type='CM')
        and linetoprint not like 'member discount%'

        union all

        select replace(replace(r1.linetoprint,'** T',' = T'),' **',' = ') as linetoprint,
        r1.emp_no,r1.register_no,r1.trans_no,
        r1.sequence,r2.dept_name,1 as ordered,r2.upc
        from rp_receipt_reorder_g r1 join rp_receipt_reorder_g r2 on r1.sequence+1=r2.sequence
        and r1.register_no=r2.register_no and r1.emp_no=r2.emp_no and r1.trans_no=r2.trans_no
        where r1.linetoprint like '** T%' and r2.dept_name is not null and r1.linetoprint<>'** Tare Weight 0 **'

        union all

        select
        ".$db->concat(
        "SUBSTR(".$db->concat("'** '","trim(CAST(percentDiscount AS char))","'% Discount Applied **'",'space(30)','').",1,30)",
        "' '", 
        "space(13)",
        "right(".$db->concat('space(8)',"CAST((-1*transDiscount) AS char)",'').",8)",
        "space(4)",'')." as linetoprint,
        emp_no,register_no,trans_no,
        0 as sequence,null as dept_name,2 as ordered,
        '' as upc
        from rp_subtotals
        where percentDiscount<>0

        union all

        select linetoprint,
        emp_no,register_no,trans_no,
        sequence,null as dept_name,2 as ordered,upc
        from rp_receipt_reorder_g
        where linetoprint like 'member discount%'

        union all

        select 
        ".$db->concat(
        "right(".$db->concat('space(44)',"'SUBTOTAL'",'').",44)",
        "right(".$db->concat('space(8)',"CAST(round(l.runningTotal-s.taxTotal-l.tenderTotal,2) AS char)",'').",8)",
        'space(4)','')." as linetoprint,
        l.emp_no,l.register_no,l.trans_no,
        1 as sequence,null as dept_name,3 as ordered,'' as upc
        from rp_lttsummary as l, rp_subtotals as s
        WHERE l.emp_no = s.emp_no and
        l.register_no = s.register_no and
        l.trans_no = s.trans_no

        union all

        select 
        ".$db->concat(
        "right(".$db->concat('space(44)',"'TAX'",'').",44)",
        "right(".$db->concat('space(8)',"CAST(round(taxTotal,2) AS char)",'').",8)", 
        "space(4)",'')." as linetoprint,
        emp_no,register_no,trans_no,
        2 as sequence,null as dept_name,3 as ordered,'' as upc
        from rp_subtotals

        union all

        select 
        ".$db->concat(
        "right(".$db->concat('space(44)',"'TOTAL'",'').",44)",
        "right(".$db->concat('space(8)',"CAST(runningTotal-tenderTotal AS char)",'').",8)", 
        'space(4)','')." as linetoprint,
        emp_no,register_no,trans_no,
        3 as sequence,null as dept_name,3 as ordered,'' as upc
        from rp_lttsummary

        union all

        select linetoprint,
        emp_no,register_no,trans_no,
        sequence,dept_name,4 as ordered,upc
        from rp_receipt_reorder_g
        where (trans_type='T' and department = 0)
        or (department = 0 and linetoprint like '%Coupon%')

        union all

        select 
        ".$db->concat(
        "right(".$db->concat('space(44)',"'CURRENT AMOUNT DUE'",'').",44)",
        "right(".$db->concat('space(8)',"CAST(runningTotal-transDiscount AS char)",'').",8)", 
        "space(4)",'')." as linetoprint,
        emp_no,register_no,trans_no,
        5 as sequence,
        null as dept_name,
        5 as ordered,'' as upc
        from rp_subtotals where runningTotal <> 0 ";
    if($type == 'mssql'){
        $rpunionsG = "CREATE view rp_receipt_reorder_unions_g as
        select linetoprint,
        emp_no,register_no,trans_no,
        sequence,dept_name,1 as ordered,upc
        from rp_receipt_reorder_g
        where (department<>0 or trans_type='CM')
        and linetoprint not like 'member discount%'

        union all

        select replace(replace(r1.linetoprint,'** T',' = T'),' **',' = ') as linetoprint,
        r1.emp_no,r1.register_no,r1.trans_no,
        r1.[sequence],r2.dept_name,1 as ordered,r2.upc
        from rp_receipt_reorder_g r1 join rp_receipt_reorder_g r2 on r1.[sequence]+1=r2.[sequence]
        and r1.emp_no=r2.emp_no and r1.register_no=r2.register_no and r1.trans_no=r2.trans_no
        where r1.linetoprint like '** T%' and r2.dept_name is not null and r1.linetoprint<>'** Tare Weight 0 **'

        union all

        select
        left('** '+rtrim(convert(char,percentdiscount))+'% Discount Applied **' + space(30), 30)
        + ' ' 
        + left('' + space(13), 13) 
        + right(space(8) + convert(varchar, (-1*transDiscount)), 8) 
        + right(space(4) + '', 4),
        emp_no,register_no,trans_no,
        0 as sequence,null as dept_name,2 as ordered,
        '' as upc
        from rp_subtotals
        where percentdiscount<>0

        union all

        select linetoprint,
        emp_no,register_no,trans_no,
        sequence,null as dept_name,2 as ordered,upc
        from rp_receipt_reorder_g
        where linetoprint like 'member discount%'

        union all

        select 
        right((space(44) + upper(rtrim('SUBTOTAL'))), 44) 
        + right((space(8) + convert(varchar,l.runningTotal-s.taxTotal-l.tenderTotal)),8)
        + right((space(4) + ''), 4) as linetoprint,
        l.emp_no,l.register_no,l.trans_no,
        1 as sequence,null as dept_name,3 as ordered,'' as upc
        from rp_lttsummary as l, rp_subtotals as s
        WHERE l.emp_no = s.emp_no and
        l.register_no = s.register_no and
        l.trans_no = s.trans_no

        union all

        select 
        right((space(44) + upper(rtrim('TAX'))), 44) 
        + right((space(8) + convert(varchar,taxtotal)), 8) 
        + right((space(4) + ''), 4) as linetoprint,
        emp_no,register_no,trans_no,
        2 as sequence,null as dept_name,3 as ordered,'' as upc
        from rp_subtotals

        union all

        select 
        right((space(44) + upper(rtrim('TOTAL'))), 44) 
        + right((space(8) +convert(varchar,runningtotal-tendertotal)),8)
        + right((space(4) + ''), 4) as linetoprint,
        emp_no,register_no,trans_no,
        3 as sequence,null as dept_name,3 as ordered,'' as upc
        from rp_lttsummary

        union all

        select linetoprint,
        emp_no,register_no,trans_no,
        sequence,dept_name,4 as ordered,upc
        from rp_receipt_reorder_g
        where (trans_type='T' and department = 0)
        or (department = 0 and linetoprint like '%Coupon%')

        union all

        select 
        right((space(44) + upper(rtrim('Current Amount Due'))), 44) 
        +right((space(8) + convert(varchar,subtotal)),8)
        + right((space(4) + ''), 4) as linetoprint,
        emp_no,register_no,trans_no,
        5 as sequence,
        null as dept_name,
        5 as ordered,'' as upc
        from rp_subtotals where runningtotal <> 0"; 
    }
    elseif($type == 'pdolite'){
        $rpunionsG = str_replace('right(','str_right(',$rpunionsG);
    }
    if(!$db->table_exists('rp_receipt_reorder_unions_g',$name)){
        InstallUtilities::dbStructureModify($db,'rp_receipt_reorder_unions_g',$rpunionsG,$errors);
    }

    return $errors;
}

function create_min_server($db,$type){
    global $CORE_LOCAL;
    $name = $CORE_LOCAL->get('mDatabase');
    $errors = array();

    if ($CORE_LOCAL->get('laneno') == 0) {
        $errors[] = array(
            'struct' => 'No structures created for lane #0',
            'query' => 'None',
            'details' => 'Zero is reserved for server',
        );

        return $errors;
    }
    
    $dtransQ = "CREATE TABLE `dtransactions` (
      `datetime` datetime default NULL,
      `register_no` smallint(6) default NULL,
      `emp_no` smallint(6) default NULL,
      `trans_no` int(11) default NULL,
      `upc` varchar(255) default NULL,
      `description` varchar(255) default NULL,
      `trans_type` varchar(255) default NULL,
      `trans_subtype` varchar(255) default NULL,
      `trans_status` varchar(255) default NULL,
      `department` smallint(6) default NULL,
      `quantity` real default NULL,
      `scale` tinyint(4) default NULL,
      `cost` real default 0.00 NULL,
      `unitPrice` real default NULL,
      `total` real default NULL,
      `regPrice` real default NULL,
      `tax` smallint(6) default NULL,
      `foodstamp` tinyint(4) default NULL,
      `discount` real default NULL,
      `memDiscount` real default NULL,
      `discountable` tinyint(4) default NULL,
      `discounttype` tinyint(4) default NULL,
      `voided` tinyint(4) default NULL,
      `percentDiscount` tinyint(4) default NULL,
      `ItemQtty` real default NULL,
      `volDiscType` tinyint(4) default NULL,
      `volume` tinyint(4) default NULL,
      `VolSpecial` real default NULL,
      `mixMatch` smallint(6) default NULL,
      `matched` smallint(6) default NULL,
      `memType` tinyint(2) default NULL,
      `staff` tinyint(4) default NULL,
      `numflag` smallint(6) default 0 NULL,
      `charflag` varchar(2) default '' NULL,
      `card_no` varchar(255) default NULL,
      `trans_id` int(11) default NULL
    )";
    if ($type == 'mssql'){
        $dtransQ = "CREATE TABLE [dtransactions] (
        [datetime] [datetime] NOT NULL ,
        [register_no] [smallint] NOT NULL ,
        [emp_no] [smallint] NOT NULL ,
        [trans_no] [int] NOT NULL ,
        [upc] [nvarchar] (13) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
        [description] [nvarchar] (30) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
        [trans_type] [nvarchar] (1) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
        [trans_subtype] [nvarchar] (2) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
        [trans_status] [nvarchar] (1) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
        [department] [smallint] NULL ,
        [quantity] [float] NULL ,
        [scale] [tinyint] NULL ,
        [unitPrice] [money] NULL ,
        [total] [money] NOT NULL ,
        [regPrice] [money] NULL ,
        [tax] [smallint] NULL ,
        [foodstamp] [tinyint] NOT NULL ,
        [discount] [money] NOT NULL ,
        [memDiscount] [money] NULL ,
        [discountable] [tinyint] NULL ,
        [discounttype] [tinyint] NULL ,
        [voided] [tinyint] NULL ,
        [percentDiscount] [tinyint] NULL ,
        [ItemQtty] [float] NULL ,
        [volDiscType] [tinyint] NOT NULL ,
        [volume] [tinyint] NOT NULL ,
        [VolSpecial] [money] NOT NULL ,
        [mixMatch] [smallint] NULL ,
        [matched] [smallint] NOT NULL ,
        [memType] [smallint] NULL ,
        [staff] [tinyint] NULL ,
        [numflag] [smallint] NULL ,
        [charflag] [nvarchar] (2) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
        [card_no] [nvarchar] (6) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
        [trans_id] [int] NOT NULL 
        ) ON [PRIMARY]";
    }
    if (!$db->table_exists("dtransactions",$name)){
        $db->query($dtransQ,$name);
        InstallUtilities::dbStructureModify($db,'dtransactions',$dtransQ,$errors);
    }

    $susQ = str_replace("dtransactions","suspended",$dtransQ);
    if(!$db->table_exists("suspended",$name)){
        InstallUtilities::dbStructureModify($db,'suspended',$susQ,$errors);
    }

    $dlogQ = "CREATE VIEW dlog AS
        SELECT
        datetime AS tdate,
        register_no,
        emp_no,
        trans_no,
        upc,
        CASE WHEN (trans_subtype IN ('CP','IC') OR upc like('%000000052')) then 'T' WHEN upc = 'DISCOUNT' then 'S' else trans_type end as trans_type,
        CASE WHEN upc = 'MAD Coupon' THEN 'MA' 
           WHEN upc like('%00000000052') THEN 'RR' ELSE trans_subtype END as trans_subtype,
        trans_status,
        department,
        quantity,
        unitPrice,
        total,
        tax,
        foodstamp,
        ItemQtty,
        memType,
        staff,
        numflag,
        charflag,
        card_no,
        trans_id,
        ".$db->concat(
            $db->convert('emp_no','char'),"'-'",
            $db->convert('register_no','char'),"'-'",
            $db->convert('trans_no','char'),'')
        ." as trans_num
        FROM dtransactions
        WHERE trans_status NOT IN ('D','X','Z')
        AND emp_no <> 9999 and register_no <> 99";
    if(!$db->table_exists("dlog",$name)){
        $errors = InstallUtilities::dbStructureModify($db,'dlog',$dlogQ,$errors);
    }

    $efsrq = "CREATE TABLE efsnetRequest (
        date int ,
        cashierNo int ,
        laneNo int ,
        transNo int ,
        transID int ,
        datetime datetime ,
        refNum varchar (50) ,
        live tinyint ,
        mode varchar (32) ,
        amount real ,
        PAN varchar (19) ,
        issuer varchar (16) ,
        name varchar (50) ,
        manual tinyint ,
        sentPAN tinyint ,
        sentExp tinyint ,
        sentTr1 tinyint ,
        sentTr2 tinyint 
        )";
    if(!$db->table_exists('efsnetRequest',$name)){
        InstallUtilities::dbStructureModify($db,'efsnetRequest',$efsrq,$errors);
    }

    $efsrp = "CREATE TABLE efsnetResponse (
        date int ,
        cashierNo int ,
        laneNo int ,
        transNo int ,
        transID int ,
        datetime datetime ,
        refNum varchar (50),
        seconds float ,
        commErr int ,
        httpCode int ,
        validResponse smallint ,
        xResponseCode varchar (4),
        xResultCode varchar (4), 
        xResultMessage varchar (100),
        xTransactionID varchar (12),
        xApprovalNumber varchar (20)
        )";
    if(!$db->table_exists('efsnetResponse',$name)){
        InstallUtilities::dbStructureModify($db,'efsnetResponse',$efsrp,$errors);
    }

    $efsrqm = "CREATE TABLE efsnetRequestMod (
        date int ,
        cashierNo int ,
        laneNo int ,
        transNo int ,
        transID int ,
        datetime datetime ,
        origRefNum varchar (50),
        origAmount real ,
        origTransactionID varchar(12) ,
        mode varchar (32),
        altRoute tinyint ,
        seconds float ,
        commErr int ,
        httpCode int ,
        validResponse smallint ,
        xResponseCode varchar(4),
        xResultCode varchar(4),
        xResultMessage varchar(100)
        )";
    if(!$db->table_exists('efsnetRequestMod',$name)){
        InstallUtilities::dbStructureModify($db,'efsnetRequestMod',$efsrqm,$errors);
    }

    $ttG = "CREATE view TenderTapeGeneric
        as
        select 
        tdate, 
        emp_no, 
        register_no,
        trans_no,
        CASE WHEN trans_subtype = 'CP' AND upc LIKE '%MAD%' THEN ''
             WHEN trans_subtype IN ('EF','EC','TA') THEN 'EF'
             ELSE trans_subtype
        END AS trans_subtype,
        CASE WHEN trans_subtype = 'ca' THEN
            CASE WHEN total >= 0 THEN total ELSE 0 END
             ELSE
            -1 * total
        END AS tender
        from dlog
        where tdate >= CURRENT_DATE
        and trans_subtype not in ('0','')";
    if (!$db->table_exists("TenderTapeGeneric",$name)){
        InstallUtilities::dbStructureModify($db,'TenderTapeGeneric',$ttG,$errors);
    }

    // re-use definition to create lane_config on server
    InstallUtilities::createIfNeeded($db, $type, $name, 'lane_config', 'op', $errors);

    return $errors;
}

?>
