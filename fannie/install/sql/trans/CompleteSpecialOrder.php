<?php
/*
Table: CompleteSpecialOrder

Columns:
    order_id int
    dtransactions columns

Depends on:
    PendingSpecialOrder

Use:
This table is for a work-in-progress special
order tracking system. Conceptually, it will
work like a partial suspended transactions,
where rows with a given order_id can be
pulled in at a register when someone picks up
their special order.

This particular table is for finished orders
*/
$CREATE['trans.CompleteSpecialOrder'] = "
    CREATE TABLE CompleteSpecialOrder (
      `order_id` int default NULL,
      `datetime` datetime default NULL,
      `register_no` smallint(6) default NULL,
      `emp_no` smallint(6) default NULL,
      `trans_no` int(11) default NULL,
      `upc` varchar(255) default NULL,
      `description` varchar(255) default NULL,
      `trans_type` varchar(255) default NULL,
      `trans_subtype` varchar(255) default NULL,
      `trans_status` varchar(255) default NULL,
      `department` smallint(6) default NULL,
      `quantity` double default NULL,
      `scale` tinyint(4) default NULL,
      `cost` double default 0.00 NULL,
      `unitPrice` double default NULL,
      `total` double default NULL,
      `regPrice` double default NULL,
      `tax` smallint(6) default NULL,
      `foodstamp` tinyint(4) default NULL,
      `discount` double default NULL,
      `memDiscount` double default NULL,
      `discountable` tinyint(4) default NULL,
      `discounttype` tinyint(4) default NULL,
      `voided` tinyint(4) default NULL,
      `percentDiscount` tinyint(4) default NULL,
      `ItemQtty` double default NULL,
      `volDiscType` tinyint(4) default NULL,
      `volume` tinyint(4) default NULL,
      `VolSpecial` double default NULL,
      `mixMatch` varchar(13) default NULL,
      `matched` smallint(6) default NULL,
      `memType` tinyint(2) default NULL,
      `staff` tinyint(4) default NULL,
      `numflag` smallint(6) default 0 NULL,
      `charflag` varchar(2) default '' NULL,
      `card_no` int(11) default NULL,
      `trans_id` int(11) default NULL
    )
";

if ($dbms == "MSSQL"){
    $CREATE['trans.CompleteSpecialOrder'] = "
        CREATE TABLE CompleteSpecialOrder (
            [order_id] [int] NOT NULL ,
            [datetime] [datetime] NOT NULL ,
            [register_no] [smallint] NOT NULL ,
            [emp_no] [smallint] NOT NULL ,
            [trans_no] [int] NOT NULL ,
            [upc] [nvarchar] (13) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [description] [nvarchar] (30) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [trans_type] [nvarchar] (1) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [trans_subtype] [nvarchar] (2) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [trans_status] [nvarchar] (1) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [department] [smallint] NULL ,
            [quantity] [float] NULL ,
            [scale] [tinyint] NULL ,
            [cost] [money] NULL ,
            [unitPrice] [money] NULL ,
            [total] [money] NOT NULL ,
            [regPrice] [money] NULL ,
            [tax] [smallint] NULL ,
            [foodstamp] [tinyint] NOT NULL ,
            [discount] [money] NOT NULL ,
            [memDiscount] [money] NULL ,
            [discountable] [tinyint] NULL ,
            [discounttype] [tinyint] NULL ,
            [voided] [tinyint] NULL ,
            [percentDiscount] [tinyint] NULL ,
            [ItemQtty] [float] NULL ,
            [volDiscType] [tinyint] NOT NULL ,
            [volume] [tinyint] NOT NULL ,
            [VolSpecial] [money] NOT NULL ,
            [mixMatch] [nvarchar] (13) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [matched] [smallint] NOT NULL ,
            [memType] [smallint] NULL ,
            [isStaff] [tinyint] NULL ,
            [numflag] [smallint] NULL ,
            [charflag] [nvarchar] (2) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [card_no] [nvarchar] (6) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [trans_id] [int] NOT NULL 
        )
    ";
}
?>
