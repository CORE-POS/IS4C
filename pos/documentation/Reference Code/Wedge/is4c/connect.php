<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

    This file is part of IS4C.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
if (!function_exists("get_config_auto")) {
    include_once("lib/conf.php");
    # apply_configurations();
}

if (!function_exists("setglobalflags")) {
    include("loadconfig.php");
}
if (!function_exists("pinghost")) {
    include("lib.php");
}
if (!function_exists("wmdiscount")) {
    include("prehkeys.php");
}

/***********************************************************************************************

 Functions transcribed from connect.asp on 07.13.03 by Brandon.

***********************************************************************************************/

function tDataConnect()
{
    $connection = sql_connect("127.0.0.1", $_SESSION["localUser"], $_SESSION["localPass"]);
    $dbID = sql_select_db($_SESSION["tDatabase"], $connection);
    return $connection;
}

function pDataConnect()
{
    $connection = sql_connect("127.0.0.1", $_SESSION["localUser"], $_SESSION["localPass"]);
    sql_select_db($_SESSION["pDatabase"], $connection);
    return $connection;
}

function mDataConnect()
{
    $_SESSION["standalone"] = 1;

    if ($_SESSION["remoteDBMS"] == "mssql") {
        if ($connection = mssql_connect($_SESSION["mServer"], $_SESSION["mUser"], $_SESSION["mPass"])) {
            if ($connection) {
                if (mssql_select_db($_SESSION["mDatabase"], $connection)) {
                    $_SESSION["standalone"] = 0;
                }
            }
        }
    }
    else {
        if ($connection = mysql_connect($_SESSION["mServer"], $_SESSION["mUser"], $_SESSION["mPass"])) {
            if ($connection) {
                if (mysql_select_db($_SESSION["mDatabase"], $connection)) {
                    $_SESSION["standalone"] = 0;
                }
            }
        }
    }
    if ($_SESSION["standalone"] == 0) {
        return $connection;
    }
    else {
        return 0;    
    }
}

function cDataConnect()
{
    $connection = mysql_connect($_SESSION["ccServer"], $_SESSION["ccUser"], $_SESSION["ccPass"]);
    mysql_select_db($_SESSION["ccDatabase"], $connection);
    return $connection;
}

// ----------getsubtotals()----------

// getsubtotals() updates the values held in our session variables.

