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
  @class HouseCouponsModel
*/
class HouseCouponsModel extends BasicModel
{

    protected $name = "houseCoupons";
    protected $preferred_db = 'op';

    protected $columns = array(
    'coupID' => array('type'=>'INT', 'primary_key'=>true),
    'description' => array('type'=>'VARCHAR(30)'),
    'startDate' => array('type'=>'DATETIME'),
    'endDate' => array('type'=>'DATETIME'),
    'limit' => array('type'=>'SMALLINT'),
    'memberOnly' => array('type'=>'SMALLINT'),
    'discountType' => array('type'=>'VARCHAR(2)'),
    'discountValue' => array('type'=>'MONEY'),
    'minType' => array('type'=>'VARCHAR(2)'),
    'minValue' => array('type'=>'MONEY'),
    'department' => array('type'=>'INT'),
    'auto' => array('type'=>'TINYINT', 'default'=>0),
	);

    /* START ACCESSOR FUNCTIONS */

    public function coupID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["coupID"])) {
                return $this->instance["coupID"];
            } elseif(isset($this->columns["coupID"]["default"])) {
                return $this->columns["coupID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["coupID"] = func_get_arg(0);
        }
    }

    public function description()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["description"])) {
                return $this->instance["description"];
            } elseif(isset($this->columns["description"]["default"])) {
                return $this->columns["description"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["description"] = func_get_arg(0);
        }
    }

    public function startDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["startDate"])) {
                return $this->instance["startDate"];
            } elseif(isset($this->columns["startDate"]["default"])) {
                return $this->columns["startDate"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["startDate"] = func_get_arg(0);
        }
    }

    public function endDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["endDate"])) {
                return $this->instance["endDate"];
            } elseif(isset($this->columns["endDate"]["default"])) {
                return $this->columns["endDate"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["endDate"] = func_get_arg(0);
        }
    }

    public function limit()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["limit"])) {
                return $this->instance["limit"];
            } elseif(isset($this->columns["limit"]["default"])) {
                return $this->columns["limit"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["limit"] = func_get_arg(0);
        }
    }

    public function memberOnly()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["memberOnly"])) {
                return $this->instance["memberOnly"];
            } elseif(isset($this->columns["memberOnly"]["default"])) {
                return $this->columns["memberOnly"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["memberOnly"] = func_get_arg(0);
        }
    }

    public function discountType()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["discountType"])) {
                return $this->instance["discountType"];
            } elseif(isset($this->columns["discountType"]["default"])) {
                return $this->columns["discountType"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["discountType"] = func_get_arg(0);
        }
    }

    public function discountValue()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["discountValue"])) {
                return $this->instance["discountValue"];
            } elseif(isset($this->columns["discountValue"]["default"])) {
                return $this->columns["discountValue"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["discountValue"] = func_get_arg(0);
        }
    }

    public function minType()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["minType"])) {
                return $this->instance["minType"];
            } elseif(isset($this->columns["minType"]["default"])) {
                return $this->columns["minType"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["minType"] = func_get_arg(0);
        }
    }

    public function minValue()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["minValue"])) {
                return $this->instance["minValue"];
            } elseif(isset($this->columns["minValue"]["default"])) {
                return $this->columns["minValue"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["minValue"] = func_get_arg(0);
        }
    }

    public function department()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["department"])) {
                return $this->instance["department"];
            } elseif(isset($this->columns["department"]["default"])) {
                return $this->columns["department"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["department"] = func_get_arg(0);
        }
    }

    public function auto()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["auto"])) {
                return $this->instance["auto"];
            } elseif(isset($this->columns["auto"]["default"])) {
                return $this->columns["auto"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["auto"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

