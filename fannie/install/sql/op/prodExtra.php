<?php
/*
Table: prodExtra

Columns:
    upc int or varchar, dbms dependent
    distributor varchar
    manufacturer varchar
    cost dbms currency
    margin dbms currency
    variable_pricing tinyint
    location varchar
    case_quantity varchar
    case_cost dbms currency
    case_info varchar

Depends on:
    products (table)

Use:
Don't add to it.
As of 20Oct2012 it is still used by item/productList.php.

Deprecated. This mess dates back to trying to stay
lock-step with the Wedge's products table (which didn't
work anyway). The thinking was "Need a new field? Toss it
in prodExtra". Multiple, purpose-specific tables that
can be joined against products on upc would be a much
better solution.
*/
$CREATE['op.prodExtra'] = "
    CREATE TABLE `prodExtra` (
        `upc` varchar(13),
        `distributor` varchar(100) default NULL,
        `manufacturer` varchar(100) default NULL,
        `cost` numeric(10,2) default NULL,
        `margin` numeric(10,2) default NULL,
        `variable_pricing` tinyint default NULL,
        `location` varchar(30) default NULL,
        `case_quantity` varchar(15) default NULL,
        `case_cost` numeric(10,2) default NULL,
        `case_info` varchar(100) default NULL,
        PRIMARY KEY (`upc`)
    )
"; 

if ($dbms == "MSSQL"){
    $CREATE['op.prodExtra'] = "
        CREATE TABLE prodExtra (
            upc varchar(13),
            distributor varchar(100),
            manufacturer varchar(100),
            cost money,
            margin money,
            variable_pricing tinyint,
            location varchar(30),
            case_quantity varchar(15),
            case_cost money,
            case_info varchar(100),
            PRIMARY KEY (upc)
        )
    ";
}

?>
