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

Depends on:
	dlog_90_view (view)

Use:
This is just a look-up table. It contains the
past 15 days worth of dlog entries. For reports
on data within that time frame, it's faster to
use this small table.
*/
$CREATE['trans.dlog_15'] = "
	CREATE TABLE dlog_15 (`tdate` datetime default NULL,
          `register_no` smallint(6) default NULL,
          `emp_no` smallint(6) default NULL,
          `trans_no` int(11) default NULL,
          `upc` varchar(255) default NULL,
          `trans_type` varchar(255) default NULL,
          `trans_subtype` varchar(255) default NULL,
          `trans_status` varchar(255) default NULL,
          `department` smallint(6) default NULL,
          `quantity` double default NULL,
          `unitPrice` double default NULL,
          `total` double default NULL,
          `tax` smallint(6) default NULL,
          `foodstamp` tinyint(4) default NULL,
          `ItemQtty` double default NULL,
          `card_no` varchar(255) default NULL,
          `trans_id` int(11) default NULL,
          `trans_num` varchar(25) default NULL
	 )";

if ($dbms == "MSSQL"){
	$CREATE['trans.dlog_15'] = "
		CREATE TABLE dlog_15 ([tdate] [datetime] NOT NULL ,
                        [register_no] [smallint] NOT NULL ,
                        [emp_no] [smallint] NOT NULL ,
                        [trans_no] [int] NOT NULL ,
                        [upc] [nvarchar] (13) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
                        [trans_type] [nvarchar] (1) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
                        [trans_subtype] [nvarchar] (2) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
                        [trans_status] [nvarchar] (1) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
                        [department] [smallint] NULL ,
                        [quantity] [float] NULL ,
                        [total] [money] NOT NULL ,
                        [regPrice] [money] NULL ,
                        [tax] [smallint] NULL ,
                        [foodstamp] [tinyint] NOT NULL ,
                        [ItemQtty] [float] NULL ,
                        [card_no] [nvarchar] (6) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
                        [trans_id] [int] NOT NULL ,
                        [trans_num] [nvarchar] (25) COLLATE SQL_Latin1_General_CP1_CI_AS NULL 
		)
	";
}
?>
