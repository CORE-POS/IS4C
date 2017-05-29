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
use COREPOS\pos\lib\CoreState;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\Drawers;
use COREPOS\pos\lib\LaneLogger;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\TransRecord;
use COREPOS\pos\lib\UdpComm;
use COREPOS\pos\lib\Kickers\Kicker;
include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class login2 extends BasicCorePage 
{

    private $boxCSS;
    private $msg;

    private function getPassword()
    {
        $passwd = $this->form->tryGet('reginput');
        if ($passwd !== '') {
            UdpComm::udpSend('goodBeep');
        } else {
            $passwd = $this->form->tryGet('userPassword');
        }

        return $passwd;
    }

    public function preprocess()
    {
        $this->boxCSS = 'coloredArea';
        $this->msg = _('please enter your password');
        $this->body_class = '';

        if ($this->form->tryGet('reginput', false) !== false || $this->form->tryGet('userPassword', false) !== false) {
            $passwd = $this->getPassword();
            if (Authenticate::checkPassword($passwd)) {

                Database::testremote();
                UdpComm::udpSend("termReset");
                $sdObj = MiscLib::scaleObject();
                if (is_object($sdObj)) {
                    $sdObj->readReset();
                }

                $drawer = $this->getDrawer();
                TransRecord::addLogRecord(array(
                    'upc' => 'SIGNIN',
                    'description' => 'Sign In Emp#' . $this->session->get('CashierNo'),
                ));
                $this->kick($drawer);

                if ($drawer->current() == 0) {
                    $this->change_page($this->page_url."gui-modules/drawerPage.php");
                } else {
                    $this->change_page($this->page_url."gui-modules/pos2.php");
                }

                return false;
            }
            $this->boxCSS = 'errorColoredArea';
            $this->msg = _('password invalid, please re-enter');
        }

        return true;
    }

    public function head_content()
    {
        ?>
        <script type="text/javascript">
        function closeFrames() {
            window.top.close();
        }
        </script>
        <?php
        $this->default_parsewrapper_js();
        $this->scanner_scale_polling(True);
    }

    public function body_content()
    {
        // 18Agu12 EL Add separately for readability of source.
        $this->add_onload_command("\$('#userPassword').focus();");
        $this->add_onload_command("\$('#scalebox').css('display','none');");
        $logger = new LaneLogger();
        $logging = $logger->isLogging() ? '' : '<div class="errorColoredArea">Default log(s) unavailable</div>';

        ?>
        <div id="loginTopBar">
            <div class="name" style="border-radius: 4px 4px 0px 0px;">
                I S 4 C
            </div>
            <div class="version">
                P H P &nbsp; D E V E L O P M E N T
                &nbsp; V E R S I O N &nbsp; 2 .0 .0
            </div>
            <div class="welcome coloredArea" style="border-radius: 0px 0px 4px 4px;">
                <?php echo _("W E L C O M E"); ?>
            </div><?php echo $logging; ?>
        </div>
        <div id="loginCenter">
        <div class="box <?php echo $this->boxCSS; ?> rounded">
                <b><?php echo _("log in"); ?></b>
                <form id="formlocal" name="form" method="post" autocomplete="off" 
                    action="<?php echo filter_input(INPUT_SERVER, 'PHP_SELF'); ?>">
                <input type="password" name="userPassword" size="20" tabindex="0" 
                    onblur="$('#userPassword').focus();" id="userPassword" >
                <input type="hidden" name="reginput" id="reginput" value="" />
                <p>
                <?php echo $this->msg ?>
                </p>
                </form>
            </div>    
        </div>
        <div id="loginExit">
            <?php 
            echo _("EXIT");
            echo "<a href=\"\" ";
            echo "onmouseover=\"document.exit.src='{$this->page_url}graphics/switchred2.gif';\" ";
            echo "onmouseout=\"document.exit.src='{$this->page_url}graphics/switchblue2.gif';\">";
            ?>
            <img id="exit" style="border:0;" alt="exit"  src="<?php echo $this->page_url; ?>graphics/switchblue2.gif" /></a>
    
        </div>
        <form name="hidden">
        <input type="hidden" name="scan" value="noScan">
        </form>
        <?php
    } // END true_body() FUNCTION

    private function getDrawer()
    {
        /**
          Find a drawer for the cashier
        */
        $dbc = Database::pDataConnect();
        $drawer = new Drawers($this->session, $dbc);
        $drawerID = $drawer->current();
        $drawer->assign($this->session->get('CashierNo'), $drawerID);
        if ($drawerID == 0) {
            $available = $drawer->available();    
            if (count($available) > 0) { 
                $drawer->assign($this->session->get('CashierNo'),$available[0]);
                $drawerID = $available[0];
            }
        }

        return $drawer;
    }

    private function kick($drawer)
    {
        /**
          Use Kicker object to determine whether the drawer should open
          The first line is just a failsafe in case the setting has not
          been configured.
        */
        if (session_id() != '') {
            session_write_close();
        }
        $kicker = Kicker::factory($this->session->get('kickerModule'));
        if ($kicker->kickOnSignIn()) {
            $drawer->kick();
        }
    }

    public function unitTest($phpunit)
    {
        $drawer = $this->getDrawer();
        $phpunit->assertEquals(1, $drawer->current());
        $this->kick($drawer); // coverage
    }

}

AutoLoader::dispatch();

