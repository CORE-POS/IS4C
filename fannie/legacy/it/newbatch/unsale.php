<?php

function unsale($batchID){
    global $sql,$FANNIE_SERVER_DBMS;

    if ($FANNIE_SERVER_DBMS == "MSSQL"){
        $unsale1Q = $sql->prepare("update products set special_price=0,
        discounttype=0,start_date='',end_date='',
        specialpricemethod=0,specialquantity=0,
        specialgroupprice=0
        from
        products as p left join
        batchlist as b on p.upc=b.upc
        where b.batchID=?");

        $unsale2Q = $sql->prepare("update products set special_price=0,
                discounttype=0,start_date='',end_date='',
                specialpricemethod=0,specialquantity=0,
                specialgroupprice=0
                from products as p left join
                upcLike as v on v.upc=p.upc left join
                batchlist as l on l.upc='LC'+convert(varchar,v.likecode)
                left join batches as b on b.batchID = l.batchID
                where b.batchID=?");
        $sql->execute($unsale1Q, array($batchID));
        $sql->execute($unsale2Q, array($batchID));
    }
    else {
        $unsale1Q = $sql->prepare("update products as p
        left join batchList as b ON p.upc=b.upc
        set special_price=0,
        p.discounttype=0,start_date='',end_date='',
        specialpricemethod=0,specialquantity=0,
        specialgroupprice=0
        where b.batchID=?");

        $unsale2Q = $sql->prepare("update products as p left join
                upcLike as v on v.upc=p.upc left join
                batchList as l on l.upc=concat('LC',convert(v.likeCode,char))
                left join batches as b on b.batchID = l.batchID
                set special_price=0,
                p.discounttype=0,start_date='',end_date='',
                specialpricemethod=0,specialquantity=0,
                specialgroupprice=0
                where b.batchID=?");
        $sql->execute($unsale1Q, array($batchID));
        $sql->execute($unsale2Q, array($batchID));
    }

    $q = $sql->prepare("SELECT upc FROM batchList WHERE batchID=?");
    $r = $sql->execute($q, array($batchID));
    $q2 = $sql->prepare("SELECT upc FROM upcLike WHERE likeCode=?");
    while($w = $sql->fetch_row($r)){
        $upcs = array($w['upc']);
        if (substr($w['upc'],0,2)=='LC'){
            $upcs = array();
            $lc = substr($w['upc'],2);
            $r2 = $sql->execute($q2, array($lc));
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

