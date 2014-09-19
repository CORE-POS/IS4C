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
  @class ProdExtraModel
*/
class ProdExtraModel extends BasicModel
{

    protected $name = "prodExtra";

    protected $columns = array(
    'upc' => array('type'=>'VARCHAR(13)', 'primary_key'=>true),
    'distributor' => array('type'=>'VARCHAR(100)'),
    'manufacturer' => array('type'=>'VARCHAR(100)'),
    'cost' => array('type'=>'MONEY'),
    'margin' => array('type'=>'DOUBLE'),
    'variable_pricing' => array('type'=>'TINYINT'),
    'location' => array('type'=>'VARCHAR(30)'),
    'case_quantity' => array('type'=>'VARCHAR(15)'),
    'case_cost' => array('type'=>'MONEY'),
    'case_info' => array('type'=>'VARCHAR(100)'),
    );

    /* START ACCESSOR FUNCTIONS */

    public function upc()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["upc"])) {
                return $this->instance["upc"];
            } else if (isset($this->columns["upc"]["default"])) {
                return $this->columns["upc"]["default"];
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
                'left' => 'upc',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["upc"]) || $this->instance["upc"] != func_get_args(0)) {
                if (!isset($this->columns["upc"]["ignore_updates"]) || $this->columns["upc"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["upc"] = func_get_arg(0);
        }
        return $this;
    }

    public function distributor()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["distributor"])) {
                return $this->instance["distributor"];
            } else if (isset($this->columns["distributor"]["default"])) {
                return $this->columns["distributor"]["default"];
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
                'left' => 'distributor',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["distributor"]) || $this->instance["distributor"] != func_get_args(0)) {
                if (!isset($this->columns["distributor"]["ignore_updates"]) || $this->columns["distributor"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["distributor"] = func_get_arg(0);
        }
        return $this;
    }

    public function manufacturer()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["manufacturer"])) {
                return $this->instance["manufacturer"];
            } else if (isset($this->columns["manufacturer"]["default"])) {
                return $this->columns["manufacturer"]["default"];
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
                'left' => 'manufacturer',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["manufacturer"]) || $this->instance["manufacturer"] != func_get_args(0)) {
                if (!isset($this->columns["manufacturer"]["ignore_updates"]) || $this->columns["manufacturer"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["manufacturer"] = func_get_arg(0);
        }
        return $this;
    }

    public function cost()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["cost"])) {
                return $this->instance["cost"];
            } else if (isset($this->columns["cost"]["default"])) {
                return $this->columns["cost"]["default"];
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
                'left' => 'cost',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["cost"]) || $this->instance["cost"] != func_get_args(0)) {
                if (!isset($this->columns["cost"]["ignore_updates"]) || $this->columns["cost"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["cost"] = func_get_arg(0);
        }
        return $this;
    }

    public function margin()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["margin"])) {
                return $this->instance["margin"];
            } else if (isset($this->columns["margin"]["default"])) {
                return $this->columns["margin"]["default"];
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
                'left' => 'margin',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["margin"]) || $this->instance["margin"] != func_get_args(0)) {
                if (!isset($this->columns["margin"]["ignore_updates"]) || $this->columns["margin"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["margin"] = func_get_arg(0);
        }
        return $this;
    }

    public function variable_pricing()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["variable_pricing"])) {
                return $this->instance["variable_pricing"];
            } else if (isset($this->columns["variable_pricing"]["default"])) {
                return $this->columns["variable_pricing"]["default"];
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
                'left' => 'variable_pricing',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["variable_pricing"]) || $this->instance["variable_pricing"] != func_get_args(0)) {
                if (!isset($this->columns["variable_pricing"]["ignore_updates"]) || $this->columns["variable_pricing"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["variable_pricing"] = func_get_arg(0);
        }
        return $this;
    }

    public function location()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["location"])) {
                return $this->instance["location"];
            } else if (isset($this->columns["location"]["default"])) {
                return $this->columns["location"]["default"];
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
                'left' => 'location',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["location"]) || $this->instance["location"] != func_get_args(0)) {
                if (!isset($this->columns["location"]["ignore_updates"]) || $this->columns["location"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["location"] = func_get_arg(0);
        }
        return $this;
    }

    public function case_quantity()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["case_quantity"])) {
                return $this->instance["case_quantity"];
            } else if (isset($this->columns["case_quantity"]["default"])) {
                return $this->columns["case_quantity"]["default"];
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
                'left' => 'case_quantity',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["case_quantity"]) || $this->instance["case_quantity"] != func_get_args(0)) {
                if (!isset($this->columns["case_quantity"]["ignore_updates"]) || $this->columns["case_quantity"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["case_quantity"] = func_get_arg(0);
        }
        return $this;
    }

    public function case_cost()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["case_cost"])) {
                return $this->instance["case_cost"];
            } else if (isset($this->columns["case_cost"]["default"])) {
                return $this->columns["case_cost"]["default"];
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
                'left' => 'case_cost',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["case_cost"]) || $this->instance["case_cost"] != func_get_args(0)) {
                if (!isset($this->columns["case_cost"]["ignore_updates"]) || $this->columns["case_cost"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["case_cost"] = func_get_arg(0);
        }
        return $this;
    }

    public function case_info()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["case_info"])) {
                return $this->instance["case_info"];
            } else if (isset($this->columns["case_info"]["default"])) {
                return $this->columns["case_info"]["default"];
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
                'left' => 'case_info',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["case_info"]) || $this->instance["case_info"] != func_get_args(0)) {
                if (!isset($this->columns["case_info"]["ignore_updates"]) || $this->columns["case_info"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["case_info"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

