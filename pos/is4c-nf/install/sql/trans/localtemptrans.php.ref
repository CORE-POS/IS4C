<?php
/*
Table: localtemptrans

Columns:
	datetime datetime
	register_no int
	emp_no int
	trans_no int
	upc varchar
	description varchar
	trans_type varchar
	trans_subtype varchar
	trans_status varchar
	department smallint
	quantity double
	scale tinyint
	cost currency
	unitPrice currency
	total currency
	regPrice currency
	tax smallint
	foodstamp tinyint
	discount currency
	memDiscount currency
	discounttable tinyint
	discounttype tinyint
	voided tinyint
	percentDiscount tinyint
	ItemQtty double
	volDiscType tinyint
	volume tinyint
	VolSpecial currency
	mixMatch varchar
	matched smallint
	memType tinyint
	staff tinyint
	numflag int
	charflag varchar
	card_no int
	trans_id int

Depends on:
	none

Use:
Stores current transaction data. See 
dtransactions for detailed information on
the columns. The only notable difference
is this table has an automatically incremented
trans_id column.
*/
$CREATE['trans.localtemptrans'] = "
	CREATE TABLE localtemptrans (
	  datetime datetime default NULL,
	  register_no smallint(6) default NULL,
	  emp_no smallint(6) default NULL,
	  trans_no int(11) default NULL,
	  upc varchar(13) default NULL,
	  description varchar(30) default NULL,
	  trans_type varchar(1) default NULL,
	  trans_subtype varchar(2) default NULL,
	  trans_status varchar(1) default NULL,
	  department smallint(6) default NULL,
	  quantity double default NULL,
	  scale tinyint(4) default NULL,
	  cost decimal(10,2) default 0.00 NULL,
	  unitPrice decimal(10,2) default NULL,
	  total decimal(10,2) default NULL,
	  regPrice decimal(10,2) default NULL,
	  tax smallint(6) default NULL,
	  foodstamp tinyint(4) default NULL,
	  discount decimal(10,2) default NULL,
	  memDiscount decimal(10,2) default NULL,
	  discountable tinyint(4) default NULL,
	  discounttype tinyint(4) default NULL,
	  voided tinyint(4) default NULL,
	  percentDiscount tinyint(4) default NULL,
	  ItemQtty double default NULL,
	  volDiscType tinyint(4) default NULL,
	  volume tinyint(4) default NULL,
	  VolSpecial decimal(10,2) default NULL,
	  mixMatch varchar(13) default NULL,
	  matched smallint(6) default NULL,
	  memType tinyint(2) default NULL,
	  staff tinyint(4) default NULL,
	  numflag int(11) default 0 NULL,
	  charflag varchar(2) default '' NULL,
	  card_no int(11) default NULL,
	  trans_id INTEGER NOT NULL auto_increment,
	  PRIMARY KEY (trans_id)
	)
";

if ($dbms == "MSSQL"){
	$CREATE['trans.localtemptrans'] = "
		CREATE TABLE localtemptrans ([datetime] [datetime] NOT NULL ,
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
			[trans_id] [int] IDENTITY (1, 1) NOT NULL 
		) ON [PRIMARY]
	";
}
elseif ($dbms == "PDOLITE"){
	$CREATE['trans.localtemptrans'] = str_replace('PRIMARY KEY (trans_id)','',$CREATE['trans.localtemptrans']);
	$CREATE['trans.localtemptrans'] = str_replace('NOT NULL auto_increment,','PRIMARY KEY autoincrement',$CREATE['trans.localtemptrans']);
}
?>
