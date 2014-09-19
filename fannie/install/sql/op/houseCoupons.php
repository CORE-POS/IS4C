<?php
/*
Table: houseCoupons

Columns:
    coupID int
    description varchar
    startDate datetime
    endDate datetime
    limit smallint
    memberOnly smallint
    discountType varchar
    discountValue double
    minType varchar
    minValue double
    department int

Depends on:
    houseCouponItems

Use:
WFC runs custom barcoded coupons with
upc prefix 499999, then the coupon ID
(zero padded to 5 digits). There's a
tool in legacy for putting these together
that may eventually make it into fannie.

startDate is the first day the coupon is valid

endDate is the last day the coupon is valid

limit is the number of times the coupon can be
used in one transaction

memberOnly means the coupon can only be used
by a member (custdata.Type='PC')

minType and minValue specify how one
qualifies for the coupon - certain item(s),
purchase amount, etc

* 'Q' must purchase at least minValue
  qualifying items (by quantity)
* 'Q+' must purchase more than minValue
  qualifying items (by quantity)
* 'D' must purchase at least minValue
  items from qualifying departments
  (by $ value)
* 'D+' must purchase more than minValue
  items from qualifying departments
  (by $ value)
* 'M' is mixed. Must purchase at least
  minValue qualifying items and at least
  one discount item
* '$' must puchase at least minValue
  goods (by $ value)
* '$+' must puchase more than minValue
  goods (by $ value)
* '' blank means no minimum purchase

discountType and discountValue specify
how the discount is calculated

(item related discounts)
* 'Q' discount equals discountValue times
  unitPrice for the cheapest qualifying 
  item (essentially percent discount)
* 'P' discount equals unitPrice minus
  discountValue for the cheapest qualifying
  item (essentially an sale price)
* 'FI' discount equals discountValues times
  quantity for the cheapest qualifying item
  (works with by-weight items)

(department related discounts)
* 'FD' discount equals discountValue times
  quantity for the cheapest qualifying item
* 'AD' discount equals discountValue times
  sum(quantity) for ALL qualifying items

(other discounts)
* 'F' discount equals discountValue
* '%' discountValue is a percent discount for
  all discountable items

Qualifying items are stored in houseCouponItems. Not
all coupons require entries here. Records can be
items (by UPC) or departments (by department number).
Some minimum and discount types only work with one
or the other.

houseCouponItems.coupID is the coupon ID

houseCouponItems.upc is an item UPC or a department number.

houseCouponItems.type is only relevant to the mixed (M)
minimum type. Values are:
* 'QUALIFIER' counts as a qualifying item for mixed
* 'DISCOUNT' counts as a discount item for mixed
* 'BOTH' can be a qualfying item or a discount item
If not using the mixed minimum, always choose 'BOTH'

The nuts and bolts of this are in
the UPC.php parser module (IT CORE).
*/
$CREATE['op.houseCoupons'] = "
    CREATE TABLE houseCoupons (
        coupID int,
        description VARCHAR(30),
        startDate DATETIME,
        endDate datetime,
        `limit` smallint,
        memberOnly smallint,
        discountType varchar(2),
        discountValue double,
        minType varchar(2),
        minValue double,
        department int,
        PRIMARY KEY (coupID)
    )
";
if ($dbms == "MSSQL")
    $CREATE['op.houseCoupons'] = str_replace("`","",$CREATE['op.houseCoupons']);
?>
