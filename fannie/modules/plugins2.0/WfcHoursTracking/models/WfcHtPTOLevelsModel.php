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

/**
  @class WfcHtPTOLevelsModel
*/
class WfcHtPTOLevelsModel extends BasicModel
{

    protected $name = "PTOLevels";

    protected $columns = array(
    'LevelID' => array('type'=>'SMALLINT', 'primary_key'=>true),
    'HoursWorked' => array('type'=>'DOUBLE'),
    'PTOHours' => array('type'=>'DOUBLE'),
    );

    /* START ACCESSOR FUNCTIONS */

    public function LevelID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["LevelID"])) {
                return $this->instance["LevelID"];
            } else if (isset($this->columns["LevelID"]["default"])) {
                return $this->columns["LevelID"]["default"];
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
                'left' => 'LevelID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["LevelID"]) || $this->instance["LevelID"] != func_get_args(0)) {
                if (!isset($this->columns["LevelID"]["ignore_updates"]) || $this->columns["LevelID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["LevelID"] = func_get_arg(0);
        }
        return $this;
    }

    public function HoursWorked()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["HoursWorked"])) {
                return $this->instance["HoursWorked"];
            } else if (isset($this->columns["HoursWorked"]["default"])) {
                return $this->columns["HoursWorked"]["default"];
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
                'left' => 'HoursWorked',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["HoursWorked"]) || $this->instance["HoursWorked"] != func_get_args(0)) {
                if (!isset($this->columns["HoursWorked"]["ignore_updates"]) || $this->columns["HoursWorked"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["HoursWorked"] = func_get_arg(0);
        }
        return $this;
    }

    public function PTOHours()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["PTOHours"])) {
                return $this->instance["PTOHours"];
            } else if (isset($this->columns["PTOHours"]["default"])) {
                return $this->columns["PTOHours"]["default"];
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
                'left' => 'PTOHours',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["PTOHours"]) || $this->instance["PTOHours"] != func_get_args(0)) {
                if (!isset($this->columns["PTOHours"]["ignore_updates"]) || $this->columns["PTOHours"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["PTOHours"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

