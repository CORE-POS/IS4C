<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op
    Modifications copyright 2010 Whole Foods Co-op

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

*********************************************************************************/

use COREPOS\pos\lib\gui\BasicCorePage;
use COREPOS\pos\lib\MiscLib;
include_once(dirname(__FILE__).'/../../../lib/AutoLoader.php');

class StripeAmountPage extends BasicCorePage 
{
    protected $amt = 0;
    protected $pay_page = 'StripePaymentPage.php';

    function preprocess()
    {
        // check for posts before drawing anything, so we can redirect
        if (isset($_REQUEST['reginput'])) {
            $input = strtoupper(trim($_REQUEST['reginput']));
            // CL always exits
            if ($input == "CL") {
                CoreLocal::set("msgrepeat",0);
                CoreLocal::set("toggletax",0);
                CoreLocal::set("togglefoodstamp",0);
                $this->change_page(MiscLib::baseURL()."gui-modules/pos2.php");

                return false;
            } elseif ($input == "") {
                $this->change_page($this->pay_page . '?amount=' . CoreLocal::get('amtdue'));
                return false;
            } elseif ($input != "" && substr($input,-2) != "CL") {
                // any other input is an alternate amount
                // convert cents to dollars and make sure it's valid
                $this->amt = $input;
                if (is_numeric($input)){
                    $this->amt = MiscLib::truncate2($this->amt / 100.00);
                }
                if ($this->validateAmount($this->amt)) {
                    $this->change_page($this->pay_page . '?amount=' . $this->amt);
                    return false;
                }
            }
        } // post?

        return true;
    }

    protected function validateAmount($amt)
    {
        $due = CoreLocal::get("amtdue");
        if (!is_numeric($amt) || abs($amt) < 0.005) {
        } elseif ($amt > 0 && $due < 0) {
        } elseif ($amt < 0 && $due > 0) {
        } else {
            return true;
        }
        return false;
    }

    function body_content()
    {
        $this->input_header();
        ?>
        <div class="baseHeight">
        <?php
        $due = CoreLocal::get("amtdue");
        if ($this->amt == 0) {
            $this->amt = $due;
        }
        $amt = $this->amt;
        if (!is_numeric($amt) || abs($amt) < 0.005) {
            echo DisplayLib::boxMsg("Invalid Amount: $amt",
                "Enter a different amount");
        } elseif ($amt > 0 && $due < 0) {
            echo DisplayLib::boxMsg("Invalid Amount",
                "Enter a negative amount");
        } elseif ($amt < 0 && $due > 0) {
            echo DisplayLib::boxMsg("Invalid Amount",
                "Enter a positive amount");
        } elseif ($amt > 0) {
            $msg = "Tender " . sprintf('$%.2f', $amt) . ' using Bitcoin';
            echo DisplayLib::boxMsg("[enter] to continue if correct<br>Enter a different amount if incorrect<br>[clear] to cancel", $msg, true);
        } elseif ($amt < 0) {
            $msg = "Refund " . sprintf('$%.2f', $amt) . ' using Bitcoin';
            echo DisplayLib::boxMsg("[enter] to continue if correct<br>Enter a different amount if incorrect<br>[clear] to cancel", $msg, true);
        } else {
            echo DisplayLib::boxMsg(
                "Enter a different amount","[clear] to cancel",
                "Invalid Amount"
            );
        }
        CoreLocal::set("msgrepeat",2);
        ?>
        </div>
        <?php
        echo "<div id=\"footer\">";
        echo DisplayLib::printfooter();
        echo "</div>";
    }
}

AutoLoader::dispatch();

