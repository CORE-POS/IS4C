<?php
function forceBatch($batchID){
	global $sql,$FANNIE_ROOT;

	$batchInfoQ = "SELECT * FROM batches WHERE batchID = $batchID";
	$batchInfoR = $sql->query($batchInfoQ);

	$batchInfoW = $sql->fetch_array($batchInfoR);

	$forceQ = "";
	$forceLCQ = "";
	$upQ = "";
	$upLCQ = "";
	if ($batchInfoW['batchType'] <> 4){
	   $forceQ="UPDATE products 
		    SET start_date = b.startdate, 
		    end_date=b.enddate,
		    special_price=l.salePrice,
		    specialgroupprice=l.salePrice,
		    specialpricemethod=l.pricemethod,
		    specialquantity=l.quantity,
		    discounttype=b.discounttype
		    FROM products as p, 
		    batches as b, 
		    batchlist as l 
		    WHERE l.upc = p.upc
		    and l.upc not like 'LC%'
		    and b.batchID = l.batchID
		    and b.batchType <> 4
		    and b.batchID = $batchID";

            
	   $forceLCQ = "update products set special_price = l.salePrice,
   			end_date = b.enddate,start_date=b.startdate,
		    specialgroupprice=l.salePrice,
		    specialpricemethod=l.pricemethod,
		    specialquantity=l.quantity,
   			discounttype = b.discounttype
   			from products as p left join
			likeCodeView as v on v.upc=p.upc left join
			batchlist as l on l.upc='LC'+convert(varchar,v.likecode)
			left join batches as b on b.batchID = l.batchID
			where b.batchID=$batchID";
	}else{
	   $forceQ = "UPDATE products
		      SET normal_price = l.salePrice,
		      modified = getdate()
		      FROM products as p,
		      batches as b,
		      batchList as l
		      WHERE l.upc = p.upc
		      AND l.upc not like 'LC%'
		      AND b.batchID = l.batchID
		      AND b.batchType = 4
		      AND b.batchID = $batchID";

	   $upQ = "INSERT INTO prodUpdate
		SELECT p.upc,description,normal_price,
		department,tax,foodstamp,scale,0,
		modified,0,qttyEnforced,discount,inUse
		FROM products as p,
		batches as b,
		batchList as l
		WHERE l.upc = p.upc
		AND l.upc not like 'LC%'
		AND b.batchID = l.batchID
		AND b.batchType = 4
		AND b.batchID = $batchID";

	   $forceLCQ = "update products set normal_price = b.salePrice,
   			modified=getdate()
   			from products as p left join
			upclike as v on v.upc=p.upc left join
			batchlist as b on b.upc='LC'+convert(varchar,v.likecode)
			where b.batchID=$batchID";

	   $upLCQ = "INSERT INTO prodUpdate
		SELECT p.upc,description,normal_price,
		department,tax,foodstamp,scale,0,
		modified,0,qttyEnforced,discount,inUse
		from products as p left join
		upclike as v on v.upc=p.upc left join
		batchlist as b on b.upc='LC'+convert(varchar,v.likecode)
		where b.batchID=$batchID";
	}

	$forceR = $sql->query($forceQ);
	if (!empty($upQ)) $sql->query($upQ);
	$forceLCR = $sql->query($forceLCQ);
	if (!empty($upLCQ)) $sql->query($upLCQ);

	//$batchUpQ = "EXEC productsUpdateAll";
	//$batchUpR = $sql->query($batchUpQ);
	if (!function_exists("updateProductAllLanes")) include($FANNIE_ROOT.'legacy/queries/laneUpdates.php');

	$q = "SELECT upc FROM batchList WHERE batchID=".$batchID;
	$r = $sql->query($q);
	while($w = $sql->fetch_row($r)){
		$upcs = array($w['upc']);
		if (substr($w['upc'],0,2)=='LC'){
			$upcs = array();
			$lc = substr($w['upc'],2);
			$q2 = "SELECT upc FROM upcLike WHERE likeCode=".$lc;
			$r2 = $sql->query($q2);
			while($w2 = $sql->fetch_row($r2))
				$upcs[] = $w2['upc'];
		}
		foreach($upcs as $u){
			updateProductAllLanes($u);
		}
	}
}
?>
