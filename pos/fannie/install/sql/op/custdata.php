<?php
/*
Table: custdata

Columns:
	CardNo int
	personNum tinyint
	LastName varchar
	FirstName varchar
	CashBack double
	Balance double
	Discount smallint
	MemDiscountLimit double
	ChargeOk tinyint
	WriteChecks tinyint
	StoreCoupons tinyint
	Type varchar
	memType
	staff
	SSI	
	Purchases
	NumberOfChecks
	memCoupons
	blueLine
	Shown
	id int (auto increment)

Depends on:
	memtype

Use:
This is one of two "primary" tables dealing with membership
(the other is meminfo). Of the two, only custdata is present
at the checkout. Column meaning may not be quite identical 
across stores.

[Probably] The Same Everywhere:
CardNo is the member's number. This identifies them.
personNum is for stores that allow more than one person per
membership. personNum starts at 1. The combination (CardNo,
personNum) should be unique for each record. FirstName and
LastName are what they sound like. Discount gives the member
a percentage discount on purchases. Type identifies whether
the record is for an actual member. If Type is 'PC', the
person is considered a member at the register. This is a 
little confusing, but not everyone in the table has to be
a member. blueLine is displayed on the checkout screen
when the member's number is entered.

[Probably] Just For Organizing:
staff identifies someone as an employee. memType allows
a little more nuance than just member yes/no. I think SSI
is there because of a historic senior citizen discount 
somewhere. The register is mostly unaware of these settings,
but they can be used on the backend for consistency checks
e.g., make sure all staff members have the appropriate
percent discount

WFC Specific:
Some members have store charge accounts. Balance is their
store charge balance as of the start of the day, and
MemDiscountLimit is their charge account limit. memCoupons
indicates how many virtual coupons (tender MA) are available.

[Probably] Ignored:
To the best of my knowledge, CashBack, ChargeOk, WriteChecks,
StoreCoupons, Purchases, NumberOfChecks, and Shown have
no meaning on the front or back end.

id just provides a guaranteed-unique row identifier.
*/
$CREATE['op.custdata'] = "
	CREATE TABLE `custdata` (
	  `CardNo` int(8) default NULL,
	  `personNum` tinyint(4) NOT NULL default '1',
	  `LastName` varchar(30) default NULL,
	  `FirstName` varchar(30) default NULL,
	  `CashBack` double NOT NULL default '60',
	  `Balance` double NOT NULL default '0',
	  `Discount` smallint(6) default NULL,
	  `MemDiscountLimit` double NOT NULL default '0',
	  `ChargeOk` tinyint(4) NOT NULL default '1',
	  `WriteChecks` tinyint(4) NOT NULL default '1',
	  `StoreCoupons` tinyint(4) NOT NULL default '1',
	  `Type` varchar(10) NOT NULL default 'pc',
	  `memType` tinyint(4) default NULL,
	  `staff` tinyint(4) NOT NULL default '0',
	  `SSI` tinyint(4) NOT NULL default '0',
	  `Purchases` double NOT NULL default '0',
	  `NumberOfChecks` smallint(6) NOT NULL default '0',
	  `memCoupons` int(11) NOT NULL default '1',
	  `blueLine` varchar(50) default NULL,
	  `Shown` tinyint(4) NOT NULL default '1',
	  `id` int(11) NOT NULL auto_increment,
	  PRIMARY KEY  (`id`),
	  KEY `CardNo` (`CardNo`),
	  KEY `LastName` (`LastName`)
	)
";

if ($dbms == "MSSQL"){
	$CREATE['op.custdata'] = "
		CREATE TABLE [custdata] (
			[CardNo] [varchar] (25) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
			[personNum] [varchar] (3) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
			[LastName] [varchar] (30) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
			[FirstName] [varchar] (30) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
			[CashBack] [money] NULL ,
			[Balance] [money] NULL ,
			[Discount] [smallint] NULL ,
			[MemDiscountLimit] [money] NULL ,
			[ChargeOk] [bit] NULL ,
			[WriteChecks] [bit] NULL ,
			[StoreCoupons] [bit] NULL ,
			[Type] [varchar] (10) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
			[memType] [smallint] NULL ,
			[staff] [tinyint] NULL ,
			[SSI] [tinyint] NULL ,
			[Purchases] [money] NULL ,
			[NumberOfChecks] [smallint] NULL ,
			[memCoupons] [int] NULL ,
			[blueLine] [varchar] (50) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
			[Shown] [tinyint] NULL ,
			[id] [int] IDENTITY (1, 1) NOT NULL 
		) ON [PRIMARY]
	";
}

?>
