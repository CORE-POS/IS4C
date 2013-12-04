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
    'upc' => array('type'=>'VARCHAR(13)','index'=>True),
    'description'=>array('type'=>'VARCHAR(30)','index'=>True),
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
    'department'=>array('type'=>'SMALLINT','index'=>True),
    'size'=>array('type'=>'VARCHAR(9)'),
    'tax'=>array('type'=>'SMALLINT'),
    'foodstamp'=>array('type'=>'TINYINT'),
    'scale'=>array('type'=>'TINYINT'),
    'scaleprice'=>array('type'=>'MONEY'),
    'mixmatchcode'=>array('type'=>'VARCHAR(13)'),
    'modified'=>array('type'=>'DATETIME'),
    'advertised'=>array('type'=>'TINYINT'),
    'tareweight'=>array('type'=>'DOUBLE'),
    'discount'=>array('type'=>'SMALLINT'),
    'discounttype'=>array('type'=>'TINYINT'),
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
    'id'=>array('type'=>'INT','default'=>0,'primary_key'=>True,'increment'=>True)
    );

    protected $unique = array('upc');

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
                    if ($name === 0 || $name === True) {
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
            return False;
        }
        if (!is_int($upc) && !ctype_digit($upc)) {
            return False;
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

    /* START ACCESSOR FUNCTIONS */

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

    public function normal_price()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["normal_price"])) {
                return $this->instance["normal_price"];
            } elseif(isset($this->columns["normal_price"]["default"])) {
                return $this->columns["normal_price"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["normal_price"] = func_get_arg(0);
        }
    }

    public function pricemethod()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["pricemethod"])) {
                return $this->instance["pricemethod"];
            } elseif(isset($this->columns["pricemethod"]["default"])) {
                return $this->columns["pricemethod"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["pricemethod"] = func_get_arg(0);
        }
    }

    public function groupprice()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["groupprice"])) {
                return $this->instance["groupprice"];
            } elseif(isset($this->columns["groupprice"]["default"])) {
                return $this->columns["groupprice"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["groupprice"] = func_get_arg(0);
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

    public function special_price()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["special_price"])) {
                return $this->instance["special_price"];
            } elseif(isset($this->columns["special_price"]["default"])) {
                return $this->columns["special_price"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["special_price"] = func_get_arg(0);
        }
    }

    public function specialpricemethod()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["specialpricemethod"])) {
                return $this->instance["specialpricemethod"];
            } elseif(isset($this->columns["specialpricemethod"]["default"])) {
                return $this->columns["specialpricemethod"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["specialpricemethod"] = func_get_arg(0);
        }
    }

    public function specialgroupprice()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["specialgroupprice"])) {
                return $this->instance["specialgroupprice"];
            } elseif(isset($this->columns["specialgroupprice"]["default"])) {
                return $this->columns["specialgroupprice"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["specialgroupprice"] = func_get_arg(0);
        }
    }

    public function specialquantity()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["specialquantity"])) {
                return $this->instance["specialquantity"];
            } elseif(isset($this->columns["specialquantity"]["default"])) {
                return $this->columns["specialquantity"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["specialquantity"] = func_get_arg(0);
        }
    }

    public function start_date()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["start_date"])) {
                return $this->instance["start_date"];
            } elseif(isset($this->columns["start_date"]["default"])) {
                return $this->columns["start_date"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["start_date"] = func_get_arg(0);
        }
    }

    public function end_date()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["end_date"])) {
                return $this->instance["end_date"];
            } elseif(isset($this->columns["end_date"]["default"])) {
                return $this->columns["end_date"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["end_date"] = func_get_arg(0);
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

    public function size()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["size"])) {
                return $this->instance["size"];
            } elseif(isset($this->columns["size"]["default"])) {
                return $this->columns["size"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["size"] = func_get_arg(0);
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

    public function scaleprice()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["scaleprice"])) {
                return $this->instance["scaleprice"];
            } elseif(isset($this->columns["scaleprice"]["default"])) {
                return $this->columns["scaleprice"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["scaleprice"] = func_get_arg(0);
        }
    }

    public function mixmatchcode()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["mixmatchcode"])) {
                return $this->instance["mixmatchcode"];
            } elseif(isset($this->columns["mixmatchcode"]["default"])) {
                return $this->columns["mixmatchcode"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["mixmatchcode"] = func_get_arg(0);
        }
    }

    public function modified()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["modified"])) {
                return $this->instance["modified"];
            } elseif(isset($this->columns["modified"]["default"])) {
                return $this->columns["modified"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["modified"] = func_get_arg(0);
        }
    }

    public function advertised()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["advertised"])) {
                return $this->instance["advertised"];
            } elseif(isset($this->columns["advertised"]["default"])) {
                return $this->columns["advertised"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["advertised"] = func_get_arg(0);
        }
    }

    public function tareweight()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["tareweight"])) {
                return $this->instance["tareweight"];
            } elseif(isset($this->columns["tareweight"]["default"])) {
                return $this->columns["tareweight"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["tareweight"] = func_get_arg(0);
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

    public function unitofmeasure()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["unitofmeasure"])) {
                return $this->instance["unitofmeasure"];
            } elseif(isset($this->columns["unitofmeasure"]["default"])) {
                return $this->columns["unitofmeasure"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["unitofmeasure"] = func_get_arg(0);
        }
    }

    public function wicable()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["wicable"])) {
                return $this->instance["wicable"];
            } elseif(isset($this->columns["wicable"]["default"])) {
                return $this->columns["wicable"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["wicable"] = func_get_arg(0);
        }
    }

    public function qttyEnforced()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["qttyEnforced"])) {
                return $this->instance["qttyEnforced"];
            } elseif(isset($this->columns["qttyEnforced"]["default"])) {
                return $this->columns["qttyEnforced"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["qttyEnforced"] = func_get_arg(0);
        }
    }

    public function idEnforced()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["idEnforced"])) {
                return $this->instance["idEnforced"];
            } elseif(isset($this->columns["idEnforced"]["default"])) {
                return $this->columns["idEnforced"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["idEnforced"] = func_get_arg(0);
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

    public function inUse()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["inUse"])) {
                return $this->instance["inUse"];
            } elseif(isset($this->columns["inUse"]["default"])) {
                return $this->columns["inUse"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["inUse"] = func_get_arg(0);
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

    public function subdept()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["subdept"])) {
                return $this->instance["subdept"];
            } elseif(isset($this->columns["subdept"]["default"])) {
                return $this->columns["subdept"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["subdept"] = func_get_arg(0);
        }
    }

    public function deposit()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["deposit"])) {
                return $this->instance["deposit"];
            } elseif(isset($this->columns["deposit"]["default"])) {
                return $this->columns["deposit"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["deposit"] = func_get_arg(0);
        }
    }

    public function local()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["local"])) {
                return $this->instance["local"];
            } elseif(isset($this->columns["local"]["default"])) {
                return $this->columns["local"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["local"] = func_get_arg(0);
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
    /* END ACCESSOR FUNCTIONS */
}

