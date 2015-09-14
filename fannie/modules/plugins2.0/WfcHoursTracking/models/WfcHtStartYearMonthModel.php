<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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
  @class WfcHtStartYearMonthModel
*/
class WfcHtStartYearMonthModel extends BasicModel
{

    protected $name = "startyearmonth";

    protected $columns = array(
    'empID' => array('type'=>'INT'),
    'year' => array('type'=>'INT'),
    'month' => array('type'=>'INT'),
    );

    public function create()
    {
        $query = "CREATE VIEW startyearmonth AS 
            select f.empID AS empID,
            p.year AS year,
            case 
                when p.dateStr = 'pre-2008' then 5 
                when locate('/',p.dateStr) = 0 then 1 
                else left(p.dateStr, locate('/',p.dateStr) - 1) 
            end) AS month,
            p.dateStr AS dateStr 
            from firstpayperiod f 
            left join PayPeriods p on f.periodID = p.periodID";
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

    public function year()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["year"])) {
                return $this->instance["year"];
            } else if (isset($this->columns["year"]["default"])) {
                return $this->columns["year"]["default"];
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
                'left' => 'year',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["year"]) || $this->instance["year"] != func_get_args(0)) {
                if (!isset($this->columns["year"]["ignore_updates"]) || $this->columns["year"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["year"] = func_get_arg(0);
        }
        return $this;
    }

    public function month()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["month"])) {
                return $this->instance["month"];
            } else if (isset($this->columns["month"]["default"])) {
                return $this->columns["month"]["default"];
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
                'left' => 'month',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["month"]) || $this->instance["month"] != func_get_args(0)) {
                if (!isset($this->columns["month"]["ignore_updates"]) || $this->columns["month"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["month"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

