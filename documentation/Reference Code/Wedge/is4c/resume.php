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
 // session_start(); 
if (!function_exists("tDataConnect")) include("connect.php");
if (!function_exists("memberID")) include("prehkeys.php");
if (!function_exists("receipt")) include("clientscripts.php");
if (!function_exists("gohome")) include("maindisplay.php");

// lines 40-45 edited by apbw 7/12/05 to resolve "undefined index" error message
if (isset($_POST["selectlist"])) {
    $resume_trans = strtoupper(trim($_POST["selectlist"]));
}
else {
    $resume_trans = "";
}

if (!$resume_trans || strlen($resume_trans) < 1) {
    gohome();
}
else {
    $resume_spec = explode("::", $resume_trans);
    $suspendedtoday = "suspendedtoday";
    $suspended = "suspended";

    $register_no = $resume_spec[0];
    $emp_no = $resume_spec[1];
    $trans_no = $resume_spec[2];

    $db_a = tDataConnect();
    $m_conn = mDataConnect();

    resumesuspended($register_no, $emp_no, $trans_no);

    $query_update = "update localtemptrans set register_no = " . $_SESSION["laneno"] . ", emp_no = " . $_SESSION["CashierNo"]
        . ", trans_no = " . $_SESSION["transno"];

    sql_query($query_update, $db_a);
    sql_close($db_a);
    getsubtotals();
    $_SESSION["unlock"] = 1;

    if ($_SESSION["memberID"] != 0 && strlen($_SESSION["memberID"]) > 0 && $_SESSION["memberID"]) {
        memberID($_SESSION["memberID"]);
    }

    $_SESSION["msg"] =0;
    goodbeep();
    gohome();
}

function resumesuspended($register_no, $emp_no, $trans_no) {
    $t_conn = tDataConnect();
    mysql_query("truncate table is4c_log.suspended");
    $output = "";
    openlog("is4c_connect", LOG_PID | LOG_PERROR, LOG_LOCAL0);
    exec('mysqldump -u ' . $_SESSION["mUser"] . " -p".$_SESSION["mPass"].' -h ' . $_SESSION["mServer"] . ' -t ' . $_SESSION['mDatabase'] . ' ' . 'suspended' . ' | mysql -u ' . $_SESSION["localUser"] . ' ' . "-p".$_SESSION["localPass"] . ' translog' . " 2>&1", $result, $return_code);
    foreach ($result as $v) {$output .= "$v\n";}
    if ($return_code == 0) {
        if (insertltt($register_no, $emp_no, $trans_no) == 1) {
            trimsuspended($register_no, $emp_no, $trans_no);
            return 1;
        } else {
        	syslog(LOG_WARNING, "IS4C debug ".mysql_error());
        }
    }
    else {
        syslog(LOG_WARNING, "resumesuspended() failed; rc: '$return_code', output: '$output'");
        return 0;
    }
}

function insertltt($register_no, $emp_no, $trans_no) {
    $inserted = 0;
    $conn = tDataConnect();
    mysql_query("truncate table localtemptrans", $conn);

    $query = "insert into localtemptrans "
        . "(datetime, register_no, emp_no, trans_no, upc, description, trans_type, trans_subtype, "
        . "trans_status, department, quantity, scale, unitPrice, total, regPrice, tax, foodstamp, "
        . "discount, memDiscount, discountable, discounttype, voided, percentDiscount, ItemQtty, "
        . "volDiscType, volume, VolSpecial, mixMatch, matched, card_no, memType, staff) "
        . "select "
        . "datetime, register_no, emp_no, trans_no, upc, description, trans_type, trans_subtype, "
        . "trans_status, department, quantity, scale, unitPrice, total, regPrice, tax, foodstamp, "
        . "discount, memDiscount, discountable, discounttype, voided, percentDiscount, ItemQtty, "
        . "volDiscType, volume, VolSpecial, mixMatch, matched, card_no, memType, staff "
        . "from translog.suspended where register_no = " . $register_no
        . " and emp_no = " . $emp_no." and trans_no = " . $trans_no;

    if (mysql_query($query, $conn)) {
        if (mysql_query("truncate table is4c_log.suspended", $conn)) $inserted = 1;
    }
    return $inserted;
}

function trimsuspended($register_no, $emp_no, $trans_no) {
    $conn = mDataConnect();
    $query = "delete from is4c_log.suspended "
        . " where register_no = " . $register_no
        . " and emp_no = " . $emp_no . " and trans_no = " . $trans_no; 
    mysql_query($query, $conn);
}
?>

<form name='hidden'>
    <input Type='hidden' name='alert' value='noScan' />
</form>
