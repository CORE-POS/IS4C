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

*********************************************************************************/

use COREPOS\pos\lib\FormLib;
if (!class_exists('AutoLoader')) include_once(dirname(__FILE__).'/../../../lib/AutoLoader.php');

class paycardboxMsgGift extends PaycardProcessPage {

    function preprocess()
    {
        // check for posts before drawing anything, so we can redirect
        if (FormLib::get('reginput', false) !== false) {
            $input = strtoupper(trim(FormLib::get('reginput')));
            // CL always exits
            if ($input == "CL") {
                $this->conf->set("msgrepeat",0);
                $this->conf->set("toggletax",0);
                $this->conf->set("togglefoodstamp",0);
                $this->conf->reset();
                $this->change_page($this->page_url."gui-modules/pos2.php");
                return false;
            }
    
            // when (de)activating/adding-value, double check that the current amount is acceptable
            // before checking input (similar logic is later when generating the message)
            $amt = $this->conf->get("paycard_amount");
            $amtValid = true;
            if (!is_numeric($amt) || $amt < 0.005) {
                $amtValid = false;
            }
    
            // no input is confirmation to proceed
            if( $input == "" && $amtValid) {
                $this->addOnloadCommand("paycard_submitWrapper();");
                $this->action = "onsubmit=\"return false;\"";
            }
            else if( $input != "" && substr($input,-2) != "CL") {
                // any other input is an alternate amount
                $this->conf->set("paycard_amount","invalid");
                if( is_numeric($input))
                    $this->conf->set("paycard_amount",$input/100);
            }
            // if we're still here, we haven't accepted a valid amount yet; display prompt again
        } // post?

        return true;
    }

    function body_content()
    {
        echo '<div class="baseHeight">';
        // generate message to print
        $mode = $this->conf->get("paycard_mode");
        $amt = $this->conf->get("paycard_amount");
        if ($amt == 0) {
            if ($mode == PaycardLib::PAYCARD_MODE_ACTIVATE) {
                echo PaycardLib::paycardMsgBox("Enter Activation Amount",
                    "Enter the amount to put on the card",
                    "[clear] to cancel");
            } elseif ($mode == PaycardLib::PAYCARD_MODE_ADDVALUE) {
                echo PaycardLib::paycardMsgBox("Enter Add-Value Amount",
                    "Enter the amount to put on the card",
                    "[clear] to cancel");
            }
        } elseif (!is_numeric($amt) || $amt < 0.005) {
            echo PaycardLib::paycardMsgBox("Invalid Amount",
                "Enter a positive amount to put on the card",
                "[clear] to cancel");
        } elseif ($mode == PaycardLib::PAYCARD_MODE_ACTIVATE) {
            echo PaycardLib::paycardMsgBox("Activate ".PaycardLib::moneyFormat($amt)."?","",
                "[enter] to continue if correct<br>Enter a different amount if incorrect<br>[clear] to cancel");
        } elseif ($mode == PaycardLib::PAYCARD_MODE_ADDVALUE) {
            echo PaycardLib::paycardMsgBox("Add Value ".PaycardLib::moneyFormat($amt)."?","",
                "[enter] to continue if correct<br>Enter a different amount if incorrect<br>[clear] to cancel");
        }
        $this->conf->set("msgrepeat",2);
        echo '</div>';
    }
}

AutoLoader::dispatch();

