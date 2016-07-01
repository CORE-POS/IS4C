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

use COREPOS\pos\lib\Authenticate;
use COREPOS\pos\lib\FormLib;
if (!class_exists('AutoLoader')) include_once(dirname(__FILE__).'/../../../lib/AutoLoader.php');

class paycardboxMsgVoid extends PaycardProcessPage 
{
    protected $mask_input = true;

    function preprocess()
    {
        // check for posts before drawing anything, so we can redirect
        if (FormLib::get('reginput', false) !== false) {
            $input = strtoupper(trim(FormLib::get('reginput')));
            // CL always exits
            if ($input == "CL") {
                $this->conf->reset();
                $this->conf->set("toggletax",0);
                $this->conf->set("togglefoodstamp",0);
                $this->change_page($this->page_url."gui-modules/pos2.php?reginput=TO&repeat=1");
                return false;
            }
    
            $continue = false;
            // when voiding tenders, the input must be an FEC's passcode
            if ($this->conf->get("paycard_mode") == PaycardLib::PAYCARD_MODE_VOID && $input != "" && substr($input,-2) != "CL") {
                if (Authenticate::checkPermission($input, 11)) {
                    $this->conf->set("adminP",$input);
                    $continue = true;
                }
            }
            // when voiding items, no code is necessary, only confirmation
            if ($this->conf->get("paycard_mode") != PaycardLib::PAYCARD_MODE_VOID && $input == "")
                $continue = true;
            // go?
            if ($continue) {
                // send the request, then disable the form
                $this->addOnloadCommand('paycard_submitWrapper();');
                $this->action = "onsubmit=\"return false;\"";
            }
            // if we're still here, display prompt again
        } elseif ($this->conf->get("paycard_mode") == PaycardLib::PAYCARD_MODE_AUTH) {
            // call paycard_void on first load to set up
            // transaction and check for problems
            $transID = $this->conf->get("paycard_id");
            foreach($this->conf->get("RegisteredPaycardClasses") as $rpc){
                $myObj = new $rpc();
                if ($myObj->handlesType($this->conf->get("paycard_type"))){
                    $ret = $myObj->paycardVoid($transID);
                    if (isset($ret['output']) && !empty($ret['output'])){
                        $this->conf->set("boxMsg",$ret['output']);
                        $this->change_page($this->page_url."gui-modules/boxMsg2.php");
                        return False;
                    }
                    break;
                }
            }
        }
        return True;
    }

    function body_content()
    {
        echo '<div class="baseHeight">';
        // generate message to print
        $amt = $this->conf->get("paycard_amount");
        if ($amt > 0) {
            echo PaycardLib::paycardMsgBox("Void " . PaycardLib::moneyFormat($amt) . " Payment?","Please enter password then","[enter] to continue voiding or<br>[clear] to cancel the void");
        } else {
            echo PaycardLib::paycardMsgBox("Void " . PaycardLib::moneyFormat($amt) . " Refund?","Please enter password then","[enter] to continue voiding or<br>[clear] to cancel the void");
        }
        $this->conf->set("msgrepeat",2);
        echo '</div>';
    }
}

AutoLoader::dispatch();

