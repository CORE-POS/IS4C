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

/**
  @class SuspendLib
  Functions related to suspend and resume transaction
*/
class SuspendLib extends LibraryClass 
{

/**
  Suspends the current transaction
  If the remote server is available, it will be suspended
  there. Otherwise it is suspended locally.
  @return [string] transaction identifier
*/
static public function suspendorder() 
{
	$query_a = "select emp_no, trans_no from localtemptrans";
	$db_a = Database::tDataConnect();
	$result_a = $db_a->query($query_a);
	$row_a = $db_a->fetch_array($result_a);
	$cashier_no = substr("000".$row_a["emp_no"], -2);
	$trans_no = substr("0000".$row_a["trans_no"], -4);
    $trans_num = ReceiptLib::receiptNumber();

	if (CoreLocal::get("standalone") == 0) {
		$db_a->add_connection(CoreLocal::get("mServer"),CoreLocal::get("mDBMS"),
			CoreLocal::get("mDatabase"),CoreLocal::get("mUser"),CoreLocal::get("mPass"),false,true);
		$cols = Database::getMatchingColumns($db_a,"localtemptrans","suspended");
		$db_a->transfer(CoreLocal::get("tDatabase"),"select {$cols} from localtemptrans",
			CoreLocal::get("mDatabase"),"insert into suspended ($cols)");
		$db_a->close(CoreLocal::get("mDatabase"),True);
	} else { 
		$query = "insert into suspended select * from localtemptrans";
		$result = $db_a->query($query);
	}

	/* ensure the cancel happens */
	$cancelR = $db_a->query("UPDATE localtemptrans SET trans_status='X',charflag='S'");
    TransRecord::finalizeTransaction(true);

	CoreLocal::set("plainmsg",_("transaction suspended"));
	$recall_line = CoreLocal::get("standalone")." ".CoreLocal::get("laneno")." ".$cashier_no." ".$trans_no;
    /**
      If the transaction is marked as complete but somehow did not
      actually finish, this will prevent the suspended receipt from
      adding tax/discount lines to the transaction
    */
    CoreLocal::set('End', 0);

    return $trans_num;
}

/**
  Check whether there are suspended transactions
  @return
   - 1 Yes
   - 0 No

  This function ignores any transactions that
  are not from the current day.
*/
static public function checksuspended() 
{
	$db_a = Database::tDataConnect();
	$query_local = "SELECT upc 
                    FROM suspended
                    WHERE datetime >= " . date("'Y-m-d 00:00:00'");
		
	$result = "";
	if (CoreLocal::get("standalone") == 1) {
		$result = $db_a->query($query_local);
	} else {
		$db_a = Database::mDataConnect();
		$result = $db_a->query($query_local);
	}

	$num_rows = $db_a->num_rows($result);

	if ($num_rows == 0) {
        return 0;
	} else {
        return 1;
    }
}

}

