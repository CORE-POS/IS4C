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

include('../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');

/* PURPOSE:
	Crunch the previous month's total sales &
	deliveries into a single archive record
*/

set_time_limit(0);

$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
		$FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

$deliveryQ = "INSERT INTO InvDeliveryArchive
	SELECT max(inv_date),upc,vendor_id,sum(quantity),sum(price)
	FROM InvDeliveryLM 
	GROUP BY upc,vendor_id";
$sql->query($deliveryQ);

$sql->query("TRUNCATE TABLE InvDeliveryLM");
$lmQ = "INSERT INTO InvDeliveryLM SELECT * FROM InvDelivery WHERE "
	.$sql->monthdiff($sql->now(),'inv_date')." = 1";
$sql->query($lmQ);

$clearQ = "DELETE FROM InvDelivery WHERE ".$sql->monthdiff($sql->now(),'inv_date')." = 1";
$sql->query($clearQ);

$salesQ = "INSERT INTO InvSalesArchive
		select max(datetime),upc,sum(quantity),sum(total)
		FROM transArchive WHERE ".$sql->monthdiff($sql->now(),'datetime')." = 1
		AND scale=0 AND trans_status NOT IN ('X','R') 
		AND trans_type = 'I' AND trans_subtype <> '0'
		AND register_no <> 99 AND emp_no <> 9999
		GROUP BY upc";
$sql->query($salesQ);

?>
