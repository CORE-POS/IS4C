<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
  @class HouseCouponItemsModel
*/
class HouseCouponItemsModel extends BasicModel
{

    protected $name = "houseCouponItems";
    protected $preferred_db = 'op';

    protected $columns = array(
    'coupID' => array('type'=>'INT', 'primary_key'=>true),
    'upc' => array('type'=>'VARCHAR(13)', 'primary_key'=>true),
    'type' => array('type'=>'VARCHAR(15)'),
    );

    public function doc()
    {
        return '
Table: houseCouponItems

Columns:
    coupID int
    upc varchar
    type varchar

Depends on:
    houseCoupons

Use:
WFC runs custom barcoded coupons with
upc prefix 499999. See houseCoupons for
more detail. Type here should be \'QUALIFIER\',
        ';
    }

    /* START ACCESSOR FUNCTIONS */

    public function coupID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["coupID"])) {
                return $this->instance["coupID"];
            } else if (isset($this->columns["coupID"]["default"])) {
                return $this->columns["coupID"]["default"];
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
                'left' => 'coupID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["coupID"]) || $this->instance["coupID"] != func_get_args(0)) {
                if (!isset($this->columns["coupID"]["ignore_updates"]) || $this->columns["coupID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["coupID"] = func_get_arg(0);
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

    public function type()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["type"])) {
                return $this->instance["type"];
            } else if (isset($this->columns["type"]["default"])) {
                return $this->columns["type"]["default"];
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
                'left' => 'type',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["type"]) || $this->instance["type"] != func_get_args(0)) {
                if (!isset($this->columns["type"]["ignore_updates"]) || $this->columns["type"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["type"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

