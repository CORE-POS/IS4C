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
  @class StoreEmployeeMapModel
*/
class StoreEmployeeMapModel extends BasicModel
{
    protected $name = "StoreEmployeeMap";
    protected $preferred_db = 'op';

    protected $columns = array(
    'storeEmployeeMapID' => array('type'=>'INT', 'index'=>true, 'increment'=>true),
    'storeID' => array('type'=>'INT', 'primary_key'=>true),
    'empNo' => array('type'=>'INT', 'primary_key'=>true),
    );

    /* START ACCESSOR FUNCTIONS */

    public function storeEmployeeMapID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["storeEmployeeMapID"])) {
                return $this->instance["storeEmployeeMapID"];
            } else if (isset($this->columns["storeEmployeeMapID"]["default"])) {
                return $this->columns["storeEmployeeMapID"]["default"];
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
                'left' => 'storeEmployeeMapID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["storeEmployeeMapID"]) || $this->instance["storeEmployeeMapID"] != func_get_args(0)) {
                if (!isset($this->columns["storeEmployeeMapID"]["ignore_updates"]) || $this->columns["storeEmployeeMapID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["storeEmployeeMapID"] = func_get_arg(0);
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

    public function empNo()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["empNo"])) {
                return $this->instance["empNo"];
            } else if (isset($this->columns["empNo"]["default"])) {
                return $this->columns["empNo"]["default"];
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
                'left' => 'empNo',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["empNo"]) || $this->instance["empNo"] != func_get_args(0)) {
                if (!isset($this->columns["empNo"]["ignore_updates"]) || $this->columns["empNo"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["empNo"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

