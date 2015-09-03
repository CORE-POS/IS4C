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
  @class UserSessionsModel
*/
class UserSessionsModel extends BasicModel
{

    protected $name = "userSessions";
    protected $preferred_db = 'op';

    protected $columns = array(
    'uid' => array('type'=>'VARCHAR(4)', 'primary_key'=>true),
    'session_id' => array('type'=>'VARCHAR(50)', 'primary_key'=>true),
    'ip' => array('type'=>'VARCHAR(45)'),
    'expires' => array('type'=>'DATETIME'),
    );


    /* START ACCESSOR FUNCTIONS */

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

    public function ip()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["ip"])) {
                return $this->instance["ip"];
            } else if (isset($this->columns["ip"]["default"])) {
                return $this->columns["ip"]["default"];
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
                'left' => 'ip',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["ip"]) || $this->instance["ip"] != func_get_args(0)) {
                if (!isset($this->columns["ip"]["ignore_updates"]) || $this->columns["ip"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["ip"] = func_get_arg(0);
        }
        return $this;
    }

    public function expires()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["expires"])) {
                return $this->instance["expires"];
            } else if (isset($this->columns["expires"]["default"])) {
                return $this->columns["expires"]["default"];
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
                'left' => 'expires',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["expires"]) || $this->instance["expires"] != func_get_args(0)) {
                if (!isset($this->columns["expires"]["ignore_updates"]) || $this->columns["expires"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["expires"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

