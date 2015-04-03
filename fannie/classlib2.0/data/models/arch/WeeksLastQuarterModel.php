<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
  @class WeeksLastQuarterModel
*/
class WeeksLastQuarterModel extends BasicModel
{

    protected $name = "weeksLastQuarter";
    protected $preferred_db = 'arch';

    protected $columns = array(
    'weekLastQuarterID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'weekStart' => array('type'=>'DATETIME'),
    'weekEnd' => array('type'=>'DATETIME'),
    );

    public function doc()
    {
        return '
Table: weeksLastQuarter

Columns:
    weekLastQuarterID int
    weekStart datetime
    weekEnd datetime

Depends on:
    none

Use:
Keep track of weeks in the last quarter.
This imposes several conventions:
* Weeks start on Monday and end on Sunday, ISO-style
* The current week is ID zero. The previous week is
  ID one. The week before that is ID two, etc.
* The Last Quarter is week IDs one through thirteen

Week #0 is provided for completeness in information.
The other thirteen weeks are used for the last quarter
so any comparisions are between full, 7-day weeks.
        ';
    }

    /* START ACCESSOR FUNCTIONS */

    public function weekLastQuarterID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["weekLastQuarterID"])) {
                return $this->instance["weekLastQuarterID"];
            } else if (isset($this->columns["weekLastQuarterID"]["default"])) {
                return $this->columns["weekLastQuarterID"]["default"];
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
                'left' => 'weekLastQuarterID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["weekLastQuarterID"]) || $this->instance["weekLastQuarterID"] != func_get_args(0)) {
                if (!isset($this->columns["weekLastQuarterID"]["ignore_updates"]) || $this->columns["weekLastQuarterID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["weekLastQuarterID"] = func_get_arg(0);
        }
        return $this;
    }

    public function weekStart()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["weekStart"])) {
                return $this->instance["weekStart"];
            } else if (isset($this->columns["weekStart"]["default"])) {
                return $this->columns["weekStart"]["default"];
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
                'left' => 'weekStart',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["weekStart"]) || $this->instance["weekStart"] != func_get_args(0)) {
                if (!isset($this->columns["weekStart"]["ignore_updates"]) || $this->columns["weekStart"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["weekStart"] = func_get_arg(0);
        }
        return $this;
    }

    public function weekEnd()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["weekEnd"])) {
                return $this->instance["weekEnd"];
            } else if (isset($this->columns["weekEnd"]["default"])) {
                return $this->columns["weekEnd"]["default"];
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
                'left' => 'weekEnd',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["weekEnd"]) || $this->instance["weekEnd"] != func_get_args(0)) {
                if (!isset($this->columns["weekEnd"]["ignore_updates"]) || $this->columns["weekEnd"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["weekEnd"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

