<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

/**
  @class StoreBatchMapModel
*/
class StoreBatchMapModel extends BasicModel
{
    protected $name = "StoreBatchMap";
    protected $preferred_db = 'op';

    protected $columns = array(
    'storeBatchMapID' => array('type'=>'INT', 'index'=>true, 'increment'=>true),
    'storeID' => array('type'=>'INT', 'primary_key'=>true),
    'batchID' => array('type'=>'INT', 'primary_key'=>true),
    );

    /**
      Assign batch to all stores
      @param $batchID [int] batch ID
    */
    public static function initBatch($batchID)
    {
        $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
        $map = new StoreBatchMapModel($dbc);
        $stores = new StoresModel($dbc);
        foreach ($stores->find() as $s) {
            $map->storeID($s->storeID());
            $map->batchID($batchID);
            $map->save(); 
        }
    }

    /* START ACCESSOR FUNCTIONS */

    public function storeBatchMapID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["storeBatchMapID"])) {
                return $this->instance["storeBatchMapID"];
            } else if (isset($this->columns["storeBatchMapID"]["default"])) {
                return $this->columns["storeBatchMapID"]["default"];
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
                'left' => 'storeBatchMapID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["storeBatchMapID"]) || $this->instance["storeBatchMapID"] != func_get_args(0)) {
                if (!isset($this->columns["storeBatchMapID"]["ignore_updates"]) || $this->columns["storeBatchMapID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["storeBatchMapID"] = func_get_arg(0);
        }
        return $this;
    }

    public function storeID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["storeID"])) {
                return $this->instance["storeID"];
            } else if (isset($this->columns["storeID"]["default"])) {
                return $this->columns["storeID"]["default"];
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
                'left' => 'storeID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["storeID"]) || $this->instance["storeID"] != func_get_args(0)) {
                if (!isset($this->columns["storeID"]["ignore_updates"]) || $this->columns["storeID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["storeID"] = func_get_arg(0);
        }
        return $this;
    }

    public function batchID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["batchID"])) {
                return $this->instance["batchID"];
            } else if (isset($this->columns["batchID"]["default"])) {
                return $this->columns["batchID"]["default"];
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
                'left' => 'batchID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["batchID"]) || $this->instance["batchID"] != func_get_args(0)) {
                if (!isset($this->columns["batchID"]["ignore_updates"]) || $this->columns["batchID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["batchID"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

