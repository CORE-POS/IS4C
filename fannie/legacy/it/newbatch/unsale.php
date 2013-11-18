<?php

function unsale($batchID){
	global $sql,$FANNIE_SERVER_DBMS;

	if ($FANNIE_SERVER_DBMS == "MSSQL"){
		$unsale1Q = "update products set special_price=0,
		discounttype=0,start_date='',end_date='',
		specialpricemethod=0,specialquantity=0,
		specialgroupprice=0
		from
		products as p left join
		batchlist as b on p.upc=b.upc
		where b.batchID=".$batchID;

		$unsale2Q = "update products set special_price=0,
				discounttype=0,start_date='',end_date='',
				specialpricemethod=0,specialquantity=0,
				specialgroupprice=0
				from products as p left join
				likeCodeView as v on v.upc=p.upc left join
				batchlist as l on l.upc='LC'+convert(varchar,v.likecode)
				left join batches as b on b.batchID = l.batchID
				where b.batchID=$batchID";
		$sql->query($unsale1Q);
		$sql->query($unsale2Q);
	}
	else {
		$unsale1Q = "update products as p
		left join batchList as b ON p.upc=b.upc
		set special_price=0,
		p.discounttype=0,start_date='',end_date='',
		specialpricemethod=0,specialquantity=0,
		specialgroupprice=0
		where b.batchID=".$batchID;

		$unsale2Q = "update products as p left join
				likeCodeView as v on v.upc=p.upc left join
				batchList as l on l.upc=concat('LC',convert(v.likeCode,char))
				left join batches as b on b.batchID = l.batchID
				set special_price=0,
				p.discounttype=0,start_date='',end_date='',
				specialpricemethod=0,specialquantity=0,
				specialgroupprice=0
				where b.batchID=$batchID";
		$sql->query($unsale1Q);
		$sql->query($unsale2Q);
	}

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
            $model = new ProductsModel();
            $model->upc($u);
            $model->pushToLanes();
		}
	}
}

?>
