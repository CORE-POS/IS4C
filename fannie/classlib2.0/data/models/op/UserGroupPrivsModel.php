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
  @class UserGroupPrivsModel
*/
class UserGroupPrivsModel extends BasicModel
{

    protected $name = "userGroupPrivs";
    protected $preferred_db = 'op';

    protected $columns = array(
    'gid' => array('type'=>'INT'),
    'auth' => array('type'=>'VARCHAR(50)'),
    'sub_start' => array('type'=>'VARCHAR(50)'),
    'sub_end' => array('type'=>'VARCHAR(50)'),
    );


    /* START ACCESSOR FUNCTIONS */

    public function gid()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["gid"])) {
                return $this->instance["gid"];
            } else if (isset($this->columns["gid"]["default"])) {
                return $this->columns["gid"]["default"];
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
                'left' => 'gid',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["gid"]) || $this->instance["gid"] != func_get_args(0)) {
                if (!isset($this->columns["gid"]["ignore_updates"]) || $this->columns["gid"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["gid"] = func_get_arg(0);
        }
        return $this;
    }

    public function auth()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["auth"])) {
                return $this->instance["auth"];
            } else if (isset($this->columns["auth"]["default"])) {
                return $this->columns["auth"]["default"];
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
                'left' => 'auth',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["auth"]) || $this->instance["auth"] != func_get_args(0)) {
                if (!isset($this->columns["auth"]["ignore_updates"]) || $this->columns["auth"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["auth"] = func_get_arg(0);
        }
        return $this;
    }

    public function sub_start()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["sub_start"])) {
                return $this->instance["sub_start"];
            } else if (isset($this->columns["sub_start"]["default"])) {
                return $this->columns["sub_start"]["default"];
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
                'left' => 'sub_start',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["sub_start"]) || $this->instance["sub_start"] != func_get_args(0)) {
                if (!isset($this->columns["sub_start"]["ignore_updates"]) || $this->columns["sub_start"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["sub_start"] = func_get_arg(0);
        }
        return $this;
    }

    public function sub_end()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["sub_end"])) {
                return $this->instance["sub_end"];
            } else if (isset($this->columns["sub_end"]["default"])) {
                return $this->columns["sub_end"]["default"];
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
                'left' => 'sub_end',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["sub_end"]) || $this->instance["sub_end"] != func_get_args(0)) {
                if (!isset($this->columns["sub_end"]["ignore_updates"]) || $this->columns["sub_end"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["sub_end"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

