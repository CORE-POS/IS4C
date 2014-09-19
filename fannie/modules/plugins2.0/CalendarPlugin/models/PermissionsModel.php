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
  @class PermissionsModel
*/
class PermissionsModel extends BasicModel
{

    protected $name = "permissions";

    protected $columns = array(
    'permissionID' => array('type'=>'INT', 'primary_key'=>true, 'increment'=>true),
    'calendarID' => array('type'=>'INT'),
    'uid' => array('type'=>'INT'),
    'classID' => array('type'=>'INT'),
    );

    /* START ACCESSOR FUNCTIONS */

    public function permissionID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["permissionID"])) {
                return $this->instance["permissionID"];
            } else if (isset($this->columns["permissionID"]["default"])) {
                return $this->columns["permissionID"]["default"];
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
                'left' => 'permissionID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["permissionID"]) || $this->instance["permissionID"] != func_get_args(0)) {
                if (!isset($this->columns["permissionID"]["ignore_updates"]) || $this->columns["permissionID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["permissionID"] = func_get_arg(0);
        }
        return $this;
    }

    public function calendarID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["calendarID"])) {
                return $this->instance["calendarID"];
            } else if (isset($this->columns["calendarID"]["default"])) {
                return $this->columns["calendarID"]["default"];
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
                'left' => 'calendarID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["calendarID"]) || $this->instance["calendarID"] != func_get_args(0)) {
                if (!isset($this->columns["calendarID"]["ignore_updates"]) || $this->columns["calendarID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["calendarID"] = func_get_arg(0);
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

    public function classID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["classID"])) {
                return $this->instance["classID"];
            } else if (isset($this->columns["classID"]["default"])) {
                return $this->columns["classID"]["default"];
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
                'left' => 'classID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["classID"]) || $this->instance["classID"] != func_get_args(0)) {
                if (!isset($this->columns["classID"]["ignore_updates"]) || $this->columns["classID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["classID"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

