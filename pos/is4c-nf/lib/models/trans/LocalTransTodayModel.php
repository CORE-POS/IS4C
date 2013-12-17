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

if (!class_exists('DTransactionsModel')) {
    include_once(dirname(__FILE__).'/DTransactionsModel.php');
}

/**
  @class LocalTransTodayModel
*/
class LocalTransTodayModel extends DTransactionsModel
{

    protected $name = "localtranstoday";

    /**
      Add extra indexes besides the ones in the
      parent table dtransactions
    */
    public function __construct($con) 
    {
        parent::__construct($con);
        $this->columns['register_no']['index'] = true;
        $this->columns['trans_no']['index'] = true;
        $this->columns['emp_no']['index'] = true;
    }

    /**
      localtranstoday used to be a view; recreate
      it as a table if needed.
    */
    public function normalize($db_name, $mode=BasicModel::NORMALIZE_MODE_CHECK, $doCreate=False)
    { 
        global $CORE_LOCAL;
        if ($db_name == $CORE_LOCAL->get('pDatabase')) {
            $this->connection = Database::pDataConnect();
        } else if ($db_name == $CORE_LOCAL->get('tDatabase')) {
            $this->connection = Database::tDataConnect();
        } else {
            echo "Error: Unknown database ($db_name)";
            return false;
        }

        if ($this->connection->isView($this->name)) {
            if ($mode == BasicModel::NORMALIZE_MODE_CHECK) {
                echo "View {$this->name} should be a table!\n";
                echo "==========================================\n";
                printf("%s table %s\n","Check complete. Need to drop view & create replacement table.", $this->name);
                echo "==========================================\n\n";
                return 999;
            } else {
                $drop = $this->connection->query('DROP VIEW '.$this->name);
                echo "==========================================\n";
                printf("Dropping view %s %s\n",$this->name, ($drop)?"OK":"failed");
                if ($drop) {
                    $cResult = $this->create();
                    printf("Update complete. Creation of table %s %s\n",$this->name, ($cResult)?"OK":"failed");
                }
                echo "==========================================\n";

                return true;
            }
        } else {
            return parent::normalize($db_name, $mode, $doCreate);
        }
    }

    /* START ACCESSOR FUNCTIONS */
    /* END ACCESSOR FUNCTIONS */
}

