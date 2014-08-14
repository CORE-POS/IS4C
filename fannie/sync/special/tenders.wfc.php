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


// map my big tender table down
foreach($FANNIE_LANES as $lane){

    $dbc->add_connection($lane['host'],$lane['type'],$lane['op'],
            $lane['user'],$lane['pw']);
    if ($dbc->connections[$lane['op']] !== False){
        $selectQ = "SELECT TenderID,TenderCode,TenderName,TenderType,ChangeMessage,
            MinAmount,MaxAmount,MaxRefund FROM tenders";
        $insQ = "INSERT INTO tenders (TenderID,TenderCode,TenderName,TenderType,
            ChangeMessage,MinAmount,MaxAmount,MaxRefund)";

        $dbc->query("TRUNCATE TABLE tenders",$lane['op']);
        $dbc->transfer($FANNIE_OP_DB,$selectQ,$lane['op'],$insQ);
    }
}

echo "<li>Tenders table synched</li>";

?>
