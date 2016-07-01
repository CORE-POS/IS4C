<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

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

class paycardboxMsgBalance extends PaycardProcessPage {

    function preprocess()
    {
        // check for posts before drawing anything, so we can redirect
        if (FormLib::get('reginput', false) !== false) {
            $input = strtoupper(trim(FormLib::get('reginput')));
            // CL always exits
            if( $input == "CL") {
                $this->conf->set("msgrepeat",0);
                $this->conf->set("toggletax",0);
                $this->conf->set("togglefoodstamp",0);
                $this->conf->reset();
                $this->change_page($this->page_url."gui-modules/pos2.php");
                return False;
            }
    
            // when checking balance, no input is confirmation to proceed
            if ($input === "") {
                $this->addOnloadCommand("paycard_submitWrapper();");
                $this->action = "onsubmit=\"return false;\"";
            }
            // any other input is unrecognized, display prompt again
        } // post?
        return True;
    }

    function body_content()
    {
        ?>
        <div class="baseHeight">
        <?php
        echo PaycardLib::paycardMsgBox("Check Card Balance?",
            "If you proceed, you <b>cannot void</b> any previous action on this card!",
            "[enter] to continue<br>[clear] to cancel");
        $this->conf->set("msgrepeat",2);
        ?>
        </div>
        <?php
    }
}

AutoLoader::dispatch();

