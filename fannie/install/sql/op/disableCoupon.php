<?php
/*
Table: disableCoupon

Columns:
    upc varchar
    reason text

Depends on:
    none

Use:
Maintain a list of manufacturer coupons
the store does not accept. Most common
usage is coupons where a store does carry
products from that manufacturer but does
not carry any products the meet coupon
requirements. In theory family codes
address this situation better, but
obtaining and maintaing those codes isn't
feasible.
*/
$CREATE['op.disableCoupon'] = "
    CREATE TABLE disableCoupon (
        upc varchar(13),
        threshold smallint default 0,
        reason text,
        PRIMARY KEY (upc)
    )
";
?>
