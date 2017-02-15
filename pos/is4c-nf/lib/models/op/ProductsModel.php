<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

namespace COREPOS\pos\lib\models\op;
use COREPOS\pos\lib\models\BasicModel;

class ProductsModel extends BasicModel 
{

    protected $name = 'products';

    protected $preferred_db = 'op';

    protected $columns = array(
    'upc' => array('type'=>'VARCHAR(13)','index'=>True),
    'description'=>array('type'=>'VARCHAR(30)','index'=>True),
    'brand'=>array('type'=>'VARCHAR(30)'),
    'formatted_name'=>array('type'=>'VARCHAR(30)'),
    'normal_price'=>array('type'=>'MONEY'),
    'pricemethod'=>array('type'=>'SMALLINT'),
    'groupprice'=>array('type'=>'MONEY'),
    'quantity'=>array('type'=>'SMALLINT'),
    'special_price'=>array('type'=>'MONEY'),
    'specialpricemethod'=>array('type'=>'SMALLINT'),
    'specialgroupprice'=>array('type'=>'MONEY'),
    'specialquantity'=>array('type'=>'SMALLINT'),
    'special_limit'=>array('type'=>'TINYINT'),
    'start_date'=>array('type'=>'DATETIME'),
    'end_date'=>array('type'=>'DATETIME'),
    'department'=>array('type'=>'SMALLINT','index'=>True),
    'size'=>array('type'=>'VARCHAR(9)'),
    'tax'=>array('type'=>'SMALLINT'),
    'foodstamp'=>array('type'=>'TINYINT'),
    'scale'=>array('type'=>'TINYINT'),
    'scaleprice'=>array('type'=>'MONEY'),
    'mixmatchcode'=>array('type'=>'VARCHAR(13)'),
    'modified'=>array('type'=>'DATETIME'),
    'batchID'=>array('type'=>'TINYINT', 'replaces'=>'advertised'),
    'tareweight'=>array('type'=>'DOUBLE'),
    'discount'=>array('type'=>'SMALLINT'),
    'discounttype'=>array('type'=>'TINYINT'),
    'line_item_discountable'=>array('type'=>'TINYINT', 'default'=>0),
    'unitofmeasure'=>array('type'=>'VARCHAR(15)'),
    'wicable'=>array('type'=>'SMALLINT'),
    'qttyEnforced'=>array('type'=>'TINYINT'),
    'idEnforced'=>array('type'=>'TINYINT'),
    'cost'=>array('type'=>'MONEY', 'default'=>0),
    'special_cost'=>array('type'=>'MONEY', 'default'=>0),
    'received_cost'=>array('type'=>'MONEY', 'default'=>0),
    'inUse'=>array('type'=>'TINYINT'),
    'numflag'=>array('type'=>'INT','default'=>0),
    'subdept'=>array('type'=>'SMALLINT'),
    'deposit'=>array('type'=>'DOUBLE'),
    'local'=>array('type'=>'INT','default'=>0),
    'store_id'=>array('type'=>'SMALLINT','default'=>0),
    'default_vendor_id'=>array('type'=>'INT','default'=>0),
    'current_origin_id'=>array('type'=>'INT','default'=>0),
    'auto_par'=>array('type'=>'DOUBLE','default'=>0),
    'price_rule_id'=>array('type'=>'INT', 'default'=>0),
    'last_sold'=>array('type'=>'DATETIME'),
    'id'=>array('type'=>'INT','primary_key'=>True,'increment'=>True)
    );

    protected $unique = array('upc');

    public function doc()
    {
        return '
Use:
This table lists items in the system.

upc is how items are identified. Regardless of
whether it\'s an integer or a varchar, it should
always have length 13. Whether or not to include
check digits is up to the individual store.

id provides a unique row identifier, but upc
should probably be unique too. If not, you\'ll have
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

department and subdept are an item\'s department
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
vary based on whose code you\'re running

line_item_discount indicates whether an item is eligible
for line item discounts. Value 0 means not eligible.

unitofmeasure might be used for screen display and
receipt listings of quantity. 

qttyEnforced forces the cashier to enter an explicit
quantity when ringing up the item

idEnforced forces the cashier to enter the customer\'s
date of birth. This flag should be set to the age
required to purchase the product - e.g., 21 for 
alcohol in the US.

Cost:
cost is the item\'s normal cost as used for calculating retail price. 
special_cost is a temporary, promotional cost. received_cost is filled in
via purchase orders. Most pricing tools  use normal cost to generate retail 
prices. special_cost or received_cost is recorded in transaction logs to more 
closely reflect actual cost at the time of sale.

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
it won\'t *do* anything.
        ';
    }
}

