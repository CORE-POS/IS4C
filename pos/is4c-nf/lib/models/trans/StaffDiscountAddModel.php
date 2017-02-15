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

/*
if (!class_exists('\\COREPOS\\pos\lib\\models\\trans\\LocalTransModel')) {
    include_once(dirname(__FILE__).'/LocalTransModel.php');
}
*/

/**
  @class StaffDiscountAddModel
*/
class StaffDiscountAddModel extends \COREPOS\pos\lib\models\trans\LocalTransModel
{

    protected $name = "staffdiscountadd";

    // not quite identical to dtransactions
    public function __construct($con)
    {
        unset($this->columns['trans_id']);

        parent::__construct($con);
    }

    /* disabled because it's a view */
    public function create()
    { 
        if ($this->connection->isView($this->name)) {
            return true;
        }
        $viewSQL = "CREATE VIEW staffdiscountadd AS
            select max(datetime) AS datetime,
            register_no AS register_no,
            emp_no AS emp_no,
            trans_no AS trans_no,
            upc AS upc,
            description AS description,
            'I' AS trans_type,
            '' AS trans_subtype,
            'S' AS trans_status,
            max(department) AS department,
            1 AS quantity,
            0 AS scale,
            0 AS cost,
            (-(1) * sum(memDiscount)) AS unitPrice,
            (-(1) * sum(memDiscount)) AS total,
            (-(1) * sum(memDiscount)) AS regPrice,
            max(tax) AS tax,
            max(foodstamp) AS foodstamp,
            0 AS discount,
            (-(1) * sum(memDiscount)) AS memDiscount,
            3 AS discountable,
            40 AS discounttype,
            8 AS voided,
            MAX(percentDiscount) as percentDiscount,
            0 AS ItemQtty,0 AS volDiscType,
            0 AS volume,0 AS VolSpecial,
            0 AS mixMatch,0 AS matched,
            MAX(memType) as memType,
            MAX(staff) as staff,
            0 as numflag,
            '' as charflag,
            card_no AS card_no 
            from localtemptrans 
            where (((discounttype = 4) and (unitPrice = regPrice)) or (trans_status = 'S')) 
            group by register_no,emp_no,trans_no,upc,description,card_no having (sum(memDiscount) <> 0)";
        $try = $this->connection->query($viewSQL);

        return ($try === false) ? false : true;
    }

    public function doc()
    {
        return '
Use:
This view calculates staff discounts on items
in the transaction that have not yet been applied.
These records are then inserted into localtemptrans
to apply the relevant discount(s).
        ';
    }

    public function delete(){ return false; }
    public function save(){ return false; }
    public function normalize($db_name, $mode=BasicModel::NORMALIZE_MODE_CHECK, $doCreate=False){ return 0; }
}

