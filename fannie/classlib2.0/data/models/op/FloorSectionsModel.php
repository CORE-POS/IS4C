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
  @class FloorSectionsModel
*/
class FloorSectionsModel extends BasicModel
{

    protected $name = "FloorSections";

    protected $columns = array(
    'floorSectionID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'storeID' => array('type'=>'INT', 'default'=>1),
    'name' => array('type'=>'VARCHAR(50)'),
    );


    /* START ACCESSOR FUNCTIONS */

    public function floorSectionID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["floorSectionID"])) {
                return $this->instance["floorSectionID"];
            } else if (isset($this->columns["floorSectionID"]["default"])) {
                return $this->columns["floorSectionID"]["default"];
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
                'left' => 'floorSectionID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["floorSectionID"]) || $this->instance["floorSectionID"] != func_get_args(0)) {
                if (!isset($this->columns["floorSectionID"]["ignore_updates"]) || $this->columns["floorSectionID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["floorSectionID"] = func_get_arg(0);
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
    /* END ACCESSOR FUNCTIONS */
}

