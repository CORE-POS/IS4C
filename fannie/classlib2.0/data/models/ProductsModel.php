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

if (!class_exists('FannieDB')) {
    include(dirname(__FILE__).'/../FannieDB.php');
}
if (!class_exists('ProdUpdateModel')) {
    include(dirname(__FILE__).'/ProdUpdateModel.php');
}
if (!class_exists('BarcodeLib')) {
    include(dirname(__FILE__).'/../../lib/BarcodeLib.php');
}

class ProductsModel extends BasicModel 
{

    protected $name = 'products';

    protected $preferred_db = 'op';

    protected $columns = array(
    'upc' => array('type'=>'VARCHAR(13)','index'=>true),
    'description'=>array('type'=>'VARCHAR(30)','index'=>true),
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
    'start_date'=>array('type'=>'DATETIME'),
    'end_date'=>array('type'=>'DATETIME'),
    'department'=>array('type'=>'SMALLINT','index'=>true),
    'size'=>array('type'=>'VARCHAR(9)'),
    'tax'=>array('type'=>'SMALLINT'),
    'foodstamp'=>array('type'=>'TINYINT'),
    'scale'=>array('type'=>'TINYINT'),
    'scaleprice'=>array('type'=>'MONEY'),
    'mixmatchcode'=>array('type'=>'VARCHAR(13)'),
    'modified'=>array('type'=>'DATETIME','ignore_updates'=>true),
    'advertised'=>array('type'=>'TINYINT'),
    'tareweight'=>array('type'=>'DOUBLE'),
    'discount'=>array('type'=>'SMALLINT'),
    'discounttype'=>array('type'=>'TINYINT'),
    'line_item_discountable'=>array('type'=>'TINYINT', 'default'=>0),
    'unitofmeasure'=>array('type'=>'VARCHAR(15)'),
    'wicable'=>array('type'=>'SMALLINT'),
    'qttyEnforced'=>array('type'=>'TINYINT'),
    'idEnforced'=>array('type'=>'TINYINT'),
    'cost'=>array('type'=>'MONEY'),
    'inUse'=>array('type'=>'TINYINT'),
    'numflag'=>array('type'=>'INT','default'=>0),
    'subdept'=>array('type'=>'SMALLINT'),
    'deposit'=>array('type'=>'DOUBLE'),
    'local'=>array('type'=>'INT','default'=>0),
    'store_id'=>array('type'=>'SMALLINT','default'=>0),
    'default_vendor_id'=>array('type'=>'INT','default'=>0),
    'current_origin_id'=>array('type'=>'INT','default'=>0),
    'auto_par'=>array('type'=>'DOUBLE','default'=>0),
    'id'=>array('type'=>'INT','default'=>0,'primary_key'=>true,'increment'=>true)
    );

    protected $unique = array('upc');

    protected $normalize_lanes = true;

    /**
      Update product record for a upc
      New records are created automatically as needed
      @param $upc the upc
      @param $fields array of column names and values
      @param $update_only don't add if the product doesn't exist
    */
    public static function update($upc,$fields, $update_only=false)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        if (!is_numeric($upc)) {
            return false;
        }
        if (!is_int($upc) && !ctype_digit($upc)) {
            return false;
        }
        $upc = substr($upc,0,13);
        $upc = BarcodeLib::padUPC($upc);

        $chkP = $dbc->prepare_statement("SELECT upc FROM products WHERE upc=?");
        $chkR = $dbc->exec_statement($chkP, array($upc));
        if ($dbc->num_rows($chkR) == 0) {
            return ($update_only===false ? self::add($upc,$fields) : true);
        }

        $valid_columns = $dbc->table_definition('products');

