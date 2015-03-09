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
    include(dirname(__FILE__).'/../../FannieDB.php');
}
if (!class_exists('BarcodeLib')) {
    include(dirname(__FILE__).'/../../../lib/BarcodeLib.php');
}

class ProdUpdateModel extends BasicModel
{
    protected $name = 'prodUpdate';

    protected $preferred_db = 'op';

    protected $columns = array(
    'prodUpdateID' => array('type'=>'BIGINT UNSIGNED', 'primary_key'=>true, 'increment'=>true),
    'updateType' => array('type'=>'VARCHAR(20)'),
    'upc' => array('type'=>'VARCHAR(13)', 'index'=>true),
    'description' => array('type'=>'VARCHAR(50)'),
    'price' => array('type'=>'MONEY'),
    'salePrice' => array('type'=>'MONEY'),
    'cost' => array('type'=>'MONEY'),
    'dept' => array('type'=>'INT'),
    'tax' => array('type'=>'TINYINT'),
    'fs' => array('type'=>'TINYINT'),
    'scale' => array('type'=>'TINYINT'),
    'likeCode' => array('type'=>'INT'),
    'modified' => array('type'=>'DATETIME'),
    'user' => array('type'=>'INT'),
    'forceQty' => array('type'=>'TINYINT'),
    'noDisc' => array('type'=>'TINYINT'),
    'inUse' => array('type'=>'TINYINT'),
    );

    public function doc()
    {
        return '
Table: prodUpdate

Columns:
    prodUpdateID int
    updateType varchar
    upc int or varchar, dbms dependent
    description varchar
    price dbms currency
    salePrice dbms currency
    cost dbms currency
    dept int
    tax bit
    fs bit
    scale bit
    likeCode int
    modified datetime
    user int
    forceQty bit
    noDisc bit
    inUse bit

Depends on:
    products (table)

Use:
In theory, every time a product is change in fannie,
the update is logged here. In practice, not all
tools/cron jobs/sprocs/etc actually do. They probably
        ';
    }

    const UPDATE_EDIT = 'EDIT';
    const UPDATE_DELETE = 'DELETE';
    const UPDATE_BATCH = 'SALE BATCH';
    const UPDATE_PC_BATCH = 'PRICE BATCH';

    public function normalize($db_name, $mode=BasicModel::NORMALIZE_MODE_CHECK, $doCreate=false)
    {
        $adds = 0;

        $this->name = 'prodUpdate';
        $chk = parent::normalize($db_name, $mode, $doCreate);
        if ($chk !== false) {
            $adds += $chk;
        }

        $this->connection = FannieDB::get($db_name);
        if ($this->connection->tableExists('prodUpdateArchive')) {
            $this->name = 'prodUpdateArchive';
            $chk = parent::normalize($db_name, $mode, false);
            if ($chk !== false) {
                $adds += $chk;
            }
        }

        return $adds;
    }

    public function logUpdate($type='UNKNOWN', $user=false)
    {
        if (!$user) {
            $user = FannieAuth::getUID(FannieAuth::checkLogin());
        }

        $product = new ProductsModel($this->connection);
        $product->upc($this->upc());
        $exists = $product->load();
        if (!$exists) {
            return false;
        }

        $this->updateType($type);
        $this->description($product->description());
        $this->price($product->normal_price());
        $this->salePrice($product->special_price());
        $this->cost($product->cost());
        $this->dept($product->department());
        $this->tax($product->tax());
        $this->fs($product->foodstamp());
        $this->scale($product->scale());
        $this->modified($product->modified());
        $this->forceQty($product->qttyEnforced());
        $this->noDisc($product->discount());
        $this->inUse($product->inUse());
        $this->user($user);

        $likecode = 0;
        if ($this->connection->table_exists('upcLike')) {
            $upcQ = $this->connection->prepare('SELECT likeCode FROM upcLike WHERE upc=?');
            $upcR = $this->connection->execute($upcQ, array($this->upc()));
            if ($this->connection->num_rows($upcR) > 0) {
                $upcW = $this->connection->fetch_row($upcR);
                $this->likeCode($upcW['likeCode']);
            }
        }

        $this->save();

        return true;
    }

    /**
      Log updates to many products at once
      @param $upcs [array] of UPCs
      @param $type [string] update type
      @param $user [string] username
      @return [boolean] success
    */
    public function logManyUpdates($upcs, $type='UNKNOWN', $user=false)
    {
        if (count($upcs) == 0) {
            // nothing to log
            return true;
        }
        $col_map = array(
            'upc' => 'p.upc',
            'description' => 'description',
            'price' => 'normal_price',
            'salePrice' => 'special_price',
            'cost' => 'cost',
            'dept' => 'department',
            'tax' => 'tax',
            'fs' => 'foodstamp',
            'scale' => 'scale',
            'modified' => 'modified',
            'forceQty' => 'qttyEnforced',
            'noDisc' => 'discount',
            'inUse' => 'inUse',
            'likeCode' => 'likeCode',
        );

        if (!$user) {
            $user = FannieAuth::getUID(FannieAuth::checkLogin());
        }

        $select_cols = '?,?,';
        $insert_cols = 'updateType,' . $this->connection->identifier_escape('user') . ',';
        foreach ($col_map as $insert => $select) {
            $insert_cols .= $this->connection->identifier_escape($insert) . ',';
            // identifier escape does not handle alias prefix
            $select_cols .= ($select == 'p.upc' ? $select :$this->connection->identifier_escape($select)) . ',';
        }
        $insert_cols = substr($insert_cols, 0, strlen($insert_cols)-1);
        $select_cols = substr($select_cols, 0, strlen($select_cols)-1);
        
        $args = array($type, $user);
        $upc_in = '';
        foreach ($upcs as $upc) {
            $args[] = $upc;
            $upc_in .= '?,';
        }
        $upc_in = substr($upc_in, 0, strlen($upc_in)-1);

        $query = 'INSERT INTO prodUpdate (' . $insert_cols . ')
                  SELECT ' . $select_cols . '
                  FROM products AS p
                    LEFT JOIN upcLike AS u ON p.upc=u.upc
                  WHERE p.upc IN (' . $upc_in . ')';
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, $args);

