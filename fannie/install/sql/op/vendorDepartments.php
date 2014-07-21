<?php
/*
Table: vendorDepartments

Columns:
    vendorID int
    deptID int
    name varchar
    margin float
    testing float
    posDeptID int

Depends on:
    vendors (table)

Use:
This table contains a vendors product categorization.
Two float fields, margin and testing, are provided
so you can try out new margins (i.e., calculate SRPs)
in testing without changing the current margin 
setting.

Traditional deptID corresponds to a UNFI's category
number. This may differ for other vendors.
*/
$CREATE['op.vendorDepartments'] = "
    create table vendorDepartments (
        vendorID int,
        deptID int,
        name varchar(125),
        margin float,
        testing float,
        posDeptID int,
        PRIMARY KEY (vendorID, deptID),
        INDEX(deptID),
        INDEX(vendorID)
    )
";
?>
