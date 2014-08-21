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
  @class BatchListModel
*/
class BatchListModel extends BasicModel 
{

    protected $name = "batchList";

    protected $columns = array(
    'listID' => array('type'=>'INT', 'primary_key'=>True, 'increment'=>True),
    'upc' => array('type'=>'VARCHAR(13)','index'=>True),
    'batchID' => array('type'=>'INT','index'=>True),
    'salePrice' => array('type'=>'MONEY'),
    'active' => array('type'=>'TINYINT'),
    'pricemethod' => array('type'=>'SMALLINT','default'=>0),
    'quantity' => array('type'=>'SMALLINT','default'=>0)
    );

    protected $unique = array('batchID','upc');

    /* START ACCESSOR FUNCTIONS */

    public function listID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["listID"])) {
                return $this->instance["listID"];
            } else if (isset($this->columns["listID"]["default"])) {
                return $this->columns["listID"]["default"];
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
                'left' => 'listID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["listID"]) || $this->instance["listID"] != func_get_args(0)) {
                if (!isset($this->columns["listID"]["ignore_updates"]) || $this->columns["listID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["listID"] = func_get_arg(0);
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

    public function active()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["active"])) {
                return $this->instance["active"];
            } else if (isset($this->columns["active"]["default"])) {
                return $this->columns["active"]["default"];
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
                'left' => 'active',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["active"]) || $this->instance["active"] != func_get_args(0)) {
                if (!isset($this->columns["active"]["ignore_updates"]) || $this->columns["active"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["active"] = func_get_arg(0);
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
    /* END ACCESSOR FUNCTIONS */
}

