<?php
/*
Table: unfi

Columns:
    brand varchar(30)
    sku int
    size varchar(25)
    upc int or varchar(13), dbms dependent
    units int
    cost dbms currency
    description varchar(35)
    depart varchar(15)  

Depends on:
    none

Use:
This table stores items from the vendor UNFI.
Of note: depart is the vendors categorization setting,
not IT CORE/Fannie's. Size relates to an indivdual product.
Units relates to a case. So a case of beer has 24
units, each with a size of 12 oz.

Deprecated. Use vendors (table) and vendorItems(table)
instead. Vendor-based functionality should
allow for more than one vendor.
*/
$CREATE['op.unfi'] = "
    CREATE TABLE `unfi` (
      `brand` varchar(30) default NULL,
      `sku` int(6) default NULL,
      `size` varchar(25) default NULL,
      `upc` bigint(13) unsigned zerofill NOT NULL default '0000000000000',
      `units` int(3) default NULL,
      `cost` decimal(9,2) default NULL,
      `description` varchar(35) default NULL,
      `depart` varchar(15) default NULL,
      PRIMARY KEY  (`upc`),
      KEY `newindex` (`upc`)
    )
";

if ($dbms == "MSSQL"){
    $CREATE['op.unfi'] = "
        CREATE TABLE [unfi] (
            [brand] [varchar] (30) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [sku] [int] NULL ,
            [size] [varchar] (25) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [upc] [varchar] (13) COLLATE SQL_Latin1_General_CP1_CI_AS NOT NULL ,
            [units] [smallint] NULL ,
            [cost] [money] NULL ,
            [description] [varchar] (35) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [depart] [varchar] (15) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
        PRIMARY KEY ([upc]) )
    ";
}

?>