function getsubtotals() {

    $query = "SELECT * FROM subtotals";
    $connection = tDataConnect();
    $result = sql_query($query, $connection);
    $row = sql_fetch_array($result);

    if (!$row || $row["LastID"] == 0) {
        $_SESSION["LastID"] = 0;
        $_SESSION["runningTotal"] = 0;
        $_SESSION["subtotal"] = 0;
        $_SESSION["taxTotal"] = 0;
        $_SESSION["discounttotal"] = 0;
        $_SESSION["tenderTotal"] = 0;
        $_SESSION["memSpecial"] = 0;
        $_SESSION["staffSpecial"] = 0;
        $_SESSION["percentDiscount"] = 0;
        $_SESSION["transDiscount"] = 0;
        $_SESSION["fsTaxExempt"] = 0;
        $_SESSION["fsEligible"] = 0;
        $_SESSION["ttlflag"] = 0;
        $_SESSION["fntlflag"] = 0;
        $_SESSION["memCouponTTL"] = 0;
        $_SESSION["refundTotal"] = 0;
        $_SESSION["chargeTotal"] = 0;
        $_SESSION["ccTotal"] = 0;
        $_SESSION["memChargeTotal"] = 0;
        $_SESSION["madCoup"] = 0;
        $_SESSION["scTaxTotal"] = 0;
        $_SESSION["scDiscount"] = 0;
        $_SESSION["paymentTotal"] = 0;
        $_SESSION['patronageTotal'] = 0;
        $_SESSION['NBdisc'] = 0;
        $_SESSION['memID'] = 0;

        setglobalflags(0);
    }
    else {

        $_SESSION["LastID"] = (double) $row["LastID"];
        $_SESSION["memberID"] = trim($row["card_no"]);
        $_SESSION["runningTotal"] = (double) $row["runningTotal"];
        $_SESSION["taxTotal"] = (double) $row["taxTotal"];
        $_SESSION["discounttotal"] = (double) $row["discountTTL"];
        $_SESSION["memSpecial"] = (double) $row["memSpecial"];
        $_SESSION["staffSpecial"] = (double) $row["staffSpecial"];
        $_SESSION["tenderTotal"] = (double) $row["tenderTotal"];
        $_SESSION["percentDiscount"] = (double) $row["percentDiscount"];
        $_SESSION["transDiscount"] = (double) $row["transDiscount"];
        $_SESSION["scDiscount"] = (double) $row["scDiscount"];
        $_SESSION["scTaxTotal"] = (double) $row["scTaxTotal"];
        $_SESSION["fsTaxExempt"] = (double) $row["fsTaxExempt"];
        $_SESSION["fsEligible"] = (double) $row["fsEligible"];
        $_SESSION["memCouponTTL"] = -1 * ((double) $row["couponTotal"]) + ((double) $row["memCoupon"]);
        $_SESSION["refundTotal"] = (double) $row["refundTotal"];
        $_SESSION["chargeTotal"] = (double) $row["chargeTotal"];
        $_SESSION["ccTotal"] = (double) $row["ccTotal"];
        $_SESSION["paymentTotal"] = (double) $row["paymentTotal"];
        $_SESSION["memChargeTotal"] = (double) $row["memChargeTotal"];
        $_SESSION["discountableTotal"] = (double) $row["discountableTotal"];
        $_SESSION["patronageTotal"] = (double) $row["discountableTotal"];
    }

    if (isset($_SESSION["memberID"]) && $_SESSION["runningTotal"] > 0) {
        if ($_SESSION["SSI"] != 0 && ($_SESSION["isStaff"] == 3 || $_SESSION["isStaff"] == 6)) {
            wmdiscount();
        }
    }

    if ($_SESSION["sc"] == 1) {
        $_SESSION["taxTotal"] = $_SESSION["scTaxTotal"];
    }

    if ( $_SESSION["TaxExempt"] == 1 ) {
        $_SESSION["taxable"] = 0;
        $_SESSION["taxTotal"] = 0;
        $_SESSION["fsTaxable"] = 0;
        $_SESSION["fsTaxExempt"] = 0;
    }

    $_SESSION["subtotal"] = number_format($_SESSION["runningTotal"] - $_SESSION["transDiscount"], 2);
    $_SESSION["amtdue"] = $_SESSION["runningTotal"] - $_SESSION["transDiscount"] + $_SESSION["taxTotal"];
    
    if ( $_SESSION["fsEligible"] > $_SESSION["subtotal"] ) {
        $_SESSION["fsEligible"] = $_SESSION["subtotal"];
    }

    sql_close($connection);
}

// ----------gettransno($CashierNo /int)----------
//
// Given $CashierNo, gettransno() will look up the number of the most recent transaction.

function gettransno($CashierNo) {
    $database = $_SESSION["tDatabase"];
    $register_no = $_SESSION["laneno"];
    $query = "SELECT max(trans_no) as maxtransno from localtranstoday where emp_no = '"
        . $CashierNo."' and register_no = '"
        . $register_no."' GROUP by register_no, emp_no";
    $connection = tDataConnect();
    $result = sql_query($query, $connection);
    $row = sql_fetch_array($result);
    if (!$row || !$row["maxtransno"]) {
        $trans_no = 1;
    }
    else {
        $trans_no = $row["maxtransno"] + 1;
    }
    sql_close($connection);
    return $trans_no;
}

// ------------------------------------------------------------------

function testremote() {
    $intConnected = pinghost($_SESSION["mServer"]);
    if ($intConnected == 1) {
        uploadtoServer();     
    }
    else {
        $_SESSION["standalone"] = 1;
    }

    return ($_SESSION["standalone"] + 1) % 2;
}

function testcc() {
    $ccConnected = pinghost($_SESSION["ccServer"]);
    if ($ccConnected == 1) {
        $cn = mysql_connect($_SESSION["ccServer"],'sa');

        if($cn){
           $_SESSION["ccMysql"]=1;    
        }
        else{
           $_SESSION["ccMysql"]=0;
        }
    }
    else{
        $_SESSION["ccMysql"]=0;
    }
}

