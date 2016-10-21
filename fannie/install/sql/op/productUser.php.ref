<?php
/*
Table: productUser

Columns:
    upc int or varchar, dbms dependent

Depends on:
    products (table)

Use:
Longer product descriptions for use in
online webstore
*/
$CREATE['op.productUser'] = "
    CREATE TABLE productUser (
        upc varchar(13), 
        description varchar(255),
        brand varchar(255),
        sizing varchar(255),
        photo varchar(255),
        long_text text,
        enableOnline tinyint,
        soldOut TINYINT DEFAULT 0,
        PRIMARY KEY(upc)
    )
"; 

?>
