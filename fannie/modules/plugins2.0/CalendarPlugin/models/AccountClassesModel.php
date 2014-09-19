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
  @class AccountClassesModel
*/
class AccountClassesModel extends BasicModel
{

    protected $name = "account_classes";

    protected $columns = array(
    'classID' => array('type'=>'INT', 'primary_key'=>true, 'increment'=>true),
    'classDesc' => array('type'=>'VARCHAR(50)'),
    );

    /* START ACCESSOR FUNCTIONS */

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

    public function classDesc()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["classDesc"])) {
                return $this->instance["classDesc"];
            } else if (isset($this->columns["classDesc"]["default"])) {
                return $this->columns["classDesc"]["default"];
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
                'left' => 'classDesc',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["classDesc"]) || $this->instance["classDesc"] != func_get_args(0)) {
                if (!isset($this->columns["classDesc"]["ignore_updates"]) || $this->columns["classDesc"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["classDesc"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

