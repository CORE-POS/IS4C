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

use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\DiscountModule;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\TransRecord;
use COREPOS\pos\parser\Parser;
use COREPOS\pos\parser\parse\VoidCmd;

class AccessProgramParser extends Parser {

    public function check($str)
    {
        if (substr($str, 0, 6) == 'ACCESS') {
            return true;
        } elseif ($str == 'VD10730') {
            return true;
        }

        return false;
    }

    public function parse($str)
    {
        $ret = $this->default_json();
        if ($str == 'VD10730') {
            return $this->voidBags($str);
        }

        if (CoreLocal::get('memberID') == '0') {
            $ret['output'] = DisplayLib::boxMsg(
                _("Apply member number first"),
                _('No member selected'),
                false,
                array_merge(array('Member Search [ID]' => 'parseWrapper(\'ID\');'), DisplayLib::standardClearButton())
            );

            return $ret;
        }

        if ($str == 'ACCESS') {
            if (CoreLocal::get('AccessQuickMenu') != '' && class_exists('QuickMenuLauncher')) {
                $qm = new QuickMenuLauncher($this->session);

                return $qm->parse('QM' . CoreLocal::get('AccessQuickMenu'));
            } else {
                $str = 'ACCESS0';
            }
        }

        /*
        if ($str !== 'ACCESS6' && CoreLocal::get('AccessSelection') === '') {
            CoreLocal::set('AccessSelection', $str);
            $ret['main_frame'] = MiscLib::baseURL() . 'gui-modules/adminlogin.php?class=AccessProgramParser';

            return $ret;
        } else {
            CoreLocal::set('AccessSelection', '');
        }
         */

        $selection = substr($str, 6);
        TransRecord::addRecord(array(
            'upc' => 'ACCESS',
            'description' => 'ACCESS SIGNUP',
            'quantity' => 1,
            'ItemQtty' => 1,
            'numflag' => $selection,
        ));

        DiscountModule::updateDiscount(new DiscountModule(10, 'custdata'));
        TransRecord::discountnotify(10);

        $ret['output'] = DisplayLib::lastpage();
        $ret['receipt'] = 'accessSignupSlip';

        return $ret;
    }

    private function voidBags($json)
    {
        $dbc = Database::tDataConnect();
        $ttlP = $dbc->prepare("SELECT SUM(total) FROM localtemptrans WHERE upc='0000000010730'");
        while ($dbc->getValue($ttlP) > 0.005) {
            $void = new VoidCmd($this->session);
            $json = $void->parse('VD0000000010730');
        }

        return $json;
    }

    public static $adminLoginMsg = 'Login to approve Access Application';
    public static $adminLoginLevel = 30;

    public static function adminLoginCallback($success)
    {
        if ($success) {
            CoreLocal::set('strRemembered', CoreLocal::get('AccessSelection'));
            CoreLocal::set('msgrepeat', 1);

            return true;
        } else {
            CoreLocal::set('AccessSelection', '');
            return false;
        }
    }
}

