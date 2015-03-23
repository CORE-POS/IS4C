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
if (file_exists('../ini.php')) {
    include('../ini.php');
}
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
$register_id_is_mapped = false;
$store_id_is_mapped = false;
if (is_array(CoreLocal::get('LaneMap'))) {
    $my_ips = MiscLib::getAllIPs();
    $map = CoreLocal::get('LaneMap');
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
            if (CoreLocal::get('store_id') === '') {
                // no store_id set. assign based on IP
                CoreLocal::set('store_id', $map[$ip]['store_id']);
                $store_id_is_mapped = true;
            } else if (CoreLocal::get('store_id') != $map[$ip]['store_id']) {
                echo '<tr><td colspan="3">Warning: store_id is set to ' 
                    . CoreLocal::get('store_id') . '. Based on IP ' . $ip
                    . ' it should be set to ' . $map[$ip]['store_id'] . '</td></tr>';
            } else {
                $store_id_is_mapped = true;
            }
            if (CoreLocal::get('laneno') === '') {
                // no store_id set. assign based on IP
                CoreLocal::set('laneno', $map[$ip]['register_id']);
                $register_id_is_mapped = true;
            } else if (CoreLocal::get('laneno') != $map[$ip]['register_id']) {
                echo '<tr><td colspan="3">Warning: register_id is set to ' 
                    . CoreLocal::get('laneno') . '. Based on IP ' . $ip
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
    <td style="width:30%;">Lane number*:</td>
    <?php if (CoreLocal::get('laneno') !== '' && CoreLocal::get('laneno') == 0) { ?>
    <td>0 (Zero)</td>
    <?php } elseif ($register_id_is_mapped) { ?>
    <td><?php echo CoreLocal::get('laneno'); ?> (assigned by IP; cannot be edited)</td>
    <?php } else { ?>
    <td><?php echo InstallUtilities::installTextField('laneno', 99, InstallUtilities::INI_SETTING, false); ?></td>
    <?php } ?>
</tr>
<tr>
    <td>Store number*:</td>
    <?php if (CoreLocal::get('store_id') !== '' && CoreLocal::get('store_id') == 0) { ?>
    <td>0 (Zero)</td>
    <?php } elseif ($store_id_is_mapped) { ?>
    <td><?php echo CoreLocal::get('store_id'); ?> (assigned by IP; cannot be edited)</td>
    <?php } else { ?>
    <td><?php echo InstallUtilities::installTextField('store_id', 1, InstallUtilities::INI_SETTING, false); ?></td>
    <?php } ?>
</tr>
<tr>
    <td>Locale:</td>
    <td><?php echo InstallUtilities::installSelectField('locale', array('en_US','en_CA'), 'en_US'); ?></td>
<?php if (CoreLocal::get('laneno') === '' || CoreLocal::get('laneno') != 0) { ?>
<tr>
    <td colspan=2 class="tblheader">
    <h3>Database set up</h3>
    </td>
</tr>
<tr>
    <td>Lane database host*: </td>
    <td><?php echo InstallUtilities::installTextField('localhost', '127.0.0.1', InstallUtilities::INI_SETTING); ?></td>
</tr>
<tr>
    <td>Lane database type*:</td>
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
    <td>Lane user name*:</td>
    <td><?php echo InstallUtilities::installTextField('localUser', 'root', InstallUtilities::INI_SETTING); ?></td>
</tr>
<tr>
    <td>Lane password*:</td>
    <td>
    <?php
    echo InstallUtilities::installTextField('localPass', '', InstallUtilities::INI_SETTING, true, array('type'=>'password'));
    ?>
    </td>
</tr>
<tr>
    <td>Lane operational DB*:</td>
    <td><?php echo InstallUtilities::installTextField('pDatabase', 'opdata', InstallUtilities::INI_SETTING); ?></td>
</tr>
<tr>
    <td colspan=2>
<div class="noteTxt">
Testing operational DB Connection:
<?php
$gotDBs = 0;
if (CoreLocal::get("DBMS") == "mysql")
    $val = ini_set('mysql.connect_timeout',5);

$sql = InstallUtilities::dbTestConnect(CoreLocal::get('localhost'),
        CoreLocal::get('DBMS'),
        CoreLocal::get('pDatabase'),
        CoreLocal::get('localUser'),
        CoreLocal::get('localPass'));
if ($sql === False) {
    echo "<span class='fail'>Failed</span>";
    echo '<div class="db_hints" style="margin-left:25px;">';
    if (!function_exists('socket_create')){
        echo '<i>Try enabling PHP\'s socket extension in php.ini for better diagnostics</i>';
    }
    elseif (@MiscLib::pingport(CoreLocal::get('localhost'),CoreLocal::get('DBMS'))){
        echo '<i>Database found at '.CoreLocal::get('localhost').'. Verify username and password
            and/or database account permissions.</i>';
    }
    else {
        echo '<i>Database does not appear to be listening for connections on '
            .CoreLocal::get('localhost').'. Verify host is correct, database is running and
            firewall is allowing connections.</i>';
    }
    echo '</div>';
} else {
    echo "<span class='success'>Succeeded</span><br />";
    //echo "<textarea rows=3 cols=80>";
    $opErrors = InstallUtilities::createOpDBs($sql, CoreLocal::get('pDatabase'));
    $opErrors = array_filter($opErrors, function($x){ return $x['error'] != 0; });
    $gotDBs++;
    if (!empty($opErrors)){
        echo '<div class="db_create_errors" style="border: solid 1px red;padding:5px;">';
        echo 'There were some errors creating operational DB structure';
        echo '<ul style="margin-top:2px;">';
        foreach($opErrors as $error){
            if ($error['error'] == 0) {
                continue; // no error occurred
            }
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

}
?>
</div> <!-- noteTxt -->
</td></tr>
<tr>
    <td>Lane transaction DB*:</td>
    <td><?php echo InstallUtilities::installTextField('tDatabase', 'translog', InstallUtilities::INI_SETTING); ?></td>
</tr>
<tr>
    <td colspan=2>
<div class="noteTxt">
Testing transactional DB connection:
<?php
$sql = InstallUtilities::dbTestConnect(CoreLocal::get('localhost'),
        CoreLocal::get('DBMS'),
        CoreLocal::get('tDatabase'),
        CoreLocal::get('localUser'),
        CoreLocal::get('localPass'));
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

    $transErrors = InstallUtilities::createTransDBs($sql, CoreLocal::get('tDatabase'));
    $transErrors = array_filter($transErrors, function($x){ return $x['error'] != 0; });
    $gotDBs++;
    if (!empty($transErrors)){
        echo '<div class="db_create_errors" style="border: solid 1px red;padding:5px;">';
        echo 'There were some errors creating transactional DB structure';
        echo '<ul style="margin-top:2px;">';
        foreach($transErrors as $error){
            if ($error['error'] == 0) {
                continue; // no error occurred
            }
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
<tr><td colspan="3">
<?php 
if ($gotDBs == 2 && CoreLocal::get('laneno') != 0) {
    InstallUtilities::validateConfiguration();
}
?>
</td></tr>
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
$sql = InstallUtilities::dbTestConnect(CoreLocal::get('mServer'),
        CoreLocal::get('mDBMS'),
        CoreLocal::get('mDatabase'),
        CoreLocal::get('mUser'),
        CoreLocal::get('mPass'));
if ($sql === False){
    echo "<span class='fail'>Failed</span>";
    echo '<div class="db_hints" style="margin-left:25px;width:350px;">';
    if (!function_exists('socket_create')){
        echo '<i>Try enabling PHP\'s socket extension in php.ini for better diagnostics</i>';
    }
    elseif (@MiscLib::pingport(CoreLocal::get('mServer'),CoreLocal::get('DBMS'))){
        echo '<i>Database found at '.CoreLocal::get('mServer').'. Verify username and password
            and/or database account permissions.</i>';
    }
    else {
        echo '<i>Database does not appear to be listening for connections on '
            .CoreLocal::get('mServer').'. Verify host is correct, database is running and
            firewall is allowing connections.</i>';
    }
    echo '</div>';
}
else {
    echo "<span class='success'>Succeeded</span><br />";
    //echo "<textarea rows=3 cols=80>";
    $sErrors = create_min_server($sql,CoreLocal::get('mDBMS'));
    $sErrors = array_filter($sErrors, function($x){ return $x['error'] != 0; });
    if (!empty($sErrors)){
        echo '<div class="db_create_errors" style="border: solid 1px red;padding:5px;">';
        echo 'There were some errors creating transactional DB structure';
        echo '<ul style="margin-top:2px;">';
        foreach($sErrors as $error){
            if ($error['error'] == 0) {
                continue; // no error occurred
            }
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
    $sql = new SQLManager(CoreLocal::get('localhost'),
            CoreLocal::get('DBMS'),
            CoreLocal::get('tDatabase'),
            CoreLocal::get('localUser'),
            CoreLocal::get('localPass'));
    if (CoreLocal::get('laneno') == 0 && CoreLocal::get('laneno') !== '') {
        // server-side rate table is in op database
        $sql = new SQLManager(CoreLocal::get('localhost'),
                CoreLocal::get('DBMS'),
                CoreLocal::get('pDatabase'),
                CoreLocal::get('localUser'),
                CoreLocal::get('localPass'));
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

function create_min_server($db,$type){
    $name = CoreLocal::get('mDatabase');
    $errors = array();

    if (CoreLocal::get('laneno') == 0) {
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

    return $errors;
}

?>
