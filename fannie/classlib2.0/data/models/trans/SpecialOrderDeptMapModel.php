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
  @class SpecialOrderDeptMapModel
*/
class SpecialOrderDeptMapModel extends BasicModel
{

    protected $name = "SpecialOrderDeptMap";
    protected $preferred_db = 'trans';

    protected $columns = array(
    'dept_ID' => array('type'=>'INT', 'primary_key'=>true),
    'map_to' => array('type'=>'INT', 'primary_key'=>true),
    );

    public function doc()
    {
        return '
Table: SpecialOrderDeptMap

Columns:
    dept_ID int
    map_to int

Optional table for mapping product departments
to alternate departments. Essentially, put
entries into historic "special order" departments
        ';
    }

    /* START ACCESSOR FUNCTIONS */

    public function dept_ID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dept_ID"])) {
                return $this->instance["dept_ID"];
            } else if (isset($this->columns["dept_ID"]["default"])) {
                return $this->columns["dept_ID"]["default"];
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
                'left' => 'dept_ID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["dept_ID"]) || $this->instance["dept_ID"] != func_get_args(0)) {
                if (!isset($this->columns["dept_ID"]["ignore_updates"]) || $this->columns["dept_ID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["dept_ID"] = func_get_arg(0);
        }
        return $this;
    }

    public function map_to()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["map_to"])) {
                return $this->instance["map_to"];
            } else if (isset($this->columns["map_to"]["default"])) {
                return $this->columns["map_to"]["default"];
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
                'left' => 'map_to',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["map_to"]) || $this->instance["map_to"] != func_get_args(0)) {
                if (!isset($this->columns["map_to"]["ignore_updates"]) || $this->columns["map_to"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["map_to"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

