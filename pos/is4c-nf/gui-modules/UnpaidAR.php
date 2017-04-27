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

use COREPOS\pos\lib\gui\BasicCorePage;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\PrehLib;
include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class UnpaidAR extends BasicCorePage 
{
    function preprocess()
    {
        $arDepartment = '990';
        if ($this->session->get("store") == 'WEFC_Toronto') {
            $arDepartment = '1005';
        }
        try {
            $dec = $this->form->reginput;
            $amt = $this->session->get("old_ar_balance");

            if (strtoupper($dec) == "CL"){
                if ($this->session->get('memType') == 0){
                    COREPOS\pos\lib\MemberLib::setMember($this->session->get("defaultNonMem"), 1);
                }
                $this->change_page($this->page_url."gui-modules/pos2.php");
                return False;
            }
            elseif ($dec == "" || strtoupper($dec) == "BQ"){
                if (strtoupper($dec)=="BQ")
                    $amt = $this->session->get("balance");
                $inp = ($amt*100)."DP{$arDepartment}0";
                $memtype = $this->session->get("memType");
                $type = $this->session->get("Type");
                if ($memtype == 1 || $memtype == 3 || $type == "INACT"){
                    $this->session->set("isMember",1);
                    PrehLib::ttl();
                }
                $this->change_page(
                    $this->page_url
                    . "gui-modules/pos2.php"
                    . '?reginput=' . $inp
                    . '&repeat=1');
                return false;
            }
        } catch (Exception $ex) {}

        return true;
    }

    function head_content()
    {
        $this->noscan_parsewrapper_js();
    }
    
    function body_content()
    {
        $amt = $this->session->get("old_ar_balance");
        $this->input_header();
        ?>
        <div class="baseHeight">

        <?php
        if ($amt == $this->session->get("balance")){
            echo DisplayLib::boxMsg(sprintf(_("Old A/R Balance: $%.2f<br />
                [Enter] to pay balance now<br />
                [Clear] to leave balance"),$amt));
        } else {
            echo DisplayLib::boxMsg(sprintf(_("Old A/R Balance: $%.2f<br />
                Total A/R Balance: $%.2f<br />
                [Enter] to pay old balance<br />
                [Balance] to pay the entire balance<br />
                [Clear] to leave the balance"),
                $amt,$this->session->get("balance")));
        }
        echo "</div>";
        echo "<div id=\"footer\">";
        echo DisplayLib::printfooter();
        echo "</div>";
    } // END body_content() FUNCTION

    public function unitTest($phpunit)
    {
        ob_start();
        $this->form->reginput = 'CL';
        $phpunit->assertEquals(false, $this->preprocess());
        $this->form->reginput = 'BQ';
        $phpunit->assertEquals(false, $this->preprocess());
        ob_end_clean();
    }
}

AutoLoader::dispatch();

