<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

if (!chdir("LanePush")){
	echo "Error: Can't find directory (lane push)";
	exit;
}

include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');

/* HELP

   This script updates custdata balances based on
   activity today
*/

set_time_limit(0);
ini_set('memory_limit','256M');

$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
		$FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

// get balances that changed today
$data = array();
$fetchQ = "SELECT card_no,recent_payments FROM unpaid_ar_today WHERE mark=1";
$fetchR = $sql->query($fetchQ);
while($fetchW = $sql->fetch_row($fetchR))
	$data[$fetchW['card_no']] = $fetchW['recent_payments'];

$errors = False;
// connect to each lane and update balances
foreach($FANNIE_LANES as $lane){
	$db = new SQLManager($lane['host'],$lane['type'],$lane['op'],$lane['user'],$lane['pw']);

	if ($db === False){
		echo "Can't connect to lane: ".$lane['host']."\n";
		$errors = True;
		continue;
	}

	foreach($data as $cn => $bal){
		$upQ = sprintf("UPDATE unpaid_ar_today SET recent_payments=%.2f WHERE card_no=%d",
				$bal,$cn);
		$db->query($upQ);
	}
}

if ($errors) {
	echo "There was an error pushing unpaid AR info to the lanes\n";
	flush();
}

?>
