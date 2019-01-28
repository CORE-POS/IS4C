<?php

namespace COREPOS\pos\ajax;
use COREPOS\pos\lib\AjaxCallback;
use COREPOS\pos\lib\Authenticate;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\Drawers;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\TransRecord;
use COREPOS\pos\lib\UdpComm;
use COREPOS\pos\lib\Kickers\Kicker;

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class AjaxLogin extends AjaxCallback
{
    protected $encoding = 'plain';

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

    public function ajax()
    {
        $ret = array('success' => false, 'msg' => _('password invalid, please re-enter'));
        $passwd = $this->getPassword();
        if (strlen($passwd) > 0 && Authenticate::checkPassword($passwd)) {
            $ret['success'] = true; 
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
            $ret['url'] = MiscLib::baseURL() . "gui-modules/pos2.php";
            if ($drawer->current() == 0) {
                $ret['url'] = MiscLib::baseURL() . "gui-modules/drawerPage.php";
            }

            // send output early in case anything goes wrong with the printer
            echo json_encode($ret);
            ob_flush();

            $this->kick($drawer);
            return '';
        }

        return json_encode($ret);
    }
}

AjaxLogin::run();

