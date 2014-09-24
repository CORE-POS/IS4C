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
  @class ObfLaborModel
*/
class ObfLaborModel extends BasicModel
{

    protected $name = "ObfLabor";

    protected $columns = array(
    'obfLaborID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'obfWeekID' => array('type'=>'INT'),
    'obfCategoryID' => array('type'=>'INT'),
    'hours' => array('type'=>'DOUBLE'),
    'wages' => array('type'=>'DOUBLE'),
    'laborTarget' => array('type'=>'DOUBLE'),
    'hoursTarget' => array('type'=>'DOUBLE'),
    'averageWage' => array('type'=>'MONEY'),
    'forecastSales' => array('type'=>'MONEY'),
    );

    protected $unique = array('obfWeekID', 'obfCategoryID');

    /* START ACCESSOR FUNCTIONS */

    public function obfLaborID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["obfLaborID"])) {
                return $this->instance["obfLaborID"];
            } else if (isset($this->columns["obfLaborID"]["default"])) {
                return $this->columns["obfLaborID"]["default"];
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
                'left' => 'obfLaborID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["obfLaborID"]) || $this->instance["obfLaborID"] != func_get_args(0)) {
                if (!isset($this->columns["obfLaborID"]["ignore_updates"]) || $this->columns["obfLaborID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["obfLaborID"] = func_get_arg(0);
        }
        return $this;
    }

    public function obfWeekID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["obfWeekID"])) {
                return $this->instance["obfWeekID"];
            } else if (isset($this->columns["obfWeekID"]["default"])) {
                return $this->columns["obfWeekID"]["default"];
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
                'left' => 'obfWeekID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["obfWeekID"]) || $this->instance["obfWeekID"] != func_get_args(0)) {
                if (!isset($this->columns["obfWeekID"]["ignore_updates"]) || $this->columns["obfWeekID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["obfWeekID"] = func_get_arg(0);
        }
        return $this;
    }

    public function obfCategoryID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["obfCategoryID"])) {
                return $this->instance["obfCategoryID"];
            } else if (isset($this->columns["obfCategoryID"]["default"])) {
                return $this->columns["obfCategoryID"]["default"];
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
                'left' => 'obfCategoryID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["obfCategoryID"]) || $this->instance["obfCategoryID"] != func_get_args(0)) {
                if (!isset($this->columns["obfCategoryID"]["ignore_updates"]) || $this->columns["obfCategoryID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["obfCategoryID"] = func_get_arg(0);
        }
        return $this;
    }

    public function hours()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["hours"])) {
                return $this->instance["hours"];
            } else if (isset($this->columns["hours"]["default"])) {
                return $this->columns["hours"]["default"];
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
                'left' => 'hours',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["hours"]) || $this->instance["hours"] != func_get_args(0)) {
                if (!isset($this->columns["hours"]["ignore_updates"]) || $this->columns["hours"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["hours"] = func_get_arg(0);
        }
        return $this;
    }

    public function wages()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["wages"])) {
                return $this->instance["wages"];
            } else if (isset($this->columns["wages"]["default"])) {
                return $this->columns["wages"]["default"];
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
                'left' => 'wages',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["wages"]) || $this->instance["wages"] != func_get_args(0)) {
                if (!isset($this->columns["wages"]["ignore_updates"]) || $this->columns["wages"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["wages"] = func_get_arg(0);
        }
        return $this;
    }

    public function laborTarget()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["laborTarget"])) {
                return $this->instance["laborTarget"];
            } else if (isset($this->columns["laborTarget"]["default"])) {
                return $this->columns["laborTarget"]["default"];
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
                'left' => 'laborTarget',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["laborTarget"]) || $this->instance["laborTarget"] != func_get_args(0)) {
                if (!isset($this->columns["laborTarget"]["ignore_updates"]) || $this->columns["laborTarget"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["laborTarget"] = func_get_arg(0);
        }
        return $this;
    }

    public function hoursTarget()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["hoursTarget"])) {
                return $this->instance["hoursTarget"];
            } else if (isset($this->columns["hoursTarget"]["default"])) {
                return $this->columns["hoursTarget"]["default"];
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
                'left' => 'hoursTarget',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["hoursTarget"]) || $this->instance["hoursTarget"] != func_get_args(0)) {
                if (!isset($this->columns["hoursTarget"]["ignore_updates"]) || $this->columns["hoursTarget"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["hoursTarget"] = func_get_arg(0);
        }
        return $this;
    }

    public function averageWage()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["averageWage"])) {
                return $this->instance["averageWage"];
            } else if (isset($this->columns["averageWage"]["default"])) {
                return $this->columns["averageWage"]["default"];
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
                'left' => 'averageWage',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["averageWage"]) || $this->instance["averageWage"] != func_get_args(0)) {
                if (!isset($this->columns["averageWage"]["ignore_updates"]) || $this->columns["averageWage"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["averageWage"] = func_get_arg(0);
        }
        return $this;
    }

    public function forecastSales()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["forecastSales"])) {
                return $this->instance["forecastSales"];
            } else if (isset($this->columns["forecastSales"]["default"])) {
                return $this->columns["forecastSales"]["default"];
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
                'left' => 'forecastSales',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["forecastSales"]) || $this->instance["forecastSales"] != func_get_args(0)) {
                if (!isset($this->columns["forecastSales"]["ignore_updates"]) || $this->columns["forecastSales"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["forecastSales"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

