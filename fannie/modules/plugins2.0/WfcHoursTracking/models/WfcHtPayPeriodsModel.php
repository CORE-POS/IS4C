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
  @class WfcHtPayPeriodsModel
*/
class WfcHtPayPeriodsModel extends BasicModel
{

    protected $name = "PayPeriods";

    protected $columns = array(
    'periodID' => array('type'=>'INT', 'primary_key'=>true),
    'dateStr' => array('type'=>'VARCHAR(25)'),
    'year' => array('type'=>'SMALLINT', 'index'=>true),
    'startDate' => array('type'=>'DATETIME'),
    'endDate' => array('type'=>'DATETIME'),
    );

    /* START ACCESSOR FUNCTIONS */

    public function periodID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["periodID"])) {
                return $this->instance["periodID"];
            } else if (isset($this->columns["periodID"]["default"])) {
                return $this->columns["periodID"]["default"];
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
                'left' => 'periodID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["periodID"]) || $this->instance["periodID"] != func_get_args(0)) {
                if (!isset($this->columns["periodID"]["ignore_updates"]) || $this->columns["periodID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["periodID"] = func_get_arg(0);
        }
        return $this;
    }

    public function dateStr()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dateStr"])) {
                return $this->instance["dateStr"];
            } else if (isset($this->columns["dateStr"]["default"])) {
                return $this->columns["dateStr"]["default"];
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
                'left' => 'dateStr',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["dateStr"]) || $this->instance["dateStr"] != func_get_args(0)) {
                if (!isset($this->columns["dateStr"]["ignore_updates"]) || $this->columns["dateStr"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["dateStr"] = func_get_arg(0);
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

    public function startDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["startDate"])) {
                return $this->instance["startDate"];
            } else if (isset($this->columns["startDate"]["default"])) {
                return $this->columns["startDate"]["default"];
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
                'left' => 'startDate',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["startDate"]) || $this->instance["startDate"] != func_get_args(0)) {
                if (!isset($this->columns["startDate"]["ignore_updates"]) || $this->columns["startDate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["startDate"] = func_get_arg(0);
        }
        return $this;
    }

    public function endDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["endDate"])) {
                return $this->instance["endDate"];
            } else if (isset($this->columns["endDate"]["default"])) {
                return $this->columns["endDate"]["default"];
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
                'left' => 'endDate',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["endDate"]) || $this->instance["endDate"] != func_get_args(0)) {
                if (!isset($this->columns["endDate"]["ignore_updates"]) || $this->columns["endDate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["endDate"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

