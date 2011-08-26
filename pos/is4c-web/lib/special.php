<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

    This file is part of IS4C.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!function_exists("receipt")) include($IS4C_PATH."lib/clientscripts.php");
if (!function_exists("getMatchingColumns")) include($IS4C_PATH."lib/connect.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");


function suspendorder() {
	global $IS4C_LOCAL;

	//testremote();
	$query_a = "select emp_no, trans_no from localtemptrans";
	$db_a = tDataConnect();
	$result_a = $db_a->query($query_a);
	$row_a = $db_a->fetch_array($result_a);
	$cashier_no = substr("000".$row_a["emp_no"], -2);
	$trans_no = substr("0000".$row_a["trans_no"], -4);

	if ($IS4C_LOCAL->get("standalone") == 0) {
		$db_a->add_connection($IS4C_LOCAL->get("mServer"),$IS4C_LOCAL->get("mDBMS"),
			$IS4C_LOCAL->get("mDatabase"),$IS4C_LOCAL->get("mUser"),$IS4C_LOCAL->get("mPass"));
		$cols = getMatchingColumns($db_a,"localtemptrans","suspended");
		$db_a->transfer($IS4C_LOCAL->get("tDatabase"),"select {$cols} from localtemptrans",
			$IS4C_LOCAL->get("mDatabase"),"insert into suspended ($cols)");
		$db_a->close($IS4C_LOCAL->get("mDatabase"));
	}
	else { 
		$query = "insert into suspended select * from localtemptrans";
		$result = $db_a->query($query);
	}

	$IS4C_LOCAL->set("plainmsg","transaction suspended");
	$IS4C_LOCAL->set("msg",2);
	receipt("suspended");
	$recall_line = $IS4C_LOCAL->get("standalone")." ".$IS4C_LOCAL->get("laneno")." ".$cashier_no." ".$trans_no;

	$db_a->close();
}

function checksuspended() {
	global $IS4C_LOCAL;

	//testremote();

	$db_a = tDataConnect();
	$query_local = "select * from suspendedtoday";
		
	$result = "";
	if ($IS4C_LOCAL->get("standalone") == 1) {
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
