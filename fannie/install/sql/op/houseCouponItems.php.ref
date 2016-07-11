<?php
/*
Table: houseCouponItems

Columns:
    coupID int
    upc varchar
    type varchar

Depends on:
    houseCoupons

Use:
WFC runs custom barcoded coupons with
upc prefix 499999. See houseCoupons for
more detail. Type here should be 'QUALIFIER',
'DISCOUNT', or 'BOTH'.
*/
$CREATE['op.houseCouponItems'] = "
    CREATE TABLE houseCouponItems (
        coupID int,
        upc varchar(13),
        type varchar(15),
        PRIMARY KEY (coupID,upc),
        INDEX (coupID),
        INDEX (upc)
    )
";
?>
