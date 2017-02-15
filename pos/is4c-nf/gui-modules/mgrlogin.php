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

use COREPOS\pos\lib\gui\NoInputCorePage;
use COREPOS\pos\lib\Authenticate;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\ReceiptLib;
use COREPOS\pos\lib\TransRecord;
use COREPOS\pos\lib\UdpComm;

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class mgrlogin extends NoInputCorePage 
{
    function preprocess()
    {
        try {
            $arr = $this->mgrauthenticate($this->form->input);
            echo json_encode($arr);
            return false;
        } catch (Exception $ex) {
            // beep on initial page load
            if ($this->session->get('LoudLogins') == 1) {
                UdpComm::udpSend('twoPairs');
            }

            return true;
        } 
    }

    function head_content(){
        ?>
        <script type="text/javascript" src="js/mgrlogin.js"></script>
        <?php
        $this->default_parsewrapper_js();
        $this->scanner_scale_polling(True);
    }

    function body_content()
    {
        $this->add_onload_command("\$('#userPassword').focus();\n");
        ?>
        <div class="baseHeight">
        <div id="cancelLoginBox" class="coloredArea centeredDisplay">
        <span class="larger">
        <?php echo _("confirm cancellation"); ?>
        </span><br />
        <form name="form" id="formlocal" method="post" 
            autocomplete="off" 
            onsubmit="return mgrlogin.submitWrapper('<?php echo $this->page_url; ?>');">
        <input type="password" name="userPassword" tabindex="0" 
            onblur="$('#userPassword').focus();" id="userPassword" />
        <input type="hidden" name="reginput" id="reginput" value="" />
        </form>
        <p>
        <span id="localmsg"><?php echo _("please enter password"); ?></span>
        </p>
        </div>
        </div>
        <?php
    } // END true_body() FUNCTION

    function mgrauthenticate($password)
    {
        $ret = array(
            'cancelOrder'=>false,
            'msg'=>_('password invalid'),
            'heading'=>_('re-enter password'),
            'giveUp'=>false
        );

        $password = strtoupper($password);
        $password = str_replace("'","",$password);

        if (!isset($password) || strlen($password) < 1 || $password == "CL") {
            $ret['giveUp'] = true;
            return $ret;
        }

        $priv = sprintf("%d",$this->session->get("SecurityCancel"));
        $chk = false;
        $chk = ($priv == 25) ? Authenticate::checkPassword($password) : Authenticate::checkPermission($password, $priv);

        if ($chk) {
            $this->cancelorder();
            $ret['cancelOrder'] = true;
            $ret['trans_num'] = ReceiptLib::receiptNumber();

            $dbc = Database::tDataConnect();
            $dbc->query("update localtemptrans set trans_status = 'X'");
            TransRecord::finalizeTransaction(true);
        }
        if ($this->session->get('LoudLogins') == 1) {
            UdpComm::udpSend('twoPairs');
        }

        return $ret;
    }

    function cancelorder() 
    {
        $this->session->set("plainmsg",_("transaction cancelled"));
        UdpComm::udpSend("rePoll");
    }

    public function unitTest($phpunit)
    {
        $this->cancelorder();
        $phpunit->assertEquals('transaction cancelled', $this->session->get('plainmsg'));
        $ret = $this->mgrauthenticate('CL');
        $phpunit->assertEquals(true, $ret['giveUp']);
        $ret = $this->mgrauthenticate('56');
        $phpunit->assertEquals(true, $ret['cancelOrder']);
        $ret = $this->mgrauthenticate('12345');
        $phpunit->assertEquals(false, $ret['cancelOrder']);
        $this->session->set('plainmsg', '');
    }
}

AutoLoader::dispatch();

