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
use COREPOS\pos\lib\Authenticate;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\UdpComm;
include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class login3 extends BasicCorePage 
{
    private $color;
    private $img;
    private $msg;

    protected $mask_input = True;

    private function getPassword()
    {
        $passwd = $this->form->tryGet('reginput');
        if ($passwd === '') {
            $passwd = $this->form->tryGet('scannerInput');
            if ($passwd !== '') {
                UdpComm::udpSend('goodBeep');
            }
        }

        return $passwd;
    }

    function preprocess()
    {
        $this->color = "coloredArea";
        $this->img = $this->page_url."graphics/key-icon.png";
        $this->msg = _("please enter password");
        if ($this->form->tryGet('reginput', false) !== false || $this->form->tryGet('scannerInput', false) !== false) {

            $passwd = $this->getPassword();
            $level = $this->session->get('SecurityUnlock');
            $level = $level ? $level : 30;

            if (Authenticate::checkPassword($passwd)){
                $this->change_page($this->page_url."gui-modules/pos2.php");
                return false;
            } elseif ($this->session->get('LastID') == 0 && Authenticate::checkPermission($passwd, $level)) {
                Database::setglobalvalue("LoggedIn", 0);
                $this->session->set("LoggedIn",0);
                $this->session->set("training",0);
                if (Database::rotateTempData()) {
                    Database::clearTempTables();
                }
                $this->change_page($this->page_url."gui-modules/login2.php");
                return false;
            }
            $this->color = "errorColoredArea";
            $this->img = $this->page_url."graphics/redkey4.gif";
            $this->msg = _("Password Invalid, Please Re-Enter");
        }

        if ($this->session->get('CustomerDisplay') == true) {
            $this->loadCustomerDisplay();
        }

        return true;
    }

    private function loadCustomerDisplay()
    {
        $childUrl = MiscLib::baseURL() . 'gui-modules/closed.php';
        $this->add_onload_command("CustomerDisplay.setURL('{$childUrl}');\n");
        $this->add_onload_command("CustomerDisplay.reloadCustomerDisplay();\n");
    }

    function head_content(){
        echo '<script type="text/javascript" src="' . $this->page_url . 'js/CustomerDisplay.js?date=20210823"></script>';
        $this->default_parsewrapper_js('scannerInput');
        $this->add_onload_command("\$('#formlocal').append('<input type=\"hidden\" name=\"scannerInput\" id=\"scannerInput\" />');");
    }

    function body_content()
    {
        $this->input_header();
        echo DisplayLib::printheaderb();
        ?>
        <div class="baseHeight">
            <div class="<?php echo $this->color; ?> centeredDisplay">
            <img alt="key" src='<?php echo $this->img ?>' />
            <p>
            <?php echo $this->msg ?>
            </p>
            </div>
        </div>
        <?php
        Database::getsubtotals();
        echo "<div id=\"footer\">";
        echo DisplayLib::printfooter();
        echo "</div>";
    } // END true_body() FUNCTION

}

AutoLoader::dispatch();

