<?php
/*
Table: vendorItems

Columns:
    upc varchar
    sku varchar
    brand varchar
    description varchar
    size varchar
    units int
    cost dbms currency
    saleCost dbms currency
    vendorDept int
    vendorID int

Depends on:
    vendors (table)
    vendorDepartments (table)

Use:
This table has items from vendors. Cost
and vendor department margin are used to 
calculate SRPs, but the other fields are useful
for making shelf tags.

Size relates to an indivdual product.
Units relates to a case. So a case of beer has 24
units, each with a size of 12 oz.

Cost represents the unit cost. Cost times units 
should then equal the case cost. Sale Cost is
for storing temporary special prices.

upc corresponds to products.upc. Multiple vendorItems
records may map to one products record if an item
is available from more than one vendor or under 
several SKUs from the one vendor. sku should 
uniquely identify an item for the purpose of ordering
it from the vendor. If the vendor does not have SKUs
you have to assign some. The field is wide enough
to hold a UPC; putting your UPC or the vendor's UPC
in the SKU field may be a simple solution to assigning
SKUs.
*/
$CREATE['op.vendorItems'] = "
    CREATE TABLE vendorItems (
        upc varchar(13),
        sku varchar(13),
        brand varchar(50),
        description varchar(50),
        size varchar(25),
        units int,
        cost decimal(10,2),
        saleCost decimal(10,2),
        vendorDept int,
        vendorID int,
        PRIMARY KEY (vendorID,sku),
        INDEX(vendorID),
        INDEX(upc),
        INDEX(sku)
    )
";

if ($dbms == 'mssql'){
    $CREATE['op.vendorItems'] = "
        CREATE TABLE vendorItems (
            upc varchar(13),
            sku varchar(10),
            brand varchar(50),
            description varchar(50),
            size varchar(25),
            units int,
            cost money,
            saleCost money,
            vendorDept int,
            vendorID int
        )
    ";
}

?>
