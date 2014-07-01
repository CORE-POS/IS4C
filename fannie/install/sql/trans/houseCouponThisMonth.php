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
    SELECT
        s.card_no,
        s.upc,
        SUM(s.quantity) AS quantity
    FROM (
        SELECT card_no,upc,quantity
        FROM dlog
        WHERE 
            trans_type='T'
            AND trans_subtype='IC'
            AND upc LIKE '004%'

        UNION ALL

        SELECT card_no,upc,quantity
        FROM dlog_90_view
        WHERE 
            trans_type='T'
            AND trans_subtype='IC'
            AND upc LIKE '004%'
            AND " . $con->monthdiff($con->now(),'tdate') . "=0
    ) AS s
    GROUP BY s.card_no, s.upc
";