        $updateQ = "UPDATE products SET ";
        $updateArgs = array();
        foreach($fields as $name => $value) {
            switch($name) {
                case 'description':
                case 'normal_price':
                case 'pricemethod':
                case 'groupprice':
                case 'quantity':
                case 'special_price':
                case 'specialpricemethod':
                case 'specialgroupprice':
                case 'specialquantity':
                case 'start_date':
                case 'end_date':
                case 'department':
                case 'size':
                case 'tax':
                case 'foodstamp':
                case 'scale':
                case 'scaleprice':
                case 'mixmatchcode':
                case 'advertised':
                case 'tareweight':
                case 'discount':
                case 'discounttype':
                case 'unitofmeasure':
                case 'wicable':
                case 'qttyEnforced':
                case 'idEnforced':
                case 'cost':
                case 'inUse':
                case 'numflag':
                case 'subdept':
                case 'deposit':
                case 'local':
                case 'store_id':
                    if ($name === 0 || $name === true) {
                        break; // switch does loose comparison...
                    }
                    if (!isset($valid_columns[$name])) {
                        break; // table does not have that column
                    }
                    $updateQ .= $name." = ?,";
                    $updateArgs[] = $value;
                    break;
                default:
                    break;
            }
        }

        /** if only name fields were provided, there's
            nothing to do here */
        if ($updateQ != "UPDATE products SET ") {
            $updateQ .= 'modified='.$dbc->now();
            $updateQ .= " WHERE upc=?";
            $updateArgs[] = $upc;

            $updateP = $dbc->prepare_statement($updateQ);
            $updateR = $dbc->exec_statement($updateP,$updateArgs);
            if ($updateR === false) {
                return false;
            }
            ProdUpdateModel::add($upc, $fields);
        }

