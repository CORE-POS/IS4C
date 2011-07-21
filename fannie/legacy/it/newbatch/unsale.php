<?php

function unsale($batchID){
	global $sql;

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

	//$batchUpQ = "EXEC productsUpdateAll";
	//$batchUpR = $sql->query($batchUpQ);
	exec("php fork.php sync products");
}

?>
