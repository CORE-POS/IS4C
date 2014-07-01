<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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

   nightly.pcbatch.php

   This script triggers Price Change batches, a
   special type of batch that changes a group of
   items' regular price rather than setting a sale
   price. Batches with a discount type of zero
   are considered price change batches.

   This script performs price changes for
   batches with a startDate matching the current
   date. To work effectively, it must be run at
   least once a day.

   This script does not update the lanes, therefore
   the day's last run should be before lane syncing.

   Changes are logged in prodUpdate if possible.
*/

include('../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include($FANNIE_ROOT.'src/cron_msg.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

set_time_limit(0);

$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
        $FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

$chk_vital = array();
$chk_opt = array();

/* change prices
*/
if (strstr($FANNIE_SERVER_DBMS, "MYSQL")){
    $chk_vital[] = $sql->query("UPDATE products AS p LEFT JOIN
        batchList AS l ON l.upc=p.upc LEFT JOIN
        batches AS b ON b.batchID=l.batchID
        SET p.normal_price = l.salePrice
        WHERE l.batchID=b.batchID AND l.upc=p.upc
        AND l.upc NOT LIKE 'LC%'
        AND b.discounttype = 0
        AND ".$sql->datediff($sql->now(),'b.startDate')." = 0");
}
else {
    $chk_vital[] = $sql->query("UPDATE products SET
        normal_price = l.salePrice
        FROM products AS p, batches AS b, batchList AS l
        WHERE l.batchID=b.batchID AND l.upc=p.upc
        AND l.upc NOT LIKE 'LC%'
        AND b.discounttype = 0
        AND ".$sql->datediff($sql->now(),'b.startDate')." = 0");
}

/* likecoded items differentiated
   for char concatenation
*/
if (strstr($FANNIE_SERVER_DBMS,"MYSQL")){
    $chk_vital[] = $sql->query("UPDATE products AS p LEFT JOIN
        upcLike AS v ON v.upc=p.upc LEFT JOIN
        batchList AS l ON l.upc=concat('LC',convert(v.likeCode,char))
        LEFT JOIN batches AS b ON b.batchID = l.batchID
        SET p.normal_price = l.salePrice
        WHERE l.upc LIKE 'LC%'
        AND b.discounttype = 0
        AND ".$sql->datediff($sql->now(),'b.startDate')." = 0");
} else {
    $chk_vital[] = $sql->query("UPDATE products SET normal_price = l.salePrice
        FROM products AS p LEFT JOIN
        upcLike AS v ON v.upc=p.upc LEFT JOIN
        batchList AS l ON l.upc='LC'+convert(varchar,v.likecode)
        LEFT JOIN batches AS b ON b.batchID = l.batchID
        WHERE l.upc LIKE 'LC%'
        AND b.discounttype = 0
        AND ".$sql->datediff($sql->now(),'b.startDate')." = 0");
}

$success = true;
foreach($chk_vital as $chk){
    if ($chk === false)
        $success = false;
}
if ($success)
    echo cron_msg("Price change batches run successfully");
else
    echo cron_msg("Error running price change batches");

// log updates to prodUpdate table
$success = true;
$likeP = $sql->prepare('SELECT upc FROM upcLike WHERE likeCode=?');
$batchQ = 'SELECT upc FROM batchList as l LEFT JOIN batches AS b
        ON l.batchID=b.batchID WHERE b.discounttype=0
        AND ' . $sql->datediff($sql->now(), 'b.startDate') . ' = 0';
$batchR = $sql->query($batchQ);
$prodUpdate = new ProdUpdateModel($sql);
while($batchW = $sql->fetch_row($batchR)) {
    $upcs = array();
    $upc = $batchW['upc'];
    // unpack likecodes to UPCs
    if (substr($upc, 0, 2) == 'LC') {
        $likeR = $sql->execute($likeP, array(substr($upc, 2)));
        while($likeW = $sql->fetch_row($likeR)) {
            $upcs[] = $likeW['upc'];
        }
    } else {
        $upcs[] = $upc;
    }

    foreach($upcs as $item) {
        $prodUpdate->reset();
        $prodUpdate->upc($item);
        $logged = $prodUpdate->logUpdate(ProdUpdateModel::UPDATE_PC_BATCH, 1001);
        if (!$logged) {
            $success = false;
        }
    }
}

if ($success)
    echo cron_msg("Changes logged in prodUpdate");
else
    echo cron_msg("Error logging changes");
?>
