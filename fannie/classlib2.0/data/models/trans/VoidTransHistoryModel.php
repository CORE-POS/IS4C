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
  @class VoidTransHistoryModel
*/
class VoidTransHistoryModel extends BasicModel
{

    protected $name = "voidTransHistory";
    protected $preferred_db = 'trans';

    protected $columns = array(
    'tdate' => array('type'=>'DATETIME', 'index'=>true),
    'description' => array('type'=>'VARCHAR(40)'),
    'trans_num' => array('type'=>'VARCHAR(20)'),
    'total' => array('type'=>'MONEY'),
    );

    public function doc()
    {
        return '
Table: voidTransHistory

Columns:
    tdate datetime
    description varchar
    trans_num varchar
    total money

Depends on:
    none

Use:
Store transaction numbers for voided transactions
(they\'re identified by comment lines which are
excluded from dlog views)
        ';
    }

    /* START ACCESSOR FUNCTIONS */

    public function tdate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["tdate"])) {
                return $this->instance["tdate"];
            } else if (isset($this->columns["tdate"]["default"])) {
                return $this->columns["tdate"]["default"];
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
                'left' => 'tdate',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["tdate"]) || $this->instance["tdate"] != func_get_args(0)) {
                if (!isset($this->columns["tdate"]["ignore_updates"]) || $this->columns["tdate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["tdate"] = func_get_arg(0);
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

    public function trans_num()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["trans_num"])) {
                return $this->instance["trans_num"];
            } else if (isset($this->columns["trans_num"]["default"])) {
                return $this->columns["trans_num"]["default"];
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
                'left' => 'trans_num',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["trans_num"]) || $this->instance["trans_num"] != func_get_args(0)) {
                if (!isset($this->columns["trans_num"]["ignore_updates"]) || $this->columns["trans_num"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["trans_num"] = func_get_arg(0);
        }
        return $this;
    }

    public function total()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["total"])) {
                return $this->instance["total"];
            } else if (isset($this->columns["total"]["default"])) {
                return $this->columns["total"]["default"];
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
                'left' => 'total',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["total"]) || $this->instance["total"] != func_get_args(0)) {
                if (!isset($this->columns["total"]["ignore_updates"]) || $this->columns["total"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["total"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

