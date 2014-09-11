<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

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
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/


// merge memtype + memdefaults for updating the lanes
foreach($FANNIE_LANES as $lane){

	$dbc->add_connection($lane['host'],$lane['type'],$lane['op'],
			$lane['user'],$lane['pw']);
	if ($dbc->connections[$lane['op']] !== False){
		$selectQ = "SELECT t.memtype,t.memDesc,d.cd_type,d.discount,d.staff,d.SSI FROM memtype t 
			LEFT JOIN memdefaults d ON t.memtype = d.memtype ORDER BY t.memtype";
		$insQ = "INSERT INTO memtype (memtype,memDesc,custdataType,discount,staff,ssi)";

		$dbc->query("TRUNCATE TABLE memtype",$lane['op']);
		$dbc->transfer($FANNIE_OP_DB,$selectQ,$lane['op'],$insQ);
	}
}

echo "<li>memtype table synced</li>";

?>
