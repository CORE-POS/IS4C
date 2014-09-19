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

class AccessProgramParser extends Parser {

    public function check($str)
    {
        if (substr($str, 0, 6) == 'ACCESS') {
            return true;
        }
    }

    public function parse($str)
    {
        global $CORE_LOCAL;
        $ret = $this->default_json();

        if ($CORE_LOCAL->get('memberID') == '0') {
            $ret['output'] = DisplayLib::boxMsg(_('Enter owner number first'));

            return $ret;
        }

        if ($str == 'ACCESS' && $CORE_LOCAL->get('msgrepeat') == 0) {
            $ret['main_frame'] = MiscLib::baseURL() . 'gui-modules/adminlogin.php?class=AccessProgramParser';

            return $ret;
        } else if ($str == 'ACCESS') {
            if ($CORE_LOCAL->get('AccessQuickMenu') != '' && class_exists('QuickMenuLauncher')) {
                $qm = new QuickMenuLauncher();

                return $qm->parse('QM' . $CORE_LOCAL->get('AccessQuickMenu'));
            } else {
                $str = 'ACCESS0';
            }
        }

        $selection = substr($str, 6);
        TransRecord::addRecord(array(
            'upc' => 'ACCESS',
            'description' => 'ACCESS SIGNUP',
            'quantity' => 1,
            'ItemQtty' => 1,
            'numflag' => $selection,
        ));

        $ret['output'] = DisplayLib::lastpage();
        $ret['receipt'] = 'accessSignupSlip';

        return $ret;
    }

    public static $adminLoginMsg = 'Login to approve Acess Application';
    public static $adminLoginLevel = 30;

    public static function adminLoginCallback($success)
    {
        global $CORE_LOCAL;
        if ($success) {
            $CORE_LOCAL->set('strRemembered', 'ACCESS');
            $CORE_LOCAL->set('msgrepeat', 1);

            return true;
        } else {
            return false;
        }
    }
}
