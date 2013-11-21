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

    /* START ACCESSOR FUNCTIONS */

    public function store_id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["store_id"])) {
                return $this->instance["store_id"];
            } elseif(isset($this->columns["store_id"]["default"])) {
                return $this->columns["store_id"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["store_id"] = func_get_arg(0);
        }
    }

    public function lane_id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["lane_id"])) {
                return $this->instance["lane_id"];
            } elseif(isset($this->columns["lane_id"]["default"])) {
                return $this->columns["lane_id"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["lane_id"] = func_get_arg(0);
        }
    }

    public function param_key()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["param_key"])) {
                return $this->instance["param_key"];
            } elseif(isset($this->columns["param_key"]["default"])) {
                return $this->columns["param_key"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["param_key"] = func_get_arg(0);
        }
    }

    public function param_value()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["param_value"])) {
                return $this->instance["param_value"];
            } elseif(isset($this->columns["param_value"]["default"])) {
                return $this->columns["param_value"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["param_value"] = func_get_arg(0);
        }
    }

    public function is_array()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["is_array"])) {
                return $this->instance["is_array"];
            } elseif(isset($this->columns["is_array"]["default"])) {
                return $this->columns["is_array"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["is_array"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

