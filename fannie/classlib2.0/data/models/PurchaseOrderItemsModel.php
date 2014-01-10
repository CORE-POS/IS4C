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
            } elseif(isset($this->columns["orderID"]["default"])) {
                return $this->columns["orderID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["orderID"] = func_get_arg(0);
        }
    }

    public function sku()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["sku"])) {
                return $this->instance["sku"];
            } elseif(isset($this->columns["sku"]["default"])) {
                return $this->columns["sku"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["sku"] = func_get_arg(0);
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

    public function unitCost()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["unitCost"])) {
                return $this->instance["unitCost"];
            } elseif(isset($this->columns["unitCost"]["default"])) {
                return $this->columns["unitCost"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["unitCost"] = func_get_arg(0);
        }
    }

    public function caseSize()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["caseSize"])) {
                return $this->instance["caseSize"];
            } elseif(isset($this->columns["caseSize"]["default"])) {
                return $this->columns["caseSize"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["caseSize"] = func_get_arg(0);
        }
    }

    public function receivedDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["receivedDate"])) {
                return $this->instance["receivedDate"];
            } elseif(isset($this->columns["receivedDate"]["default"])) {
                return $this->columns["receivedDate"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["receivedDate"] = func_get_arg(0);
        }
    }

    public function receivedQty()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["receivedQty"])) {
                return $this->instance["receivedQty"];
            } elseif(isset($this->columns["receivedQty"]["default"])) {
                return $this->columns["receivedQty"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["receivedQty"] = func_get_arg(0);
        }
    }

    public function receivedTotalCost()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["receivedTotalCost"])) {
                return $this->instance["receivedTotalCost"];
            } elseif(isset($this->columns["receivedTotalCost"]["default"])) {
                return $this->columns["receivedTotalCost"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["receivedTotalCost"] = func_get_arg(0);
        }
    }

    public function unitSize()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["unitSize"])) {
                return $this->instance["unitSize"];
            } elseif(isset($this->columns["unitSize"]["default"])) {
                return $this->columns["unitSize"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["unitSize"] = func_get_arg(0);
        }
    }

    public function brand()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["brand"])) {
                return $this->instance["brand"];
            } elseif(isset($this->columns["brand"]["default"])) {
                return $this->columns["brand"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["brand"] = func_get_arg(0);
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

    public function internalUPC()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["internalUPC"])) {
                return $this->instance["internalUPC"];
            } elseif(isset($this->columns["internalUPC"]["default"])) {
                return $this->columns["internalUPC"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["internalUPC"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

