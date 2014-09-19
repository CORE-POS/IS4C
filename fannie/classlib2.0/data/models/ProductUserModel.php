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
  @class ProductUserModel
*/
class ProductUserModel extends BasicModel 
{

    protected $name = "productUser";

    protected $columns = array(
    'upc' => array('type'=>'VARCHAR(13)', 'primary_key'=>True),
    'description' => array('type'=>'VARCHAR(255)'),
    'brand' => array('type'=>'VARCHAR(255)'),
    'sizing' => array('type'=>'VARCHAR(255)'),
    'photo' => array('type'=>'VARCHAR(255)'),
    'long_text' => array('type'=>'TEXT'),
    'enableOnline' => array('type'=>'TINYINT'),
    'soldOut' => array('type'=>'TINYINT', 'default'=>0),
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

    public function brand()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["brand"])) {
                return $this->instance["brand"];
            } else if (isset($this->columns["brand"]["default"])) {
                return $this->columns["brand"]["default"];
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
                'left' => 'brand',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["brand"]) || $this->instance["brand"] != func_get_args(0)) {
                if (!isset($this->columns["brand"]["ignore_updates"]) || $this->columns["brand"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["brand"] = func_get_arg(0);
        }
        return $this;
    }

    public function sizing()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["sizing"])) {
                return $this->instance["sizing"];
            } else if (isset($this->columns["sizing"]["default"])) {
                return $this->columns["sizing"]["default"];
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
                'left' => 'sizing',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["sizing"]) || $this->instance["sizing"] != func_get_args(0)) {
                if (!isset($this->columns["sizing"]["ignore_updates"]) || $this->columns["sizing"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["sizing"] = func_get_arg(0);
        }
        return $this;
    }

    public function photo()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["photo"])) {
                return $this->instance["photo"];
            } else if (isset($this->columns["photo"]["default"])) {
                return $this->columns["photo"]["default"];
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
                'left' => 'photo',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["photo"]) || $this->instance["photo"] != func_get_args(0)) {
                if (!isset($this->columns["photo"]["ignore_updates"]) || $this->columns["photo"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["photo"] = func_get_arg(0);
        }
        return $this;
    }

    public function long_text()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["long_text"])) {
                return $this->instance["long_text"];
            } else if (isset($this->columns["long_text"]["default"])) {
                return $this->columns["long_text"]["default"];
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
                'left' => 'long_text',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["long_text"]) || $this->instance["long_text"] != func_get_args(0)) {
                if (!isset($this->columns["long_text"]["ignore_updates"]) || $this->columns["long_text"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["long_text"] = func_get_arg(0);
        }
        return $this;
    }

    public function enableOnline()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["enableOnline"])) {
                return $this->instance["enableOnline"];
            } else if (isset($this->columns["enableOnline"]["default"])) {
                return $this->columns["enableOnline"]["default"];
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
                'left' => 'enableOnline',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["enableOnline"]) || $this->instance["enableOnline"] != func_get_args(0)) {
                if (!isset($this->columns["enableOnline"]["ignore_updates"]) || $this->columns["enableOnline"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["enableOnline"] = func_get_arg(0);
        }
        return $this;
    }

    public function soldOut()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["soldOut"])) {
                return $this->instance["soldOut"];
            } else if (isset($this->columns["soldOut"]["default"])) {
                return $this->columns["soldOut"]["default"];
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
                'left' => 'soldOut',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["soldOut"]) || $this->instance["soldOut"] != func_get_args(0)) {
                if (!isset($this->columns["soldOut"]["ignore_updates"]) || $this->columns["soldOut"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["soldOut"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

