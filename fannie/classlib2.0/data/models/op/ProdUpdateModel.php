<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of CORE-POS.

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
    'storeID' => array('type'=>'INT', 'default'=>0),
    'description' => array('type'=>'VARCHAR(50)'),
    'price' => array('type'=>'MONEY'),
    'salePrice' => array('type'=>'MONEY'),
    'cost' => array('type'=>'DECIMAL(10,3)'),
    'dept' => array('type'=>'INT'),
    'tax' => array('type'=>'TINYINT'),
    'fs' => array('type'=>'TINYINT'),
    'wic' => array('type'=>'TINYINT'),
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
Depends on:
* products (table)

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
        if ($this->storeID()) {
            $product->store_id($this->storeID());
        }
        $exists = $product->load();
        if (!$exists) {
            return false;
        }

        $this->storeID($product->store_id());
        $this->updateType($type);
        $this->description($product->description());
        $this->price($product->normal_price());
        $this->salePrice($product->special_price());
        $this->cost($product->cost());
        $this->dept($product->department());
        $this->tax($product->tax());
        $this->fs($product->foodstamp());
        $this->wic($product->wicable());
        $this->scale($product->scale());
        $this->modified(date('Y-m-d H:i:s'));
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

    private $col_map = array(
        'upc' => 'p.upc',
        'description' => 'description',
        'price' => 'normal_price',
        'salePrice' => 'special_price',
        'cost' => 'cost',
        'dept' => 'department',
        'tax' => 'tax',
        'fs' => 'foodstamp',
        'wic' => 'wicable',
        'scale' => 'scale',
        'modified' => 'modified',
        'forceQty' => 'qttyEnforced',
        'noDisc' => 'discount',
        'inUse' => 'inUse',
        'likeCode' => 'likeCode',
        'storeID' => 'store_id',
    );

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

        if (!$user) {
            $user = FannieAuth::getUID(FannieAuth::checkLogin());
        }

        $select_cols = '?,?,';
        $insert_cols = 'updateType,' . $this->connection->identifierEscape('user') . ',';
        foreach ($this->col_map as $insert => $select) {
            $insert_cols .= $this->connection->identifierEscape($insert) . ',';
            // identifier escape does not handle alias prefix
            $select_cols .= ($select == 'p.upc' ? $select :$this->connection->identifierEscape($select)) . ',';
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
}

