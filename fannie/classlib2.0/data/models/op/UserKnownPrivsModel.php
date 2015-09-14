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
  @class UserKnownPrivsModel
*/
class UserKnownPrivsModel extends BasicModel
{

    protected $name = "userKnownPrivs";
    protected $preferred_db = 'op';

    protected $columns = array(
    'auth_class' => array('type'=>'VARCHAR(50)', 'primary_key'=>true),
    'notes' => array('type'=>'TEXT'),
    );


    /* START ACCESSOR FUNCTIONS */

    public function auth_class()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["auth_class"])) {
                return $this->instance["auth_class"];
            } else if (isset($this->columns["auth_class"]["default"])) {
                return $this->columns["auth_class"]["default"];
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
                'left' => 'auth_class',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["auth_class"]) || $this->instance["auth_class"] != func_get_args(0)) {
                if (!isset($this->columns["auth_class"]["ignore_updates"]) || $this->columns["auth_class"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["auth_class"] = func_get_arg(0);
        }
        return $this;
    }

    public function notes()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["notes"])) {
                return $this->instance["notes"];
            } else if (isset($this->columns["notes"]["default"])) {
                return $this->columns["notes"]["default"];
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
                'left' => 'notes',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["notes"]) || $this->instance["notes"] != func_get_args(0)) {
                if (!isset($this->columns["notes"]["ignore_updates"]) || $this->columns["notes"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["notes"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

