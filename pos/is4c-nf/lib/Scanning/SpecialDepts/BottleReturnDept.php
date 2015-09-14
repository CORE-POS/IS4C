<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

class BottleReturnDept extends SpecialDept
{

    public $help_summary = 'Negate entered amount e.g. 100 means $1 refund not $1 sale';

    public function handle($deptID,$amount,$json)
    {
        if (CoreLocal::get('msgrepeat') == 0) { // invert has not happened yet
            CoreLocal::set('strEntered', (100*$amount * -1).'DP'.$deptID);
            CoreLocal::set('msgrepeat', 1);
            $json['main_frame'] = MiscLib::base_url().'gui-modules/boxMsg2.php?autoconfirm=1';
        }

        return $json;
    }
}

