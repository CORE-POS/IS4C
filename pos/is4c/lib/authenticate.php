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
        include_once("/pos/is4c/lib/conf.php");
        apply_configurations();
    }

    if (!function_exists("pDataConnect")) {
        include("../connect.php");
    }
    if (!function_exists("tDataConnect")) {
        include("../connect.php");
    }
    if (!function_exists("addactivity")) {
        include("../additem.php");
    }
    if (!function_exists("loginscreen")) {
        include("../clientscripts.php");
    }
    if (!function_exists("memberID")) {
        include("../prehkeys.php");
    }

    $_SESSION["away"] = 1;
    rePoll();
    $_SESSION["training"] = 0;

    $password = str_replace(array("TRAINING", "'", ",", "+"), array('9999', ""), strtoupper(trim($_POST["reginput"])));

    $global_values = get_global_values();

    if (!$global_values["LoggedIn"]) {

        $employee_number = user_pass($password);

        $employee = get_user_info($employee_number);

        if ($employee) {
            testremote();

            setglobalvalue("CashierNo", $employee["EmpNo"]);
            setglobalvalue("cashier", $employee["FirstName"] . " " . substr($employee["LastName"], 0, 1) . ".");
            loadglobalvalues();

            $transno = gettransno($password);
            $_SESSION["transno"] = $transno;
            setglobalvalue("transno", $transno);
            setglobalvalue("LoggedIn", 1);

            if ($transno == 1) {
                addactivity(1);
            }

            loginscreen();
        }
        elseif ($password == 9999) {
            setglobalvalue("CashierNo", 9999);
            setglobalvalue("cashier", "Training Mode");
            setglobalvalue("LoggedIn", 1);
            loadglobalvalues();
            $_SESSION["training"] = 1;
            loginscreen();
        }
        else {
            $_SESSION["auth_fail"] = 1;
            header("Location:/login.php");
        }
    }
    else {
        if (get_user_info(user_pass($password)) == $global_values["CashierNo"]) {
            loadglobalvalues();
            testremote();
            loginscreen();
        }
        else {
            if (user_pass_priv($password)) {
                loadglobalvalues();
                testremote();
                loginscreen();
            }
            else {
                $_SESSION["auth_fail"] = 1;
                header("Location:/login.php");
            }

            sql_close($db_a);
        }
    }

    getsubtotals();
    $_SESSION["datetimestamp"] = strftime("%Y-%m-%m/%d/%y %T",time());

    if ($_SESSION["LastID"] != 0 && $_SESSION["memberID"] != "0" and $_SESSION["memberID"]) {
        $_SESSION["unlock"] = 1;
        memberID($_SESSION["memberID"]);
    }

