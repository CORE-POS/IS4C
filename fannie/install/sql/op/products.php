<?php
/*
Table: products

Columns:
    upc int or varchar, dbms dependent
    description varchar
    brand varchar
    formatted_name varchar
    normal_price double
    pricemethod smallint
    groupprice double
    quantity smallint
    special_price double
    specialpricemethod smallint
    specialgroupprice double
    specialquantity smallint
    start_date datetime
    end_date datetime
    department smallint
    size varchar
    tax smallint
    foodstamp tinyint
    scale tinyint
    scaleprice tinyint
    mixmatchcode varchar
    modified datetime
    advertised tinyint
    tareweight double
    discount smallint
    discounttype tinyint
    line_item_discountable tinyint
    unitofmeasure varchar
    wicable tinyint
    qttyEnforced tinyint
    idEnforced tinyint
    cost double
    inUse tinyint
    numflag int
    subdept smallint
    deposit double
    local tinyint
    store_id smallint
    default_vendor_id int
    current_origin_id
    id int auto_increment

Depends on:
    none

Use:
This table lists items in the system.

upc is how items are identified. Regardless of
whether it's an integer or a varchar, it should
always have length 13. Whether or not to include
check digits is up to the individual store.

id provides a unique row identifier, but upc
should probably be unique too. If not, you'll have
to add code to either let the cashier choose which
matching record or to limit which records are
pushed to the registers.

description is used for screen display, reporting,
and receipts. formatted_name is an alternative that
will be used instead of description if it has a
non-NULL, non-empty value. brand and description are
intended to be distinct fields for use in things
like shelf tags and signage. formatted_name can
be used to combine these two fields or otherwise
create a standardized screen/receipt description
containing extra information. 

Pricing:
When an item has pricemethod 0, the price is
simply normal_price. If pricemethod is greater than
0, groupprice and quantity are used to calculate
the price. There is variance here by implementation,
but generally pricemethod 1 or 2 will yield the
most obvious grouped pricing. Example: buy one, get
the second 50% off
    normal_price => 1.00
    pricemethod => 1 (or maybe 2)
    groupprice => 1.50
    quantity => 2
If discounttype is greater than zero, the special*
columns get used instead but otherwise behavior
should be similar.

start_date and end_date indicate the start and end
of a sale. The current register code does not check
these to validate sales.

department and subdept are an item's department
and subdepartment settings.

tax indicates if an item is taxable and at what rate

foodstamp indicates whether an item can be purchased
using foodstamps

scale indicates whether an item should be sold by weight

scaleprice indicates what type of random-weight barcodes
are used. Value zero means UPC-A where the last 4 digits
contains price with max value $99.99. Value one means
EAN-13 where the last 5 digits contain price with
max value $999.99.

mixmatchcode relates to pricing when pricemethod is
greater than zero. Items with the same mixmatchcode
are considred equivalent when determining whether the
customer has reached the required quantity.

modified [ideally] lists the last time a product was
changed. Not all back end tools remember to update this
and of course direct updates via SQL may forget too.

tareweight is a default tare for by weight items

discount indicates whether an item is eligible for
percentage discounts on a whole transactions. Value 0
means exclude from discounts.

discounttype indicates if an item is on sale
    0 => not on sale
    1 => on sale for everyone
    2 => on sale for members
Values greater than 2 may be used, but results will
vary based on whose code you're running

line_item_discount indicates whether an item is eligible
for line item discounts. Value 0 means not eligible.

unitofmeasure might be used for screen display and
receipt listings of quantity. 

qttyEnforced forces the cashier to enter an explicit
quantity when ringing up the item

idEnforced forces the cashier to enter the customer's
date of birth. This flag should be set to the age
required to purchase the product - e.g., 21 for 
alcohol in the US.

cost is the item's cost

isUse indicates whether the item is currently
available for sale. Whether cashiers can bypass this
setting probably varies by front end implementation.

local indicates whether the item is locally sourced.

deposit is a PLU. The product record with this UPC will
be added to the transaction automatically when the item
is rung.

default_vendor_id is the identifier (vendors.vendorID)
for the vendor who typically supplies the product.

current_origin_id is the identifier (origins.originID)
for the geographical location where the product is
currently sourced from.

Other columns:
size, advertised, wicable, and numflag 
have no current meaning on the
front or back end. Or no current implementation.
The meaning of idEnforced is pretty clear, but setting
it won't *do* anything.
*/

