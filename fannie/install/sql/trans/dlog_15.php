<?php
/*
Table: dlog_15

Columns:
    tdate datetime
    register_no int
    emp_no int
    trans_no int
    upc varchar
    trans_type varchar
    trans_subtype varchar
    trans_status varchar
    department int
    quantity double
    unitPrice dbms currency
    total dbms currency
    tax int
    foodstamp int
    ItemQtty double
    card_no int
    trans_id int
    pos_row_id int
    store_row_id int
    trans_num

Depends on:
    dlog_90_view (view)

Use:
This is just a look-up table. It contains the
past 15 days worth of dlog entries. For reports
on data within that time frame, it's faster to
use this small table.

Maintenance:
Truncated and populated by cron/nightly.dtrans.php
*/


/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    * 23Oct2012 Eric Lee Added Maintenance section to comments.

*/

$CREATE['trans.dlog_15'] = "
    CREATE TABLE dlog_15 (`tdate` datetime default NULL,
          `register_no` smallint(6) default NULL,
          `emp_no` smallint(6) default NULL,
          `trans_no` int(11) default NULL,
          `upc` varchar(13) default NULL,
          `description` varchar(30) default NULL,
          `trans_type` varchar(1) default NULL,
          `trans_subtype` varchar(2) default NULL,
          `trans_status` varchar(1) default NULL,
          `department` smallint(6) default NULL,
          `quantity` double default NULL,
          `scale` tinyint(4) default NULL,
          `cost` double default NULL,
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
          `mixMatch` VARCHAR(13) default NULL,
          `matched` tinyint(4) default NULL,
        `memType` tinyint(2) default NULL,
        `staff` tinyint(4) default NULL,
        `numflag` int(11) default 0 NULL,
        `charflag` varchar(2) default '' NULL,
          `card_no` varchar(255) default NULL,
          `trans_id` int(11) default NULL,
          `pos_row_id` bigint unsigned,
          `store_row_id` bigint unsigned,
          `trans_num` varchar(25) default NULL
     )";

if ($dbms == "MSSQL"){
    $CREATE['trans.dlog_15'] = "
        CREATE TABLE dlog_15 ([tdate] [datetime] NOT NULL ,
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
                        [discount] [money] NULL ,
                        [memDiscount] [money] NULL ,
                        [discountable] [tinyint] NOT NULL ,
                        [discounttype] [tinyint] NOT NULL ,
                        [voided] [tinyint] NOT NULL ,
                        [percentDiscount] [tinyint] NOT NULL ,
                        [ItemQtty] [float] NULL ,
                        [volDiscType] [tinyint] NULL ,
                        [volume] [tinyint] NULL ,
                        [VolSpecial] [money] NULL ,
            [mixMatch] [nvarchar] (13) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
                        [matched] [tinyint] NULL ,
            [memType] [smallint] NULL ,
            [isStaff] [tinyint] NULL ,
            [numflag] [smallint] NULL ,
            [charflag] [nvarchar] (2) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
                        [card_no] [nvarchar] (6) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
                        [trans_id] [int] NOT NULL ,
                        [pos_row_id [bigint] ,
                        [store_row_id] [bigint] ,
                        [trans_num] [nvarchar] (25) COLLATE SQL_Latin1_General_CP1_CI_AS NULL 
        )
    ";
}
?>
