<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
  @class ProductOriginsMapModel
*/
class ProductOriginsMapModel extends BasicModel
{

    protected $name = "ProductOriginsMap";
    protected $preferred_db = 'op';

    protected $columns = array(
    'originID' => array('type'=>'INT', 'primary_key'=>true),
    'upc' => array('type'=>'VARCHAR(13)', 'primary_key'=>true),
    'active' => array('type'=>'TINYINT', 'default'=>1),
    );
    
    public function doc()
    {
        return '
Table: ProductOriginsMap

Columns:
    upc int
    originID int
    active tinyint

Depends on:
    origins
    products

Use:
Maps products to multiple origins. A product
has a single "current" origin via
products.current_origin_id but a 
product from multiple locations 
could also occur. Produce is the most
common use case.
        ';
    }

    /* START ACCESSOR FUNCTIONS */

    public function originID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["originID"])) {
                return $this->instance["originID"];
            } else if (isset($this->columns["originID"]["default"])) {
                return $this->columns["originID"]["default"];
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
                'left' => 'originID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["originID"]) || $this->instance["originID"] != func_get_args(0)) {
                if (!isset($this->columns["originID"]["ignore_updates"]) || $this->columns["originID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["originID"] = func_get_arg(0);
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
    /* END ACCESSOR FUNCTIONS */
}

