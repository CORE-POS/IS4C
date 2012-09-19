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

$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

if (!function_exists("receipt")) include($CORE_PATH."lib/clientscripts.php");
if (!function_exists("getMatchingColumns")) include($CORE_PATH."lib/connect.php");
if (!isset($CORE_LOCAL)) include($CORE_PATH."lib/LocalStorage/conf.php");

/**
  @file
  @brief Functions related to suspend and resume transaction
  @deprecated see SuspendLib
*/

/**
  Suspends the current transaction
  If the remote server is available, it will be suspended
  there. Otherwise it is suspended locally.
*/
function suspendorder() {
	global $CORE_LOCAL;

	//testremote();
	$query_a = "select emp_no, trans_no from localtemptrans";
	$db_a = tDataConnect();
	$result_a = $db_a->query($query_a);
	$row_a = $db_a->fetch_array($result_a);
	$cashier_no = substr("000".$row_a["emp_no"], -2);
	$trans_no = substr("0000".$row_a["trans_no"], -4);

	if ($CORE_LOCAL->get("standalone") == 0) {
		$db_a->add_connection($CORE_LOCAL->get("mServer"),$CORE_LOCAL->get("mDBMS"),
			$CORE_LOCAL->get("mDatabase"),$CORE_LOCAL->get("mUser"),$CORE_LOCAL->get("mPass"));
		$cols = getMatchingColumns($db_a,"localtemptrans","suspended");
		$db_a->transfer($CORE_LOCAL->get("tDatabase"),"select {$cols} from localtemptrans",
			$CORE_LOCAL->get("mDatabase"),"insert into suspended ($cols)");
		$db_a->close($CORE_LOCAL->get("mDatabase"));
	}
	else { 
		$query = "insert into suspended select * from localtemptrans";
		$result = $db_a->query($query);
	}

	$CORE_LOCAL->set("plainmsg","transaction suspended");
	$CORE_LOCAL->set("msg",2);
	receipt("suspended");
	$recall_line = $CORE_LOCAL->get("standalone")." ".$CORE_LOCAL->get("laneno")." ".$cashier_no." ".$trans_no;

	$db_a->close();
}

/**
  Check whether there are suspended transactions
  @return
   - 1 Yes
   - 0 No

  This function ignores any transactions that
  are not from the current day.
*/
function checksuspended() {
	global $CORE_LOCAL;

	//testremote();

	$db_a = tDataConnect();
	$query_local = "select * from suspendedtoday";
		
	$result = "";
	if ($CORE_LOCAL->get("standalone") == 1) {
		$result = $db_a->query($query_local);
	} else {
		$db_a->close();
		$db_a = mDataConnect();
		$result = $db_a->query($query_local);
	}

	$num_rows = $db_a->num_rows($result);

	if ($num_rows == 0) return 0;
	else return 1;

	$db_a->close();
}

?>
