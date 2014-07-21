<?php
/*
Table: autoCoupons

Columns:
    coupID int
    description varchar

Depends on:
    houseCoupons
    houseCouponItems

Use:
Apply coupons to transactions automatically

*/
$CREATE['op.autoCoupons'] = "
    CREATE TABLE autoCoupons (
        coupID int,
        description varchar(30),
        PRIMARY KEY (coupID)
    )
";

