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
  @class WfcHtHoursYTDModel
*/
class WfcHtHoursYTDModel extends BasicModel
{

    protected $name = "hoursytd";

    protected $columns = array(
    'empID' => array('type'=>'INT'),
    'regularHours' => array('type'=>'DOUBLE'),
    'overtimeHours' => array('type'=>'DOUBLE'),
    'emergencyHours' => array('type'=>'DOUBLE'),
    'rateHours' => array('type'=>'DOUBLE'),
    'totalHours' => array('type'=>'DOUBLE'),
    );

    public function create()
    {
        $query = "CREATE VIEW hoursytd AS 
            select empID AS empID,
            sum(hours) AS regularHours,
            sum(OTHours) AS overtimeHours,
            sum(EmergencyHours) AS emergencyHours,
            sum(SecondRateHours) AS rateHours,
            sum(hours) + sum(OTHours) + sum(SecondRateHours) + sum(EmergencyHours) AS totalHours 
            from ImportedHoursData where year = year(curdate())) group by empID";
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

    public function regularHours()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["regularHours"])) {
                return $this->instance["regularHours"];
            } else if (isset($this->columns["regularHours"]["default"])) {
                return $this->columns["regularHours"]["default"];
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
                'left' => 'regularHours',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["regularHours"]) || $this->instance["regularHours"] != func_get_args(0)) {
                if (!isset($this->columns["regularHours"]["ignore_updates"]) || $this->columns["regularHours"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["regularHours"] = func_get_arg(0);
        }
        return $this;
    }

    public function overtimeHours()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["overtimeHours"])) {
                return $this->instance["overtimeHours"];
            } else if (isset($this->columns["overtimeHours"]["default"])) {
                return $this->columns["overtimeHours"]["default"];
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
                'left' => 'overtimeHours',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["overtimeHours"]) || $this->instance["overtimeHours"] != func_get_args(0)) {
                if (!isset($this->columns["overtimeHours"]["ignore_updates"]) || $this->columns["overtimeHours"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["overtimeHours"] = func_get_arg(0);
        }
        return $this;
    }

    public function emergencyHours()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["emergencyHours"])) {
                return $this->instance["emergencyHours"];
            } else if (isset($this->columns["emergencyHours"]["default"])) {
                return $this->columns["emergencyHours"]["default"];
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
                'left' => 'emergencyHours',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["emergencyHours"]) || $this->instance["emergencyHours"] != func_get_args(0)) {
                if (!isset($this->columns["emergencyHours"]["ignore_updates"]) || $this->columns["emergencyHours"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["emergencyHours"] = func_get_arg(0);
        }
        return $this;
    }

    public function rateHours()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["rateHours"])) {
                return $this->instance["rateHours"];
            } else if (isset($this->columns["rateHours"]["default"])) {
                return $this->columns["rateHours"]["default"];
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
                'left' => 'rateHours',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["rateHours"]) || $this->instance["rateHours"] != func_get_args(0)) {
                if (!isset($this->columns["rateHours"]["ignore_updates"]) || $this->columns["rateHours"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["rateHours"] = func_get_arg(0);
        }
        return $this;
    }

    public function totalHours()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["totalHours"])) {
                return $this->instance["totalHours"];
            } else if (isset($this->columns["totalHours"]["default"])) {
                return $this->columns["totalHours"]["default"];
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
                'left' => 'totalHours',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["totalHours"]) || $this->instance["totalHours"] != func_get_args(0)) {
                if (!isset($this->columns["totalHours"]["ignore_updates"]) || $this->columns["totalHours"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["totalHours"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

