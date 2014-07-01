<?php
/*
Table: prodFlags

Columns:
    bit_number int
    description varchar

Depends on:
    products (table)

Use:
Properties for the product table's
numflag column
*/
$CREATE['op.prodFlags'] = "
    CREATE TABLE prodFlags (
        bit_number tinyint,
        description varchar(50),
        PRIMARY KEY (bit_number)
    )
";
