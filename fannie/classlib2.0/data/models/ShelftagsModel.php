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
  @class ShelftagsModel
*/
class ShelftagsModel extends BasicModel
{

    protected $name = "shelftags";

    protected $columns = array(
    'id' => array('type'=>'INT', 'primary_key'=>true),
    'upc' => array('type'=>'VARCHAR(13)', 'primary_key'=>true),
    'description' => array('type'=>'VARCHAR(30)'),
    'normal_price' => array('type'=>'MONEY'),
    'brand' => array('type'=>'VARCHAR(100)'),
    'sku' => array('type'=>'VARCHAR(13)'),
    'size' => array('type'=>'VARCHAR(50)'),
    'units' => array('type'=>'INT'),
    'vendor' => array('type'=>'VARCHAR(50)'),
    'pricePerUnit' => array('type'=>'VARCHAR(50)'),
    'count' => array('type'=>'TINYINT', 'default'=>1),
	);

    /* START ACCESSOR FUNCTIONS */

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
        } else {
            if (!isset($this->instance["id"]) || $this->instance["id"] != func_get_args(0)) {
                if (!isset($this->columns["id"]["ignore_updates"]) || $this->columns["id"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["id"] = func_get_arg(0);
        }
    }

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
        } else {
            if (!isset($this->instance["upc"]) || $this->instance["upc"] != func_get_args(0)) {
                if (!isset($this->columns["upc"]["ignore_updates"]) || $this->columns["upc"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["upc"] = func_get_arg(0);
        }
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
        } else {
            if (!isset($this->instance["description"]) || $this->instance["description"] != func_get_args(0)) {
                if (!isset($this->columns["description"]["ignore_updates"]) || $this->columns["description"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["description"] = func_get_arg(0);
        }
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
        } else {
            if (!isset($this->instance["normal_price"]) || $this->instance["normal_price"] != func_get_args(0)) {
                if (!isset($this->columns["normal_price"]["ignore_updates"]) || $this->columns["normal_price"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["normal_price"] = func_get_arg(0);
        }
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
        } else {
            if (!isset($this->instance["brand"]) || $this->instance["brand"] != func_get_args(0)) {
                if (!isset($this->columns["brand"]["ignore_updates"]) || $this->columns["brand"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["brand"] = func_get_arg(0);
        }
    }

    public function sku()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["sku"])) {
                return $this->instance["sku"];
            } else if (isset($this->columns["sku"]["default"])) {
                return $this->columns["sku"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["sku"]) || $this->instance["sku"] != func_get_args(0)) {
                if (!isset($this->columns["sku"]["ignore_updates"]) || $this->columns["sku"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["sku"] = func_get_arg(0);
        }
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
        } else {
            if (!isset($this->instance["size"]) || $this->instance["size"] != func_get_args(0)) {
                if (!isset($this->columns["size"]["ignore_updates"]) || $this->columns["size"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["size"] = func_get_arg(0);
        }
    }

    public function units()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["units"])) {
                return $this->instance["units"];
            } else if (isset($this->columns["units"]["default"])) {
                return $this->columns["units"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["units"]) || $this->instance["units"] != func_get_args(0)) {
                if (!isset($this->columns["units"]["ignore_updates"]) || $this->columns["units"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["units"] = func_get_arg(0);
        }
    }

    public function vendor()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["vendor"])) {
                return $this->instance["vendor"];
            } else if (isset($this->columns["vendor"]["default"])) {
                return $this->columns["vendor"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["vendor"]) || $this->instance["vendor"] != func_get_args(0)) {
                if (!isset($this->columns["vendor"]["ignore_updates"]) || $this->columns["vendor"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["vendor"] = func_get_arg(0);
        }
    }

    public function pricePerUnit()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["pricePerUnit"])) {
                return $this->instance["pricePerUnit"];
            } else if (isset($this->columns["pricePerUnit"]["default"])) {
                return $this->columns["pricePerUnit"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["pricePerUnit"]) || $this->instance["pricePerUnit"] != func_get_args(0)) {
                if (!isset($this->columns["pricePerUnit"]["ignore_updates"]) || $this->columns["pricePerUnit"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["pricePerUnit"] = func_get_arg(0);
        }
    }

    public function count()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["count"])) {
                return $this->instance["count"];
            } else if (isset($this->columns["count"]["default"])) {
                return $this->columns["count"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["count"]) || $this->instance["count"] != func_get_args(0)) {
                if (!isset($this->columns["count"]["ignore_updates"]) || $this->columns["count"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["count"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

