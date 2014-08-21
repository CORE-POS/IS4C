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
  @class ServiceScalesModel
*/
class ServiceScalesModel extends BasicModel
{

    protected $name = "ServiceScales";

    protected $columns = array(
    'serviceScaleID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'description' => array('type'=>'VARCHAR(50)'),
    'host' => array('type'=>'VARCHAR(50)'),
    'scaleType' => array('type'=>'VARCHAR(50)'),
    'scaleDeptName' => array('type'=>'VARCHAR(25)'),
    'superID' => array('type'=>'INT'),
    );

    /* START ACCESSOR FUNCTIONS */

    public function serviceScaleID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["serviceScaleID"])) {
                return $this->instance["serviceScaleID"];
            } else if (isset($this->columns["serviceScaleID"]["default"])) {
                return $this->columns["serviceScaleID"]["default"];
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
                'left' => 'serviceScaleID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["serviceScaleID"]) || $this->instance["serviceScaleID"] != func_get_args(0)) {
                if (!isset($this->columns["serviceScaleID"]["ignore_updates"]) || $this->columns["serviceScaleID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["serviceScaleID"] = func_get_arg(0);
        }
        return $this;
    }

    public function description()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["description"])) {
                return $this->instance["description"];
            } else if (isset($this->columns["description"]["default"])) {
                return $this->columns["description"]["default"];
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
                'left' => 'description',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["description"]) || $this->instance["description"] != func_get_args(0)) {
                if (!isset($this->columns["description"]["ignore_updates"]) || $this->columns["description"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["description"] = func_get_arg(0);
        }
        return $this;
    }

    public function host()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["host"])) {
                return $this->instance["host"];
            } else if (isset($this->columns["host"]["default"])) {
                return $this->columns["host"]["default"];
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
                'left' => 'host',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["host"]) || $this->instance["host"] != func_get_args(0)) {
                if (!isset($this->columns["host"]["ignore_updates"]) || $this->columns["host"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["host"] = func_get_arg(0);
        }
        return $this;
    }

    public function scaleType()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["scaleType"])) {
                return $this->instance["scaleType"];
            } else if (isset($this->columns["scaleType"]["default"])) {
                return $this->columns["scaleType"]["default"];
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
                'left' => 'scaleType',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["scaleType"]) || $this->instance["scaleType"] != func_get_args(0)) {
                if (!isset($this->columns["scaleType"]["ignore_updates"]) || $this->columns["scaleType"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["scaleType"] = func_get_arg(0);
        }
        return $this;
    }

    public function scaleDeptName()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["scaleDeptName"])) {
                return $this->instance["scaleDeptName"];
            } else if (isset($this->columns["scaleDeptName"]["default"])) {
                return $this->columns["scaleDeptName"]["default"];
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
                'left' => 'scaleDeptName',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["scaleDeptName"]) || $this->instance["scaleDeptName"] != func_get_args(0)) {
                if (!isset($this->columns["scaleDeptName"]["ignore_updates"]) || $this->columns["scaleDeptName"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["scaleDeptName"] = func_get_arg(0);
        }
        return $this;
    }

    public function superID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["superID"])) {
                return $this->instance["superID"];
            } else if (isset($this->columns["superID"]["default"])) {
                return $this->columns["superID"]["default"];
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
                'left' => 'superID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["superID"]) || $this->instance["superID"] != func_get_args(0)) {
                if (!isset($this->columns["superID"]["ignore_updates"]) || $this->columns["superID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["superID"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

