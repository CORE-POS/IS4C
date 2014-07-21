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
    ChargeLimit double
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
    LastChange
    id int (auto increment)

Depends on:
    memtype

Use:
This is one of two "primary" tables dealing with membership
(the other is meminfo). Of the two, only custdata is present
at the checkout. Column meaning may not be quite identical 
across stores.

[Probably] The Same Everywhere:
- CardNo is the member's number. This identifies them.
- personNum is for stores that allow more than one person per membership.
  personNum starts at 1.
    The combination (CardNo, personNum) should be unique for each record.
- FirstName what it sounds like.
- LastName what it sounds like.
- Discount gives the member a percentage discount on purchases.
- Type identifies whether the record is for an actual member.
  If Type is 'PC', the person is considered a member at the register.
    This is a little confusing, but not everyone in the table has to be
   a member.
- blueLine is displayed on the checkout screen when the member's number is entered.
- id just provides a guaranteed-unique row identifier.
[20Feb2014 Use of these fields is becoming more general]
- ChargeOk=1 if member may run a store charge balance; =0 may not.
- MemDiscountLimit is their store charge account limit.
  (Deprecated in favour of ChargeLimit)
- ChargeLimit is their store charge account limit.
- Balance is a store charge balance as of the start of the day,
   if the person has one.
     Some records are for organizations, esp vendors,
     that have charge accounts.
     Is updated from ar_live_balance by cronjob arbalance.sanitycheck.php
      and by its replacement cron/tasks/ArHistoryTask.php
      
[Probably] Just For Organizing:
The register is mostly unaware of these settings,
but they can be used on the backend for consistency checks
e.g., make sure all staff members have the appropriate percent discount
- staff identifies someone as an employee. Value: 1?
- memType allows a little more nuance than just member yes/no.
  FK to memtype.memtype
- SSI probably because of a historic senior citizen discount.
  (Sounds like it is obsolete or at least not used.)

WFC Specific:
- memCoupons indicates how many virtual coupons (tender MA) are available.

[Probably] Ignored:
To the best of my (Andy's) knowledge, these have no meaning on the front or back end.
- CashBack
- WriteChecks
- StoreCoupons
- Purchases
- NumberOfChecks
- Shown

Maintenance:
- Single edit: fannie/mem/search.php
- Single add: fannie/mem/new.php
- Batch import: fannie/mem/import/*.php
- custdata.Balance is updated from ar_live_balance by cronjob arbalance.sanitycheck.php

*/
/*--COMMENTS - - - - - - - - - - - - - - - - - - - -

 * 12Dec12 EL Field LastChange
 *            MSSQL will probably need support like described at:
 *             http://www.dociletree.co.za/mssql-and-on-update/
 * 22Oct12 EL Comment about cron update of Balance.
 *         EL Comment about ChargeOK
 *         EL Maintenance section in comments.
 * 26Jun12 Eric Lee Reformatted Use section
 * epoch   AT Original notes by Andy Theuninck.

*/
$CREATE['op.custdata'] = "
    CREATE TABLE `custdata` (
      `CardNo` int(11) default NULL,
      `personNum` tinyint(4) NOT NULL default '1',
      `LastName` varchar(30) default NULL,
      `FirstName` varchar(30) default NULL,
      `CashBack` double NOT NULL default '60',
      `Balance` double NOT NULL default '0',
      `Discount` smallint(6) default NULL,
      `MemDiscountLimit` double NOT NULL default '0',
      `ChargeLimit` double NOT NULL default '0',
      `ChargeOk` tinyint(4) NOT NULL default '0',
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
      `LastChange` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
      `id` int(11) NOT NULL auto_increment,
      PRIMARY KEY  (`id`),
      KEY `CardNo` (`CardNo`),
      KEY `LastName` (`LastName`),
      KEY `LastChange` (`LastChange`)
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
            [ChargeLimit] [money] NULL ,
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
            [LastChange] [datetime] NULL ,
            [id] [int] IDENTITY (1, 1) NOT NULL 
        ) ON [PRIMARY]
    ";
}

?>
