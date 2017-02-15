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

namespace COREPOS\pos\lib;

/**
  @class Notifier
  Draw information on the right-hand side
  of the screen.
*/
class Notifier 
{
    /**
      Display the notification
      @return [string] html
    */
    public function draw()
    {
        return '';
    }

    /**
      Called after every transaction
      if any cleanup is neecessary
    */
    public function transactionReset()
    {

    }
}

/**
  @example 

  Show current member IOU balance

class MemBalanceNotifier extends Notifier 
{
    public function draw()
    {
        if (CoreLocal::get('memberID') == 0 || CoreLocal::get('memberID') == CoreLocal::get('defaultNonMem')) {
            return '';
        }

        $db = Database::pDataConnect();

        $query = $db->prepare('SELECT Balance FROM custdata WHERE CardNo=?');
        $result = $db->execute($query, array(CoreLocal::get('memberID')));

        // non-valid member number apparently
        if ($db->num_rows($result) == 0) {
            return '';
        }

        $row = $db->fetch_row($result);

        return sprintf('<div style="border:1px solid black;">Balance $%.2f</div>',
                        $row['Balance']);

    }
}
*/

