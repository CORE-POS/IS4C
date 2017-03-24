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

namespace COREPOS\pos\lib;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\ReceiptLib;
use COREPOS\pos\lib\TransRecord;

/**
  @class SuspendLib
  Functions related to suspend and resume transaction
*/
class SuspendLib 
{

/**
  Suspends the current transaction
  If the remote server is available, it will be suspended
  there. Otherwise it is suspended locally.
  @return [string] transaction identifier
*/
static public function suspendorder($session) 
{
    $dba = Database::tDataConnect();
    $transNum = ReceiptLib::receiptNumber();

    if ($session->get("standalone") == 0) {
        $dba->addConnection($session->get("mServer"),$session->get("mDBMS"),
            $session->get("mDatabase"),$session->get("mUser"),$session->get("mPass"),false,true);
        if ($session->get('CoreCharSet') != '') {
            $dba->setCharSet($session->get('CoreCharSet'), $session->get('mDatabase'));
        }
        $cols = Database::getMatchingColumns($dba,"localtemptrans","suspended");
        $dba->transfer($session->get("tDatabase"),"select {$cols} from localtemptrans",
            $session->get("mDatabase"),"insert into suspended ($cols)");
        $dba->close($session->get("mDatabase"),True);
    } else { 
        $query = "insert into suspended select * from localtemptrans";
        $dba->query($query);
    }

    /* ensure the cancel happens */
    $dba->query("UPDATE localtemptrans SET trans_status='X',charflag='S'");
    TransRecord::finalizeTransaction(true);

    $session->set("plainmsg",_("transaction suspended"));
    /**
      If the transaction is marked as complete but somehow did not
      actually finish, this will prevent the suspended receipt from
      adding tax/discount lines to the transaction
    */
    $session->set('End', 0);

    return $transNum;
}

/**
  Check whether there are suspended transactions
  @return
   - 1 Yes
   - 0 No

  This function ignores any transactions that
  are not from the current day.
*/
static public function checksuspended($session) 
{
    $queryLocal = "SELECT upc 
                    FROM suspended
                    WHERE datetime >= " . date("'Y-m-d 00:00:00'");
        
    $dba = $session->get('standalone') == 1 ? Database::tDataConnect() : Database::mDataConnect();
    $result = $dba->query($queryLocal);
    $numRows = $dba->numRows($result);

    if ($numRows == 0) {
        return 0;
    }

    return 1;
}

}

