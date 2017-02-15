<?php
/*
Table: batches

Columns:
    batchID int
    startDate datetime
    endDate datetime
    batchName varchar
    batchType int
    discountType int
    priority int
    owner varchar

Depends on:
    batchType

Use:
This table contains basic information
for a sales batch. On startDate, items
in batchList with a corresponding batchID
go on sale (as specified in that table) and
with the discount type set here. On endDate,
those same items revert to normal pricing.
*/
$CREATE['op.batches'] = "
    CREATE TABLE `batches` (
      `batchID` int(5) NOT NULL auto_increment,
      `startDate` datetime default NULL,
      `endDate` datetime default NULL,
      `batchName` varchar(80) default NULL,
      `batchType` int(3) default NULL,
      `discountType` int(2) default NULL,
      `priority` int(2) default NULL,
      `owner` varchar(50) default NULL,
      PRIMARY KEY  (`batchID`)
    )
";

if ($dbms == "MSSQL"){
    $CREATE['op.batches'] = "
        CREATE TABLE [batches] (
            [batchID] [int] IDENTITY (1, 1) NOT NULL ,
            [startDate] [datetime] NULL ,
            [endDate] [datetime] NULL ,
            [batchName] [varchar] (50) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [batchType] [int] NULL ,
            [discountType] [int] NULL,
            [priority] [smallint] NULL 
            [owner] [varchar] (50) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
        ) ON [PRIMARY]
    ";
}

?>
