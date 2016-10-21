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

if (!function_exists("receipt")) include("clientscripts.php");
include_once("connect.php");
include_once("end.php");

function suspendorder() {
    testremote();
    $query_a = "select emp_no, trans_no from localtemptrans";
    $db_a = tDataConnect();
    $result_a = sql_query($query_a, $db_a);
    $row_a = sql_fetch_array($result_a);
    $cashier_no = substr("000".$row_a["emp_no"], -2);
    $trans_no = substr("0000".$row_a["trans_no"], -4);

    if ($_SESSION["standalone"] == 0) {
        if ($_SESSION["remoteDBMS"] == "mssql") {
            $query = "insert ".trim($_SESSION["mServer"]) . "." . trim($_SESSION["mDatabase"]) . ".dbo.suspended select * from localtemptrans";
            $result = sql_query($query, $db_a);    
        }
        else {
            $query = "insert suspended select * from localtemptrans";
            $result = sql_query($query, $db_a);
            if (uploadtable("suspended") == 1) {
                cleartemptrans();
            }                
        }        
    }
    else {
        $query = "insert suspended select * from localtemptrans";
        $result = sql_query($query, $db_a);
    }

    $_SESSION["plainmsg"] = "transaction suspended";
    $_SESSION["msg"] = 2;
    receipt("suspended");
    $recall_line = $_SESSION["standalone"] . " " . $_SESSION["laneno"] . " " . $cashier_no . " " . $trans_no;

    gohome();

    sql_close($db_a);
}

function checksuspended() {
    testremote();

    $db_a = tDataConnect();
    $m_conn = mDataConnect();

    $query_local = "select * from suspendedtoday";
    $query_remote = "select * from suspendedtoday";
    $query = "select * from suspendedlist";

    if ($_SESSION["standalone"] == 1) {
        if ($_SESSION["remoteDBMS"] == "mssql") {
            $result = mssql_query($query_local, $db_a);
        }
        else {
            $result = mysql_query($query, $db_a);
        }
    }
    else {
        if ($_SESSION["remoteDBMS"] == "mssql") {
            $result = sql_query($query_remote, $db_a);
        }
        else {
            $result = mysql_query($query_remote, $m_conn);
        }
    }

# That's just not right, fix it later?
#    $num_rows = sql_fetch_array($result);

    $num_rows=mysql_num_rows($result);


    if ($num_rows == 0) {
        return 0;
    }
    else {
        return 1;
    }

    sql_close($db_a);
}

