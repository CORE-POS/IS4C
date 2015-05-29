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
    'trans_id'    => array('type'=>'TINYINT'),
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

    /* START ACCESSOR FUNCTIONS */

    public function datetime()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["datetime"])) {
                return $this->instance["datetime"];
            } elseif(isset($this->columns["datetime"]["default"])) {
                return $this->columns["datetime"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["datetime"] = func_get_arg(0);
        }
    }

    public function store_id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["store_id"])) {
                return $this->instance["store_id"];
            } elseif(isset($this->columns["store_id"]["default"])) {
                return $this->columns["store_id"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["store_id"] = func_get_arg(0);
        }
    }

    public function register_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["register_no"])) {
                return $this->instance["register_no"];
            } elseif(isset($this->columns["register_no"]["default"])) {
                return $this->columns["register_no"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["register_no"] = func_get_arg(0);
        }
    }

    public function emp_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["emp_no"])) {
                return $this->instance["emp_no"];
            } elseif(isset($this->columns["emp_no"]["default"])) {
                return $this->columns["emp_no"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["emp_no"] = func_get_arg(0);
        }
    }

    public function trans_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["trans_no"])) {
                return $this->instance["trans_no"];
            } elseif(isset($this->columns["trans_no"]["default"])) {
                return $this->columns["trans_no"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["trans_no"] = func_get_arg(0);
        }
    }

    public function upc()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["upc"])) {
                return $this->instance["upc"];
            } elseif(isset($this->columns["upc"]["default"])) {
                return $this->columns["upc"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["upc"] = func_get_arg(0);
        }
    }

    public function description()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["description"])) {
                return $this->instance["description"];
            } elseif(isset($this->columns["description"]["default"])) {
                return $this->columns["description"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["description"] = func_get_arg(0);
        }
    }

    public function trans_type()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["trans_type"])) {
                return $this->instance["trans_type"];
            } elseif(isset($this->columns["trans_type"]["default"])) {
                return $this->columns["trans_type"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["trans_type"] = func_get_arg(0);
        }
    }

    public function trans_subtype()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["trans_subtype"])) {
                return $this->instance["trans_subtype"];
            } elseif(isset($this->columns["trans_subtype"]["default"])) {
                return $this->columns["trans_subtype"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["trans_subtype"] = func_get_arg(0);
        }
    }

    public function trans_status()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["trans_status"])) {
                return $this->instance["trans_status"];
            } elseif(isset($this->columns["trans_status"]["default"])) {
                return $this->columns["trans_status"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["trans_status"] = func_get_arg(0);
        }
    }

    public function department()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["department"])) {
                return $this->instance["department"];
            } elseif(isset($this->columns["department"]["default"])) {
                return $this->columns["department"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["department"] = func_get_arg(0);
        }
    }

    public function quantity()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["quantity"])) {
                return $this->instance["quantity"];
            } elseif(isset($this->columns["quantity"]["default"])) {
                return $this->columns["quantity"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["quantity"] = func_get_arg(0);
        }
    }

    public function scale()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["scale"])) {
                return $this->instance["scale"];
            } elseif(isset($this->columns["scale"]["default"])) {
                return $this->columns["scale"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["scale"] = func_get_arg(0);
        }
    }

    public function cost()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["cost"])) {
                return $this->instance["cost"];
            } elseif(isset($this->columns["cost"]["default"])) {
                return $this->columns["cost"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["cost"] = func_get_arg(0);
        }
    }

    public function unitPrice()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["unitPrice"])) {
                return $this->instance["unitPrice"];
            } elseif(isset($this->columns["unitPrice"]["default"])) {
                return $this->columns["unitPrice"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["unitPrice"] = func_get_arg(0);
        }
    }

    public function total()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["total"])) {
                return $this->instance["total"];
            } elseif(isset($this->columns["total"]["default"])) {
                return $this->columns["total"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["total"] = func_get_arg(0);
        }
    }

    public function regPrice()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["regPrice"])) {
                return $this->instance["regPrice"];
            } elseif(isset($this->columns["regPrice"]["default"])) {
                return $this->columns["regPrice"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["regPrice"] = func_get_arg(0);
        }
    }

    public function tax()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["tax"])) {
                return $this->instance["tax"];
            } elseif(isset($this->columns["tax"]["default"])) {
                return $this->columns["tax"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["tax"] = func_get_arg(0);
        }
    }

    public function foodstamp()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["foodstamp"])) {
                return $this->instance["foodstamp"];
            } elseif(isset($this->columns["foodstamp"]["default"])) {
                return $this->columns["foodstamp"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["foodstamp"] = func_get_arg(0);
        }
    }

    public function discount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["discount"])) {
                return $this->instance["discount"];
            } elseif(isset($this->columns["discount"]["default"])) {
                return $this->columns["discount"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["discount"] = func_get_arg(0);
        }
    }

    public function memDiscount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["memDiscount"])) {
                return $this->instance["memDiscount"];
            } elseif(isset($this->columns["memDiscount"]["default"])) {
                return $this->columns["memDiscount"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["memDiscount"] = func_get_arg(0);
        }
    }

    public function discountable()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["discountable"])) {
                return $this->instance["discountable"];
            } elseif(isset($this->columns["discountable"]["default"])) {
                return $this->columns["discountable"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["discountable"] = func_get_arg(0);
        }
    }

    public function discounttype()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["discounttype"])) {
                return $this->instance["discounttype"];
            } elseif(isset($this->columns["discounttype"]["default"])) {
                return $this->columns["discounttype"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["discounttype"] = func_get_arg(0);
        }
    }

    public function voided()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["voided"])) {
                return $this->instance["voided"];
            } elseif(isset($this->columns["voided"]["default"])) {
                return $this->columns["voided"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["voided"] = func_get_arg(0);
        }
    }

    public function percentDiscount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["percentDiscount"])) {
                return $this->instance["percentDiscount"];
            } elseif(isset($this->columns["percentDiscount"]["default"])) {
                return $this->columns["percentDiscount"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["percentDiscount"] = func_get_arg(0);
        }
    }

    public function ItemQtty()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["ItemQtty"])) {
                return $this->instance["ItemQtty"];
            } elseif(isset($this->columns["ItemQtty"]["default"])) {
                return $this->columns["ItemQtty"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["ItemQtty"] = func_get_arg(0);
        }
    }

    public function volDiscType()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["volDiscType"])) {
                return $this->instance["volDiscType"];
            } elseif(isset($this->columns["volDiscType"]["default"])) {
                return $this->columns["volDiscType"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["volDiscType"] = func_get_arg(0);
        }
    }

    public function volume()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["volume"])) {
                return $this->instance["volume"];
            } elseif(isset($this->columns["volume"]["default"])) {
                return $this->columns["volume"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["volume"] = func_get_arg(0);
        }
    }

    public function VolSpecial()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["VolSpecial"])) {
                return $this->instance["VolSpecial"];
            } elseif(isset($this->columns["VolSpecial"]["default"])) {
                return $this->columns["VolSpecial"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["VolSpecial"] = func_get_arg(0);
        }
    }

    public function mixMatch()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["mixMatch"])) {
                return $this->instance["mixMatch"];
            } elseif(isset($this->columns["mixMatch"]["default"])) {
                return $this->columns["mixMatch"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["mixMatch"] = func_get_arg(0);
        }
    }

    public function matched()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["matched"])) {
                return $this->instance["matched"];
            } elseif(isset($this->columns["matched"]["default"])) {
                return $this->columns["matched"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["matched"] = func_get_arg(0);
        }
    }

    public function memType()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["memType"])) {
                return $this->instance["memType"];
            } elseif(isset($this->columns["memType"]["default"])) {
                return $this->columns["memType"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["memType"] = func_get_arg(0);
        }
    }

    public function staff()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["staff"])) {
                return $this->instance["staff"];
            } elseif(isset($this->columns["staff"]["default"])) {
                return $this->columns["staff"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["staff"] = func_get_arg(0);
        }
    }

    public function numflag()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["numflag"])) {
                return $this->instance["numflag"];
            } elseif(isset($this->columns["numflag"]["default"])) {
                return $this->columns["numflag"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["numflag"] = func_get_arg(0);
        }
    }

    public function charflag()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["charflag"])) {
                return $this->instance["charflag"];
            } elseif(isset($this->columns["charflag"]["default"])) {
                return $this->columns["charflag"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["charflag"] = func_get_arg(0);
        }
    }

    public function card_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["card_no"])) {
                return $this->instance["card_no"];
            } elseif(isset($this->columns["card_no"]["default"])) {
                return $this->columns["card_no"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["card_no"] = func_get_arg(0);
        }
    }

    public function trans_id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["trans_id"])) {
                return $this->instance["trans_id"];
            } elseif(isset($this->columns["trans_id"]["default"])) {
                return $this->columns["trans_id"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["trans_id"] = func_get_arg(0);
        }
    }

    public function pos_row_id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["pos_row_id"])) {
                return $this->instance["pos_row_id"];
            } elseif(isset($this->columns["pos_row_id"]["default"])) {
                return $this->columns["pos_row_id"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["pos_row_id"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */

}

