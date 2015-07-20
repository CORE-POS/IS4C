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
  @class UsersModel
*/
class UsersModel extends BasicModel
{

    protected $name = "Users";
    protected $preferred_db = 'op';

    protected $columns = array(
    'name' => array('type'=>'VARCHAR(50)', 'primary_key'=>true),
    'password' => array('type'=>'VARCHAR(50)'),
    'salt' => array('type'=>'VARCHAR(10)'),
    'uid' => array('type'=>'VARCHAR(4)'),
    'session_id' => array('type'=>'VARCHAR(50)'),
    'real_name' => array('type'=>'VARCHAR(75)'),
    );


    /* START ACCESSOR FUNCTIONS */

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

    public function password()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["password"])) {
                return $this->instance["password"];
            } else if (isset($this->columns["password"]["default"])) {
                return $this->columns["password"]["default"];
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
                'left' => 'password',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["password"]) || $this->instance["password"] != func_get_args(0)) {
                if (!isset($this->columns["password"]["ignore_updates"]) || $this->columns["password"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["password"] = func_get_arg(0);
        }
        return $this;
    }

    public function salt()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["salt"])) {
                return $this->instance["salt"];
            } else if (isset($this->columns["salt"]["default"])) {
                return $this->columns["salt"]["default"];
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
                'left' => 'salt',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["salt"]) || $this->instance["salt"] != func_get_args(0)) {
                if (!isset($this->columns["salt"]["ignore_updates"]) || $this->columns["salt"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["salt"] = func_get_arg(0);
        }
        return $this;
    }

    public function uid()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["uid"])) {
                return $this->instance["uid"];
            } else if (isset($this->columns["uid"]["default"])) {
                return $this->columns["uid"]["default"];
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
                'left' => 'uid',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["uid"]) || $this->instance["uid"] != func_get_args(0)) {
                if (!isset($this->columns["uid"]["ignore_updates"]) || $this->columns["uid"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["uid"] = func_get_arg(0);
        }
        return $this;
    }

    public function session_id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["session_id"])) {
                return $this->instance["session_id"];
            } else if (isset($this->columns["session_id"]["default"])) {
                return $this->columns["session_id"]["default"];
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
                'left' => 'session_id',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["session_id"]) || $this->instance["session_id"] != func_get_args(0)) {
                if (!isset($this->columns["session_id"]["ignore_updates"]) || $this->columns["session_id"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["session_id"] = func_get_arg(0);
        }
        return $this;
    }

    public function real_name()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["real_name"])) {
                return $this->instance["real_name"];
            } else if (isset($this->columns["real_name"]["default"])) {
                return $this->columns["real_name"]["default"];
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
                'left' => 'real_name',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["real_name"]) || $this->instance["real_name"] != func_get_args(0)) {
                if (!isset($this->columns["real_name"]["ignore_updates"]) || $this->columns["real_name"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["real_name"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

