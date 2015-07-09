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
  @class ShiftsModel
*/
class ShiftsModel extends BasicModel
{

    protected $name = "shifts";
    protected $preferred_db = 'plugin:TimesheetDatabase';

    protected $columns = array(
    'ShiftName' => array('type'=>'VARCHAR(25)'),
    'NiceName' => array('type'=>'VARCHAR(255)'),
    'ShiftID' => array('type'=>'INT', 'primary_key'=>true),
    'visible' => array('type'=>'TINYINT'),
    'ShiftOrder' => array('type'=>'INT'),
    'timesheetDepartmentID' => array('type'=>'INT'),
    );

    /* START ACCESSOR FUNCTIONS */

    public function ShiftName()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["ShiftName"])) {
                return $this->instance["ShiftName"];
            } else if (isset($this->columns["ShiftName"]["default"])) {
                return $this->columns["ShiftName"]["default"];
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
                'left' => 'ShiftName',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["ShiftName"]) || $this->instance["ShiftName"] != func_get_args(0)) {
                if (!isset($this->columns["ShiftName"]["ignore_updates"]) || $this->columns["ShiftName"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["ShiftName"] = func_get_arg(0);
        }
        return $this;
    }

    public function NiceName()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["NiceName"])) {
                return $this->instance["NiceName"];
            } else if (isset($this->columns["NiceName"]["default"])) {
                return $this->columns["NiceName"]["default"];
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
                'left' => 'NiceName',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["NiceName"]) || $this->instance["NiceName"] != func_get_args(0)) {
                if (!isset($this->columns["NiceName"]["ignore_updates"]) || $this->columns["NiceName"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["NiceName"] = func_get_arg(0);
        }
        return $this;
    }

    public function ShiftID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["ShiftID"])) {
                return $this->instance["ShiftID"];
            } else if (isset($this->columns["ShiftID"]["default"])) {
                return $this->columns["ShiftID"]["default"];
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
                'left' => 'ShiftID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["ShiftID"]) || $this->instance["ShiftID"] != func_get_args(0)) {
                if (!isset($this->columns["ShiftID"]["ignore_updates"]) || $this->columns["ShiftID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["ShiftID"] = func_get_arg(0);
        }
        return $this;
    }

    public function visible()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["visible"])) {
                return $this->instance["visible"];
            } else if (isset($this->columns["visible"]["default"])) {
                return $this->columns["visible"]["default"];
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
                'left' => 'visible',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["visible"]) || $this->instance["visible"] != func_get_args(0)) {
                if (!isset($this->columns["visible"]["ignore_updates"]) || $this->columns["visible"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["visible"] = func_get_arg(0);
        }
        return $this;
    }

    public function ShiftOrder()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["ShiftOrder"])) {
                return $this->instance["ShiftOrder"];
            } else if (isset($this->columns["ShiftOrder"]["default"])) {
                return $this->columns["ShiftOrder"]["default"];
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
                'left' => 'ShiftOrder',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["ShiftOrder"]) || $this->instance["ShiftOrder"] != func_get_args(0)) {
                if (!isset($this->columns["ShiftOrder"]["ignore_updates"]) || $this->columns["ShiftOrder"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["ShiftOrder"] = func_get_arg(0);
        }
        return $this;
    }

    public function timesheetDepartmentID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["timesheetDepartmentID"])) {
                return $this->instance["timesheetDepartmentID"];
            } else if (isset($this->columns["timesheetDepartmentID"]["default"])) {
                return $this->columns["timesheetDepartmentID"]["default"];
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
                'left' => 'timesheetDepartmentID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["timesheetDepartmentID"]) || $this->instance["timesheetDepartmentID"] != func_get_args(0)) {
                if (!isset($this->columns["timesheetDepartmentID"]["ignore_updates"]) || $this->columns["timesheetDepartmentID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["timesheetDepartmentID"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

