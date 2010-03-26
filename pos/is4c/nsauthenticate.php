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

if (!function_exists("drawerKick")) include("printLib.php");
if (!function_exists("pDataConnect")) include("connect.php");
if (!function_exists("gohome")) include("maindisplay.php");

$_SESSION["away"] = 1;
$password = strtoupper(trim($_POST["reginput"]));

if ($password == "TRAINING") {
    $password = 9999;
}

if ($password == "CL") {
    gohome();
}

elseif (!is_numeric($password)) {
    header("Location:nsinvalid.php");
}
elseif ($password > "9999" || $password < "1") {
    header("Location:nsinvalid.php");
}
else {
    $query_global = "select * from globalvalues";
    $db = pDataConnect();
    $result = sql_query($query_global, $db);
    $row = sql_fetch_array($result);

    if ($password == $row["CashierNo"]) {
        drawerKick();
        gohome();
    }
    else {
        sql_close($db);
        $query2 = "select emp_no, FirstName, LastName from employees where empactive = 1 and "
            . "frontendsecurity >= 11 and (cashierpassword = " . $password . " or adminpassword = "
            . $password . ")";
        $db2 = pDataConnect();
        $result2 = sql_query($query2, $db2);
        $num_row2 = sql_num_rows($result2);

        if ($num_row2 > 0) {
            drawerKick();
            gohome();
        }
        else {
            header("Location:nsinvalid.php");
        }
    }
    sql_close($db);
}

