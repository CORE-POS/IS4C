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

/**
  @class TaxViewModel
*/
class TaxViewModel extends ViewModel
{

    protected $name = "taxView";
    protected $preferred_db = 'trans';

    protected $columns = array(
    'id' => array('type'=>'INT'),
    'description' => array('type'=>'VARCHAR(255)'),
    'taxTotal' => array('type'=>'MONEY'),
    'fsTaxable' => array('type'=>'MONEY'),
    'fsTaxTotal' => array('type'=>'MONEY'),
    'foodstampTender' => array('type'=>'MONEY'),
    'taxrate' => array('type'=>'FLOAT'),
    );

    public function definition()
    {
        return "
            SELECT 
            r.id AS id,
            r.description AS description,
            CAST(SUM(CASE 
                WHEN l.trans_type IN ('I','D') AND discountable=0 THEN total 
                WHEN l.trans_type IN ('I','D') AND discountable<>0 THEN total * ((100-s.percentDiscount)/100)
                ELSE 0 END
            ) * r.rate AS DECIMAL(10,2)) as taxTotal,
            CAST(SUM(CASE 
                WHEN l.trans_type IN ('I','D') AND discountable=0 AND foodstamp=1 THEN total 
                WHEN l.trans_type IN ('I','D') AND discountable<>0 AND foodstamp=1 THEN total * ((100-s.percentDiscount)/100)
                ELSE 0 END
            ) AS DECIMAL(10,2)) as fsTaxable,
            CAST(SUM(CASE 
                WHEN l.trans_type IN ('I','D') AND discountable=0 AND foodstamp=1 THEN total 
                WHEN l.trans_type IN ('I','D') AND discountable<>0 AND foodstamp=1 THEN total * ((100-s.percentDiscount)/100)
                ELSE 0 END
            ) * r.rate AS DECIMAL(10,2)) as fsTaxTotal,
            -1*MAX(fsTendered) as foodstampTender,
            MAX(r.rate) as taxrate
            FROM
            taxrates AS r 
            LEFT JOIN localtemptrans AS l
            ON r.id=l.tax
            JOIN lttsummary AS s
            WHERE trans_type <> 'L'
            GROUP BY r.id,r.description";
    }

    public function doc()
    {
        return '
Use:
This view is a revised, BETA way of dealing
with taxes. Rather than generate the tax total
(including foodstamp exemptions) with a series of
cascading views, this single view provides a
record for each available tax rate. Exemption 
calculations then occur on the code side in a
far-easier-to-read imperative style.

id is the tax rate\'s identifier and description
is its description.

taxTotal is the total tax due for this particular
rate. SUM(taxTotal) over the view would be the total
tax due with all rates.

fsTaxable is the *retail* cost of goods taxed at this rate.
fsTaxTotal is tax due on those items at this rate.

foodstampTender is the total amount tendered in foodstamps
for the transaction. This will be the same for all records
in this view and is provided as a convenience to avoid a 
second look-up query.

rate is this tax rate as a decimal - i.e., 1% is 0.01.

----------------------------------------------
In calculating exemptions, foodstampTender and fsTaxable
are important. If foodstampTender is >= fsTaxable then
all foodstampable, taxable items were purchased with foodstamps
and you can subtract fsTaxTotal from taxTotal. On the other
hand if foodstampTender is < fsTaxable then you should reduce
taxTotal by a proportional pro-rated amount.

When dealing with multiple tax rates, it is important to
reduce foodstampTender each time it is used. The value in the
view is the same for all records and POS has to decide where
to apply that tender more than once.
        ';
    }

    /* START ACCESSOR FUNCTIONS */

    public function id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["id"])) {
                return $this->instance["id"];
            } elseif(isset($this->columns["id"]["default"])) {
                return $this->columns["id"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["id"] = func_get_arg(0);
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

    public function taxTotal()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["taxTotal"])) {
                return $this->instance["taxTotal"];
            } elseif(isset($this->columns["taxTotal"]["default"])) {
                return $this->columns["taxTotal"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["taxTotal"] = func_get_arg(0);
        }
    }

    public function fsTaxable()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["fsTaxable"])) {
                return $this->instance["fsTaxable"];
            } elseif(isset($this->columns["fsTaxable"]["default"])) {
                return $this->columns["fsTaxable"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["fsTaxable"] = func_get_arg(0);
        }
    }

    public function fsTaxTotal()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["fsTaxTotal"])) {
                return $this->instance["fsTaxTotal"];
            } elseif(isset($this->columns["fsTaxTotal"]["default"])) {
                return $this->columns["fsTaxTotal"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["fsTaxTotal"] = func_get_arg(0);
        }
    }

    public function foodstampTender()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["foodstampTender"])) {
                return $this->instance["foodstampTender"];
            } elseif(isset($this->columns["foodstampTender"]["default"])) {
                return $this->columns["foodstampTender"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["foodstampTender"] = func_get_arg(0);
        }
    }

    public function taxrate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["taxrate"])) {
                return $this->instance["taxrate"];
            } elseif(isset($this->columns["taxrate"]["default"])) {
                return $this->columns["taxrate"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["taxrate"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