$CREATE['op.products'] = "
    CREATE TABLE `products` (
          `upc` varchar(13) default NULL,
          `description` varchar(30) default NULL,
          `brand` varchar(30) default NULL,
          `formatted_name` varchar(30) default NULL,
          `normal_price` double default NULL,
          `pricemethod` smallint(6) default NULL,
          `groupprice` double default NULL,
          `quantity` smallint(6) default NULL,
          `special_price` double default NULL,
          `specialpricemethod` smallint(6) default NULL,
          `specialgroupprice` double default NULL,
          `specialquantity` smallint(6) default NULL,
          `start_date` datetime default NULL,
          `end_date` datetime default NULL,
          `department` smallint(6) default NULL,
          `size` varchar(9) default NULL,
          `tax` smallint(6) default NULL,
          `foodstamp` tinyint(4) default NULL,
          `scale` tinyint(4) default NULL,
          `scaleprice` tinyint(4) default 0 NULL,
          `mixmatchcode` varchar(13) default NULL,
          `modified` datetime default NULL,
          `advertised` tinyint(4) default NULL,
          `tareweight` double default NULL,
          `discount` smallint(6) default NULL,
          `discounttype` tinyint(4) default NULL,
          `line_item_discountable` tinyint(4) default NULL,
          `unitofmeasure` varchar(15) default NULL,
          `wicable` smallint(6) default NULL,
          `qttyEnforced` tinyint(4) default NULL,
          `idEnforced` tinyint(4) default NULL,
          `cost` double default 0 NULL,
          `inUse` tinyint(4) default NULL,
          `numflag` int(11) default '0',
          `subdept` smallint(4) default NULL,
          `deposit` double default NULL,
          `local` int(11) default '0',
          `store_id` smallint default '0',
          `default_vendor_id` int(11) default '0',
          `current_origin_id` int(11) default '0',
          `id` int(11) NOT NULL auto_increment,
          PRIMARY KEY  (`id`),
          KEY `upc` (`upc`),
          KEY `description` (`description`),
          KEY `normal_price` (`normal_price`),
          KEY `subdept` (`subdept`),
          KEY `department` (`department`),
          KEY `store_id` (`store_id`)
    )
";
if ($dbms == "MSSQL"){
    $CREATE['op.products'] = "
        CREATE TABLE [products] (
            [upc] [varchar] (13) COLLATE SQL_Latin1_General_CP1_CI_AS NOT NULL ,
            [description] [varchar] (30) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [brand] [varchar] (30) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [formatted_name] [varchar] (30) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [normal_price] [money] NULL ,
            [pricemethod] [smallint] NULL ,
            [groupprice] [money] NULL ,
            [quantity] [smallint] NULL ,
            [special_price] [money] NULL ,
            [specialpricemethod] [smallint] NULL ,
            [specialgroupprice] [money] NULL ,
            [specialquantity] [smallint] NULL ,
            [start_date] [datetime] NULL ,
            [end_date] [datetime] NULL ,
            [department] [smallint] NOT NULL ,
            [size] [varchar] (9) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [tax] [smallint] NOT NULL ,
            [foodstamp] [bit] NOT NULL ,
            [Scale] [bit] NOT NULL ,
            [scaleprice] [tinyint] NULL ,
            [mixmatchcode] [varchar] (13) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [modified] [datetime] NULL ,
            [advertised] [bit] NOT NULL ,
            [tareweight] [float] NULL ,
            [discount] [smallint] NULL ,
            [discounttype] [tinyint] NULL ,
            [line_item_discountable] [tinyint] NULL ,
            [unitofmeasure] [varchar] (15) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [wicable] [smallint] NULL ,
            [qttyEnforced] [tinyint] NULL ,
            [idEnforced] [tinyint] NULL ,
            [cost] [money] NULL ,
            [inUse] [tinyint] NOT NULL ,        
            [numflag] [int] NULL ,
            [subdept] [smallint] NULL ,
            [deposit] [money] NULL ,
            [local] [int] NULL ,
            [store_id] [smallint] 0,
            [default_vendor_id] [int] NULL ,
            [current_origin_id] [int] NULL ,
            [id] [int] IDENTITY (1, 1) NOT NULL ,
            PRIMARY KEY ([id]) )
    ";
}

?>