function uploadtoServer()
{
    $uploaded = 0;

    if ($_SESSION["DBMS"] == "mssql") {
        $strUploadDTrans = "insert ".trim($_SESSION["mServer"]).".".trim($_SESSION["mDatabase"]).".dbo.dtransactions select * from dtransactions";
        $strUploadAlog = "insert ".trim($_SESSION["mServer"]).".".trim($_SESSION["mDatabase"]).".dbo.alog select * from alog";

        $strUploadsuspended = "insert ".trim($_SESSION["mServer"]).".".trim($_SESSION["mDatabase"]).".dbo.suspended select * from suspended";        
        $connect = tDataConnect();

        if ( sql_query($strUploadDTrans, $connect) ) {
            sql_query("truncate table dtransactions", $connect);
            sql_query($strUploadAlog, $connect);
            sql_query("truncate table alog", $connect);
            sql_query($strUploadsuspended, $connect);
            sql_query("truncate table suspended", $connect);
            $uploaded = 1;
            $_SESSION["standalone"] = 0;
        }
        else {
            $uploaded = 0;
            $_SESSION["standalone"] = 1;
        }
    }
    else {
        $uploaded = uploadtable("dtransactions");
        if ($uploaded == 1) {
            uploadtable("suspended");
            uploadtable("activitylog");
            $_SESSION["standalone"] = 0;
        }
        else {
            $uploaded = 0;
            $_SESSION["standalone"] = 1;
        }
    }

    return $uploaded;
}

function uploadtable($table) {
    $output = "";

    if ($_SESSION["localPass"] == "") {
        $localpass = "";
    }
    else {
        $localpass = "-p".$_SESSION["localPass"];
    }

    if ($_SESSION["mPass"] == "") {
        $serverpass = "";
    }
    else {
        $serverpass = "-p".$_SESSION["mPass"];
    }

    $upload = "mysqldump -u " . $_SESSION['localUser'] . " " . $localpass . " -t " . $_SESSION['tDatabase'] . " " . $table
             . " | mysql -h " . $_SESSION['mServer'] . " -u " . $_SESSION["mUser"] . " " . $serverpass . " " . $_SESSION['mDatabase']." 2>&1";

    exec($upload, $aResult);
    $error = 0;
    $output = 0;
    foreach ($aResult as $errormsg) {
        if ($errormsg && strlen($errormsg) > 0) {
            $output = $output."\n".$errormsg;
            $error = 1;
        }
    }
    if ($error == 1) {
        syslog(LOG_WARNING, "uploadtable($table) failed; rc: errormsg: '$output'");
        return 0;
    }
    else {
        $t_conn = tDataConnect();
        mysql_query("TRUNCATE TABLE ".$table, $t_conn);
        return 1;
    }
}

function sql_connect($server, $user, $pass) {
    if ($_SESSION["DBMS"] == "mssql") {
        $connection = mssql_connect($server, $user, $pass);
    }
    else {
        $connection = mysql_connect($server, $user, $pass);
    }
    return $connection;
}

function sql_select_db($db, $connection) {

    if ($_SESSION["DBMS"]  == "mssql") {
        $selectdb = mssql_select_db($db, $connection);
    }
    else {
        $selectdb = mysql_select_db($db, $connection) or die(mysql_error());
    }
    return $selectdb;
}

function sql_query($query, $connection)
{
    if ($_SESSION["DBMS"]  == "mssql") {
        $result = mssql_query($query, $connection);
    }
    else {
        $result = mysql_query($query, $connection) or die(mysql_error());
    }
    return $result;
}

function sql_num_rows($result) {
    if ($_SESSION["DBMS"] == "mssql") {
        $num_rows = mssql_num_rows($result);
    }
    else {
        $num_rows = mysql_num_rows($result);
    }
    if (!$num_rows) {
        $num_rows = 0;
    }
    return $num_rows;
}


function sql_fetch_array($result) {
    if ($_SESSION["DBMS"] == "mssql") {
        $row = mssql_fetch_array($result);
    }
    else {
        $row = mysql_fetch_array($result);
    }
    return $row;
}

function sql_fetch_assoc_array($result) {
    if ($_SESSION["DBMS"] == "mssql") {
        $row = mssql_fetch_assoc($result);
    }
    else {
        $row = mysql_fetch_assoc($result);
    }
    return $row;
}

function sql_fetch_row($result) {
    if ($_SESSION["DBMS"] == "mssql") {
        $row = mssql_fetch_row($result);
    }
    else {
        $row = mysql_fetch_row($result);
    }
    return $row;
}


function sql_close($connection) {
    if ($_SESSION["DBMS"] == "mssql") {
        $close = mssql_close($connection);
    }
    else {
        $close = "";
    }
    return $close;
}

function sql_error($connection) {
    if ($_SESSION["DBMS"] == "mssql") {
        $error = "";
    }
    else {
        $error = mysql_error($connection);
    }
    return $error;
}

