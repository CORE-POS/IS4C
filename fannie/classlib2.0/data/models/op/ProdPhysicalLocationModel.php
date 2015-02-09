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
  @class ProdPhysicalLocationModel
*/
class ProdPhysicalLocationModel extends BasicModel
{

    protected $name = "prodPhysicalLocation";
    protected $preferred_db = 'op';

    protected $columns = array(
    'upc' => array('type'=>'VARCHAR(13)', 'primary_key'=>true),
    'store_id' => array('type'=>'SMALLINT', 'default'=>0),
    'section' => array('type'=>'SMALLINT', 'default'=>0),
    'subsection' => array('type'=>'SMALLINT', 'default'=>0),
    'shelf_set' => array('type'=>'SMALLINT', 'default'=>0),
    'shelf' => array('type'=>'SMALLINT', 'default'=>0),
    'location' => array('type'=>'INT', 'default'=>0),
    );

    public function doc()
    {
        return '
Table: prodPhysicalLocation

Columns:
    upc varchar
    store_id smallint
    section smallint
    subsection smallint
    shelf_set smallint
    shelf smallint
    location int

Depends on:
    products (table)

Use:
Storing physical location of products within a store.

Section and/or subsection represents a set of shelves.
In a lot of cases this would be one side of an aisle but
it could also be an endcap or a cooler or something against
a wall that isn\'t formally an aisle. A store can use either
or both. For example, section could map to aisle numbering
and subsection could indicate the left or right side of
that aisle. Another option would be to map section to a
super department (e.g., grocery) and subsection to an aisle-side
within that department.

"Shelf set" is a division within a subsection. It could be
one physical shelving unit or a freezer door.

Shelf indicates the vertical shelf location. Bottom to
top numbering is recommended.

Location is the horizontal location on the shelf.
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

    public function section()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["section"])) {
                return $this->instance["section"];
            } else if (isset($this->columns["section"]["default"])) {
                return $this->columns["section"]["default"];
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
                'left' => 'section',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["section"]) || $this->instance["section"] != func_get_args(0)) {
                if (!isset($this->columns["section"]["ignore_updates"]) || $this->columns["section"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["section"] = func_get_arg(0);
        }
        return $this;
    }

    public function subsection()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["subsection"])) {
                return $this->instance["subsection"];
            } else if (isset($this->columns["subsection"]["default"])) {
                return $this->columns["subsection"]["default"];
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
                'left' => 'subsection',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["subsection"]) || $this->instance["subsection"] != func_get_args(0)) {
                if (!isset($this->columns["subsection"]["ignore_updates"]) || $this->columns["subsection"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["subsection"] = func_get_arg(0);
        }
        return $this;
    }

    public function shelf_set()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["shelf_set"])) {
                return $this->instance["shelf_set"];
            } else if (isset($this->columns["shelf_set"]["default"])) {
                return $this->columns["shelf_set"]["default"];
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
                'left' => 'shelf_set',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["shelf_set"]) || $this->instance["shelf_set"] != func_get_args(0)) {
                if (!isset($this->columns["shelf_set"]["ignore_updates"]) || $this->columns["shelf_set"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["shelf_set"] = func_get_arg(0);
        }
        return $this;
    }

    public function shelf()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["shelf"])) {
                return $this->instance["shelf"];
            } else if (isset($this->columns["shelf"]["default"])) {
                return $this->columns["shelf"]["default"];
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
                'left' => 'shelf',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["shelf"]) || $this->instance["shelf"] != func_get_args(0)) {
                if (!isset($this->columns["shelf"]["ignore_updates"]) || $this->columns["shelf"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["shelf"] = func_get_arg(0);
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
    /* END ACCESSOR FUNCTIONS */
}

