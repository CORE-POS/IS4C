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
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/* HELP

   monthly.inventory.php

   This script shuffles around data related to
   product deliveries and sales for the sake
   of calculating relative inventory levels.

   At any given time, InvDelivery contains
   item orders from the current month, 
   InvDeliveryLM contains item orders from
   the previous month, and InvDeliveryArchive
   contains compressed order data with a 
   single record per-UPC, per-month listing
   quantity ordered and cost. At the beginning
   of a new month, this script performs the
   rotations to make that happen.

   InvSalesArchive is analogous to
   InvDeliveryArchive with one record per-UPC,
   per-month indicating quantity sold and price.
   This script also adds an appropriate record
   to that table at the beginnign of a new
   month.
*/

include('../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include($FANNIE_ROOT.'src/cron_msg.php');

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
$chk = $sql->query($deliveryQ);
if ($chk === false)
    echo cron_msg("Error archiving last month's inventory data");

$chk1 = $sql->query("TRUNCATE TABLE InvDeliveryLM");
$lmQ = "INSERT INTO InvDeliveryLM SELECT * FROM InvDelivery WHERE "
    .$sql->monthdiff($sql->now(),'inv_date')." = 1";
$chk2 = $sql->query($lmQ);
if ($chk1 === false || $chk2 === false)
    echo cron_msg("Error setting up last month's inventory data");

$clearQ = "DELETE FROM InvDelivery WHERE ".$sql->monthdiff($sql->now(),'inv_date')." = 1";
$chk = $sql->query($clearQ);
if ($chk === false)
    echo cron_msg("Error clearing inventory data");

$salesQ = "INSERT INTO InvSalesArchive
        select max(datetime),upc,sum(quantity),sum(total)
        FROM transarchive WHERE ".$sql->monthdiff($sql->now(),'datetime')." = 1
        AND scale=0 AND trans_status NOT IN ('X','R') 
        AND trans_type = 'I' AND trans_subtype <> '0'
        AND register_no <> 99 AND emp_no <> 9999
        GROUP BY upc";
$chk = $sql->query($salesQ);
if ($chk === false)
    echo cron_msg("Error archiving sales data for inventory");

?>
