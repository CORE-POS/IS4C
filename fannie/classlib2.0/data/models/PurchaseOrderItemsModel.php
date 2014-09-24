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
  @class PurchaseOrderItemsModel
*/
class PurchaseOrderItemsModel extends BasicModel 
{

    protected $name = "PurchaseOrderItems";

    protected $columns = array(
    'orderID' => array('type'=>'INT','primary_key'=>True),
    'sku' => array('type'=>'VARCHAR(13)','primary_key'=>True),
    'quantity' => array('type'=>'INT'),
    'unitCost' => array('type'=>'MONEY'),
    'caseSize' => array('type'=>'INT'),
    'receivedDate' => array('type'=>'DATETIME'),
    'receivedQty' => array('type'=>'INT'),
    'receivedTotalCost' => array('type'=>'MONEY'),
    'unitSize' => array('type'=>'VARCHAR(25)'),
    'brand' => array('type'=>'VARCHAR(50)'),
    'description' => array('type'=>'VARCHAR(50)'),
    'internalUPC' => array('type'=>'VARCHAR(13)')
    );

    protected $preferred_db = 'op';

    /**
      A really, REALLY old version of this table might exist.
      If so, just delete it and start over with the new schema.
    */
    public function normalize($db_name, $mode=BasicModel::NORMALIZE_MODE_CHECK, $doCreate=false)
    {
        $dbc = FannieDB::get($db_name);
        $this->connection = $dbc;
        if (!$dbc->table_exists($this->name)) {
            return parent::normalize($db_name, $mode, $doCreate);
        }
        $def = $dbc->table_definition($this->name);
        if (count($def)==4 && isset($def['upc']) && isset($def['vendor_id']) && isset($def['order_id']) && isset($def['quantity'])) {
            echo "==========================================\n";
            if ($mode == BasicModel::NORMALIZE_MODE_APPLY){
                $dbc->query('DROP TABLE '.$dbc->identifier_escape($this->name));
                $success = $this->create();    
                echo "Recreating table ".$this->name.": ";
                echo ($success) ? 'Succeeded' : 'Failed';
                echo "\n";
                echo "==========================================\n";
                return $success;
            } else {
                echo $this->name." is very old. It needs to be re-created\n";
                echo "Any data in the current table will be lost\n";
                echo "==========================================\n";
                return count($this->columns);
            }
        } else {
            return parent::normalize($db_name, $mode, $doCreate);
        }
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

    public function sku()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["sku"])) {
                return $this->instance["sku"];
            } else if (isset($this->columns["sku"]["default"])) {
                return $this->columns["sku"]["default"];
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
                'left' => 'sku',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["sku"]) || $this->instance["sku"] != func_get_args(0)) {
                if (!isset($this->columns["sku"]["ignore_updates"]) || $this->columns["sku"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["sku"] = func_get_arg(0);
        }
        return $this;
    }

    public function quantity()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["quantity"])) {
                return $this->instance["quantity"];
            } else if (isset($this->columns["quantity"]["default"])) {
                return $this->columns["quantity"]["default"];
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
                'left' => 'quantity',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["quantity"]) || $this->instance["quantity"] != func_get_args(0)) {
                if (!isset($this->columns["quantity"]["ignore_updates"]) || $this->columns["quantity"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["quantity"] = func_get_arg(0);
        }
        return $this;
    }

    public function unitCost()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["unitCost"])) {
                return $this->instance["unitCost"];
            } else if (isset($this->columns["unitCost"]["default"])) {
                return $this->columns["unitCost"]["default"];
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
                'left' => 'unitCost',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["unitCost"]) || $this->instance["unitCost"] != func_get_args(0)) {
                if (!isset($this->columns["unitCost"]["ignore_updates"]) || $this->columns["unitCost"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["unitCost"] = func_get_arg(0);
        }
        return $this;
    }

    public function caseSize()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["caseSize"])) {
                return $this->instance["caseSize"];
            } else if (isset($this->columns["caseSize"]["default"])) {
                return $this->columns["caseSize"]["default"];
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
                'left' => 'caseSize',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["caseSize"]) || $this->instance["caseSize"] != func_get_args(0)) {
                if (!isset($this->columns["caseSize"]["ignore_updates"]) || $this->columns["caseSize"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["caseSize"] = func_get_arg(0);
        }
        return $this;
    }

    public function receivedDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["receivedDate"])) {
                return $this->instance["receivedDate"];
            } else if (isset($this->columns["receivedDate"]["default"])) {
                return $this->columns["receivedDate"]["default"];
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
                'left' => 'receivedDate',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["receivedDate"]) || $this->instance["receivedDate"] != func_get_args(0)) {
                if (!isset($this->columns["receivedDate"]["ignore_updates"]) || $this->columns["receivedDate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["receivedDate"] = func_get_arg(0);
        }
        return $this;
    }

    public function receivedQty()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["receivedQty"])) {
                return $this->instance["receivedQty"];
            } else if (isset($this->columns["receivedQty"]["default"])) {
                return $this->columns["receivedQty"]["default"];
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
                'left' => 'receivedQty',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["receivedQty"]) || $this->instance["receivedQty"] != func_get_args(0)) {
                if (!isset($this->columns["receivedQty"]["ignore_updates"]) || $this->columns["receivedQty"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["receivedQty"] = func_get_arg(0);
        }
        return $this;
    }

    public function receivedTotalCost()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["receivedTotalCost"])) {
                return $this->instance["receivedTotalCost"];
            } else if (isset($this->columns["receivedTotalCost"]["default"])) {
                return $this->columns["receivedTotalCost"]["default"];
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
                'left' => 'receivedTotalCost',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["receivedTotalCost"]) || $this->instance["receivedTotalCost"] != func_get_args(0)) {
                if (!isset($this->columns["receivedTotalCost"]["ignore_updates"]) || $this->columns["receivedTotalCost"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["receivedTotalCost"] = func_get_arg(0);
        }
        return $this;
    }

    public function unitSize()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["unitSize"])) {
                return $this->instance["unitSize"];
            } else if (isset($this->columns["unitSize"]["default"])) {
                return $this->columns["unitSize"]["default"];
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
                'left' => 'unitSize',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["unitSize"]) || $this->instance["unitSize"] != func_get_args(0)) {
                if (!isset($this->columns["unitSize"]["ignore_updates"]) || $this->columns["unitSize"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["unitSize"] = func_get_arg(0);
        }
        return $this;
    }

    public function brand()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["brand"])) {
                return $this->instance["brand"];
            } else if (isset($this->columns["brand"]["default"])) {
                return $this->columns["brand"]["default"];
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
                'left' => 'brand',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["brand"]) || $this->instance["brand"] != func_get_args(0)) {
                if (!isset($this->columns["brand"]["ignore_updates"]) || $this->columns["brand"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["brand"] = func_get_arg(0);
        }
        return $this;
    }

    public function description()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["description"])) {
                return $this->instance["description"];
            } else if (isset($this->columns["description"]["default"])) {
                return $this->columns["description"]["default"];
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
                'left' => 'description',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["description"]) || $this->instance["description"] != func_get_args(0)) {
                if (!isset($this->columns["description"]["ignore_updates"]) || $this->columns["description"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["description"] = func_get_arg(0);
        }
        return $this;
    }

    public function internalUPC()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["internalUPC"])) {
                return $this->instance["internalUPC"];
            } else if (isset($this->columns["internalUPC"]["default"])) {
                return $this->columns["internalUPC"]["default"];
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
                'left' => 'internalUPC',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["internalUPC"]) || $this->instance["internalUPC"] != func_get_args(0)) {
                if (!isset($this->columns["internalUPC"]["ignore_updates"]) || $this->columns["internalUPC"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["internalUPC"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

