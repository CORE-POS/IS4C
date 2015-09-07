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
if (!function_exists("setDrawerKick")) include("setDrawerKick.php");  // apbw 03/29/05 Drawer Kick Patch
if (!function_exists("writeLine")) include_once("printLib.php");    // apbw 03/26/05 Wedge Printer Swap Patch

// ----------------------------------------------------------- 
// printReceipt.php is the main page for printing receipts.  
// It invokes the following functions from other pages:  
// -----------------------------------------------------------

function printReceipt($arg1) {
    $receipt = "";

    setDrawerKick();

    if ($arg1 == "full" and $_SESSION["kick"] != 0) {    // ---- apbw 03/29/05 Drawer Kick Patch
        writeLine(chr(27) . chr(112) . chr(0) . chr(48) . "0");
    }

    /* --------------------------------------------------------------
      turn off staff charge receipt printing if toggled - apbw 2/1/05 
      ---------------------------------------------------------------- */

    if ($_SESSION["TenderType"] == "MI" and ($_SESSION["receiptToggle"] == 0 or $_SESSION["SCReceipt"] == 0)) {
        $_SESSION["noreceipt"] = 1;    // apbw 2/15/05 SCR
    }

    $dateTimeStamp = time();        // moved by apbw 2/15/05 SCR

    // -- Our Reference number for the transaction.
    $ref = trim($_SESSION["CashierNo"]) . "-" . trim($_SESSION["laneno"]) . "-" . trim($_SESSION["transno"]);

    $_SESSION["noreceipt"] = ($_SESSION["receiptToggle"] + 1) % 2;

    if ($_SESSION["noreceipt"] != 1)         // moved by apbw 2/15/05 SCR
    {
        $receipt = printReceiptHeader($dateTimeStamp, $ref);
        //    call to transLog, the body of the receipt comes from the view 'receipt'
        $query = "SELECT * from receipt";
        $db = tDataConnect();
        $result = sql_query($query, $db);
        $num_rows = sql_num_rows($result);
        //    loop through the results to generate the items listing.

        for ($i = 0; $i < $num_rows; $i++) {
            $row = sql_fetch_array($result);
            $receipt .= $row[0]."\n";
        }

        // The Nitty Gritty:
        if ($arg1 == "full") {
            $member = "Member " . trim($_SESSION["memberID"]);
            $your_discount = $_SESSION["transDiscount"] + $_SESSION["memCouponTTL"];

            if ($_SESSION["transDiscount"] + $_SESSION["memCouponTTL"] + $_SESSION["specials"] > 0) {
                $receipt .= "\n" . centerString("------------------ YOUR SAVINGS -------------------") . "\n";

                if ($your_discount > 0) {
                    $receipt .= "    DISCOUNTS: $" . number_format($your_discount, 2) . "\n";
                }

                if ($_SESSION["specials"] > 0) {
                    $receipt .= "    SPECIALS: $" . number_format($_SESSION["specials"], 2) . "\n";
                }

                $receipt .= centerString("---------------------------------------------------") . "\n";
            }
            $receipt .= "\n";
    
            if (trim($_SESSION["memberID"]) != 99999) {            //    mem# 99999 = NON-MEMBER 
                $receipt .= centerString("Thank You - " . $member) . "\n";
            }
            else {
                $receipt .= centerString("Thank You!") . "\n";
            }

            if ($_SESSION["yousaved"] > 0) {
                $receipt .= centerString("You Saved $" . number_format($_SESSION["yousaved"], 2)) . "\n";
            }

            if ($_SESSION["couldhavesaved"] > 0 && $_SESSION["yousaved"] > 0) {
                $receipt .= centerString("You could have saved an additional $"  . number_format($_SESSION["couldhavesaved"], 2)) . "\n";
            }
            elseif ($_SESSION["couldhavesaved"] > 0) {
                $receipt .= centerString("You could have saved $"
                        . number_format($_SESSION["couldhavesaved"], 2)) . "\n";
            }

            $receipt .= centerString($_SESSION["receiptFooter1"]) . "\n"
                . centerString($_SESSION["receiptFooter2"]) . "\n"
                . centerString($_SESSION["receiptFooter3"]) . "\n"
                . centerString($_SESSION["receiptFooter4"]) . "\n";

            // --- apbw 2/15/05 SCR ---
            if ($_SESSION["chargetender"] == 1) {                        
                $receipt = $receipt . printChargeFooterCust($dateTimeStamp, $ref);    
            }

            if ($_SESSION["ccTender"] == 1) {
                $receipt = $receipt . printCCFooter($dateTimeStamp,$ref);
            }

            if ($_SESSION["promoMsg"] == 1) {
                promoMsg();
            }

            $_SESSION["headerprinted"] = 0;
        }

        else {
            $dashes = "\n" . centerString("----------------------------------------------") . "\n";

            if ($arg1 == "partial") {            
                $receipt .= $dashes . centerString("*    P A R T I A L  T R A N S A C T I O N    *") . $dashes;
            }
            elseif ($arg1 == "cancelled") {
                $receipt .= $dashes . centerString("*  T R A N S A C T I O N  C A N C E L L E D  *") . $dashes;
            }
            elseif ($arg1 == "resume") {
                $receipt .= $dashes . centerString("*    T R A N S A C T I O N  R E S U M E D    *") . $dashes
                         . centerString("A complete receipt will be printed\n")
                         . centerString("at the end of the transaction");
            }
            elseif ($arg1 == "suspended") {
                $receipt .= $dashes . centerString("*  T R A N S A C T I O N  S U S P E N D E D  *") . $dashes
                         . centerString($ref);
            }
        }
    }

    /* --------------------------------------------------------------
      print store copy of charge slip regardless of receipt print setting - apbw 2/14/05 
      ---------------------------------------------------------------- */
    if ($_SESSION["chargetender"] == 1) {
        if ($_SESSION["noreceipt"] == 1) {    
            $receipt = printChargeFooterStore($dateTimeStamp, $ref);
        }
        else {    
            $receipt = $receipt . printChargeFooterStore($dateTimeStamp, $ref);    
        }    
    }        

    $receipt = $receipt . "\n\n\n\n\n\n\n";

    if ($_SESSION["noreceipt"] == 0) {
        writeLine($receipt . chr(27) . chr(105));
    }
    
    $receipt = "";

    $_SESSION["noreceipt"] = 0;    // apbw 2/15/05 SCR
    $_SESSION["kick"] = 1; // apbw 05/03/05 KickFix
}

