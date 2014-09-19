<?php
/*
Table: dtransactions

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
    pos_row_id int

Depends on:
	none

Use:
This is IT CORE's transaction log. A rather important table.

A transaction can be uniquely identified by:
date + register_no + emp_no + trans_no
A record in a transaction can be uniquely identified by:
date + register_no + emp_no + trans_no + trans_id
Note that "date" is not necessary datetime. All records
in a transaction don't always have the exact same time
to the second.

upc is generally a product. The column is always a varchar
here, regardless of dbms, because sometimes non-numeric
data goes here such as 'DISCOUNT', 'TAX', or 'amountDPdept'
(transaction discounts, applicable tax, and open rings,
respectively).

description is what's displayed on screen and on receipts.

trans_type indicates the record's type Values include
(but may not be limited to at all co-ops):
	I => normally a product identified by upc, but
	     can also be a discount line (upc='DISCOUNT')
	     or a YOU SAVED line (upc='0'). 
	A => tax total line
	C => a commentary line. These generally exist 
	     only for generating the on-screen display
	     at the register (subtotal lines, etc).
	D => open ring to a department. In this case,
	     upc will be the amount, 'DP', and the
	     department number
	T => tender record. UPC is generally, but not
	     always, '0' (e.g., manufacturer coupons
	     have their own UPCs)
	0 => another commentary line

trans_subtype refines the record's type. Values include
(but may not be limited to at all co-ops):
	CM => record is a cashier-written comment.
	      Used to make notes on a transaction
	(tender code) => goes with trans_type 'T',
			 exact values depends what's
			 in core_op.tenders
	blank => no refinement available for this trans_type

trans_status is a fairly all-purpose indicator. Values include
(but may not be limited to at all co-ops):
	X => the transaction is canceled
	D => this can be omitted with back-end reporting
	R => this line is a refund
	V => this line is a void
	M => this line is a member special discount
	C => this line is a coupon
	Z => this item was damaged, not sold (WFC)
	blank => no particular meaning

department is set for a UPC item, an open-department ring,
a member special discount, or a manufacturer coupon. All
other lines have zero here.

quantity and ItemQtty are the number of items sold on
that line. These can be fractional for by-weight items.
These values are normally the same, except for:
	1. member special lines, where ItemQtty is always zero.
	   This is useful for tracking actual movement by UPC
	2. refund lines, where quantity is negative and ItemQtty
	   is not. No idea what the reasoning was here.	

scale indicates an item sold by-weight. Meaningless on
non-item records.

cost indicates an item's cost. Meaningless on non-item
records.

unitPrice is the price a customer will be charged per item.
total is quantity times unitPrice. This is what the
customer actually pays. If an item is on sale, regPrice
indicates the regular price per item. On non-item records,
total is usually the only relevant column. Sales are 
positive, voids/refunds/tenders are negative.

tax indicates whether to tax an item and at what rate

foodstamp indicates whether an item can be paid for
using foodstamps

discount is any per-item discount that was applied.
In the simplest case, this is the regularPrice
minus the unitPrice (times quantity). Discounts are
listed as positive values.

memDiscount is the same as discount, but these
discounts are only applied if the customer is a
member (custdata.Type = 'PC')

discountable indicates whether an item is eligible
for transaction-wide percent discounts.

discounttype indicates what type of sale an item
is on.
	0 => not on sale
	1 => on sale for everyone
	2 => on sale for members
Values over 2 may be used, but aren't used 
consistently across co-ops at this time.

voided indicates whether a line has been voided
	0 => no
	1 => yes
voided is also used as a status flag in some cases
You'd have to dig into IT CORE code a bit to get a
handle on that.
	
percentDiscount is a percentage discount applied to
the whole transaction. This is an integer, so
5 = 5% = 0.05

volDiscType is a volume discount type. Usage varies
a lot here, but in general:
	volDiscType => core_op.products.pricemethod
	volume => core_op.products.quantity
	VolSpecial => core_op.products.groupprice
If an item is on sale, those become specialpricemethod,
specialquantity, and specialgroupprice (respectively).
Exact calculations depend a lot of volDiscType. 0 means
there is no volume discount, and either 1 or 2 (depending
on IT CORE version) will probably do a simple 3 for $2 style
sale (quantity=3, groupprice=2.00). Higher type values
vary.

mixMatch relates to volume pricing. In general, items
with the same mixMatch setting are interchangeable. This
is so you can do sales across a set of products (e.g., Clif
Bars) and the customer can buy various flavors but still
get the discount.

matched notes item quantites that have already been used
for a volume pricing group. This is so the same item doesn't
get counted more than once.

memType and staff match values in core_op.custdata. Including
them here helps determine membership status at the time of 
purchase as opposed to current status.

numflag and charflag are generic status indicators. As far
as I know, there's no uniform usage across implementations.
Also used by the shrink/DDD module to indicate the reason 
the product has been marked as unsellable, for which 
trans_status = 'Z'.

card_no is the customer number from core_op.custdata.
*/
$CREATE['trans.dtransactions'] = "
	CREATE TABLE dtransactions (
	  `datetime` datetime default NULL,
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
	  `cost` decimal(10,2) default 0.00 NULL,
	  `unitPrice` decimal(10,2) default NULL,
	  `total` decimal(10,2) default NULL,
	  `regPrice` decimal(10,2) default NULL,
	  `tax` smallint(6) default NULL,
	  `foodstamp` tinyint(4) default NULL,
	  `discount` decimal(10,2) default NULL,
	  `memDiscount` decimal(10,2) default NULL,
	  `discountable` tinyint(4) default NULL,
	  `discounttype` tinyint(4) default NULL,
	  `voided` tinyint(4) default NULL,
	  `percentDiscount` tinyint(4) default NULL,
	  `ItemQtty` double default NULL,
	  `volDiscType` tinyint(4) default NULL,
	  `volume` tinyint(4) default NULL,
	  `VolSpecial` decimal(10,2) default NULL,
	  `mixMatch` varchar(13) default NULL,
	  `matched` smallint(6) default NULL,
	  `memType` tinyint(2) default NULL,
	  `staff` tinyint(4) default NULL,
	  `numflag` int(11) default 0 NULL,
	  `charflag` varchar(2) default '' NULL,
	  `card_no` int(11) default NULL,
	  `trans_id` int(11) default NULL,
      `pos_row_id` bigint unsigned not null auto_increment,
      primary key (`pos_row_id`)
	)
";

if ($dbms == "MSSQL"){
	$CREATE['trans.dtransactions'] = "
		CREATE TABLE dtransactions ([datetime] [datetime] NOT NULL ,
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
			[trans_id] [int] NOT NULL ,
            [pos_row_id] [bigint] IDENTITY(1, 1) NOT NULL
		)
	";
}
?>
