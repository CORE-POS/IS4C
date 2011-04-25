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
session_cache_limiter('nocache');

if (!function_exists("lastpage")) include("listitems.php");
if (!function_exists("lockscreen")) include("clientscripts.php");
if (!function_exists("inputunknown")) include("drawscreen.php");
if (!function_exists("upcscanned")) include("upcscanned.php");
if (!function_exists("tender")) include("prehkeys.php");
if (!function_exists("voiditem")) include("void.php");
if (!function_exists("clubCard")) include ("clubCard.php");            // --- apbw 2/15/05 ClubCard ---
if (!function_exists("ccEntered")) include("ccEntered.php");
if (!function_exists("drawerKick")) include_once("printLib.php");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title></title>
    </head>
    <body>
        <form name='form1' method='post' action='/pos2.php'>
            <input type='hidden' name='input' value='start' />
        </form>

<?php
    $ls = lockscreen();

    //---------------------------------
    //          MAIN
    //---------------------------------

    $intAway = 0;
    $_SESSION["quantity"] = 0;
    $_SESSION["multiple"] = 0;
    $_SESSION["casediscount"] = 0;

    if (!$_SESSION["memberID"]) {
        $_SESSION["memberID"] = "0";
    }

    if (isset($_POST["input"])) {
        $entered = strtoupper(trim($_POST["input"]));
    }
    else {
        $entered = "";
    }

    if (substr($entered, -2) == "CL") {
        $entered = "CL";
    }

    if ($entered == "RI") $entered = $_SESSION["strEntered"];

    $_SESSION["strEntered"] = $entered;

    if ($_SESSION["msgrepeat"] == 1 && $entered != "CL") {
        $entered = $_SESSION["strRemembered"];
        $_SESSION["strEntered"] = $_SESSION["strRemembered"];
        $_SESSION["msgrepeat"] = 0;
    }

    //-----------First Filter-----------------\\
    if (substr($entered, 0, 4) == "SO8A") {
        $entered = substr($entered, 4);
    }

    if (is_numeric($entered) && substr($entered, 0, 2) == "55" && strlen($entered) == 11) {
        $entered = (int) substr($entered, 3);
        $entered = (string) $entered;
        $entered = $entered."ID";
    }

    if ($entered == "TNPR") {
        // patronage tracking module    ~joel 2006-12-26
        $_SESSION["togglePatronage"] = 1;
        trackPatronage();
        $entered = "TL";
    }

    if (substr($entered, 0, 4) == "10DI") {
        $_SESSION["itemPD"] = 10;
        $entered = substr($entered, 4);
        if (!$entered || strlen($entered) < 1) {
            $entered = "stop";
        }
    }
    elseif (substr($entered, 0, 7) == "10.00DI") {
        $_SESSION["itemPD"] = 10;
        $entered = substr($entered, 7);
        if (!$entered || strlen($entered) < 1) {
            $entered = "stop";
        }
    }
    elseif (strpos($entered, "PD") > 0) {
        $aPD = explode("PD", $entered);
        if (is_numeric($aPD[0]) && strlen($aPD[1]) > 0) {
            $_SESSION["itemDiscount"] = $aPD[0];
            $entered = $aPD[1];
        }
    }

    if (substr($entered, 0, 5) == "1TNFN" || substr($entered, 0, 5) == "FN1TN") {
        $entered = substr($entered, 5);
        $_SESSION["toggletax"] = 1;
        $_SESSION["togglefoodstamp"] = 1;
    }
    elseif (substr($entered, 0, 3) == "1TN") {
        $entered = substr($entered, 3);
        $_SESSION["toggletax"] = 1;
    }
    elseif ((substr($entered, 0, 4) == "FNDN") || (substr($entered, 0, 4) == "DNFN")) {
        $entered = substr($entered, 4);
        $_SESSION["togglefoodstamp"] = 1;
        $_SESSION["toggleDiscountable"] = 1;    
    }
    elseif ((substr($entered, 0, 2) == "FN") && substr($entered, 2, 2) != "TL") {
        $entered = substr($entered, 2);
        $_SESSION["togglefoodstamp"] = 1;
    }

    elseif (substr($entered, 0, 2) == "DN") {
        // toggle discountable flag
        $entered = substr($entered, 2);
        $_SESSION["toggleDiscountable"] = 1;
    }

    /***** staff charge receipt toggle ***** edited 2/1/05 by apbw SCR *****/
    if (substr($entered, -2) == "NR") {
        $_SESSION["receiptToggle"] = 0;        // apbw 2/1/05 SCR
        $entered = substr($entered, 0, -2);
    }

    if (substr($entered, 0, 2) == "ND") {
        $_SESSION["nd"] = 1;
        $entered = substr($entered, 2, strlen($entered) - 2);
    }

    if ($entered == "RF1TN") {
        $entered = "stop";
    }
    elseif (substr($entered, 0, 2) == "RF") {
        $entered = substr($entered, 2);

        if (!is_numeric($entered) && !strpos($entered, "*") && !strpos($entered, "DP") ) {
            $entered = "stop";
        }
        else {
            $_SESSION["refund"] = 1;
        }
    }
    if ($entered == "0MI" || $entered == "0.00MI") {
        // Misc Pay-In. Used at the Wedge to tender employee charges
        $entered = "MI";
    }

    if (substr($entered, 0, 2) == "MC") {
        // Manufacturer's coupon
        $_SESSION["mfcoupon"] = 1;
        $entered = substr($entered, 2);
    }
    elseif ($entered == "ED") {
        // meant to be Employee Discount. The key does not exist as yet
        $entered = "15DA";
    }
    elseif ($entered == "MD") {
        // meant to be Member Discount. The key does not exist as yet
        $entered = "10DA";
    }
    elseif (strstr($entered, "DT")) {
        // Case discount
        $dt = explode("DT", $entered);
        if (is_numeric($dt["0"]) && strlen($dt["0"]) > 0 && strlen($dt["1"]) > 0) {
            if ($dt["0"] != 5 && $dt["0"] != 10) {
                $entered = "cdinvalid";
            }
            elseif ($_SESSION["isStaff"] == "1") {
                $entered = "cdStaffNA";
            }
            elseif ($_SESSION["SSI"] == "1") {
                $entered = "cdSSINA";
            }
            elseif ($_SESSION["isMember"] == "1") {
                $_SESSION["casediscount"] = 10;
                $entered = $dt["1"];
            }
            elseif ($_SESSION["isMember"] != "1") {
                $_SESSION["casediscount"] = 5;
                $entered = $dt["1"];
            }
        }
        else {
            $entered = "stop";
        }
    }




    //---------- SECOND FILTER ----------------\\

    if (strpos($entered, "*")) {
        if (strpos($entered, "**") || strpos($entered, "*") == 0 || strpos($entered, "*") == (strlen($entered) -1)) {
            $entered = "stop";
        }
        else {
            $split = explode("*", $entered);
            if (is_numeric($split[0]) && (strpos($split[1], "DP") || is_numeric($split[1]))) {
                $_SESSION["quantity"] = $split[0];
                $_SESSION["multiple"] = 1;
                $entered = $split[1];
            }
            elseif (substr($entered, 0, 2) != "VD") {
                $entered = "stop";
            }
        }
    }

    //-------------------- $entered IS FILTERED ---------------------\\
    $taresplit = explode("TW", $entered);

    if ( $_SESSION["msg"] == "0") {
        lastpage();
        $_SESSION["msg"] = 99;
        $_SESSION["unlock"] = 0;
    }
    elseif ($_SESSION["plainmsg"] && strlen($_SESSION["plainmsg"]) > 0) {
        printheaderb();
        plainmsg($_SESSION["plainmsg"]);
        $_SESSION["plainmsg"] = 0;
        $_SESSION["msg"] = 99;
    }
    else {
        if ($entered == "stop") {
            inputUnknown();
        }
        elseif ($entered == "LOCK") {
                $away = moveto("login3.php");
        }
        elseif ($entered == "WAKEUP") {
            lastpage();
            rePoll();
            if ($_SESSION["OS"] != "win32") {
                exec("echo -e 'S11\\r' > /dev/ttyS0");
                exec("echo -e 'S14\\r' > /dev/ttyS0");
            }
        }
        elseif ($entered == "WAKEUP2") {
            lastpage();
            $_SESSION["beep"] = "wgtrequest";
        }
        elseif ($entered == "cdInvalid") {
            boxMsg($dt[0]."% case discount invalid");
        }
        elseif ($entered == "cdStaffNA") {
            boxMsg("case discount not applicable to staff");
        }
        elseif ($entered == "cdSSINA") {
            boxMsg("hit 10% key to apply case discount for member ".$_SESSION["memberID"]);
        }
        elseif ((!$entered || strlen($entered) < 1) && $_SESSION["away"] != 1) {
            $_SESSION["repeat"] = 0;
            lastpage();
        }
        elseif ((!$entered || strlen($entered) < 1) && $_SESSION["away"] == "1") {
            lastpage();
        }
        elseif (is_numeric($entered)) {
            upcscanned($entered);
        }
        elseif (substr($entered, -1, 1) == "?") {
            ccEntered($entered);  // include ccEntered.php
        }
        elseif ($entered == "U") {
            listitems($_SESSION["currenttopid"], $_SESSION["currentid"] -1);
        }
        elseif ($entered == "D") {
            listitems($_SESSION["currenttopid"], $_SESSION["currentid"] + 1);
        }
        elseif ($entered == "FNTL") {
            fsEligible();
        }
        elseif($entered == "TETL") {
            addTaxExempt();
            lastpage();
        }
        elseif ($entered == "FTTL") {
            finalttl();
            lastpage();
        }
        elseif ($entered == "VD15.00DA") {
            percentDiscount(0);
        }
        elseif ($entered == "VD10.01DA") {
            percentDiscount(0);
        }
        elseif ($entered == "TL") {
            ttl();
            lastpage();
        }
        elseif (strlen($entered) == 1) {
            inputUnknown();
        }
        elseif (strlen($entered) == 2) {
            switch ($entered) {
                case "ES":
                    endofShift();
                    break;
                case "RI":
                    break;
                case "PP":
                    receipt("partial");
                    lastpage();
                    break;
                case "PV":
                    $_SESSION["pvsearch"] = "";
                    maindisplay("productsearch.php");
                    $intAway = 1;
                    break;
                case "MG":
                    $intAway = 1;
                    maindisplay("adminlist.php");
                    break;
                case "LN":
                    $intAway = 1;
                    maindisplay("loanform.php");
                    break;
                case "RP":
                    if ($_SESSION["LastID"] != "0") {
                        boxMsg("transaction in progress");
                    }
                    else {
                        $query = "SELECT register_no, emp_no, trans_no, "
                            . "sum((CASE WHEN trans_type = 'T' THEN -1 * total ELSE 0 END)) AS total "
                            . "FROM localtrans WHERE register_no = " . $_SESSION["laneno"]
                            . " AND emp_no = " . $_SESSION["CashierNo"]
                            . " GROUP BE register_no, emp_no, trans_no order by 1000 - trans_no";
                        $db = tDataConnect();
                        $result = sql_query($query, $db);
                        $num_rows = sql_num_rows($result);

                        if ($num_rows == 0) {
                            boxMsg("no receipt found");
                        }
                        else {
                            maindisplay("rplist.php");
                        }
                        sql_close($db);
                    }
                    break;
                case "ID":
                    maindisplay("memsearch.php");
                    $intAway = 1;
                    break;
                case "VD":
                    if ($_SESSION["currentid"] == 0) {
                        boxMsg("No Item on Order");
                    }
                    else {
                        $str = $_SESSION["currentid"];
                        checkstatus($str);
                        if ($_SESSION["voided"] == 2) {
                            voiditem($str -1);
                        }
                        elseif ($_SESSION["voided"] == 3 || $_SESSION["voided"] == 6 || $_SESSION["voided"] == 8) {
                            boxMsg("Cannot void this entry");
                        }
                        elseif ($_SESSION["voided"] == 4 || $_SESSION["voided"] == 5) {
                            percentDiscount(0);
                        }
                        elseif ($_SESSION["voided"] == 10) {
                            reverseTaxExempt();
                            lastpage();
                        }
                        elseif ($_SESSION["transstatus"] == "V") {
                            boxMsg("Item already voided");
                            $_SESSION["transstatus"] = "";
                        }
                        else {
                            voiditem($str);
                        }
                    }
                    break;
                case "SO":
                    if ($_SESSION["LastID"] != 0) {
                        boxMsg("Transaction in Progress");
                    }
                    else {
                        setglobalvalue("LoggedIn", 0);
                        echo "<script type=\"text/javascript\">"
                            ."window.top.location='/login.php'"
                            ."</script>";
                        $intAway = 1;
                        $_SESSION["training"] = 0;
                    }
                    break;
                case "CL":
                    clearinput();
                    break;
                case "NS":
                    if ($_SESSION["LastID"] != 0) {
                        boxMsg("Transaction in Progress");
                    }
                    else {
                        $intAway = 1;
                        maindisplay("nslogin.php");
                    }
                    break;
                case "CN":
                    if ($_SESSION["runningTotal"] == 0) {
                        printheaderb();
                        receipt("cancelled");
                        $_SESSION["msg"] = 2;
                        plainmsg("transaction cancelled");
                    }
                    else {
                        $intAway = 1;
                        maindisplay("mgrlogin.php");
                    }
                    break;
                case "TA":
                    tender("TA", $_SESSION["runningTotal"] * 100);
                    break;
                case "TB":
                    boxMsg("credit card tender must specify amount");
                    break;
                case "EC":
                    boxMsg("EBT tender must specify amount");
                    break;
                case "FS":
                    boxMsg("EBT tender must specify amount");
                    break;
                case "EF":
                    tender("EF", $_SESSION["fsEligible"] * 100);
                    break;
                case "CC":
                    boxMsg("credit card tender must specify amount");
                    break;
                case "CX":
                    tender("CX", $_SESSION["runningTotal"] * 100);
                    break;
                case "MI":
                    if ($_SESSION["LastID"] == 0 ) {
                        boxMsg("No transaction in progress");
                    }
                    elseif ($_SESSION["ttlflag"] == 1) {
                        boxMsg("Charge tender must specify amount");
                    }
                    elseif ($_SESSION["memberID"] != "0" && $_SESSION["isStaff"] == 1) {
                        ttl();
                        $_SESSION["runningTotal"] = $_SESSION["amtdue"];
                        tender("MI", $_SESSION["runningTotal"] * 100);
                    }
                    elseif ($_SESSION["memberID"] != "0" && $_SESSION["isStaff"] == 0) {
                        xboxMsg("member ".$_SESSION["memberID"]." <br />is not authorized to make employee charges.");
                    }
                    else {
                        $_SESSION["mirequested"] = 1;
                        maindisplay("memsearch.php");
                        $intAway = 1;
                    }
                    break;
                case "SS":
                    $input = $_SESSION["weight"];
                    lastpage();
                    break;
                case "MA":
                    madCoupon();
                    break;
                case "BQ":
                    // Pop up a dialogue box with available charge balance on account
                    chargeOK();
                    $memChargeCommitted=$_SESSION["availBal"] - $_SESSION["memChargeTotal"];
                    boxMsg("Member #". $_SESSION["memberID"]."<br />Current charge total " . $_SESSION["balance"]);
                    break;
                default:
                    inputUnknown();
            }
        }
        elseif (substr($entered, 0, 3) == "S11") {
            $weight = substr($entered, 4);
            if (is_numeric($weight) || $weight < 9999) {
                $_SESSION["scale"] = 1;
                $_SESSION["weight"] = $weight / 100;
            }
            lastpage();
        }
        elseif (substr($entered, 0, 4) == "S143") {
            $_SESSION["scale"] = 0;
            lastpage();
        }
        elseif (strstr($entered, "DP") && strlen($entered) > 3&& substr($entered, 0, 2) != "VD") {
            $deptsplit = explode("DP", $entered);
            deptkey($deptsplit["0"], $deptsplit["1"]);
        }
        elseif (strlen($entered) > 2) {
            $left = substr($entered, 0, 2);
            $right = substr($entered, strlen($entered) - 2, 2);
            $strl = substr($entered, 0, strlen($entered) - 2);
            $strr = substr($entered, 2);
            switch ($right) {
                            case "BD":
                                    if (!is_numeric($strl)) {
                                            inputUnknown();
                                    }
                                    else {
                                            $deposit = $strl/100;
                                            addDeposit(1, $deposit, 0);
                                            lastpage();
                                    }
                                    break;
                            case "BR":
                                    if (!is_numeric($strl)) {
                                            inputUnknown();
                                    }
                                    else {
                                            $deposit = $strl/100;
                                            addBottleReturn(1, $deposit, 0);
                                            lastpage();
                                    }
                                    break;
                case "TW":
                    if (is_numeric($strl)) {
                        if (strlen($strl) > 4) {
                            boxMsg(truncate2($strl/100)." tare not supported");
                        }
                        elseif ($strl/100 > $_SESSION["weight"] && $_SESSION["weight"] > 0) {
                            boxMsg("Tare cannot be<br />greater than item weight");
                        }
                        else {
                            addtare($strl);
                            lastpage();
                        }
                    }
                    else {
                        inputUnknown();
                    }
                    break;
                case "CL":
                    clearinput();
                    break;
                case "ID":
                    if ($left != "PV") {
                        memberID($strl);
                    }
                    else {
                        $intAway = 1;
                        $_SESSION["pvsearch"] = $strr;
                        maindisplay("productlist.php");                
                    }
                    break;
                case "DA":

                    if (!is_numeric($strl)) {
                        inputUnknown();
                    }
                    elseif ($_SESSION["tenderTotal"] != 0) {
                        boxMsg("Discount not applicable after tender.");
                    }
                    elseif ($strl > 50) {
                        boxMsg("Discount exceeds maximum.");
                    }
                    elseif ($strl <= 0) {
                        boxMsg("Discount must be greater than zero.");
                    }
                    elseif ($strl <= 50 and $strl > 0) {
                        percentDiscount($strl);
                    }
                    else {
                        inputUnknown();
                    }
                    break;

    // ------------- Stackable Percentage Discount -------------------
                case "SD":
                    if (!is_numeric($strl)) {
                        inputUnknown();
                    }
                    elseif ($_SESSION["tenderTotal"] != 0) {
                        boxMsg("Discount not applicable after tender.");
                    }
                    elseif ($strl > 50) {
                        boxMsg("Discount exceeds maximum.");
                    }
                    elseif ($strl <= 0) {
                        boxMsg("Discount must be greater than zero.");
                    }
                    elseif ($strl <= 50 and $strl > 0) {
                        $existingPD = $_SESSION["percentDiscount"];
                        $stackablePD = $strl;
                        $equivalentPD = ($existingPD + $stackablePD);                                //    sum discounts
                        percentDiscount($equivalentPD);
                    } 
                    else {
                           inputUnknown();
                    }
                break;

    // --- Juice Bar club card added by apbw 2/15/2005 -----

                case "JC":                            // apbw 2/15/05 ClubCard
                    if ($strl == 50) {                // apbw 2/15/05 ClubCard
                        clubCard($_SESSION["currentid"]);    // apbw 2/15/05 ClubCard
                            }                            // apbw 2/15/05 ClubCard
                    break;                        // apbw 2/15/05 ClubCard        
                case "SC":
                    $strl = str_replace(".", " ", $strl);
                    $strl = str_replace(",", " ", $strl);
                    if (!is_numeric($strl) || strlen($strl) != 6) {
                        inputUnknown();
                    }
                    else {
                        staffCharge($strl);
                    }
                    break;
                default:
                    if (is_numeric($strl)) {
                        tender($right, $strl);
                    }
                    else {
                        switch ($left) {
                            case "VD":
                                voidupc($strr);
                                break;
                            case "PV":
                                $intAway = 1;
                                $_SESSION["pvsearch"] = $strr;
                                maindisplay("productlist.php");
                                break;
                            case "FN":
                                lastpage();
                                break;
                            default:
                                inputUnknown();
                        }
                    }
            }
        }
        else {
            inputUnknown();
        }
    }

    if ($intAway == 1) {
        $intAway = 0;
        printfooterb();
    }
    else {
        printfooter();
    }

    if ($_SESSION["End"] == 1) {
        rePoll();
        receipt("full");
    }

    $_SESSION["away"] = 0;

    function pos2_dataError($Type, $msg, $file, $line, $context) {
        $_SESSION["errorMsg"] = $Type." ".$msg." ".$file." ".$line." ".$context;
        if ($Type != 8) {
            $_SESSION["standalone"] = 1;
        }
    }

?>
    </body>
</html>
