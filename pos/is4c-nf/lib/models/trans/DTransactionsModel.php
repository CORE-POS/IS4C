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

namespace COREPOS\pos\lib\models\trans;
use COREPOS\pos\lib\models\BasicModel;

class DTransactionsModel extends BasicModel 
{

    protected $name = 'dtransactions';

    protected $preferred_db = 'trans';

    protected $columns = array(
    'datetime'    => array('type'=>'DATETIME','index'=>True),
    'store_id' => array('type'=>'SMALLINT'),
    'register_no'    => array('type'=>'SMALLINT'),
    'emp_no'    => array('type'=>'SMALLINT'),
    'trans_no'    => array('type'=>'INT'),
    'upc'        => array('type'=>'VARCHAR(13)','index'=>True),
    'description'    => array('type'=>'VARCHAR(30)'),
    'trans_type'    => array('type'=>'VARCHAR(1)','index'=>True),
    'trans_subtype'    => array('type'=>'VARCHAR(2)'),
    'trans_status'    => array('type'=>'VARCHAR(1)'),
    'department'    => array('type'=>'SMALLINT','index'=>True),
    'quantity'    => array('type'=>'DOUBLE'),
    'scale'        => array('type'=>'TINYINT','default'=>0.00),
    'cost'        => array('type'=>'MONEY'),
    'unitPrice'    => array('type'=>'MONEY'),
    'total'        => array('type'=>'MONEY'),
    'regPrice'    => array('type'=>'MONEY'),
    'tax'        => array('type'=>'SMALLINT'),
    'foodstamp'    => array('type'=>'TINYINT'),
    'discount'    => array('type'=>'MONEY'),
    'memDiscount'    => array('type'=>'MONEY'),
    'discountable'    => array('type'=>'TINYINT'),
    'discounttype'    => array('type'=>'TINYINT'),
    'voided'    => array('type'=>'TINYINT'),
    'percentDiscount'=> array('type'=>'TINYINT'),
    'ItemQtty'    => array('type'=>'DOUBLE'),
    'volDiscType'    => array('type'=>'TINYINT'),
    'volume'    => array('type'=>'TINYINT'),
    'VolSpecial'    => array('type'=>'MONEY'),
    'mixMatch'    => array('type'=>'VARCHAR(13)'),
    'matched'    => array('type'=>'SMALLINT'),
    'memType'    => array('type'=>'TINYINT'),
    'staff'        => array('type'=>'TINYINT'),
    'numflag'    => array('type'=>'INT','default'=>0),
    'charflag'    => array('type'=>'VARCHAR(2)','default'=>"''"),
    'card_no'    => array('type'=>'INT','index'=>True),
    'trans_id'    => array('type'=>'INT'),
    'pos_row_id' => array('type'=>'BIGINT UNSIGNED', 'increment'=>true, 'primary_key'=>true),
    );

    public function doc()
    {
        return '
Use:
This is IT CORE\'s transaction log. A rather important table.

A transaction can be uniquely identified by:
date + register_no + emp_no + trans_no
A record in a transaction can be uniquely identified by:
date + register_no + emp_no + trans_no + trans_id
Note that "date" is not necessary datetime. All records
in a transaction don\'t always have the exact same time
to the second.

upc is generally a product. The column is always a varchar
here, regardless of dbms, because sometimes non-numeric
data goes here such as \'DISCOUNT\', \'TAX\', or \'amountDPdept\'
(transaction discounts, applicable tax, and open rings,
respectively).

description is what\'s displayed on screen and on receipts.

trans_type indicates the record\'s type Values include
(but may not be limited to at all co-ops):
    I => normally a product identified by upc, but
         can also be a discount line (upc=\'DISCOUNT\')
         or a YOU SAVED line (upc=\'0\'). 
    A => tax total line
    C => a commentary line. These generally exist 
         only for generating the on-screen display
         at the register (subtotal lines, etc).
    D => open ring to a department. In this case,
         upc will be the amount, \'DP\', and the
         department number
    T => tender record. UPC is generally, but not
         always, \'0\' (e.g., manufacturer coupons
         have their own UPCs)
    0 => another commentary line

trans_subtype refines the record\'s type. Values include
(but may not be limited to at all co-ops):
    CM => record is a cashier-written comment.
          Used to make notes on a transaction
    (tender code) => goes with trans_type \'T\',
             exact values depends what\'s
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

cost indicates an item\'s cost. Meaningless on non-item
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
member (custdata.Type = \'PC\')

discountable indicates whether an item is eligible
for transaction-wide percent discounts.

discounttype indicates what type of sale an item
is on.
    0 => not on sale
    1 => on sale for everyone
    2 => on sale for members
Values over 2 may be used, but aren\'t used 
consistently across co-ops at this time.

voided indicates whether a line has been voided
    0 => no
    1 => yes
voided is also used as a status flag in some cases
You\'d have to dig into IT CORE code a bit to get a
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
for a volume pricing group. This is so the same item doesn\'t
get counted more than once.

memType and staff match values in core_op.custdata. Including
them here helps determine membership status at the time of 
purchase as opposed to current status.

numflag and charflag are generic status indicators. As far
as I know, there\'s no uniform usage across implementations.
Also used by the shrink/DDD module to indicate the reason 
the product has been marked as unsellable, for which 
trans_status = \'Z\'.

card_no is the customer number from core_op.custdata.
        ';
    }
}

