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

/* why is this file such a mess?

   SQL for UPDATE against multiple tables is different 
   for MSSQL and MySQL. There's not a particularly clean
   way around it that I can think of, hence alternates
   for all queries.
*/

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI'))
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
if (!function_exists('updateProductAllLanes'))
    include($FANNIE_ROOT.'item/laneUpdates.php');

function forceBatch($batchID){
    global $FANNIE_OP_DB,$FANNIE_SERVER_DBMS;
    $dbc = FannieDB::get($FANNIE_OP_DB);

    $batchInfoQ = $dbc->prepare_statement("SELECT batchType,discountType FROM batches WHERE batchID = ?");
    $batchInfoR = $dbc->exec_statement($batchInfoQ,array($batchID));
    $batchInfoW = $dbc->fetch_array($batchInfoR);

    $forceQ = "";
    $forceLCQ = "";
    $forceMMQ = "";
    if ($batchInfoW['discountType'] != 0){

        $forceQ="UPDATE products AS p
            INNER JOIN batchList AS l
            ON p.upc=l.upc
            INNER JOIN batches AS b
            ON l.batchID=b.batchID
            SET p.start_date = b.startDate, 
            p.end_date=b.endDate,
            p.special_price=l.salePrice,
            p.specialgroupprice=CASE WHEN l.salePrice < 0 THEN -1*l.salePrice ELSE l.salePrice END,
            p.specialpricemethod=l.pricemethod,
            p.specialquantity=l.quantity,
            p.discounttype=b.discounttype,
            p.mixmatchcode = CASE 
            WHEN l.pricemethod IN (3,4) AND l.salePrice >= 0 THEN convert(l.batchID,char)
            WHEN l.pricemethod IN (3,4) AND l.salePrice < 0 THEN convert(-1*l.batchID,char)
            WHEN l.pricemethod = 0 AND l.quantity > 0 THEN concat('b',convert(l.batchID,char))
            ELSE p.mixmatchcode 
            END 
            WHERE l.upc not like 'LC%'
            and l.batchID = ?";
            
        $forceLCQ = "UPDATE products AS p
            INNER JOIN upcLike AS v 
            ON v.upc=p.upc
            INNER JOIN batchList as l 
            ON l.upc=concat('LC',convert(v.likecode,char))
            INNER JOIN batches AS b 
            ON b.batchID=l.batchID
            set p.special_price = l.salePrice,
            p.end_date = b.endDate,p.start_date=b.startDate,
                p.specialgroupprice=CASE WHEN l.salePrice < 0 THEN -1*l.salePrice ELSE l.salePrice END,
            p.specialpricemethod=l.pricemethod,
            p.specialquantity=l.quantity,
            p.discounttype = b.discounttype,
                p.mixmatchcode = CASE 
                WHEN l.pricemethod IN (3,4) AND l.salePrice >= 0 THEN convert(l.batchID,char)
                WHEN l.pricemethod IN (3,4) AND l.salePrice < 0 THEN convert(-1*l.batchID,char)
                WHEN l.pricemethod = 0 AND l.quantity > 0 THEN concat('b',convert(l.batchID,char))
                ELSE p.mixmatchcode 
                END 
            where l.batchID=?";

        if ($FANNIE_SERVER_DBMS == 'MSSQL'){
            $forceQ="UPDATE products
                SET start_date = b.startDate, 
                end_date=b.endDate,
                special_price=l.salePrice,
                    specialgroupprice=CASE WHEN l.salePrice < 0 THEN -1*l.salePrice ELSE l.salePrice END,
                specialpricemethod=l.pricemethod,
                specialquantity=l.quantity,
                discounttype=b.discounttype,
                mixmatchcode = CASE 
                WHEN l.pricemethod IN (3,4) AND l.salePrice >= 0 THEN convert(varchar,l.batchID)
                WHEN l.pricemethod IN (3,4) AND l.salePrice < 0 THEN convert(varchar,-1*l.batchID)
                WHEN l.pricemethod = 0 AND l.quantity > 0 THEN 'b'+convert(varchar,l.batchID)
                ELSE p.mixmatchcode 
                END 
                FROM products as p, 
                batches as b, 
                batchList as l 
                WHERE l.upc = p.upc
                and l.upc not like 'LC%'
                and b.batchID = l.batchID
                and b.batchID = ?";

            $forceLCQ = "update products set special_price = l.salePrice,
                end_date = b.endDate,start_date=b.startDate,
                discounttype = b.discounttype,
                specialpricemethod=l.pricemethod,
                specialquantity=l.quantity,
                        specialgroupprice=CASE WHEN l.salePrice < 0 THEN -1*l.salePrice ELSE l.salePrice END,
                mixmatchcode = CASE 
                    WHEN l.pricemethod IN (3,4) AND l.salePrice >= 0 THEN convert(varchar,l.batchID)
                    WHEN l.pricemethod IN (3,4) AND l.salePrice < 0 THEN convert(varchar,-1*l.batchID)
                    WHEN l.pricemethod = 0 AND l.quantity > 0 THEN 'b'+convert(varchar,l.batchID)
                    ELSE p.mixmatchcode 
                END 
                from products as p left join
                upcLike as v on v.upc=p.upc left join
                batchList as l on l.upc='LC'+convert(varchar,v.likecode)
                left join batches as b on b.batchID = l.batchID
                where b.batchID=?";
        }
    } else {
        $forceQ = "UPDATE products AS p
              INNER JOIN batchList AS l
              ON l.upc=p.upc
              SET p.normal_price = l.salePrice,
              p.modified = curdate()
              WHERE l.upc not like 'LC%'
              AND l.batchID = ?";

        $forceLCQ = "UPDATE products AS p
            INNER JOIN upcLike AS v
            ON v.upc=p.upc INNER JOIN
            batchList as b on b.upc=concat('LC',convert(v.likecode,char))
            set p.normal_price = b.salePrice,
            p.modified=curdate()
            where b.batchID=?";

        if ($FANNIE_SERVER_DBMS == 'MSSQL'){
            $forceQ = "UPDATE products
                  SET normal_price = l.salePrice,
                  modified = getdate()
                  FROM products as p,
                  batches as b,
                  batchList as l
                  WHERE l.upc = p.upc
                  AND l.upc not like 'LC%'
                  AND b.batchID = l.batchID
                  AND b.batchID = ?";

            $forceLCQ = "update products set normal_price = b.salePrice,
                modified=getdate()
                from products as p left join
                upcLike as v on v.upc=p.upc left join
                batchList as b on b.upc='LC'+convert(varchar,v.likecode)
                where b.batchID=?";
        }
    }

    $forceP = $dbc->prepare_statement($forceQ);
    $forceR = $dbc->exec_statement($forceP,array($batchID));
    $forceLCP = $dbc->prepare_statement($forceLCQ);
    $forceR = $dbc->exec_statement($forceLCP,array($batchID));

    $q = $dbc->prepare_statement("SELECT upc FROM batchList WHERE batchID=?");
    $r = $dbc->exec_statement($q,array($batchID));
    $likeP = $dbc->prepare_statement('SELECT upc FROM upcLike WHERE likeCode=?');
    $update = new ProdUpdateModel($dbc);
    $updateType = ($batchInfoW['discountType'] == 0) ? ProdUpdateModel::UPDATE_PC_BATCH : ProdUpdateModel::UPDATE_BATCH;
    while($w = $dbc->fetch_row($r)) {
        $upcs = array($w['upc']);
        if (substr($w['upc'],0,2)=='LC') {
            $upcs = array();
            $lc = substr($w['upc'],2);
            $r2 = $dbc->exec_statement($likeP,array($lc));
            while($w2 = $dbc->fetch_row($r2))
                $upcs[] = $w2['upc'];
        }
        foreach($upcs as $u) {
            $update->reset();
            $update->upc($u);
            $update->logUpdate($updateType);
            updateProductAllLanes($u);
        }
    }
}

?>
