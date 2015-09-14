<?php
/*
Table: shelftags

Columns:
    id int
    upc int or varchar, dbms dependent
    description varchar
    normal_price dbms currency
    brand varchar
    sku varchar
    size varchar
    units int
    vendor varchar
    pricePerUnit varchar
    count int 

Depends on:
    none

Use:
Data for generating shelf tag PDFs. id is used 
to segment sets into different PDF documents.
An id maps to a buyer at WFC, but doesn't have to.

Size relates to an indivdual product.
Units relates to a case. So a case of beer has 24
units, each with a size of 12 oz.

Count is used to print multiples of the same tag
*/
$CREATE['op.shelftags'] = "
    CREATE TABLE `shelftags` (
        `id` int(6) default NULL ,
        `upc` bigint(13) unsigned zerofill NOT NULL default '0000000000000',
        `description` varchar(30) default NULL,
        `normal_price` decimal(9,2) default NULL,
        `brand` varchar(100) default NULL,
        `sku` varchar(12) default NULL,
        `size` varchar(50) default NULL,
        `units` int(4) default NULL ,
        `vendor` varchar(50) default NULL,
        `pricePerUnit` varchar(50) default NULL,
        `count` TINYINT DEFAULT 1,
        PRIMARY KEY (`id`,`upc`),
        INDEX (`id`)
    )
";

if ($dbms == "MSSQL"){
    $CREATE['op.shelftags'] = "
        CREATE TABLE [shelftags] (
            [id] [int] NULL ,
            [upc] [varchar] (13) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [description] [varchar] (30) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [normal_price] [money] NULL ,
            [brand] [varchar] (100) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [sku] [varchar] (12) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [size] [varchar] (50) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [units] [int] NULL ,
            [vendor] [varchar] (50) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [pricePerUnit] [varchar] (50) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [count] [TINYINT]
        )
    ";
}

?>
