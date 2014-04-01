<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op.

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

/**
  @class CashDropNotifier
  Alert cashier a drop is needed
*/
class CashDropNotifier extends Notifier 
{
    /**
      Display the notification
      @return [string] html
    */
    public function draw()
    {
        global $CORE_LOCAL;

        $ret = '';
        if ($CORE_LOCAL->get('cashDropWarned') === true) {
            $ret .= '<div style="background:red;border: solid 1px black;padding:7px;text-align:center;font-size:120%;">';
            $ret .= '! ! !';
            $ret .= '</div>';
        }

        return $ret;
    }
}

