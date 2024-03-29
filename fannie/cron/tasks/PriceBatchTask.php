<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
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

class PriceBatchTask extends FannieTask
{
    public $name = 'Price Batch Task';

    public $description = 'Apply price change batches. 
    This will update an item\'s regular retail price 
    on the batch\'s start date. Unlike sale batches
    there is no equivalent end date operation. 
    Replaces the old nightly.pcbatch.php script.';

    public $default_schedule = array(
        'min' => 10,
        'hour' => 2,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $sql = FannieDB::get($this->config->get('OP_DB'));

        $chk_vital = array();
        $chk_opt = array();

        /* change prices
        */
        $costChange = '';
        $batchList = $sql->tableDefinition('batchList');
        if (isset($batchList['cost'])) {
            $costChange = ", p.cost = CASE WHEN l.cost IS NOT NULL AND l.cost > 0 THEN l.cost ELSE p.cost END";
        }
        if (strstr(strtoupper($this->config->get('SERVER_DBMS')), "MYSQL")) {
            $chk_vital[] = $sql->query("UPDATE products AS p LEFT JOIN
                batchList AS l ON l.upc=p.upc LEFT JOIN
                batches AS b ON b.batchID=l.batchID
                SET p.normal_price = l.salePrice
                {$costChange}
                WHERE l.batchID=b.batchID AND l.upc=p.upc
                AND l.upc NOT LIKE 'LC%'
                AND b.discounttype = 0
                AND ".$sql->datediff($sql->now(),'b.startDate')." = 0");
            $chk_vital[] = $sql->query("UPDATE scaleItems AS p LEFT JOIN
                batchList AS l ON l.upc=p.plu LEFT JOIN
                batches AS b ON b.batchID=l.batchID
                SET p.price = l.salePrice
                WHERE l.batchID=b.batchID AND l.upc=p.plu
                AND l.upc NOT LIKE 'LC%'
                AND b.discounttype = 0
                AND ".$sql->datediff($sql->now(),'b.startDate')." = 0");
        } else {
            $costChange = str_replace('p.cost', 'cost', $costChange);
            $chk_vital[] = $sql->query("UPDATE products SET
                {$costChange}
                normal_price = l.salePrice
                FROM products AS p, batches AS b, batchList AS l
                WHERE l.batchID=b.batchID AND l.upc=p.upc
                AND l.upc NOT LIKE 'LC%'
                AND b.discounttype = 0
                AND ".$sql->datediff($sql->now(),'b.startDate')." = 0");
            $chk_vital[] = $sql->query("UPDATE scaleItems SET
                price = l.salePrice
                FROM scaleItems AS p, batches AS b, batchList AS l
                WHERE l.batchID=b.batchID AND l.upc=p.plu
                AND l.upc NOT LIKE 'LC%'
                AND b.discounttype = 0
                AND ".$sql->datediff($sql->now(),'b.startDate')." = 0");
        }

        /* likecoded items differentiated
           for char concatenation
        */
        if (strstr(strtoupper($this->config->get('SERVER_DBMS')), "MYSQL")) {
            $chk_vital[] = $sql->query("UPDATE products AS p LEFT JOIN
                upcLike AS v ON v.upc=p.upc LEFT JOIN
                batchList AS l ON l.upc=concat('LC',convert(v.likeCode,char))
                LEFT JOIN batches AS b ON b.batchID = l.batchID
                SET p.normal_price = l.salePrice
                {$costChange}
                WHERE l.upc LIKE 'LC%'
                AND b.discounttype = 0
                AND ".$sql->datediff($sql->now(),'b.startDate')." = 0");
        } else {
            $costChange = str_replace('p.cost', 'cost', $costChange);
            $chk_vital[] = $sql->query("UPDATE products SET normal_price = l.salePrice
                {$costChange}
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
            if ($chk === false) {
                $success = false;
                break;
            }
        }
        if ($success) {
            $this->cronMsg("Price change batches run successfully");
            $res = $sql->query("SELECT l.batchID
                FROM batchList AS l
                    INNER JOIN batches AS b ON l.batchID=b.batchID
                WHERE b.discounttype=0
                    AND ".$sql->datediff($sql->now(),'b.startDate')." = 0
                GROUP BY l.batchID");
            $ids = array();
            while ($row = $sql->fetchRow($res)) {
                $ids[] = $row['batchID'];
            }
            list($inStr, $args) = $sql->safeInClause($ids);
            $prep = $sql->prepare("UPDATE batches SET applied=1 WHERE batchID IN ({$inStr})");
            $sql->execute($prep, $args);
            $model = new BatchesModel($sql);
            $res = $sql->query("SELECT l.batchID
                FROM batchList AS l
                    INNER JOIN batches AS b ON l.batchID=b.batchID
                WHERE b.discounttype=0
                    AND l.upc LIKE '002%'
                    AND ".$sql->datediff($sql->now(),'b.startDate')." = 0
                GROUP BY l.batchID");
            while ($row = $sql->fetchRow($res)) {
                //$model->scaleSendPrice($row['batchID']);
            }
        } else {
            $this->cronMsg("Error running price change batches");
        }

        // log updates to prodUpdate table
        $success = true;
        $likeP = $sql->prepare('SELECT upc FROM upcLike WHERE likeCode=?');
        $batchQ = 'SELECT upc FROM batchList as l LEFT JOIN batches AS b
                ON l.batchID=b.batchID WHERE b.discounttype=0
                AND ' . $sql->datediff($sql->now(), 'b.startDate') . ' = 0';
        $batchR = $sql->query($batchQ);
        $prodUpdate = new ProdUpdateModel($sql);
        $queue = new COREPOS\Fannie\API\jobs\QueueManager();
        while ($batchW = $sql->fetch_row($batchR)) {
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

            $queue->add(array(
                'class' => 'COREPOS\\Fannie\\API\\jobs\\SyncItem',
                'data' => array(
                    'upc' => $upcs,
                ),
            ));
        }

        if ($success) {
            $this->cronMsg("Changes logged in prodUpdate");
        } else {
            $this->cronMsg("Error logging changes");
        }
    }
}

