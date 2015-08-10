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
  @class TimesheetEmployeesModel
*/
class TimesheetEmployeesModel extends BasicModel
{

    protected $name = "TimesheetEmployees";
    protected $preferred_db = 'plugin:TimesheetDatabase';

    protected $columns = array(
    'timesheetEmployeeID' => array('type'=>'INT', 'primary_key'=>true),
    'firstName' => array('type'=>'VARCHAR(50)'),
    'lastName' => array('type'=>'VARCHAR(50)'),
    'username' => array('type'=>'VARCHAR(50)'),
    'posMemberID' => array('type'=>'INT'),
    'payrollProviderID' => array('type'=>'VARCHAR(50)'),
    'timeclockToken' => array('type'=>'VARCHAR(255)'),
    'timesheetDepartmentID' => array('type'=>'INT'),
    'primaryShiftID' => array('type'=>'INT'),
    'wage' => array('type'=>'MONEY'),
    'hireDate' => array('type'=>'DATETIME'),
    'active' => array('type'=>'TINYINT', 'default'=>1),
    'clockedIn' => array('type'=>'TINYINT', 'default'=>0),
    'clockInDateTime' => array('type'=>'DATETIME'),
    'clockInShiftID' => array('type'=>'INT'),
    );

    /* START ACCESSOR FUNCTIONS */

