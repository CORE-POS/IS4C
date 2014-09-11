<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

class EmployeesModel extends BasicModel 
{

    protected $name = 'employees';

    protected $preferred_db = 'op';

    protected $columns = array(
    'emp_no'    => array('type'=>'SMALLINT','primary_key'=>True),
    'CashierPassword'=>array('type'=>'VARCHAR(50)'),
    'AdminPassword'=>array('type'=>'VARCHAR(50)'),
    'FirstName'=>array('type'=>'VARCHAR(50)'),
    'LastName'=>array('type'=>'VARCHAR(50)'),
    'JobTitle'=>array('type'=>'VARCHAR(50)'),
    'EmpActive'=>array('type'=>'TINYINT'),
    'frontendsecurity'=>array('type'=>'SMALLINT'),
    'backendsecurity'=>array('type'=>'SMALLINT'),
    'birthdate'=>array('type'=>'DATETIME')
    );

    /* START ACCESSOR FUNCTIONS */

    public function emp_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["emp_no"])) {
                return $this->instance["emp_no"];
            } else if (isset($this->columns["emp_no"]["default"])) {
                return $this->columns["emp_no"]["default"];
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
                'left' => 'emp_no',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["emp_no"]) || $this->instance["emp_no"] != func_get_args(0)) {
                if (!isset($this->columns["emp_no"]["ignore_updates"]) || $this->columns["emp_no"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["emp_no"] = func_get_arg(0);
        }
        return $this;
    }

    public function CashierPassword()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["CashierPassword"])) {
                return $this->instance["CashierPassword"];
            } else if (isset($this->columns["CashierPassword"]["default"])) {
                return $this->columns["CashierPassword"]["default"];
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
                'left' => 'CashierPassword',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["CashierPassword"]) || $this->instance["CashierPassword"] != func_get_args(0)) {
                if (!isset($this->columns["CashierPassword"]["ignore_updates"]) || $this->columns["CashierPassword"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["CashierPassword"] = func_get_arg(0);
        }
        return $this;
    }

    public function AdminPassword()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["AdminPassword"])) {
                return $this->instance["AdminPassword"];
            } else if (isset($this->columns["AdminPassword"]["default"])) {
                return $this->columns["AdminPassword"]["default"];
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
                'left' => 'AdminPassword',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["AdminPassword"]) || $this->instance["AdminPassword"] != func_get_args(0)) {
                if (!isset($this->columns["AdminPassword"]["ignore_updates"]) || $this->columns["AdminPassword"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["AdminPassword"] = func_get_arg(0);
        }
        return $this;
    }

    public function FirstName()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["FirstName"])) {
                return $this->instance["FirstName"];
            } else if (isset($this->columns["FirstName"]["default"])) {
                return $this->columns["FirstName"]["default"];
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
                'left' => 'FirstName',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["FirstName"]) || $this->instance["FirstName"] != func_get_args(0)) {
                if (!isset($this->columns["FirstName"]["ignore_updates"]) || $this->columns["FirstName"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["FirstName"] = func_get_arg(0);
        }
        return $this;
    }

    public function LastName()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["LastName"])) {
                return $this->instance["LastName"];
            } else if (isset($this->columns["LastName"]["default"])) {
                return $this->columns["LastName"]["default"];
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
                'left' => 'LastName',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["LastName"]) || $this->instance["LastName"] != func_get_args(0)) {
                if (!isset($this->columns["LastName"]["ignore_updates"]) || $this->columns["LastName"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["LastName"] = func_get_arg(0);
        }
        return $this;
    }

    public function JobTitle()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["JobTitle"])) {
                return $this->instance["JobTitle"];
            } else if (isset($this->columns["JobTitle"]["default"])) {
                return $this->columns["JobTitle"]["default"];
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
                'left' => 'JobTitle',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["JobTitle"]) || $this->instance["JobTitle"] != func_get_args(0)) {
                if (!isset($this->columns["JobTitle"]["ignore_updates"]) || $this->columns["JobTitle"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["JobTitle"] = func_get_arg(0);
        }
        return $this;
    }

    public function EmpActive()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["EmpActive"])) {
                return $this->instance["EmpActive"];
            } else if (isset($this->columns["EmpActive"]["default"])) {
                return $this->columns["EmpActive"]["default"];
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
                'left' => 'EmpActive',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["EmpActive"]) || $this->instance["EmpActive"] != func_get_args(0)) {
                if (!isset($this->columns["EmpActive"]["ignore_updates"]) || $this->columns["EmpActive"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["EmpActive"] = func_get_arg(0);
        }
        return $this;
    }

    public function frontendsecurity()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["frontendsecurity"])) {
                return $this->instance["frontendsecurity"];
            } else if (isset($this->columns["frontendsecurity"]["default"])) {
                return $this->columns["frontendsecurity"]["default"];
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
                'left' => 'frontendsecurity',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["frontendsecurity"]) || $this->instance["frontendsecurity"] != func_get_args(0)) {
                if (!isset($this->columns["frontendsecurity"]["ignore_updates"]) || $this->columns["frontendsecurity"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["frontendsecurity"] = func_get_arg(0);
        }
        return $this;
    }

    public function backendsecurity()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["backendsecurity"])) {
                return $this->instance["backendsecurity"];
            } else if (isset($this->columns["backendsecurity"]["default"])) {
                return $this->columns["backendsecurity"]["default"];
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
                'left' => 'backendsecurity',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["backendsecurity"]) || $this->instance["backendsecurity"] != func_get_args(0)) {
                if (!isset($this->columns["backendsecurity"]["ignore_updates"]) || $this->columns["backendsecurity"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["backendsecurity"] = func_get_arg(0);
        }
        return $this;
    }

    public function birthdate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["birthdate"])) {
                return $this->instance["birthdate"];
            } else if (isset($this->columns["birthdate"]["default"])) {
                return $this->columns["birthdate"]["default"];
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
                'left' => 'birthdate',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["birthdate"]) || $this->instance["birthdate"] != func_get_args(0)) {
                if (!isset($this->columns["birthdate"]["ignore_updates"]) || $this->columns["birthdate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["birthdate"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

