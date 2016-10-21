<?php
/*******************************************************************************

    Copyright 2001, 2004, 2008 Wedge Community Co-op

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

if (!function_exists("addItem")) include("additem.php");
if (!function_exists("couponcode")) include("mcoupon.php");

function upcscanned($entered) {
    $hitareflag = 0;
    $entered = str_replace(".", " ", $entered);

    if (strlen($entered) == 0 || !$entered) {
        lastpage();
    }
    else {
        if ($_SESSION["quantity"] == 0 && $_SESSION["multiple"] == 0) {
            $quantity = 1;
        }
        else {
            $quantity = $_SESSION["quantity"];
        }
    }

    $scaleprice = 0;

    if (substr($entered, 0, 1) == 0 && strlen($entered) == 7) {
        $p6 = substr($entered, -1);
        if ($p6 == 0) {
            $entered = substr($entered, 0, 3) . "00000" . substr($entered, 3, 3);
        }
        elseif ($p6 == 1) {
            $entered = substr($entered, 0, 3) . "10000" . substr($entered, 3, 3);
        }
        elseif ($p6 == 2) {
            $entered = substr($entered, 0, 3) . "20000" . substr($entered, 3, 3);
        }
        elseif ($p6 == 3) {
            $entered = substr($entered, 0, 4) . "00000" . substr($entered, 4, 2);
        }
        elseif ($p6 == 4) {
            $entered = substr($entered, 0, 5) . "00000" . substr($entered, 5, 1);
        }
        else {
            $entered = substr($entered, 0, 6) . "0000" . $p6;
        }
    }

    if (strlen($entered) == 13 && substr($entered, 0, 1) != 0) {
        $upc = "0".substr($entered, 0, 12);
    }
    else {
        $upc = substr("0000000000000".$entered, -13);
    }

    if (substr($upc, 0, 3) == "002") {
	$scaleprice = truncate2(substr($upc, -4)/100);
	$upc = substr($upc, 0, 8) . "00000";
    }

    $query = "select * from products where upc = '" . $upc . "' AND inUse = 1";
    
    $db = pDataConnect();
    $result = sql_query($query, $db);
    $num_rows = sql_num_rows($result);
    $row = sql_fetch_array($result);

    $normal_price = $row["normal_price"];
    $special_price = $row["special_price"];
    $cost = 0;
    $deposit = $row["deposit"];

    $dept = $row["department"];

    if ($num_rows == 0 && substr($upc, 0, 3) != "005") {
        $fconn = tDataconnect();
        $datetimestamp = strftime("%Y-%m-%d %H:%M:%S %p", time());
        $inserterror = "insert into failedscans select "
            . $upc." as upc, "
            . $_SESSION["laneno"] . " as lane_no, "
            . $_SESSION["CashierNo"] . " as emp_no, "
            . $_SESSION["transno"] . " as trans_no, "
            . "'" . $datetimestamp . "' as fdate";

        mysql_query($inserterror, $fconn);

        boxMsg($upc."<br /><b>is not a valid item</b>");
    }
    elseif ($num_rows == 0 && substr($upc, 0, 3) == "005") {
        couponcode($upc);
    }
    // RVM Hardcode for checking whether the customer is 21 or over in case item is alcoholic
    elseif (($dept == 19 || $dept == 20) && $_SESSION["store"] == "rvm" && $_SESSION["msgrepeat"] == 0 && $_SESSION["carded"] == 0) {
        if ($_SESSION["bdaystatus"] == 99) {
            $boxMsg  = "<b>Enter date of birth from valid ID</b><br />in the form mmddyyyy<p><font size=-1>[Clear] to cancel</font></p>";
        }
        if ($_SESSION["bdaystatus"] == -1) {
            $boxMsg  = "<b>Invalid date of birth</b><br />re-enter in the form mmddyyyy<p><font size=-1>[Clear] to cancel</font></p>";
        }
        if ($_SESSION["bdaystatus"] == 0) {
            $boxMsg  = "<b>Sales of item declined</b><p><font size=-1>[Clear] to cancel</font></p>";
        }
        $_SESSION["boxMsg"] = $boxMsg;
                bdBoxMsgScreen();
        }
    elseif ($row["scale"] != 0 && $_SESSION["weight"] == 0 && $_SESSION["quantity"] == 0) {
        if ($_SESSION["wgtRequested"] == 0) {
            $_SESSION["wgtRequested"] = 1;
            lastpage();
            echo "<script type=\"text/javascript\">\n"
                . "lockScreen = setTimeout('document.forms[0].elements[0].value = \"" . $_SESSION["strEntered"]
                . "\"; document.forms[0].submit();', 700)\n"
                . "</script>";
        }
        else {
            $_SESSION["SNR"] = 1;
            boxMsg("please put item on scale");
            $_SESSION["wgtRequested"] = 0;
        }
    }
    elseif ($row["scale"] != 0 && $_SESSION["scale"] == 0) {
        $_SESSION["waitforScale"] = 1;
        $_SESSION["SNR"] = 1;
        lastpage();
    }
    elseif ($row["scale"] == 0 && (int) $_SESSION["quantity"] != $_SESSION["quantity"] && $_SESSION["fractions"] != 1) {
        boxMsg("fractional quantity cannot be accepted for this item");
    }
    elseif ($_SESSION["itemDiscount"] < 0 || $_SESSION["itemDiscount"] > 65) {
        xboxMsg("item cannot be<br />discounted at " . $_SESSION["itemDiscount"] . "%");
        $_SESSION["itemDiscount"] = 0;
    }
    else {
        $mixMatch = 0;
        $qttyEnforced = $row["qttyEnforced"];

        if (($qttyEnforced == 1) && ($_SESSION["multiple"] == 0) && ($_SESSION["msgrepeat"] == 0)) {
            qttyscreen();
        }
        else {
            $_SESSION["qttyvalid"] = 1;
        }

        if ($_SESSION["qttyvalid"] != 1) {
            sql_close($db);
        }
        else {
            $upc = $row["upc"];
            $description = $row["description"];
            $description = str_replace("'", "", $description);
            $description = str_replace(",", "", $description);
            $transType = "I";
            $transsubType = "CA";
            $department = $row["department"];
            $unitPrice = $normal_price;

            $regPrice = $normal_price;
            $CardNo = $_SESSION["memberID"];

            if ($row["scale"] != 0) {
                $scale = 1;
            }
            else {
                $scale = 0;
            }

            if ($row["tax"] <> 0 && $_SESSION["toggletax"] == 0) {
                $tax = $row["tax"];
            }
            elseif ($row["tax"] <> 0 && $_SESSION["toggletax"] <> 0) {
                $tax = 0;
                $_SESSION["toggletax"] = 0;
            }
            elseif ($row["tax"] == 0 && $_SESSION["toggletax"] <> 0) {
                $tax = $_SESSION["toggletax"];
                $_SESSION["toggletax"] = 0;
            }
            else {
                $tax = 0;
            }

            if ($row["foodstamp"] != 0 && $_SESSION["togglefoodstamp"] == 0) {
                $foodstamp = 1;
            }
            elseif ($row["foodstamp"] != 0 && $_SESSION["togglefoodstamp"] == 1) {
                $foodstamp = 0;
                $_SESSION["togglefoodstamp"] = 0;
            }
            elseif ($row["foodstamp"] == 0 && $_SESSION["togglefoodstamp"] == 1) {
                $foodstamp = 1;
                $_SESSION["togglefoodstamp"] = 0;
            }
            else {
                $foodstamp = 0;
            }

            if ($scale == 1) {
                $hitareflag = 0;

                if ($_SESSION["quantity"] != 0) {
                    $quantity = $_SESSION["quantity"] - $_SESSION["tare"];
                }
                else {
                    $quantity = $_SESSION["weight"] - $_SESSION["tare"];
                }

                if ($quantity <= 0) {
                    $hitareflag = 1;
                }

                $_SESSION["tare"] = 0;
            }

            $discounttype = nullwrap($row["discounttype"]);
            $discountable = $row["discount"];
            $sale_price = $row["special_price"];

            if ($_SESSION["itemDiscount"] > 0 && $_SESSION["itemDiscount"] < 65 && $discounttype != 0) {
                $discountable = 1;
                $special_price = number_format(($sale_price * (100 - $_SESSION["itemDiscount"]) / 100), 2);
                $_SESSION["itemDiscount"] = 0;
            }
            elseif ($_SESSION["itemDiscount"] > 0 && $_SESSION["itemDiscount"] < 65) {
                $discountable = 1;
                $discounttype = 1;
                $special_price = number_format(($normal_price * (100 - $_SESSION["itemDiscount"]) / 100), 2);
                $_SESSION["itemDiscount"] = 0;
            }

            if ($_SESSION["toggleDiscountable"] == 1) {
                $_SESSION["toggleDiscountable"] = 0;
                if  ($discountable != 0) {
                    $discountable = 0;
                }
                else {
                    $discountable = 1;
                }
            }

            if ($_SESSION["nd"] == 1 && $discountable == 7) {
                $discountable = 3;
                $_SESSION["nd"] = 0;
            }

            if ($discounttype == 2 || $discounttype == 4) {
                $memDiscount = truncate2($normal_price * $quantity) - truncate2($special_price * $quantity);
                $discount = 0;
                $unitPrice = $normal_price;

            }
            elseif ($discounttype == 1) {
                $unitPrice = $special_price;
                $unitDiscount = $normal_price - $special_price;
                $discount = $unitDiscount * $quantity;
                $memDiscount = 0;
            }
            else {
                $unitPrice = $normal_price;
                $discount = 0;
                $memDiscount = 0;
            }

            if ($_SESSION["isMember"] == 1 && $discounttype == 2) {
                $unitPrice = nullwrap($special_price);
            }

            if ($_SESSION["isStaff"] != 0 && $discounttype == 4) {
                $unitPrice = nullwrap($special_price);
            }

            if ($_SESSION["casediscount"] > 0 && $_SESSION["casediscount"] <= 100) {
                $casediscount = (100 - $_SESSION["casediscount"])/100;
                $unitPrice = $casediscount * $unitPrice;
            }

            //-------------Mix n Match -------------------------------------
            $matched = 0;

            $VolSpecial = nullwrap($row["groupprice"]);    
            $volDiscType = nullwrap($row["pricemethod"]);
            $volume = nullwrap($row["quantity"]);

            if ($row["advertised"] != 0) {
                if (($row["discounttype"] == 2 && $_SESSION["isMember"] == 1) || $row["discounttype"] != 2) {
                    $VolSpecial = nullwrap($row["specialgroupprice"]);
                    $volDiscType = nullwrap($row["specialpricemethod"]);
                    $volume = nullwrap($row["specialquantity"]);
                }
            }

            if ($volDiscType && $volDiscType >= 1) {            // If item is on volume discount
                if (!$row["mixmatchcode"] || $row["mixmatchcode"] == 0) {
                    $mixMatch = 0;
                    $queryt = "select sum(ItemQtty - matched) as mmqtty from localtemptrans where "
                        ."upc = '".$row["upc"]."' group by upc";
                }
                else {
                    $mixMatch  = $row["mixmatchcode"];
                    $queryt = "select sum(ItemQtty - matched) as mmqtty, mixMatch from localtemptrans "
                        . "where mixMatch = '" . $mixMatch . "' group by mixMatch";
                }

                if ($volDiscType == 1) {
                    $unitPrice = truncate2($VolSpecial/$volume);
                }

                $voladj = $VolSpecial - (($volume - 1) * $unitPrice);   // one at special price
                $newmm = (int) ($quantity/$volume);                  // number of complete sets
                
                $dbt = tDataConnect();
                $resultt = sql_query($queryt, $dbt);
                $num_rowst = sql_num_rows($resultt);

                if ($num_rowst > 0) {
                    $rowt = sql_fetch_array($resultt);
                    $mmqtty = $rowt["mmqtty"];                 // number not in complete sets in localtemptrans
                }
                else {
                    $mmqtty = 0;
                }

                $newmmtotal = $mmqtty + ($quantity % $volume);         
                $na = $newmmtotal % $volume;
                $quantity = $quantity % $volume;

                if ($newmm >= 1) {
                    addItem($upc, $description, "I", "", "", $department, $cost, $newmm, truncate2($VolSpecial), truncate2($newmm * $VolSpecial), truncate2($VolSpecial), $scale, $tax, $foodstamp, $discount, $memDiscount, $discountable, $discounttype, $volume * $newmm, $volDiscType, $volume, $VolSpecial, $mixMatch, $volume * $newmm, 0, 0, '');
                    $newmm = 0;
                    $_SESSION["qttyvalid"] = 0;
                }

                if ($newmmtotal >= $volume) {
                    addItem($upc, $description, "I", "", "", $department, $cost, 1, $voladj, $voladj, $voladj, $scale, $tax, $foodstamp, $discount, $memDiscount, $discountable, $discounttype, 1, $volDiscType, $volume, $VolSpecial, $mixMatch, $volume, 0, 0, '');
                    $quantity = $quantity - 1;
                    $newmmtotal = 0;
                    $_SESSION["qttyvalid"] = 0;
                }
                sql_close($dbt);
            }

            $total = $unitPrice * $quantity;

	    if (substr($upc, 0, 3) == "002" and $discounttype != 2) {
                $unitPrice = truncate2($scaleprice);
                $regPrice = $total;
                $total = $unitPrice * $quantity;
            }

            $total = truncate2($total);
            $unitPrice = truncate2($unitPrice);

            if ($upc == "0000000008010" && $_SESSION["msgrepeat"] == 0) {
                $_SESSION["endorseType"] = "giftcert";
                $_SESSION["tenderamt"] = $total;
                $_SESSION["boxMsg"] = "<b>" . $total . " gift certificate</b><br />insert document<br />press [enter] to endorse<p><font size='-1'>[clear] to cancel</font></p>";
                boxMsgscreen();
            }
            elseif ($upc == "0000000008006" && $_SESSION["msgrepeat"] == 0) {
                $_SESSION["endorseType"] = "stock";
                $_SESSION["tenderamt"] = $total;
                $_SESSION["boxMsg"] = "<B>".$total." stock payment</B><br />insert form<br />press [enter] to endorse<P><FONT size='-1'>[clear] to cancel</FONT>";
                boxMsgscreen();
            }
            elseif ($upc == "0000000008011" && $_SESSION["msgrepeat"] == 0) {
                $_SESSION["endorseType"] = "classreg";
                $_SESSION["tenderamt"] = $total;
                $_SESSION["boxMsg"] = "<b>" . $total . " class registration</b><br />insert form<br />press [enter] to endorse<p><font size='-1'>[clear] to cancel</font></p>";
                boxMsgscreen();
            }

            elseif ($hitareflag == 1) {
                boxMsg("item weight must be greater than tare weight");
            }
            else {
                if ($quantity != 0) {
                    $qtty = $quantity;

                    if ($scale == 1) goodBeep();

                    if ($_SESSION["casediscount"] > 0) {
                        addcdnotify();
                        $discounttype = 3;
                        $_SESSION["casediscount"] =0;
                        $quantity = 1;
                        $unitPrice = $total;
                        $regPrice = $total;
                    }

                    if ($_SESSION["ddNotify"] == 1 && $_SESSION["itemPD"] == 10) {
                        $_SESSION["itemPD"] = 0;
                        $discountable = 7;                        
                    }

                    if ($_SESSION["ddNotify"] == 1 && $discountable == 7) {
                        $intvoided = 22;
                    }
                    else {
                        $intvoided = 0;
                    }

                    addItem($upc, $description, "I", " ", " ", $department, $cost, $quantity, $unitPrice, $total, $regPrice, $scale, $tax, $foodstamp, $discount, $memDiscount, $discountable, $discounttype, $qtty, $volDiscType, $volume, $VolSpecial, $mixMatch, $matched, $intvoided, 0, '');
                    $_SESSION["msgrepeat"] = 0;
                    $_SESSION["qttyvalid"] = 0;
                }
            }
            if ($deposit && $deposit > 0) {
                addDeposit($quantity, $deposit, $foodstamp);
            }

            if ($tax == 1) {
                $_SESSION["istaxable"] = 1;
            }
            else {
                $_SESSION["istaxable"] = 0;
                $_SESSION["voided"] = 0;
            }

            if ($discounttype == 1) {
                $_SESSION["ondiscount"] = 1;
                $_SESSION["voided"] = 2;
                adddiscount($discount);
            }
            elseif ($discounttype == 2 && $_SESSION["isMember"] == 1) {
                $_SESSION["ondiscount"] = 1;
                $_SESSION["voided"] = 2;
                addmemspecialmsg();
            }
            elseif ($discounttype == 4 && $_SESSION["isStaff"] != 0) {
                $_SESSION["ondiscount"] = 1;
                $_SESSION["voided"] = 2;
                adddiscount($memDiscount);
            }
            else {
                $_SESSION["ondiscount"] = 0;
                $_SESSION["voided"] = 0;
            }

            if ($_SESSION["tare"] != 0) {
                $_SESSION["tare"] = 0;
            }
            $_SESSION["alert"] = "";
            $_SESSION["ttlflag"] = 0;

            $_SESSION["ttlrequested"] = 0;
            $_SESSION["fntlflag"] = 0;

            $_SESSION["togglefoodstamp"] = 0;
            $_SESSION["toggletax"] = 0;
            $_SESSION["repeat"] = 1;

            setglobalflags(0);

            if ($hitareflag != 1) {
                lastpage();
            }
        }
    }

    $_SESSION["quantity"] = 0;
    $_SESSION["itemPD"] = 0;

}

function couponsused() {
    $db = tDataConnect();
    $query = "select sum(ItemQtty) as couponsused from localtemptrans where upc = '0000000008005' group by upc";
    $result = sql_query($query, $db);
    $num_rows = sql_num_rows($result);

    if ($num_rows > 0) {
        $row = sql_fetch_array($result);
        $couponsused = nullwrap($row["couponsused"]);
    }
    else {
        $couponsused = 0;
    }

    sql_close($db);
}

function couponTotal() {
    $db = tDataConnect();
    $query = "select sum(total) as couponTotal from localtemptrans where upc = '0000000008005'";
    $result = sql_query($query, $db);
    $num_rows = sql_num_rows($result);

    if ($num_rows > 0) {
        $row = sql_fetch_array($result);
        $couponTotal = nullwrap($row["couponTotal"]);
    }
    else {
        $couponTotal = 0;
    }

    sql_close($db);
}



?>
