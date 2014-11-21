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
  @class ParametersModel
*/
class ParametersModel extends BasicModel
{

    protected $name = "parameters";
    protected $preferred_db = 'op';

    protected $columns = array(
    'store_id' => array('type'=>'SMALLINT', 'primary_key'=>true),
    'lane_id' => array('type'=>'SMALLINT', 'primary_key'=>true),
    'param_key' => array('type'=>'VARCHAR(100)', 'primary_key'=>true),
    'param_value' => array('type'=>'VARCHAR(255)'),
    'is_array' => array('type'=>'TINYINT'),
	);

    /**
      Get the parameter's effective value by
      transforming it into an array or boolean
      if appropriate
      @return [mixed] param_value as correct PHP type
    */
    public function materializeValue()
    {
        $value = $this->param_value();
        if ($this->is_array()) {
            if ($value === '') {
                $value = array();
            } else {
                $value = explode(',', $value);
            }
            if (isset($value[0]) && strstr($value[0], '=>')) {
                $tmp = array();
                foreach ($value as $pair) {
                    list($key, $val) = explode('=>', $pair, 2);
                    $tmp[$key] = $val;
                }
                $value = $tmp;
            }
        } elseif (strtoupper($value) === 'TRUE') {
            $value = true;
        } elseif (strtoupper($value) === 'FALSE') {
            $value = false;
        }

        return $value;
    }

    public function doc()
    {
        return '
Table: parameters

Columns:
	store_id int
	lane_id int
	param_key varchar
	param_value varchar
	is_array int

Depends on:
    none

Use:
Partial replacement for ini.php.
This differs from the lane_config table.
This contains actual values where as lane_config
contains PHP code snippets that can
be written to a file.

Values with store_id=0 (or NULL) and lane_id=0 (or NULL)
are applied first, then values with the lane\'s own
lane_id are applied second as local overrides. A similar
precedent level based on store_id may be added at a later date.
        ';
    }

    /* START ACCESSOR FUNCTIONS */

    public function store_id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["store_id"])) {
                return $this->instance["store_id"];
            } else if (isset($this->columns["store_id"]["default"])) {
                return $this->columns["store_id"]["default"];
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
                'left' => 'store_id',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["store_id"]) || $this->instance["store_id"] != func_get_args(0)) {
                if (!isset($this->columns["store_id"]["ignore_updates"]) || $this->columns["store_id"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["store_id"] = func_get_arg(0);
        }
        return $this;
    }

    public function lane_id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["lane_id"])) {
                return $this->instance["lane_id"];
            } else if (isset($this->columns["lane_id"]["default"])) {
                return $this->columns["lane_id"]["default"];
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
                'left' => 'lane_id',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["lane_id"]) || $this->instance["lane_id"] != func_get_args(0)) {
                if (!isset($this->columns["lane_id"]["ignore_updates"]) || $this->columns["lane_id"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["lane_id"] = func_get_arg(0);
        }
        return $this;
    }

    public function param_key()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["param_key"])) {
                return $this->instance["param_key"];
            } else if (isset($this->columns["param_key"]["default"])) {
                return $this->columns["param_key"]["default"];
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
                'left' => 'param_key',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["param_key"]) || $this->instance["param_key"] != func_get_args(0)) {
                if (!isset($this->columns["param_key"]["ignore_updates"]) || $this->columns["param_key"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["param_key"] = func_get_arg(0);
        }
        return $this;
    }

    public function param_value()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["param_value"])) {
                return $this->instance["param_value"];
            } else if (isset($this->columns["param_value"]["default"])) {
                return $this->columns["param_value"]["default"];
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
                'left' => 'param_value',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["param_value"]) || $this->instance["param_value"] != func_get_args(0)) {
                if (!isset($this->columns["param_value"]["ignore_updates"]) || $this->columns["param_value"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["param_value"] = func_get_arg(0);
        }
        return $this;
    }

    public function is_array()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["is_array"])) {
                return $this->instance["is_array"];
            } else if (isset($this->columns["is_array"]["default"])) {
                return $this->columns["is_array"]["default"];
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
                'left' => 'is_array',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["is_array"]) || $this->instance["is_array"] != func_get_args(0)) {
                if (!isset($this->columns["is_array"]["ignore_updates"]) || $this->columns["is_array"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["is_array"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

