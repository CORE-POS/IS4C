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
  @class ProductBackupModel
*/
class ProductBackupModel extends ProductsModel
{

    protected $name = "productBackup";

    public function doc()
    {
        return '
Table: productBackup

Columns:
    same as products

Depends on:
    products (table)

Use:
Stores an older snapshot of products
Easier to pull small bits of info from
instead of restoring an entire DB backup
        ';
    }

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

    public function formatted_name()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["formatted_name"])) {
                return $this->instance["formatted_name"];
            } else if (isset($this->columns["formatted_name"]["default"])) {
                return $this->columns["formatted_name"]["default"];
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
                'left' => 'formatted_name',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["formatted_name"]) || $this->instance["formatted_name"] != func_get_args(0)) {
                if (!isset($this->columns["formatted_name"]["ignore_updates"]) || $this->columns["formatted_name"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["formatted_name"] = func_get_arg(0);
        }
        return $this;
    }

    public function normal_price()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["normal_price"])) {
                return $this->instance["normal_price"];
            } else if (isset($this->columns["normal_price"]["default"])) {
                return $this->columns["normal_price"]["default"];
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
                'left' => 'normal_price',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["normal_price"]) || $this->instance["normal_price"] != func_get_args(0)) {
                if (!isset($this->columns["normal_price"]["ignore_updates"]) || $this->columns["normal_price"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["normal_price"] = func_get_arg(0);
        }
        return $this;
    }

    public function pricemethod()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["pricemethod"])) {
                return $this->instance["pricemethod"];
            } else if (isset($this->columns["pricemethod"]["default"])) {
                return $this->columns["pricemethod"]["default"];
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
                'left' => 'pricemethod',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["pricemethod"]) || $this->instance["pricemethod"] != func_get_args(0)) {
                if (!isset($this->columns["pricemethod"]["ignore_updates"]) || $this->columns["pricemethod"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["pricemethod"] = func_get_arg(0);
        }
        return $this;
    }

    public function groupprice()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["groupprice"])) {
                return $this->instance["groupprice"];
            } else if (isset($this->columns["groupprice"]["default"])) {
                return $this->columns["groupprice"]["default"];
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
                'left' => 'groupprice',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["groupprice"]) || $this->instance["groupprice"] != func_get_args(0)) {
                if (!isset($this->columns["groupprice"]["ignore_updates"]) || $this->columns["groupprice"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["groupprice"] = func_get_arg(0);
        }
        return $this;
    }

    public function quantity()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["quantity"])) {
                return $this->instance["quantity"];
            } else if (isset($this->columns["quantity"]["default"])) {
                return $this->columns["quantity"]["default"];
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
                'left' => 'quantity',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["quantity"]) || $this->instance["quantity"] != func_get_args(0)) {
                if (!isset($this->columns["quantity"]["ignore_updates"]) || $this->columns["quantity"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["quantity"] = func_get_arg(0);
        }
        return $this;
    }

    public function special_price()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["special_price"])) {
                return $this->instance["special_price"];
            } else if (isset($this->columns["special_price"]["default"])) {
                return $this->columns["special_price"]["default"];
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
                'left' => 'special_price',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["special_price"]) || $this->instance["special_price"] != func_get_args(0)) {
                if (!isset($this->columns["special_price"]["ignore_updates"]) || $this->columns["special_price"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["special_price"] = func_get_arg(0);
        }
        return $this;
    }

    public function specialpricemethod()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["specialpricemethod"])) {
                return $this->instance["specialpricemethod"];
            } else if (isset($this->columns["specialpricemethod"]["default"])) {
                return $this->columns["specialpricemethod"]["default"];
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
                'left' => 'specialpricemethod',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["specialpricemethod"]) || $this->instance["specialpricemethod"] != func_get_args(0)) {
                if (!isset($this->columns["specialpricemethod"]["ignore_updates"]) || $this->columns["specialpricemethod"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["specialpricemethod"] = func_get_arg(0);
        }
        return $this;
    }

    public function specialgroupprice()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["specialgroupprice"])) {
                return $this->instance["specialgroupprice"];
            } else if (isset($this->columns["specialgroupprice"]["default"])) {
                return $this->columns["specialgroupprice"]["default"];
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
                'left' => 'specialgroupprice',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["specialgroupprice"]) || $this->instance["specialgroupprice"] != func_get_args(0)) {
                if (!isset($this->columns["specialgroupprice"]["ignore_updates"]) || $this->columns["specialgroupprice"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["specialgroupprice"] = func_get_arg(0);
        }
        return $this;
    }

    public function specialquantity()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["specialquantity"])) {
                return $this->instance["specialquantity"];
            } else if (isset($this->columns["specialquantity"]["default"])) {
                return $this->columns["specialquantity"]["default"];
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
                'left' => 'specialquantity',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["specialquantity"]) || $this->instance["specialquantity"] != func_get_args(0)) {
                if (!isset($this->columns["specialquantity"]["ignore_updates"]) || $this->columns["specialquantity"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["specialquantity"] = func_get_arg(0);
        }
        return $this;
    }

    public function start_date()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["start_date"])) {
                return $this->instance["start_date"];
            } else if (isset($this->columns["start_date"]["default"])) {
                return $this->columns["start_date"]["default"];
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
                'left' => 'start_date',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["start_date"]) || $this->instance["start_date"] != func_get_args(0)) {
                if (!isset($this->columns["start_date"]["ignore_updates"]) || $this->columns["start_date"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["start_date"] = func_get_arg(0);
        }
        return $this;
    }

    public function end_date()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["end_date"])) {
                return $this->instance["end_date"];
            } else if (isset($this->columns["end_date"]["default"])) {
                return $this->columns["end_date"]["default"];
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
                'left' => 'end_date',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["end_date"]) || $this->instance["end_date"] != func_get_args(0)) {
                if (!isset($this->columns["end_date"]["ignore_updates"]) || $this->columns["end_date"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["end_date"] = func_get_arg(0);
        }
        return $this;
    }

    public function department()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["department"])) {
                return $this->instance["department"];
            } else if (isset($this->columns["department"]["default"])) {
                return $this->columns["department"]["default"];
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
                'left' => 'department',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["department"]) || $this->instance["department"] != func_get_args(0)) {
                if (!isset($this->columns["department"]["ignore_updates"]) || $this->columns["department"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["department"] = func_get_arg(0);
        }
        return $this;
    }

    public function size()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["size"])) {
                return $this->instance["size"];
            } else if (isset($this->columns["size"]["default"])) {
                return $this->columns["size"]["default"];
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
                'left' => 'size',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["size"]) || $this->instance["size"] != func_get_args(0)) {
                if (!isset($this->columns["size"]["ignore_updates"]) || $this->columns["size"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["size"] = func_get_arg(0);
        }
        return $this;
    }

    public function tax()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["tax"])) {
                return $this->instance["tax"];
            } else if (isset($this->columns["tax"]["default"])) {
                return $this->columns["tax"]["default"];
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
                'left' => 'tax',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["tax"]) || $this->instance["tax"] != func_get_args(0)) {
                if (!isset($this->columns["tax"]["ignore_updates"]) || $this->columns["tax"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["tax"] = func_get_arg(0);
        }
        return $this;
    }

    public function foodstamp()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["foodstamp"])) {
                return $this->instance["foodstamp"];
            } else if (isset($this->columns["foodstamp"]["default"])) {
                return $this->columns["foodstamp"]["default"];
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
                'left' => 'foodstamp',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["foodstamp"]) || $this->instance["foodstamp"] != func_get_args(0)) {
                if (!isset($this->columns["foodstamp"]["ignore_updates"]) || $this->columns["foodstamp"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["foodstamp"] = func_get_arg(0);
        }
        return $this;
    }

    public function scale()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["scale"])) {
                return $this->instance["scale"];
            } else if (isset($this->columns["scale"]["default"])) {
                return $this->columns["scale"]["default"];
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
                'left' => 'scale',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["scale"]) || $this->instance["scale"] != func_get_args(0)) {
                if (!isset($this->columns["scale"]["ignore_updates"]) || $this->columns["scale"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["scale"] = func_get_arg(0);
        }
        return $this;
    }

    public function scaleprice()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["scaleprice"])) {
                return $this->instance["scaleprice"];
            } else if (isset($this->columns["scaleprice"]["default"])) {
                return $this->columns["scaleprice"]["default"];
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
                'left' => 'scaleprice',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["scaleprice"]) || $this->instance["scaleprice"] != func_get_args(0)) {
                if (!isset($this->columns["scaleprice"]["ignore_updates"]) || $this->columns["scaleprice"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["scaleprice"] = func_get_arg(0);
        }
        return $this;
    }

    public function mixmatchcode()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["mixmatchcode"])) {
                return $this->instance["mixmatchcode"];
            } else if (isset($this->columns["mixmatchcode"]["default"])) {
                return $this->columns["mixmatchcode"]["default"];
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
                'left' => 'mixmatchcode',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["mixmatchcode"]) || $this->instance["mixmatchcode"] != func_get_args(0)) {
                if (!isset($this->columns["mixmatchcode"]["ignore_updates"]) || $this->columns["mixmatchcode"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["mixmatchcode"] = func_get_arg(0);
        }
        return $this;
    }

    public function modified()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["modified"])) {
                return $this->instance["modified"];
            } else if (isset($this->columns["modified"]["default"])) {
                return $this->columns["modified"]["default"];
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
                'left' => 'modified',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["modified"]) || $this->instance["modified"] != func_get_args(0)) {
                if (!isset($this->columns["modified"]["ignore_updates"]) || $this->columns["modified"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["modified"] = func_get_arg(0);
        }
        return $this;
    }

    public function advertised()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["advertised"])) {
                return $this->instance["advertised"];
            } else if (isset($this->columns["advertised"]["default"])) {
                return $this->columns["advertised"]["default"];
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
                'left' => 'advertised',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["advertised"]) || $this->instance["advertised"] != func_get_args(0)) {
                if (!isset($this->columns["advertised"]["ignore_updates"]) || $this->columns["advertised"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["advertised"] = func_get_arg(0);
        }
        return $this;
    }

    public function tareweight()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["tareweight"])) {
                return $this->instance["tareweight"];
            } else if (isset($this->columns["tareweight"]["default"])) {
                return $this->columns["tareweight"]["default"];
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
                'left' => 'tareweight',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["tareweight"]) || $this->instance["tareweight"] != func_get_args(0)) {
                if (!isset($this->columns["tareweight"]["ignore_updates"]) || $this->columns["tareweight"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["tareweight"] = func_get_arg(0);
        }
        return $this;
    }

    public function discount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["discount"])) {
                return $this->instance["discount"];
            } else if (isset($this->columns["discount"]["default"])) {
                return $this->columns["discount"]["default"];
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
                'left' => 'discount',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["discount"]) || $this->instance["discount"] != func_get_args(0)) {
                if (!isset($this->columns["discount"]["ignore_updates"]) || $this->columns["discount"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["discount"] = func_get_arg(0);
        }
        return $this;
    }

    public function discounttype()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["discounttype"])) {
                return $this->instance["discounttype"];
            } else if (isset($this->columns["discounttype"]["default"])) {
                return $this->columns["discounttype"]["default"];
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
                'left' => 'discounttype',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["discounttype"]) || $this->instance["discounttype"] != func_get_args(0)) {
                if (!isset($this->columns["discounttype"]["ignore_updates"]) || $this->columns["discounttype"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["discounttype"] = func_get_arg(0);
        }
        return $this;
    }

    public function line_item_discountable()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["line_item_discountable"])) {
                return $this->instance["line_item_discountable"];
            } else if (isset($this->columns["line_item_discountable"]["default"])) {
                return $this->columns["line_item_discountable"]["default"];
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
                'left' => 'line_item_discountable',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["line_item_discountable"]) || $this->instance["line_item_discountable"] != func_get_args(0)) {
                if (!isset($this->columns["line_item_discountable"]["ignore_updates"]) || $this->columns["line_item_discountable"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["line_item_discountable"] = func_get_arg(0);
        }
        return $this;
    }

    public function unitofmeasure()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["unitofmeasure"])) {
                return $this->instance["unitofmeasure"];
            } else if (isset($this->columns["unitofmeasure"]["default"])) {
                return $this->columns["unitofmeasure"]["default"];
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
                'left' => 'unitofmeasure',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["unitofmeasure"]) || $this->instance["unitofmeasure"] != func_get_args(0)) {
                if (!isset($this->columns["unitofmeasure"]["ignore_updates"]) || $this->columns["unitofmeasure"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["unitofmeasure"] = func_get_arg(0);
        }
        return $this;
    }

    public function wicable()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["wicable"])) {
                return $this->instance["wicable"];
            } else if (isset($this->columns["wicable"]["default"])) {
                return $this->columns["wicable"]["default"];
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
                'left' => 'wicable',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["wicable"]) || $this->instance["wicable"] != func_get_args(0)) {
                if (!isset($this->columns["wicable"]["ignore_updates"]) || $this->columns["wicable"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["wicable"] = func_get_arg(0);
        }
        return $this;
    }

    public function qttyEnforced()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["qttyEnforced"])) {
                return $this->instance["qttyEnforced"];
            } else if (isset($this->columns["qttyEnforced"]["default"])) {
                return $this->columns["qttyEnforced"]["default"];
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
                'left' => 'qttyEnforced',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["qttyEnforced"]) || $this->instance["qttyEnforced"] != func_get_args(0)) {
                if (!isset($this->columns["qttyEnforced"]["ignore_updates"]) || $this->columns["qttyEnforced"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["qttyEnforced"] = func_get_arg(0);
        }
        return $this;
    }

    public function idEnforced()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["idEnforced"])) {
                return $this->instance["idEnforced"];
            } else if (isset($this->columns["idEnforced"]["default"])) {
                return $this->columns["idEnforced"]["default"];
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
                'left' => 'idEnforced',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["idEnforced"]) || $this->instance["idEnforced"] != func_get_args(0)) {
                if (!isset($this->columns["idEnforced"]["ignore_updates"]) || $this->columns["idEnforced"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["idEnforced"] = func_get_arg(0);
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

    public function inUse()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["inUse"])) {
                return $this->instance["inUse"];
            } else if (isset($this->columns["inUse"]["default"])) {
                return $this->columns["inUse"]["default"];
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
                'left' => 'inUse',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["inUse"]) || $this->instance["inUse"] != func_get_args(0)) {
                if (!isset($this->columns["inUse"]["ignore_updates"]) || $this->columns["inUse"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["inUse"] = func_get_arg(0);
        }
        return $this;
    }

    public function numflag()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["numflag"])) {
                return $this->instance["numflag"];
            } else if (isset($this->columns["numflag"]["default"])) {
                return $this->columns["numflag"]["default"];
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
                'left' => 'numflag',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["numflag"]) || $this->instance["numflag"] != func_get_args(0)) {
                if (!isset($this->columns["numflag"]["ignore_updates"]) || $this->columns["numflag"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["numflag"] = func_get_arg(0);
        }
        return $this;
    }

    public function subdept()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["subdept"])) {
                return $this->instance["subdept"];
            } else if (isset($this->columns["subdept"]["default"])) {
                return $this->columns["subdept"]["default"];
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
                'left' => 'subdept',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["subdept"]) || $this->instance["subdept"] != func_get_args(0)) {
                if (!isset($this->columns["subdept"]["ignore_updates"]) || $this->columns["subdept"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["subdept"] = func_get_arg(0);
        }
        return $this;
    }

    public function deposit()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["deposit"])) {
                return $this->instance["deposit"];
            } else if (isset($this->columns["deposit"]["default"])) {
                return $this->columns["deposit"]["default"];
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
                'left' => 'deposit',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["deposit"]) || $this->instance["deposit"] != func_get_args(0)) {
                if (!isset($this->columns["deposit"]["ignore_updates"]) || $this->columns["deposit"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["deposit"] = func_get_arg(0);
        }
        return $this;
    }

    public function local()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["local"])) {
                return $this->instance["local"];
            } else if (isset($this->columns["local"]["default"])) {
                return $this->columns["local"]["default"];
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
                'left' => 'local',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["local"]) || $this->instance["local"] != func_get_args(0)) {
                if (!isset($this->columns["local"]["ignore_updates"]) || $this->columns["local"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["local"] = func_get_arg(0);
        }
        return $this;
    }

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

    public function default_vendor_id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["default_vendor_id"])) {
                return $this->instance["default_vendor_id"];
            } else if (isset($this->columns["default_vendor_id"]["default"])) {
                return $this->columns["default_vendor_id"]["default"];
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
                'left' => 'default_vendor_id',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["default_vendor_id"]) || $this->instance["default_vendor_id"] != func_get_args(0)) {
                if (!isset($this->columns["default_vendor_id"]["ignore_updates"]) || $this->columns["default_vendor_id"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["default_vendor_id"] = func_get_arg(0);
        }
        return $this;
    }

    public function current_origin_id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["current_origin_id"])) {
                return $this->instance["current_origin_id"];
            } else if (isset($this->columns["current_origin_id"]["default"])) {
                return $this->columns["current_origin_id"]["default"];
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
                'left' => 'current_origin_id',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["current_origin_id"]) || $this->instance["current_origin_id"] != func_get_args(0)) {
                if (!isset($this->columns["current_origin_id"]["ignore_updates"]) || $this->columns["current_origin_id"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["current_origin_id"] = func_get_arg(0);
        }
        return $this;
    }

    public function auto_par()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["auto_par"])) {
                return $this->instance["auto_par"];
            } else if (isset($this->columns["auto_par"]["default"])) {
                return $this->columns["auto_par"]["default"];
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
                'left' => 'auto_par',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["auto_par"]) || $this->instance["auto_par"] != func_get_args(0)) {
                if (!isset($this->columns["auto_par"]["ignore_updates"]) || $this->columns["auto_par"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["auto_par"] = func_get_arg(0);
        }
        return $this;
    }

    public function id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["id"])) {
                return $this->instance["id"];
            } else if (isset($this->columns["id"]["default"])) {
                return $this->columns["id"]["default"];
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
                'left' => 'id',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["id"]) || $this->instance["id"] != func_get_args(0)) {
                if (!isset($this->columns["id"]["ignore_updates"]) || $this->columns["id"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["id"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

