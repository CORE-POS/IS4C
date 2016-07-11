<?php
/*
Table: houseVirtualCoupons

Columns:
    card_no int
    coupID int
    description varchar
    start_date datetime 
    end_date datetime   

Depends on:
    houseCoupons
    houseCouponItems

Use:
Assign house coupons to members so
they can be applied without scanning
a barcode
*/
$CREATE['op.houseVirtualCoupons'] = "
    CREATE TABLE houseVirtualCoupons (
        card_no int,
        coupID int,
        description varchar(100),
        start_date datetime,
        end_date datetime,
        PRIMARY KEY (coupID, card_no)
    )
";
?>
