<?php
/*******************************************************************************

    Copyright 2016 Whole Foods Co-op

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

use COREPOS\pos\parser\Parser;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DisplayLib;

class VpParser extends Parser 
{
    public function check($str)
    {
        return ($str === 'VP');
    }

    public function parse($str)
    {
        $ret = $this->default_json();
        if (CoreLocal::get('memberID') == 0) {
            $ret['output'] = DisplayLib::boxMsg(
                _("Apply member number first"),
                _('Member only voucher'),
                false,
                array_merge(array('Member Search [ID]' => 'parseWrapper(\'ID\');'), DisplayLib::standardClearButton())
            );

            return $ret;
        }

        $dbc = Database::mDataConnect();
        $prep = $dbc->prepare("
            SELECT cardNo, amount
            FROM VirtualVouchers
            WHERE redeemed=0
                AND expired=0
                AND cardNo=?
                AND amount > 0
        ");
        $info = $dbc->getRow($prep, array(CoreLocal::get('memberID')));
        if ($info !== false) {
            $plugin_info = new VirtualPatronageLane();
            $ret['main_frame'] = $plugin_info->pluginUrl().'/VpPage.php';
        } else {
            $ret['output'] = DisplayLib::boxMsg(
                _('No voucher'),
                _('No voucher available for member'),
                false,
                DisplayLib::standardClearButton()
            );
        }

        return $ret;
    }
}

