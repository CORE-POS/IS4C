<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\FormLib;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\gui\BasicCorePage;
if (!class_exists('AutoLoader')) include_once(dirname(__FILE__).'/../../../lib/AutoLoader.php');

class PaycardTransLookupPage extends BasicCorePage 
{

    function preprocess()
    {
        $this->conf = new PaycardConf();
        if (FormLib::get('doLookup', false) !== false) {
            $ref = FormLib::get('id');
            $local = FormLib::get('local');
            $mode = FormLib::get('mode');

            $obj = null;
            $resp = array();
            foreach($this->conf->get('RegisteredPaycardClasses') as $rpc) {
                $obj = new $rpc();
                if ($obj->myRefNum($ref)) {
                    break;
                } else {
                    $obj = null;
                }
            }

            if ($obj === null) {
                $resp['output'] = DisplayLib::boxMsg('Invalid Transaction ID' . '<br />Local System Error', '', true);
                $resp['confirm_dest'] = MiscLib::baseURL() . 'gui-modules/pos2.php';
                $resp['cancel_dest'] = MiscLib::baseURL() . 'gui-modules/pos2.php';
            } else if ($local == 0 && $mode == 'verify') {
                $resp['output'] = DisplayLib::boxMsg('Cannot Verify - Already Complete' . '<br />Local System Error', '', true);
                $resp['confirm_dest'] = MiscLib::baseURL() . 'gui-modules/pos2.php';
                $resp['cancel_dest'] = MiscLib::baseURL() . 'gui-modules/pos2.php';
            } else {
                $resp = $obj->lookupTransaction($ref, $local, $mode);
            }

            echo json_encode($resp);

            return false;
        }

        return true;
    }

    function body_content()
    {
        $this->input_header('onsubmit="PaycardTransLookupPage.formCallback();return false;"');
        echo '<div class="baseHeight">';
        $ptid = FormLib::get('id', 0);
        $local = false;
        if (substr($ptid, 0, 2) == '_l') {
            $local = true;
            $ptid = substr($ptid, 2);
        }
        $mode = FormLib::get('mode', 'lookup');
        $msg = 'Looking up transaction';
        if ($mode == 'verify') {
            $msg = 'Verifying transaction';
        }
        echo DisplayLib::boxMsg($msg . '<br />Please wait', '', true);
        echo '</div>'; // baseHeight

        printf('<input type="hidden" id="refNum" value="%s" />', $ptid);
        printf('<input type="hidden" id="local" value="%d" />', ($local) ? 1 : 0);
        printf('<input type="hidden" id="lookupMode" value="%s" />', $mode);

        echo "<div id=\"footer\">";
        echo DisplayLib::printfooter();
        echo "</div>\n";

        $this->addOnloadCommand('PaycardTransLookupPage.performLookup();');
    }

    function head_content()
    {
        echo '<script type="text/javascript" src="../js/PaycardTransLookupPage.js"></script>';
    }
}

AutoLoader::dispatch();

