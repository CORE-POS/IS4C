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
            } elseif(isset($this->columns["emp_no"]["default"])) {
                return $this->columns["emp_no"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["emp_no"] = func_get_arg(0);
        }
    }

    public function CashierPassword()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["CashierPassword"])) {
                return $this->instance["CashierPassword"];
            } elseif(isset($this->columns["CashierPassword"]["default"])) {
                return $this->columns["CashierPassword"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["CashierPassword"] = func_get_arg(0);
        }
    }

    public function AdminPassword()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["AdminPassword"])) {
                return $this->instance["AdminPassword"];
            } elseif(isset($this->columns["AdminPassword"]["default"])) {
                return $this->columns["AdminPassword"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["AdminPassword"] = func_get_arg(0);
        }
    }

    public function FirstName()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["FirstName"])) {
                return $this->instance["FirstName"];
            } elseif(isset($this->columns["FirstName"]["default"])) {
                return $this->columns["FirstName"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["FirstName"] = func_get_arg(0);
        }
    }

    public function LastName()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["LastName"])) {
                return $this->instance["LastName"];
            } elseif(isset($this->columns["LastName"]["default"])) {
                return $this->columns["LastName"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["LastName"] = func_get_arg(0);
        }
    }

    public function JobTitle()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["JobTitle"])) {
                return $this->instance["JobTitle"];
            } elseif(isset($this->columns["JobTitle"]["default"])) {
                return $this->columns["JobTitle"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["JobTitle"] = func_get_arg(0);
        }
    }

    public function EmpActive()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["EmpActive"])) {
                return $this->instance["EmpActive"];
            } elseif(isset($this->columns["EmpActive"]["default"])) {
                return $this->columns["EmpActive"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["EmpActive"] = func_get_arg(0);
        }
    }

    public function frontendsecurity()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["frontendsecurity"])) {
                return $this->instance["frontendsecurity"];
            } elseif(isset($this->columns["frontendsecurity"]["default"])) {
                return $this->columns["frontendsecurity"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["frontendsecurity"] = func_get_arg(0);
        }
    }

    public function backendsecurity()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["backendsecurity"])) {
                return $this->instance["backendsecurity"];
            } elseif(isset($this->columns["backendsecurity"]["default"])) {
                return $this->columns["backendsecurity"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["backendsecurity"] = func_get_arg(0);
        }
    }

    public function birthdate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["birthdate"])) {
                return $this->instance["birthdate"];
            } elseif(isset($this->columns["birthdate"]["default"])) {
                return $this->columns["birthdate"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["birthdate"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

