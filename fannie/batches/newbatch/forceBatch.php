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

/* why is this file such a mess?

   SQL for UPDATE against multiple tables is different 
   for MSSQL and MySQL. There's not a particularly clean
   way around it that I can think of, hence alternates
   for all queries.
*/

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

function forceBatch($batchID)
{
    global $FANNIE_OP_DB,$FANNIE_SERVER_DBMS, $FANNIE_LANES;
    $dbc = FannieDB::get($FANNIE_OP_DB);

    $batchInfoQ = $dbc->prepare("SELECT batchType,discountType FROM batches WHERE batchID = ?");
    $batchInfoR = $dbc->execute($batchInfoQ,array($batchID));
    $batchInfoW = $dbc->fetchRow($batchInfoR);

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

    $forceP = $dbc->prepare($forceQ);
    $forceR = $dbc->execute($forceP,array($batchID));
    $forceLCP = $dbc->prepare($forceLCQ);
    $forceR = $dbc->execute($forceLCP,array($batchID));

    $columnsP = $dbc->prepare('
        SELECT p.upc,
            p.normal_price,
            p.special_price,
            p.modified,
            p.specialpricemethod,
            p.specialquantity,
            p.specialgroupprice,
            p.discounttype,
            p.mixmatchcode,
            p.start_date,
            p.end_date
        FROM products AS p
            INNER JOIN batchList AS b ON p.upc=b.upc
        WHERE b.batchID=?');
    $lcColumnsP = $dbc->prepare('
        SELECT p.upc,
            p.normal_price,
            p.special_price,
            p.modified,
            p.specialpricemethod,
            p.specialquantity,
            p.specialgroupprice,
            p.discounttype,
            p.mixmatchcode,
            p.start_date,
            p.end_date
        FROM products AS p
            INNER JOIN upcLike AS u ON p.upc=u.upc
            INNER JOIN batchList AS b 
                ON b.upc = ' . $dbc->concat("'LC'", $dbc->convert('u.likeCode', 'CHAR'), '') . '
        WHERE b.batchID=?');

    /**
      Get changed columns for each product record
    */
    $upcs = array();
    $columnsR = $dbc->execute($columnsP, array($batchID));
    while ($w = $dbc->fetch_row($columnsR)) {
        $upcs[$w['upc']] = $w;
    }
    $columnsR = $dbc->execute($lcColumnsP, array($batchID));
    while ($w = $dbc->fetch_row($columnsR)) {
        $upcs[$w['upc']] = $w;
    }

    $updateQ = '
        UPDATE products AS p SET
            p.normal_price = ?,
            p.special_price = ?,
            p.modified = ?,
            p.specialpricemethod = ?,
            p.specialquantity = ?,
            p.specialgroupprice = ?,
            p.discounttype = ?,
            p.mixmatchcode = ?,
            p.start_date = ?,
            p.end_date = ?
        WHERE p.upc = ?';

    /**
      Update all records on each lane before proceeding
      to the next lane. Hopefully faster / more efficient
    */
    for ($i = 0; $i < count($FANNIE_LANES); $i++) {
        $lane_sql = new SQLManager($FANNIE_LANES[$i]['host'],$FANNIE_LANES[$i]['type'],
            $FANNIE_LANES[$i]['op'],$FANNIE_LANES[$i]['user'],
            $FANNIE_LANES[$i]['pw']);
        
        if (!isset($lane_sql->connections[$FANNIE_LANES[$i]['op']]) || $lane_sql->connections[$FANNIE_LANES[$i]['op']] === false) {
            // connect failed
            continue;
        }

        $updateP = $lane_sql->prepare($updateQ);
        foreach ($upcs as $upc => $data) {
            $lane_sql->execute($updateP, array(
                $data['normal_price'],
                $data['special_price'],
                $data['modified'],
                $data['specialpricemethod'],
                $data['specialquantity'],
                $data['specialgroupprice'],
                $data['discounttype'],
                $data['mixmatchcode'],
                $data['start_date'],
                $data['end_date'],
                $upc,
            ));
        }
    }

    $update = new ProdUpdateModel($dbc);
    $updateType = ($batchInfoW['discountType'] == 0) ? ProdUpdateModel::UPDATE_PC_BATCH : ProdUpdateModel::UPDATE_BATCH;
    $update->logManyUpdates(array_keys($upcs), $updateType);
}