    public function timesheetEmployeeID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["timesheetEmployeeID"])) {
                return $this->instance["timesheetEmployeeID"];
            } else if (isset($this->columns["timesheetEmployeeID"]["default"])) {
                return $this->columns["timesheetEmployeeID"]["default"];
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
                'left' => 'timesheetEmployeeID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["timesheetEmployeeID"]) || $this->instance["timesheetEmployeeID"] != func_get_args(0)) {
                if (!isset($this->columns["timesheetEmployeeID"]["ignore_updates"]) || $this->columns["timesheetEmployeeID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["timesheetEmployeeID"] = func_get_arg(0);
        }
        return $this;
    }

    public function firstName()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["firstName"])) {
                return $this->instance["firstName"];
            } else if (isset($this->columns["firstName"]["default"])) {
                return $this->columns["firstName"]["default"];
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
                'left' => 'firstName',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["firstName"]) || $this->instance["firstName"] != func_get_args(0)) {
                if (!isset($this->columns["firstName"]["ignore_updates"]) || $this->columns["firstName"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["firstName"] = func_get_arg(0);
        }
        return $this;
    }

    public function lastName()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["lastName"])) {
                return $this->instance["lastName"];
            } else if (isset($this->columns["lastName"]["default"])) {
                return $this->columns["lastName"]["default"];
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
                'left' => 'lastName',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["lastName"]) || $this->instance["lastName"] != func_get_args(0)) {
                if (!isset($this->columns["lastName"]["ignore_updates"]) || $this->columns["lastName"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["lastName"] = func_get_arg(0);
        }
        return $this;
    }

    public function username()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["username"])) {
                return $this->instance["username"];
            } else if (isset($this->columns["username"]["default"])) {
                return $this->columns["username"]["default"];
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
                'left' => 'username',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["username"]) || $this->instance["username"] != func_get_args(0)) {
                if (!isset($this->columns["username"]["ignore_updates"]) || $this->columns["username"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["username"] = func_get_arg(0);
        }
        return $this;
    }

    public function posMemberID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["posMemberID"])) {
                return $this->instance["posMemberID"];
            } else if (isset($this->columns["posMemberID"]["default"])) {
                return $this->columns["posMemberID"]["default"];
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
                'left' => 'posMemberID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["posMemberID"]) || $this->instance["posMemberID"] != func_get_args(0)) {
                if (!isset($this->columns["posMemberID"]["ignore_updates"]) || $this->columns["posMemberID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["posMemberID"] = func_get_arg(0);
        }
        return $this;
    }

    public function payrollProviderID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["payrollProviderID"])) {
                return $this->instance["payrollProviderID"];
            } else if (isset($this->columns["payrollProviderID"]["default"])) {
                return $this->columns["payrollProviderID"]["default"];
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
                'left' => 'payrollProviderID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["payrollProviderID"]) || $this->instance["payrollProviderID"] != func_get_args(0)) {
                if (!isset($this->columns["payrollProviderID"]["ignore_updates"]) || $this->columns["payrollProviderID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["payrollProviderID"] = func_get_arg(0);
        }
        return $this;
    }

    public function timeclockToken()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["timeclockToken"])) {
                return $this->instance["timeclockToken"];
            } else if (isset($this->columns["timeclockToken"]["default"])) {
                return $this->columns["timeclockToken"]["default"];
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
                'left' => 'timeclockToken',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["timeclockToken"]) || $this->instance["timeclockToken"] != func_get_args(0)) {
                if (!isset($this->columns["timeclockToken"]["ignore_updates"]) || $this->columns["timeclockToken"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["timeclockToken"] = func_get_arg(0);
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

    public function primaryShiftID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["primaryShiftID"])) {
                return $this->instance["primaryShiftID"];
            } else if (isset($this->columns["primaryShiftID"]["default"])) {
                return $this->columns["primaryShiftID"]["default"];
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
                'left' => 'primaryShiftID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["primaryShiftID"]) || $this->instance["primaryShiftID"] != func_get_args(0)) {
                if (!isset($this->columns["primaryShiftID"]["ignore_updates"]) || $this->columns["primaryShiftID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["primaryShiftID"] = func_get_arg(0);
        }
        return $this;
    }

    public function wage()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["wage"])) {
                return $this->instance["wage"];
            } else if (isset($this->columns["wage"]["default"])) {
                return $this->columns["wage"]["default"];
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
                'left' => 'wage',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["wage"]) || $this->instance["wage"] != func_get_args(0)) {
                if (!isset($this->columns["wage"]["ignore_updates"]) || $this->columns["wage"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["wage"] = func_get_arg(0);
        }
        return $this;
    }

    public function hireDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["hireDate"])) {
                return $this->instance["hireDate"];
            } else if (isset($this->columns["hireDate"]["default"])) {
                return $this->columns["hireDate"]["default"];
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
                'left' => 'hireDate',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["hireDate"]) || $this->instance["hireDate"] != func_get_args(0)) {
                if (!isset($this->columns["hireDate"]["ignore_updates"]) || $this->columns["hireDate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["hireDate"] = func_get_arg(0);
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

    public function clockedIn()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["clockedIn"])) {
                return $this->instance["clockedIn"];
            } else if (isset($this->columns["clockedIn"]["default"])) {
                return $this->columns["clockedIn"]["default"];
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
                'left' => 'clockedIn',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["clockedIn"]) || $this->instance["clockedIn"] != func_get_args(0)) {
                if (!isset($this->columns["clockedIn"]["ignore_updates"]) || $this->columns["clockedIn"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["clockedIn"] = func_get_arg(0);
        }
        return $this;
    }

    public function clockInDateTime()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["clockInDateTime"])) {
                return $this->instance["clockInDateTime"];
            } else if (isset($this->columns["clockInDateTime"]["default"])) {
                return $this->columns["clockInDateTime"]["default"];
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
                'left' => 'clockInDateTime',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["clockInDateTime"]) || $this->instance["clockInDateTime"] != func_get_args(0)) {
                if (!isset($this->columns["clockInDateTime"]["ignore_updates"]) || $this->columns["clockInDateTime"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["clockInDateTime"] = func_get_arg(0);
        }
        return $this;
    }

    public function clockInShiftID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["clockInShiftID"])) {
                return $this->instance["clockInShiftID"];
            } else if (isset($this->columns["clockInShiftID"]["default"])) {
                return $this->columns["clockInShiftID"]["default"];
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
                'left' => 'clockInShiftID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["clockInShiftID"]) || $this->instance["clockInShiftID"] != func_get_args(0)) {
                if (!isset($this->columns["clockInShiftID"]["ignore_updates"]) || $this->columns["clockInShiftID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["clockInShiftID"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

