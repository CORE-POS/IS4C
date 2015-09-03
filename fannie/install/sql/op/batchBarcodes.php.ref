<?php
/*
Table: batchBarcodes

Columns:
    upc bigint or varchar, dbms dependent
    description varchar(30)
    normal_price dbms currency
    brand varchar(50)
    sku varchar(14)
    size varchar(50)
    units varchar(15)
    vendor varchar(50)
    batchID int

Depends on:
    batches (table)

Use:
This table has information for generating shelf tags
for a batch. This makes sense primarily when working
with batches that update items' regular price rather
than sale batches.

Note: size relates to an indivdual product.
Units relates to a case. So a case of beer has 24
units, each with a size of 12 oz.
*/
$CREATE['op.batchBarcodes'] = "
    CREATE TABLE batchBarcodes (
        `upc` varchar(13),
        `description` varchar(30) default NULL,
        `normal_price` decimal(10,2) default NULL,
        `brand` varchar(50) default NULL,
        `sku` varchar(14) default NULL,
        `size` varchar(50) default NULL,
        `units` varchar(15) default NULL,
        `vendor` varchar(50) default NULL,
        `batchID` int,
        PRIMARY KEY (`batchID`,`upc`)
    )
";

if ($dbms == "MSSQL"){
    $CREATE['op.batchBarcodes'] = "
        CREATE TABLE [batchBarcodes] (
            [upc] [varchar] (13) COLLATE SQL_Latin1_General_CP1_CI_AS NOT NULL ,
            [description] [varchar] (30) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [normal_price] [money] NULL ,
            [brand] [varchar] (255) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [sku] [varchar] (50) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [size] [varchar] (255) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [units] [varchar] (15) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [vendor] [varchar] (50) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [batchID] [int] NOT NULL
        )
    ";
}

?>
