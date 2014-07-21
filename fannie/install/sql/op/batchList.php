<?php
/*
Table: batchList

Columns:
    listID int (auto increment)
    upc varchar(13)
    batchID int
    salePrice dbms currency
    active bit
    pricemethod int
    quantity int

Depends on:
    batches (table)

Use:
This table has a list of items in a batch.
In most cases, salePrice maps to
products.special_price AND products.specialgroupprice,
pricemethod maps to products.specialpricemethod,
and quantity maps to products.specialquantity.

WFC has some weird exceptions. The main on is that 
upc can be a likecode, prefixed with 'LC'
*/
$CREATE['op.batchList'] = "
    CREATE TABLE `batchList` (
      `listID` int(6) NOT NULL auto_increment,
      `upc` varchar(13) default NULL,
      `batchID` int(5) default NULL,
      `salePrice` decimal(10,2) default NULL,
      `active` bit(1) default NULL,
      `pricemethod` int(4) default 0,
      `quantity` int(4) default 0, 
      PRIMARY KEY  (`listID`)
    )
";

if ($dbms == "MSSQL"){
    $CREATE['op.batchList'] = "
        CREATE TABLE [batchList] (
            [listID] [int] IDENTITY (1, 1) NOT NULL ,
            [upc] [char] (13) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [batchID] [int] NULL ,
            [salePrice] [money] NULL ,
            [active] [bit] NULL ,
            [pricemethod] [int] NULL ,
            [quantity] [int] NULL 
        ) ON [PRIMARY]
    ";
}

?>
