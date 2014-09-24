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
  @class PurchaseOrderModel
*/
class PurchaseOrderModel extends BasicModel 
{

    protected $name = "PurchaseOrder";

    protected $columns = array(
    'orderID' => array('type'=>'INT','default'=>0,'increment'=>True,'primary_key'=>True),
    'vendorID' => array('type'=>'INT'),
    'creationDate' => array('type'=>'DATETIME'),
    'placed' => array('type'=>'TINYINT','default'=>0,'index'=>True),
    'placedDate' => array('type'=>'DATETIME'),
    'userID' => array('type'=>'INT'),
    'vendorOrderID' => array('type'=>'VARCHAR(25)'),
    'vendorInvoiceID' => array('type'=>'VARCHAR(25)'),
    'standingID' => array('type'=>'INT')
    );

    protected $preferred_db = 'op';

    /**
      A really, REALLY old version of this table might exist.
      If so, just delete it and start over with the new schema.
    */
    public function normalize($db_name, $mode=BasicModel::NORMALIZE_MODE_CHECK, $doCreate=False){
        $dbc = FannieDB::get($db_name);
        $this->connection = $dbc;
        if (!$dbc->table_exists($this->name))
            return parent::normalize($db_name, $mode, $doCreate);
        $def = $dbc->table_definition($this->name);
        if (count($def)==3 && isset($def['stamp']) && isset($def['id']) && isset($def['name'])){
            echo "==========================================\n";
            if ($mode == BasicModel::NORMALIZE_MODE_APPLY){
                $dbc->query('DROP TABLE '.$dbc->identifier_escape($this->name));
                $success = $this->create();    
                echo "Recreating table ".$this->name.": ";
                echo ($success) ? 'Succeeded' : 'Failed';
                echo "\n";
                echo "==========================================\n";
                return $success;
            }
            else {
                echo $this->name." is very old. It needs to be re-created\n";
                echo "Any data in the current table will be lost\n";
                echo "==========================================\n";
                return count($this->columns);
            }
        }
        else
            return parent::normalize($db_name, $mode, $doCreate);
    }

    /* START ACCESSOR FUNCTIONS */

    public function orderID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["orderID"])) {
                return $this->instance["orderID"];
            } else if (isset($this->columns["orderID"]["default"])) {
                return $this->columns["orderID"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'orderID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["orderID"]) || $this->instance["orderID"] != func_get_args(0)) {
                if (!isset($this->columns["orderID"]["ignore_updates"]) || $this->columns["orderID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["orderID"] = func_get_arg(0);
        }
        return $this;
    }

    public function vendorID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["vendorID"])) {
                return $this->instance["vendorID"];
            } else if (isset($this->columns["vendorID"]["default"])) {
                return $this->columns["vendorID"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'vendorID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["vendorID"]) || $this->instance["vendorID"] != func_get_args(0)) {
                if (!isset($this->columns["vendorID"]["ignore_updates"]) || $this->columns["vendorID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["vendorID"] = func_get_arg(0);
        }
        return $this;
    }

    public function creationDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["creationDate"])) {
                return $this->instance["creationDate"];
            } else if (isset($this->columns["creationDate"]["default"])) {
                return $this->columns["creationDate"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'creationDate',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["creationDate"]) || $this->instance["creationDate"] != func_get_args(0)) {
                if (!isset($this->columns["creationDate"]["ignore_updates"]) || $this->columns["creationDate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["creationDate"] = func_get_arg(0);
        }
        return $this;
    }

    public function placed()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["placed"])) {
                return $this->instance["placed"];
            } else if (isset($this->columns["placed"]["default"])) {
                return $this->columns["placed"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'placed',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["placed"]) || $this->instance["placed"] != func_get_args(0)) {
                if (!isset($this->columns["placed"]["ignore_updates"]) || $this->columns["placed"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["placed"] = func_get_arg(0);
        }
        return $this;
    }

    public function placedDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["placedDate"])) {
                return $this->instance["placedDate"];
            } else if (isset($this->columns["placedDate"]["default"])) {
                return $this->columns["placedDate"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'placedDate',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["placedDate"]) || $this->instance["placedDate"] != func_get_args(0)) {
                if (!isset($this->columns["placedDate"]["ignore_updates"]) || $this->columns["placedDate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["placedDate"] = func_get_arg(0);
        }
        return $this;
    }

    public function userID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["userID"])) {
                return $this->instance["userID"];
            } else if (isset($this->columns["userID"]["default"])) {
                return $this->columns["userID"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'userID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["userID"]) || $this->instance["userID"] != func_get_args(0)) {
                if (!isset($this->columns["userID"]["ignore_updates"]) || $this->columns["userID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["userID"] = func_get_arg(0);
        }
        return $this;
    }

    public function vendorOrderID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["vendorOrderID"])) {
                return $this->instance["vendorOrderID"];
            } else if (isset($this->columns["vendorOrderID"]["default"])) {
                return $this->columns["vendorOrderID"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'vendorOrderID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["vendorOrderID"]) || $this->instance["vendorOrderID"] != func_get_args(0)) {
                if (!isset($this->columns["vendorOrderID"]["ignore_updates"]) || $this->columns["vendorOrderID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["vendorOrderID"] = func_get_arg(0);
        }
        return $this;
    }

    public function vendorInvoiceID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["vendorInvoiceID"])) {
                return $this->instance["vendorInvoiceID"];
            } else if (isset($this->columns["vendorInvoiceID"]["default"])) {
                return $this->columns["vendorInvoiceID"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'vendorInvoiceID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["vendorInvoiceID"]) || $this->instance["vendorInvoiceID"] != func_get_args(0)) {
                if (!isset($this->columns["vendorInvoiceID"]["ignore_updates"]) || $this->columns["vendorInvoiceID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["vendorInvoiceID"] = func_get_arg(0);
        }
        return $this;
    }

    public function standingID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["standingID"])) {
                return $this->instance["standingID"];
            } else if (isset($this->columns["standingID"]["default"])) {
                return $this->columns["standingID"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'standingID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["standingID"]) || $this->instance["standingID"] != func_get_args(0)) {
                if (!isset($this->columns["standingID"]["ignore_updates"]) || $this->columns["standingID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["standingID"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

