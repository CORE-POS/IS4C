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
use COREPOS\pos\parser\Parser;

class B2BParser extends Parser 
{
    public function check($str)
    {
        if ($str == 'B2B') {
            return true;
        }
    }

    public function parse($str)
    {
        $ret = $this->default_json();

        if (CoreLocal::get('memberID') == '0') {
            $ret['output'] = DisplayLib::boxMsg(
                _("Apply member number first"),
                _('No member selected'),
                false,
                array_merge(array('Member Search [ID]' => 'parseWrapper(\'ID\');'), DisplayLib::standardClearButton())
            );

            return $ret;
        }

        $dbc = Database::mDataConnect();
        $mAlt = Database::mAltName();
        $prep = $dbc->prepare("SELECT COUNT(*) FROM {$mAlt}B2BInvoices WHERE cardNo=? AND isPaid=0");
        $res = $dbc->execute($prep, CoreLocal::get('memberID'));
        if ($res == false || $dbc->numRows($res) == 0) {
            $ret['output'] = DisplayLib::boxMsg(
                _('No invoices available'),
                '',
                false,
                DisplayLib::standardClearButton()
            );
        }

        $b2b = new B2B();
        $url = $b2b->pluginUrl();
        $ret['main_frame'] = $url . '/B2BListPage.php';

        return $ret;
    }
}