        return ($res) ? true : false;
    }

    /* START ACCESSOR FUNCTIONS */

    public function prodUpdateID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["prodUpdateID"])) {
                return $this->instance["prodUpdateID"];
            } else if (isset($this->columns["prodUpdateID"]["default"])) {
                return $this->columns["prodUpdateID"]["default"];
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
                'left' => 'prodUpdateID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["prodUpdateID"]) || $this->instance["prodUpdateID"] != func_get_args(0)) {
                if (!isset($this->columns["prodUpdateID"]["ignore_updates"]) || $this->columns["prodUpdateID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["prodUpdateID"] = func_get_arg(0);
        }
        return $this;
    }

    public function updateType()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["updateType"])) {
                return $this->instance["updateType"];
            } else if (isset($this->columns["updateType"]["default"])) {
                return $this->columns["updateType"]["default"];
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
                'left' => 'updateType',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["updateType"]) || $this->instance["updateType"] != func_get_args(0)) {
                if (!isset($this->columns["updateType"]["ignore_updates"]) || $this->columns["updateType"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["updateType"] = func_get_arg(0);
        }
        return $this;
    }

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

    public function price()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["price"])) {
                return $this->instance["price"];
            } else if (isset($this->columns["price"]["default"])) {
                return $this->columns["price"]["default"];
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
                'left' => 'price',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["price"]) || $this->instance["price"] != func_get_args(0)) {
                if (!isset($this->columns["price"]["ignore_updates"]) || $this->columns["price"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["price"] = func_get_arg(0);
        }
        return $this;
    }

    public function salePrice()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["salePrice"])) {
                return $this->instance["salePrice"];
            } else if (isset($this->columns["salePrice"]["default"])) {
                return $this->columns["salePrice"]["default"];
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
                'left' => 'salePrice',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["salePrice"]) || $this->instance["salePrice"] != func_get_args(0)) {
                if (!isset($this->columns["salePrice"]["ignore_updates"]) || $this->columns["salePrice"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["salePrice"] = func_get_arg(0);
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

    public function dept()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dept"])) {
                return $this->instance["dept"];
            } else if (isset($this->columns["dept"]["default"])) {
                return $this->columns["dept"]["default"];
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
                'left' => 'dept',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["dept"]) || $this->instance["dept"] != func_get_args(0)) {
                if (!isset($this->columns["dept"]["ignore_updates"]) || $this->columns["dept"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["dept"] = func_get_arg(0);
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

    public function fs()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["fs"])) {
                return $this->instance["fs"];
            } else if (isset($this->columns["fs"]["default"])) {
                return $this->columns["fs"]["default"];
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
                'left' => 'fs',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["fs"]) || $this->instance["fs"] != func_get_args(0)) {
                if (!isset($this->columns["fs"]["ignore_updates"]) || $this->columns["fs"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["fs"] = func_get_arg(0);
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

    public function likeCode()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["likeCode"])) {
                return $this->instance["likeCode"];
            } else if (isset($this->columns["likeCode"]["default"])) {
                return $this->columns["likeCode"]["default"];
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
                'left' => 'likeCode',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["likeCode"]) || $this->instance["likeCode"] != func_get_args(0)) {
                if (!isset($this->columns["likeCode"]["ignore_updates"]) || $this->columns["likeCode"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["likeCode"] = func_get_arg(0);
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

    public function user()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["user"])) {
                return $this->instance["user"];
            } else if (isset($this->columns["user"]["default"])) {
                return $this->columns["user"]["default"];
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
                'left' => 'user',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["user"]) || $this->instance["user"] != func_get_args(0)) {
                if (!isset($this->columns["user"]["ignore_updates"]) || $this->columns["user"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["user"] = func_get_arg(0);
        }
        return $this;
    }

    public function forceQty()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["forceQty"])) {
                return $this->instance["forceQty"];
            } else if (isset($this->columns["forceQty"]["default"])) {
                return $this->columns["forceQty"]["default"];
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
                'left' => 'forceQty',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["forceQty"]) || $this->instance["forceQty"] != func_get_args(0)) {
                if (!isset($this->columns["forceQty"]["ignore_updates"]) || $this->columns["forceQty"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["forceQty"] = func_get_arg(0);
        }
        return $this;
    }

    public function noDisc()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["noDisc"])) {
                return $this->instance["noDisc"];
            } else if (isset($this->columns["noDisc"]["default"])) {
                return $this->columns["noDisc"]["default"];
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
                'left' => 'noDisc',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["noDisc"]) || $this->instance["noDisc"] != func_get_args(0)) {
                if (!isset($this->columns["noDisc"]["ignore_updates"]) || $this->columns["noDisc"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["noDisc"] = func_get_arg(0);
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
    /* END ACCESSOR FUNCTIONS */

}

