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
  @class SaInventoryModel
*/
class SaInventoryModel extends BasicModel
{

    protected $name = "sa_inventory";

    protected $columns = array(
    'id' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'datetime' => array('type'=>'DATETIME'),
    'upc' => array('type'=>'VARCHAR(13)', 'index'=>true),
    'clear' => array('type'=>'TINYINT'),
    'quantity' => array('type'=>'DOUBLE'),
    'section' => array('type'=>'INT'),
    );

    /* START ACCESSOR FUNCTIONS */

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

    public function datetime()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["datetime"])) {
                return $this->instance["datetime"];
            } else if (isset($this->columns["datetime"]["default"])) {
                return $this->columns["datetime"]["default"];
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
                'left' => 'datetime',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["datetime"]) || $this->instance["datetime"] != func_get_args(0)) {
                if (!isset($this->columns["datetime"]["ignore_updates"]) || $this->columns["datetime"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["datetime"] = func_get_arg(0);
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

    public function clear()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["clear"])) {
                return $this->instance["clear"];
            } else if (isset($this->columns["clear"]["default"])) {
                return $this->columns["clear"]["default"];
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
                'left' => 'clear',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["clear"]) || $this->instance["clear"] != func_get_args(0)) {
                if (!isset($this->columns["clear"]["ignore_updates"]) || $this->columns["clear"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["clear"] = func_get_arg(0);
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

    public function section()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["section"])) {
                return $this->instance["section"];
            } else if (isset($this->columns["section"]["default"])) {
                return $this->columns["section"]["default"];
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
                'left' => 'section',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["section"]) || $this->instance["section"] != func_get_args(0)) {
                if (!isset($this->columns["section"]["ignore_updates"]) || $this->columns["section"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["section"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

