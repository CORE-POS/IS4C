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
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/* why is this file such a mess?

   SQL for UPDATE against multiple tables is different 
   for MSSQL and MySQL. There's not a particularly clean
   way around it that I can think of, hence alternates
   for all queries.
*/

function forceBatch($batchID){
	global $dbc,$FANNIE_SERVER_DBMS;

	$batchInfoQ = "SELECT batchType,discountType FROM batches WHERE batchID = $batchID";
	$batchInfoR = $dbc->query($batchInfoQ);
	$batchInfoW = $dbc->fetch_array($batchInfoR);

	$forceQ = "";
	$forceLCQ = "";
	if ($batchInfoW['discountType'] != 0){

		$forceQ="UPDATE products AS p
		    INNER JOIN batchList AS l
		    ON p.upc=l.upc
		    INNER JOIN batches AS b
		    ON l.batchID=b.batchID
		    SET p.start_date = b.startDate, 
		    p.end_date=b.endDate,
		    p.special_price=l.salePrice,
		    p.specialgroupprice=l.salePrice,
		    p.specialpricemethod=l.pricemethod,
		    p.specialquantity=l.quantity,
		    p.discounttype=b.discounttype
		    WHERE l.upc not like 'LC%'
		    and l.batchID = $batchID";
            
		$forceLCQ = "UPDATE products AS p
			INNER JOIN likeCodeView AS v 
			ON v.upc=p.upc
			INNER JOIN batchList as l 
			ON l.upc='LC'+convert(v.likecode,char)
			INNER JOIN batches AS b 
			ON b.batchID=l.batchID
			set p.special_price = l.salePrice,
			p.end_date = b.endDate,p.start_date=b.startDate,
			p.specialgroupprice=l.salePrice,
			p.specialpricemethod=l.pricemethod,
			p.specialquantity=l.quantity,
			p.discounttype = b.discounttype
			where l.batchID=$batchID";

		if ($FANNIE_SERVER_DBMS == 'MSSQL'){
			$forceQ="UPDATE products
			    SET start_date = b.startDate, 
			    end_date=b.endDate,
			    special_price=l.salePrice,
			    specialgroupprice=l.salePrice,
			    specialpricemethod=l.pricemethod,
			    specialquantity=l.quantity,
			    discounttype=b.discounttype
			    FROM products as p, 
			    batches as b, 
			    batchList as l 
			    WHERE l.upc = p.upc
			    and l.upc not like 'LC%'
			    and b.batchID = l.batchID
			    and b.batchID = $batchID";

			$forceLCQ = "update products set special_price = l.salePrice,
				end_date = b.endDate,start_date=b.startDate,
				discounttype = b.discounttype
				from products as p left join
				likeCodeView as v on v.upc=p.upc left join
				batchList as l on l.upc='LC'+convert(varchar,v.likecode)
				left join batches as b on b.batchID = l.batchID
				where b.batchID=$batchID";
		}
	}
	else{
		$forceQ = "UPDATE products AS p
		      INNER JOIN batchList AS l
		      ON l.upc=p.upc
		      SET p.normal_price = l.salePrice,
		      p.modified = curdate()
		      WHERE l.upc not like 'LC%'
		      AND l.batchID = $batchID";

		$forceLCQ = "UPDATE products AS p
			INNER JOIN upcLike AS v
			ON v.upc=p.upc INNER JOIN
			batchList as b on b.upc='LC'+convert(v.likecode,char)
			set p.normal_price = b.salePrice,
   			p.modified=curdate()
			where b.batchID=$batchID";

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
			      AND b.batchID = $batchID";

			$forceLCQ = "update products set normal_price = b.salePrice,
				modified=getdate()
				from products as p left join
				upcLike as v on v.upc=p.upc left join
				batchList as b on b.upc='LC'+convert(varchar,v.likecode)
				where b.batchID=$batchID";
		}
	}

	$forceR = $dbc->query($forceQ);
	$forceLCR = $dbc->query($forceLCQ);
}
?>
