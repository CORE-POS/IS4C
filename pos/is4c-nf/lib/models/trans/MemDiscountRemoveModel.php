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
use COREPOS\pos\lib\Database;

/*
if (!class_exists('\\COREPOS\\pos\lib\\models\\trans\\LocalTransModel')) {
    include_once(dirname(__FILE__).'/LocalTransModel.php');
}
*/

/**
  @class MemDiscountRemoveModel
*/
class MemDiscountRemoveModel extends \COREPOS\pos\lib\models\trans\LocalTransModel
{

    protected $name = "memdiscountremove";

    // not quite identical to dtransactions
    // need to add increment to trans_id
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
        $viewSQL = "CREATE view memdiscountremove as
            Select 
            max(datetime) as datetime, 
            register_no, 
            emp_no, 
            trans_no, 
            upc, 
            CASE WHEN volDiscType IN (3,4) THEN 'Set Discount' ELSE description END as description, 
            'I' as trans_type, 
            '' as trans_subtype, 
            'M' as trans_status, 
            max(department) as department, 
            1 as quantity, 
            0 as scale, 
            0 as cost,
            -1 * (sum(case when (discounttype = 2 and unitPrice <> regPrice) then -1 * memDiscount 
            else memDiscount end)) as unitPrice, 
            -1 * (sum(case when (discounttype = 2 and unitPrice <> regPrice) then -1 * memDiscount 
            else memDiscount end)) as total, 
            -1 * (sum(case when (discounttype = 2 and unitPrice <> regPrice) then -1 * memDiscount 
            else memDiscount end))as regPrice, 
            max(tax) as tax, 
            max(foodstamp) as foodstamp, 
            0 as discount, 
            -1 * (sum(case when (discounttype = 2 and unitPrice <> regPrice) then -1 * memDiscount 
            else memDiscount end)) as memDiscount, 
            max(discountable) as discountable, 
            20 as discounttype, 
            8 as voided, 
            MAX(percentDiscount) as percentDiscount,
            0 as ItemQtty, 
            0 as volDiscType, 
            0 as volume, 
            0 as VolSpecial, 
            0 as mixMatch, 
            0 as matched, 
            MAX(memType) as memType,
            MAX(staff) as staff,
            0 as numflag,
            '' as charflag,
            card_no as card_no
            from localtemptrans 
            where ((discounttype = 2 and unitPrice <> regPrice) or trans_status = 'M') 
            group by register_no, emp_no, trans_no, upc, description, card_no 
            having 
            sum(case when (discounttype = 2 and unitPrice <> regPrice) then -1 * memDiscount 
            else memDiscount end)<> 0";
        $try = $this->connection->query($viewSQL);

        return ($try === false) ? false : true;
    }

    public function doc()
    {
        return '
Use:
This view is the opposite of memdiscountadd.
It calculates the reverse of all currently
applied member discounts on items. These records
are inserted into localtemptrans to remove
member discounts if needed.
        ';
    }

    public function delete(){ return false; }
    public function save(){ return false; }

    public function normalize($db_name, $mode=BasicModel::NORMALIZE_MODE_CHECK, $doCreate=False)
    {
        if ($db_name == \CoreLocal::get('pDatabase')) {
            $this->connection = Database::pDataConnect();
        } else if ($db_name == \CoreLocal::get('tDatabase')) {
            $this->connection = Database::tDataConnect();
        } else {
            echo "Error: Unknown database ($db_name)";
            return false;
        }

        $viewSQL = $this->connection->getViewDefinition($this->name);

        echo "==========================================\n";
        printf("%s view %s\n", 
            ($mode==BasicModel::NORMALIZE_MODE_CHECK)?"Checking":"Updating", 
            "{$db_name}.{$this->name}"
        );
        echo "==========================================\n";

        if (strstr($viewSQL, '0 AS memType') || strstr($viewSQL, '0 AS ' . $this->connection->identifierEscape('memType'))) {
            /**
              Structure-check 27Dec2013
              Make sure memType is calcluated instead of hardcoded to zero
            */
            echo "==========================================\n";
            if ($mode == BasicModel::NORMALIZE_MODE_CHECK) {
                echo "View needs to be rebuild to calculate memType correctly\n";    
            } else {
                echo "Rebuilding view to calculate memType correctly... ";
                $this->connection->query('DROP VIEW ' . $this->connection->identifierEscape($this->name));
                $success = $this->create();
                echo ($success ? 'succeeded' : 'failed') . "\n";
            }
        }

        return 0;
    }

}