        return true;
    }

    /**
      Delete an item
      @param $upc the upc
    */
    public static function staticDelete($upc)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        if (!is_numeric($upc)) {
            return false;
        }
        if (!is_int($upc) && !ctype_digit($upc)) {
            return false;
        }
        $upc = substr($upc,0,13);
        $upc = BarcodeLib::padUPC($upc);

        $delP = $dbc->prepare_statement('DELETE FROM products WHERE upc=?');
        $delR = $dbc->exec_statement($delP,array($upc));
        if ($delR === false) {
            return false;
        }
        ProdUpdateModel::add($upc,array('description'=>'_DELETED'));
        
        return true;    
    }

    /**
      Create product record for a upc
      @param $upc the upc
      @param $fields array of column names and values
    */
    private static function add($upc,$fields)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);        
        if (!is_numeric($upc)) {
            return false;
        }
        if (!is_int($upc) && !ctype_digit($upc)) {
            return false;
        }
        $upc = substr($upc,0,13);
        $upc = BarcodeLib::padUPC($upc);
        
        $args = array();
        $q = 'INSERT INTO products ';

        $q .= '(upc,';
        $args[] = $upc;

        $q .= 'description,';
        $args[] = isset($fields['description'])?$fields['description']:'';

        $q .= 'normal_price,';
        $args[] = isset($fields['normal_price'])?$fields['normal_price']:0.00;

        $q .= 'pricemethod,';
        $args[] = isset($fields['pricemethod'])?$fields['pricemethod']:0;

        $q .= 'groupprice,';
        $args[] = isset($fields['groupprice'])?$fields['groupprice']:0.00;

        $q .= 'quantity,';
        $args[] = isset($fields['quantity'])?$fields['quantity']:0;

        $q .= 'special_price,';
        $args[] = isset($fields['special_price'])?$fields['special_price']:0.00;

        $q .= 'specialpricemethod,';
        $args[] = isset($fields['specialpricemethod'])?$fields['specialpricemethod']:0;

        $q .= 'specialgroupprice,';
        $args[] = isset($fields['specialgroupprice'])?$fields['specialgroupprice']:0.00;

        $q .= 'specialquantity,';
        $args[] = isset($fields['specialquantity'])?$fields['specialquantity']:0;

        $q .= 'department,';
        $args[] = isset($fields['department'])?$fields['department']:0;

        $q .= 'size,';
        $args[] = isset($fields['size'])?$fields['size']:'';

        $q .= 'tax,';
        $args[] = isset($fields['tax'])?$fields['tax']:0;

        $q .= 'foodstamp,';
        $args[] = isset($fields['foodstamp'])?$fields['foodstamp']:0;

        $q .= 'scale,';
        $args[] = isset($fields['scale'])?$fields['scale']:0;

        $q .= 'scaleprice,';
        $args[] = isset($fields['scaleprice'])?$fields['scaleprice']:0.00;

        $q .= 'mixmatchcode,';
        $args[] = isset($fields['mixmatchcode'])?$fields['mixmatchcode']:'';

        $q .= 'advertised,';
        $args[] = isset($fields['advertised'])?$fields['advertised']:1;

        $q .= 'tareweight,';
        $args[] = isset($fields['tareweight'])?$fields['tareweight']:0.00;

        $q .= 'discount,';
        $args[] = isset($fields['discount'])?$fields['discount']:0;

        $q .= 'discounttype,';
        $args[] = isset($fields['discounttype'])?$fields['discounttype']:0;

        $q .= 'unitofmeasure,';
        $args[] = isset($fields['unitofmeasure'])?$fields['unitofmeasure']:'';

        $q .= 'wicable,';
        $args[] = isset($fields['wicable'])?$fields['wicable']:0;

        $q .= 'qttyEnforced,';
        $args[] = isset($fields['qttyEnforced'])?$fields['qttyEnforced']:0;

        $q .= 'idEnforced,';
        $args[] = isset($fields['idEnforced'])?$fields['idEnforced']:0;

        $q .= 'cost,';
        $args[] = isset($fields['cost'])?$fields['cost']:0.00;

        $q .= 'inUse,';
        $args[] = isset($fields['inUse'])?$fields['inUse']:1;

        $q .= 'numflag,';
        $args[] = isset($fields['numflag'])?$fields['numflag']:0;

        $q .= 'subdept,';
        $args[] = isset($fields['subdept'])?$fields['subdept']:0;

        $q .= 'deposit,';
        $args[] = isset($fields['deposit'])?$fields['deposit']:0;

        $q .= 'local,';
        $args[] = isset($fields['local'])?$fields['local']:0;

        $q .= 'store_id,';
        $args[] = isset($fields['store_id'])?$fields['store_id']:0;

        $q .= 'modified) VALUES (';
        foreach($args as $a) $q .= '?,';
        $q .= $dbc->now().')';

        $insP = $dbc->prepare_statement($q);
        $insR = $dbc->exec_statement($insP, $args);
        if ($insR === false) {
            return false;
        }

        ProdUpdateModel::add($upc, $fields);

        return true;
    }

    public function save()
    {
        // using save() to update lane-side product records
        // 1) always write the record
        // 2) not create a prodUpdate entry
        $stack = debug_backtrace();
        $lane_push = false;
        if (isset($stack[1]) && $stack[1]['function'] == 'pushToLanes') {
            $lane_push = true;
        }

        // writing DB is not necessary
        if (!$this->record_changed && !$lane_push) {
            return true;
        } else if ($this->record_changed) {
            $this->modified(date('Y-m-d H:i:s'));
        }

        // call parent method to save the product record,
        // then add a corresponding prodUpdate record
        $try = parent::save();
        if ($try && !$lane_push && $this->connection->tableExists('prodUpdate')) {
            $update = new ProdUpdateModel($this->connection);
            $update->upc($this->upc());
            $update->logUpdate(ProdUpdateModel::UPDATE_EDIT);
        }

        return $try;
    }

    /**
      Log deletes to prodUpdate
      Delete corresponding records from other tables
    */
    public function delete()
    {
        $update = new ProdUpdateModel($this->connection);
        $update->upc($this->upc());
        $update->logUpdate(ProdUpdateModel::UPDATE_DELETE);

        $try = parent::delete();
        if ($try) {
            if ($this->connection->tableExists('prodExtra')) {
                $extra = new ProdExtraModel($this->connection);
                $extra->upc($this->upc());
                $extra->delete();
            }

            $user = new ProductUserModel($this->connection);
            $user->upc($this->upc());
            $user->delete();
        }

        return $try;
    }

    /* START ACCESSOR FUNCTIONS */

    public function upc()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["upc"])) {
                return $this->instance["upc"];
            } else if (isset($this->columns["upc"]["default"])) {
                return $this->columns["upc"]["default"];
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
                'left' => 'upc',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["upc"]) || $this->instance["upc"] != func_get_args(0)) {
                if (!isset($this->columns["upc"]["ignore_updates"]) || $this->columns["upc"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["upc"] = func_get_arg(0);
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

    public function formatted_name()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["formatted_name"])) {
                return $this->instance["formatted_name"];
            } else if (isset($this->columns["formatted_name"]["default"])) {
                return $this->columns["formatted_name"]["default"];
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
                'left' => 'formatted_name',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["formatted_name"]) || $this->instance["formatted_name"] != func_get_args(0)) {
                if (!isset($this->columns["formatted_name"]["ignore_updates"]) || $this->columns["formatted_name"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["formatted_name"] = func_get_arg(0);
        }
        return $this;
    }

    public function normal_price()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["normal_price"])) {
                return $this->instance["normal_price"];
            } else if (isset($this->columns["normal_price"]["default"])) {
                return $this->columns["normal_price"]["default"];
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
                'left' => 'normal_price',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["normal_price"]) || $this->instance["normal_price"] != func_get_args(0)) {
                if (!isset($this->columns["normal_price"]["ignore_updates"]) || $this->columns["normal_price"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["normal_price"] = func_get_arg(0);
        }
        return $this;
    }

    public function pricemethod()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["pricemethod"])) {
                return $this->instance["pricemethod"];
            } else if (isset($this->columns["pricemethod"]["default"])) {
                return $this->columns["pricemethod"]["default"];
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
                'left' => 'pricemethod',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["pricemethod"]) || $this->instance["pricemethod"] != func_get_args(0)) {
                if (!isset($this->columns["pricemethod"]["ignore_updates"]) || $this->columns["pricemethod"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["pricemethod"] = func_get_arg(0);
        }
        return $this;
    }

    public function groupprice()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["groupprice"])) {
                return $this->instance["groupprice"];
            } else if (isset($this->columns["groupprice"]["default"])) {
                return $this->columns["groupprice"]["default"];
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
                'left' => 'groupprice',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["groupprice"]) || $this->instance["groupprice"] != func_get_args(0)) {
                if (!isset($this->columns["groupprice"]["ignore_updates"]) || $this->columns["groupprice"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["groupprice"] = func_get_arg(0);
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

    public function special_price()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["special_price"])) {
                return $this->instance["special_price"];
            } else if (isset($this->columns["special_price"]["default"])) {
                return $this->columns["special_price"]["default"];
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
                'left' => 'special_price',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["special_price"]) || $this->instance["special_price"] != func_get_args(0)) {
                if (!isset($this->columns["special_price"]["ignore_updates"]) || $this->columns["special_price"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["special_price"] = func_get_arg(0);
        }
        return $this;
    }

    public function specialpricemethod()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["specialpricemethod"])) {
                return $this->instance["specialpricemethod"];
            } else if (isset($this->columns["specialpricemethod"]["default"])) {
                return $this->columns["specialpricemethod"]["default"];
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
                'left' => 'specialpricemethod',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["specialpricemethod"]) || $this->instance["specialpricemethod"] != func_get_args(0)) {
                if (!isset($this->columns["specialpricemethod"]["ignore_updates"]) || $this->columns["specialpricemethod"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["specialpricemethod"] = func_get_arg(0);
        }
        return $this;
    }

    public function specialgroupprice()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["specialgroupprice"])) {
                return $this->instance["specialgroupprice"];
            } else if (isset($this->columns["specialgroupprice"]["default"])) {
                return $this->columns["specialgroupprice"]["default"];
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
                'left' => 'specialgroupprice',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["specialgroupprice"]) || $this->instance["specialgroupprice"] != func_get_args(0)) {
                if (!isset($this->columns["specialgroupprice"]["ignore_updates"]) || $this->columns["specialgroupprice"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["specialgroupprice"] = func_get_arg(0);
        }
        return $this;
    }

    public function specialquantity()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["specialquantity"])) {
                return $this->instance["specialquantity"];
            } else if (isset($this->columns["specialquantity"]["default"])) {
                return $this->columns["specialquantity"]["default"];
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
                'left' => 'specialquantity',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["specialquantity"]) || $this->instance["specialquantity"] != func_get_args(0)) {
                if (!isset($this->columns["specialquantity"]["ignore_updates"]) || $this->columns["specialquantity"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["specialquantity"] = func_get_arg(0);
        }
        return $this;
    }

    public function start_date()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["start_date"])) {
                return $this->instance["start_date"];
            } else if (isset($this->columns["start_date"]["default"])) {
                return $this->columns["start_date"]["default"];
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
                'left' => 'start_date',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["start_date"]) || $this->instance["start_date"] != func_get_args(0)) {
                if (!isset($this->columns["start_date"]["ignore_updates"]) || $this->columns["start_date"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["start_date"] = func_get_arg(0);
        }
        return $this;
    }

    public function end_date()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["end_date"])) {
                return $this->instance["end_date"];
            } else if (isset($this->columns["end_date"]["default"])) {
                return $this->columns["end_date"]["default"];
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
                'left' => 'end_date',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["end_date"]) || $this->instance["end_date"] != func_get_args(0)) {
                if (!isset($this->columns["end_date"]["ignore_updates"]) || $this->columns["end_date"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["end_date"] = func_get_arg(0);
        }
        return $this;
    }

    public function department()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["department"])) {
                return $this->instance["department"];
            } else if (isset($this->columns["department"]["default"])) {
                return $this->columns["department"]["default"];
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
                'left' => 'department',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["department"]) || $this->instance["department"] != func_get_args(0)) {
                if (!isset($this->columns["department"]["ignore_updates"]) || $this->columns["department"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["department"] = func_get_arg(0);
        }
        return $this;
    }

    public function size()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["size"])) {
                return $this->instance["size"];
            } else if (isset($this->columns["size"]["default"])) {
                return $this->columns["size"]["default"];
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
                'left' => 'size',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["size"]) || $this->instance["size"] != func_get_args(0)) {
                if (!isset($this->columns["size"]["ignore_updates"]) || $this->columns["size"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["size"] = func_get_arg(0);
        }
        return $this;
    }

    public function tax()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["tax"])) {
                return $this->instance["tax"];
            } else if (isset($this->columns["tax"]["default"])) {
                return $this->columns["tax"]["default"];
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
                'left' => 'tax',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["tax"]) || $this->instance["tax"] != func_get_args(0)) {
                if (!isset($this->columns["tax"]["ignore_updates"]) || $this->columns["tax"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["tax"] = func_get_arg(0);
        }
        return $this;
    }

    public function foodstamp()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["foodstamp"])) {
                return $this->instance["foodstamp"];
            } else if (isset($this->columns["foodstamp"]["default"])) {
                return $this->columns["foodstamp"]["default"];
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
                'left' => 'foodstamp',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["foodstamp"]) || $this->instance["foodstamp"] != func_get_args(0)) {
                if (!isset($this->columns["foodstamp"]["ignore_updates"]) || $this->columns["foodstamp"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["foodstamp"] = func_get_arg(0);
        }
        return $this;
    }

    public function scale()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["scale"])) {
                return $this->instance["scale"];
            } else if (isset($this->columns["scale"]["default"])) {
                return $this->columns["scale"]["default"];
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
                'left' => 'scale',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["scale"]) || $this->instance["scale"] != func_get_args(0)) {
                if (!isset($this->columns["scale"]["ignore_updates"]) || $this->columns["scale"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["scale"] = func_get_arg(0);
        }
        return $this;
    }

    public function scaleprice()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["scaleprice"])) {
                return $this->instance["scaleprice"];
            } else if (isset($this->columns["scaleprice"]["default"])) {
                return $this->columns["scaleprice"]["default"];
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
                'left' => 'scaleprice',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["scaleprice"]) || $this->instance["scaleprice"] != func_get_args(0)) {
                if (!isset($this->columns["scaleprice"]["ignore_updates"]) || $this->columns["scaleprice"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["scaleprice"] = func_get_arg(0);
        }
        return $this;
    }

    public function mixmatchcode()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["mixmatchcode"])) {
                return $this->instance["mixmatchcode"];
            } else if (isset($this->columns["mixmatchcode"]["default"])) {
                return $this->columns["mixmatchcode"]["default"];
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
                'left' => 'mixmatchcode',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["mixmatchcode"]) || $this->instance["mixmatchcode"] != func_get_args(0)) {
                if (!isset($this->columns["mixmatchcode"]["ignore_updates"]) || $this->columns["mixmatchcode"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["mixmatchcode"] = func_get_arg(0);
        }
        return $this;
    }

    public function modified()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["modified"])) {
                return $this->instance["modified"];
            } else if (isset($this->columns["modified"]["default"])) {
                return $this->columns["modified"]["default"];
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
                'left' => 'modified',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["modified"]) || $this->instance["modified"] != func_get_args(0)) {
                if (!isset($this->columns["modified"]["ignore_updates"]) || $this->columns["modified"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["modified"] = func_get_arg(0);
        }
        return $this;
    }

    public function advertised()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["advertised"])) {
                return $this->instance["advertised"];
            } else if (isset($this->columns["advertised"]["default"])) {
                return $this->columns["advertised"]["default"];
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
                'left' => 'advertised',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["advertised"]) || $this->instance["advertised"] != func_get_args(0)) {
                if (!isset($this->columns["advertised"]["ignore_updates"]) || $this->columns["advertised"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["advertised"] = func_get_arg(0);
        }
        return $this;
    }

    public function tareweight()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["tareweight"])) {
                return $this->instance["tareweight"];
            } else if (isset($this->columns["tareweight"]["default"])) {
                return $this->columns["tareweight"]["default"];
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
                'left' => 'tareweight',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["tareweight"]) || $this->instance["tareweight"] != func_get_args(0)) {
                if (!isset($this->columns["tareweight"]["ignore_updates"]) || $this->columns["tareweight"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["tareweight"] = func_get_arg(0);
        }
        return $this;
    }

    public function discount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["discount"])) {
                return $this->instance["discount"];
            } else if (isset($this->columns["discount"]["default"])) {
                return $this->columns["discount"]["default"];
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
                'left' => 'discount',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["discount"]) || $this->instance["discount"] != func_get_args(0)) {
                if (!isset($this->columns["discount"]["ignore_updates"]) || $this->columns["discount"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["discount"] = func_get_arg(0);
        }
        return $this;
    }

    public function discounttype()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["discounttype"])) {
                return $this->instance["discounttype"];
            } else if (isset($this->columns["discounttype"]["default"])) {
                return $this->columns["discounttype"]["default"];
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
                'left' => 'discounttype',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["discounttype"]) || $this->instance["discounttype"] != func_get_args(0)) {
                if (!isset($this->columns["discounttype"]["ignore_updates"]) || $this->columns["discounttype"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["discounttype"] = func_get_arg(0);
        }
        return $this;
    }

    public function line_item_discountable()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["line_item_discountable"])) {
                return $this->instance["line_item_discountable"];
            } else if (isset($this->columns["line_item_discountable"]["default"])) {
                return $this->columns["line_item_discountable"]["default"];
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
                'left' => 'line_item_discountable',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["line_item_discountable"]) || $this->instance["line_item_discountable"] != func_get_args(0)) {
                if (!isset($this->columns["line_item_discountable"]["ignore_updates"]) || $this->columns["line_item_discountable"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["line_item_discountable"] = func_get_arg(0);
        }
        return $this;
    }

    public function unitofmeasure()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["unitofmeasure"])) {
                return $this->instance["unitofmeasure"];
            } else if (isset($this->columns["unitofmeasure"]["default"])) {
                return $this->columns["unitofmeasure"]["default"];
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
                'left' => 'unitofmeasure',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["unitofmeasure"]) || $this->instance["unitofmeasure"] != func_get_args(0)) {
                if (!isset($this->columns["unitofmeasure"]["ignore_updates"]) || $this->columns["unitofmeasure"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["unitofmeasure"] = func_get_arg(0);
        }
        return $this;
    }

    public function wicable()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["wicable"])) {
                return $this->instance["wicable"];
            } else if (isset($this->columns["wicable"]["default"])) {
                return $this->columns["wicable"]["default"];
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
                'left' => 'wicable',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["wicable"]) || $this->instance["wicable"] != func_get_args(0)) {
                if (!isset($this->columns["wicable"]["ignore_updates"]) || $this->columns["wicable"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["wicable"] = func_get_arg(0);
        }
        return $this;
    }

    public function qttyEnforced()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["qttyEnforced"])) {
                return $this->instance["qttyEnforced"];
            } else if (isset($this->columns["qttyEnforced"]["default"])) {
                return $this->columns["qttyEnforced"]["default"];
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
                'left' => 'qttyEnforced',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["qttyEnforced"]) || $this->instance["qttyEnforced"] != func_get_args(0)) {
                if (!isset($this->columns["qttyEnforced"]["ignore_updates"]) || $this->columns["qttyEnforced"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["qttyEnforced"] = func_get_arg(0);
        }
        return $this;
    }

    public function idEnforced()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["idEnforced"])) {
                return $this->instance["idEnforced"];
            } else if (isset($this->columns["idEnforced"]["default"])) {
                return $this->columns["idEnforced"]["default"];
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
                'left' => 'idEnforced',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["idEnforced"]) || $this->instance["idEnforced"] != func_get_args(0)) {
                if (!isset($this->columns["idEnforced"]["ignore_updates"]) || $this->columns["idEnforced"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["idEnforced"] = func_get_arg(0);
        }
        return $this;
    }

    public function cost()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["cost"])) {
                return $this->instance["cost"];
            } else if (isset($this->columns["cost"]["default"])) {
                return $this->columns["cost"]["default"];
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
                'left' => 'cost',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["cost"]) || $this->instance["cost"] != func_get_args(0)) {
                if (!isset($this->columns["cost"]["ignore_updates"]) || $this->columns["cost"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["cost"] = func_get_arg(0);
        }
        return $this;
    }

    public function inUse()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["inUse"])) {
                return $this->instance["inUse"];
            } else if (isset($this->columns["inUse"]["default"])) {
                return $this->columns["inUse"]["default"];
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
                'left' => 'inUse',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["inUse"]) || $this->instance["inUse"] != func_get_args(0)) {
                if (!isset($this->columns["inUse"]["ignore_updates"]) || $this->columns["inUse"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["inUse"] = func_get_arg(0);
        }
        return $this;
    }

    public function numflag()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["numflag"])) {
                return $this->instance["numflag"];
            } else if (isset($this->columns["numflag"]["default"])) {
                return $this->columns["numflag"]["default"];
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
                'left' => 'numflag',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["numflag"]) || $this->instance["numflag"] != func_get_args(0)) {
                if (!isset($this->columns["numflag"]["ignore_updates"]) || $this->columns["numflag"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["numflag"] = func_get_arg(0);
        }
        return $this;
    }

    public function subdept()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["subdept"])) {
                return $this->instance["subdept"];
            } else if (isset($this->columns["subdept"]["default"])) {
                return $this->columns["subdept"]["default"];
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
                'left' => 'subdept',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["subdept"]) || $this->instance["subdept"] != func_get_args(0)) {
                if (!isset($this->columns["subdept"]["ignore_updates"]) || $this->columns["subdept"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["subdept"] = func_get_arg(0);
        }
        return $this;
    }

    public function deposit()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["deposit"])) {
                return $this->instance["deposit"];
            } else if (isset($this->columns["deposit"]["default"])) {
                return $this->columns["deposit"]["default"];
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
                'left' => 'deposit',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["deposit"]) || $this->instance["deposit"] != func_get_args(0)) {
                if (!isset($this->columns["deposit"]["ignore_updates"]) || $this->columns["deposit"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["deposit"] = func_get_arg(0);
        }
        return $this;
    }

    public function local()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["local"])) {
                return $this->instance["local"];
            } else if (isset($this->columns["local"]["default"])) {
                return $this->columns["local"]["default"];
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
                'left' => 'local',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["local"]) || $this->instance["local"] != func_get_args(0)) {
                if (!isset($this->columns["local"]["ignore_updates"]) || $this->columns["local"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["local"] = func_get_arg(0);
        }
        return $this;
    }

    public function store_id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["store_id"])) {
                return $this->instance["store_id"];
            } else if (isset($this->columns["store_id"]["default"])) {
                return $this->columns["store_id"]["default"];
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
                'left' => 'store_id',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["store_id"]) || $this->instance["store_id"] != func_get_args(0)) {
                if (!isset($this->columns["store_id"]["ignore_updates"]) || $this->columns["store_id"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["store_id"] = func_get_arg(0);
        }
        return $this;
    }

    public function default_vendor_id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["default_vendor_id"])) {
                return $this->instance["default_vendor_id"];
            } else if (isset($this->columns["default_vendor_id"]["default"])) {
                return $this->columns["default_vendor_id"]["default"];
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
                'left' => 'default_vendor_id',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["default_vendor_id"]) || $this->instance["default_vendor_id"] != func_get_args(0)) {
                if (!isset($this->columns["default_vendor_id"]["ignore_updates"]) || $this->columns["default_vendor_id"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["default_vendor_id"] = func_get_arg(0);
        }
        return $this;
    }

    public function current_origin_id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["current_origin_id"])) {
                return $this->instance["current_origin_id"];
            } else if (isset($this->columns["current_origin_id"]["default"])) {
                return $this->columns["current_origin_id"]["default"];
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
                'left' => 'current_origin_id',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["current_origin_id"]) || $this->instance["current_origin_id"] != func_get_args(0)) {
                if (!isset($this->columns["current_origin_id"]["ignore_updates"]) || $this->columns["current_origin_id"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["current_origin_id"] = func_get_arg(0);
        }
        return $this;
    }

    public function auto_par()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["auto_par"])) {
                return $this->instance["auto_par"];
            } else if (isset($this->columns["auto_par"]["default"])) {
                return $this->columns["auto_par"]["default"];
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
                'left' => 'auto_par',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["auto_par"]) || $this->instance["auto_par"] != func_get_args(0)) {
                if (!isset($this->columns["auto_par"]["ignore_updates"]) || $this->columns["auto_par"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["auto_par"] = func_get_arg(0);
        }
        return $this;
    }

    public function id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["id"])) {
                return $this->instance["id"];
            } else if (isset($this->columns["id"]["default"])) {
                return $this->columns["id"]["default"];
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
                'left' => 'id',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["id"]) || $this->instance["id"] != func_get_args(0)) {
                if (!isset($this->columns["id"]["ignore_updates"]) || $this->columns["id"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["id"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

