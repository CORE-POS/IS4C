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
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\TransRecord;

/* this module is intended for re-use. 
 * Pass the name of a class with the interface AdminLoginInterface
 *
 * The callback should return a URL or True (for pos2.php)
 * when $success is True. When $success is False, the return
 * value is irrelevant. That call is provided in case any
 * cleanup is necessary after a failed login.
 */

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class adminlogin extends NoInputCorePage 
{
    private $boxColor;
    private $msg;
    private $heading;

    private function getClass()
    {
        $class = $this->form->tryGet('class');
        $class = str_replace('-', '\\', $class);
        try {
            $refl = new ReflectionClass($class); 
            if ($refl->implementsInterface('COREPOS\\pos\\lib\\adminlogin\\AdminLoginInterface')) {
                // make sure calling class implements required
                // method and properties
                return $class;
            }
        } catch (Exception $ex) {}

        return false;

    }

    function preprocess()
    {
        $this->boxColor="coloredArea";
        $this->msg = _("enter admin password");

        $posHome = MiscLib::base_url().'gui-modules/pos2.php';
        // get calling class (required)
        $class = $this->getClass();
        if ($class === false || !class_exists($class)){
            $this->change_page($posHome);
            return False;
        }

        list($this->heading, $loginLevel) = $class::messageAndLevel();

        if ($this->form->tryGet('reginput') !== '' || $this->form->tryGet('userPassword') !== '') {
            $passwd = $this->form->tryGet('reginput');
            if ($passwd === '') {
                $passwd = $this->form->tryGet('userPassword');
            }

            if (strtoupper($passwd) == "CL") {
                $class::adminLoginCallback(false);
                $this->change_page($this->page_url."gui-modules/pos2.php");
                return false;    
            } elseif (empty($passwd)) {
                $this->boxColor="errorColoredArea";
                $this->msg = _("re-enter admin password");
            } else {
                $dbc = Database::pDataConnect();
                if (Authenticate::checkPermission($passwd, $loginLevel)) {
                    $this->approvedAction($class, $passwd);

                    return false;
                }
                $this->boxColor="errorColoredArea";
                $this->msg = _("re-enter admin password");

                TransRecord::addLogRecord(array(
                    'upc' => $passwd,
                    'description' => substr($this->heading,0,30),
                    'charflag' => 'PW'
                ));
                $this->beep();
            }
        } else {
            // beep on initial page load
            $this->beep();
        }

        return true;
    }

    private function beep()
    {
        if ($this->session->get('LoudLogins') == 1) {
            UdpComm::udpSend('errorBeep');
        }
    }

    private function approvedAction($class, $passwd)
    {
        $row = Authenticate::getEmployeeByPassword($passwd);
        TransRecord::addLogRecord(array(
            'upc' => $row['emp_no'],
            'description' => substr($this->heading . ' ' . $row['FirstName'],0,30),
            'charflag' => 'PW',
            'num_flag' => $row['emp_no']
        ));
        $this->beep();
        $result = $class::adminLoginCallback(True);
        if ($result === true) {
            $this->change_page(MiscLib::base_url().'gui-modules/pos2.php');
        } else {
            $this->change_page($result);
        }
    }

    function head_content(){
        $this->default_parsewrapper_js();
        $this->scanner_scale_polling(True);
    }

    function body_content()
    {
        ?>
        <div class="baseHeight">
        <div class="<?php echo $this->boxColor; ?> centeredDisplay">
        <span class="larger">
        <?php echo $this->heading ?>
        </span><br />
        <form name="form" id="formlocal" method="post" 
            autocomplete="off" action="<?php echo filter_input(INPUT_SERVER, 'PHP_SELF'); ?>">
        <input type="password" id="userPassword" name="userPassword" tabindex="0" onblur="$('#userPassword').focus();" />
        <input type="hidden" name="reginput" id="reginput" value="" />
        <input type="hidden" name="class" value="<?php echo $this->form->tryGet('class'); ?>" />
        </form>
        <p>
        <?php echo $this->msg ?>
        </p>
        </div>
        </div>
        <?php
        $this->addOnloadCommand("\$('#userPassword').focus();");
    } // END true_body() FUNCTION

    public function unitTest($phpunit)
    {
        ob_start();
        $this->form->class = 'COREPOS-pos-lib-adminlogin-UndoAdminLogin';
        $phpunit->assertEquals(true, $this->preprocess());
        ob_end_clean();
        ob_start();
        $this->head_content();
        $phpunit->assertNotEquals(0, strlen(ob_get_clean()));
        ob_start();
        $this->body_content();
        $phpunit->assertNotEquals(0, strlen(ob_get_clean()));
    }
}

AutoLoader::dispatch();

