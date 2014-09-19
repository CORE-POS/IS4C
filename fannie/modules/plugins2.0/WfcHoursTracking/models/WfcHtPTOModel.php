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
  @class WfcHtPTOModel
*/
class WfcHtPTOModel extends BasicModel
{

    protected $name = "pto";

    protected $columns = array(
    'empID' => array('type'=>'INT'),
    'PTORemaining' => array('type'=>'DOUBLE'),
    'totalPTO' => array('type'=>'DOUBLE'),
    );

    public function create()
    {
        $query = "CREATE VIEW pto AS 
            select i.empID AS empID,
            ROUND(max(p.PTOHours) - sum(case when i.periodID > e.PTOCutoff then i.PTOHours else 0 end), 2) AS PTORemaining,
            max(p.PTOHours) AS totalPTO 
            from ImportedHoursData i 
            LEFT JOIN employees e on i.empID = e.empID 
            LEFT JOIN PTOLevels p on p.LevelID = e.PTOLevel
            group by i.empID";
        $try = $this->connection->query($query);

        if ($try) {
            return true;
        } else {
            return false;
        }
    }

    /* START ACCESSOR FUNCTIONS */

    public function empID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["empID"])) {
                return $this->instance["empID"];
            } else if (isset($this->columns["empID"]["default"])) {
                return $this->columns["empID"]["default"];
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
                'left' => 'empID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["empID"]) || $this->instance["empID"] != func_get_args(0)) {
                if (!isset($this->columns["empID"]["ignore_updates"]) || $this->columns["empID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["empID"] = func_get_arg(0);
        }
        return $this;
    }

    public function PTORemaining()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["PTORemaining"])) {
                return $this->instance["PTORemaining"];
            } else if (isset($this->columns["PTORemaining"]["default"])) {
                return $this->columns["PTORemaining"]["default"];
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
                'left' => 'PTORemaining',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["PTORemaining"]) || $this->instance["PTORemaining"] != func_get_args(0)) {
                if (!isset($this->columns["PTORemaining"]["ignore_updates"]) || $this->columns["PTORemaining"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["PTORemaining"] = func_get_arg(0);
        }
        return $this;
    }

    public function totalPTO()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["totalPTO"])) {
                return $this->instance["totalPTO"];
            } else if (isset($this->columns["totalPTO"]["default"])) {
                return $this->columns["totalPTO"]["default"];
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
                'left' => 'totalPTO',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["totalPTO"]) || $this->instance["totalPTO"] != func_get_args(0)) {
                if (!isset($this->columns["totalPTO"]["ignore_updates"]) || $this->columns["totalPTO"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["totalPTO"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

