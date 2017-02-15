<?php
/*
View: houseCouponThisMonth

Columns:
    card_no  int
    upc  varchar
    quantity float

Depends on:
    dlog (view)
    dlog_90_view (view)

Use:
List of custom coupons redeemed, per member
*/
$CREATE['trans.houseCouponThisMonth'] = "
    CREATE VIEW houseCouponThisMonth AS
        SELECT card_no,upc,SUM(quantity) AS quantity
        FROM dlog_90_view
        WHERE 
            trans_type='T'
            AND trans_subtype='IC'
            AND upc LIKE '004%'
            AND " . $con->monthdiff($con->now(),'tdate') . "=0
	GROUP BY card_no, upc
";

