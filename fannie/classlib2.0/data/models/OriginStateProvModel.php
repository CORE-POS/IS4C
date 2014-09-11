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
  @class OriginStateProvModel
*/
class OriginStateProvModel extends BasicModel
{

    protected $name = "originStateProv";

    protected $columns = array(
    'stateProvID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'name' => array('type'=>'VARCHAR(50)'),
    'abbr' => array('type'=>'VARCHAR(5)'),
    );

    /* START ACCESSOR FUNCTIONS */

    public function stateProvID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["stateProvID"])) {
                return $this->instance["stateProvID"];
            } else if (isset($this->columns["stateProvID"]["default"])) {
                return $this->columns["stateProvID"]["default"];
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
                'left' => 'stateProvID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["stateProvID"]) || $this->instance["stateProvID"] != func_get_args(0)) {
                if (!isset($this->columns["stateProvID"]["ignore_updates"]) || $this->columns["stateProvID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["stateProvID"] = func_get_arg(0);
        }
        return $this;
    }

    public function name()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["name"])) {
                return $this->instance["name"];
            } else if (isset($this->columns["name"]["default"])) {
                return $this->columns["name"]["default"];
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
                'left' => 'name',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["name"]) || $this->instance["name"] != func_get_args(0)) {
                if (!isset($this->columns["name"]["ignore_updates"]) || $this->columns["name"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["name"] = func_get_arg(0);
        }
        return $this;
    }

    public function abbr()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["abbr"])) {
                return $this->instance["abbr"];
            } else if (isset($this->columns["abbr"]["default"])) {
                return $this->columns["abbr"]["default"];
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
                'left' => 'abbr',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["abbr"]) || $this->instance["abbr"] != func_get_args(0)) {
                if (!isset($this->columns["abbr"]["ignore_updates"]) || $this->columns["abbr"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["abbr"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

