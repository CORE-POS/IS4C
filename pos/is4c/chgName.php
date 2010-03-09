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

// --- written by apbw 2/15/05 for name to print on charge slips (for biz charge & staff charges) 

function getChgName() {
     
	$query = "select LastName, FirstName from custdata where CardNo = '" .$_SESSION["memberID"] ."'";
	$connection = pDataConnect();
	$result = sql_query($query, $connection);
	$row = sql_fetch_array($result);
	$num_rows = sql_num_rows($result);

	if ($num_rows > 0) {
		if (strlen($_SESSION["memberID"])!= 4) {
				$_SESSION["ChgName"] = $row["LastName"];
		} 
		elseif (strlen($_SESSION["memberID"]) == 4) { 
				$LastInit = substr($row["LastName"], 0, 1).".";
				$_SESSION["ChgName"] = trim($row["FirstName"]) ." ". $LastInit;
		}
	else
		$_SESSION["ChgName"] = $_SESSION["memMsg"];
	}

sql_close($connection);

}


?>