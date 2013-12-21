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
  @class AutoCouponsModel
*/
class AutoCouponsModel extends BasicModel
{

    protected $name = "autoCoupons";

    protected $columns = array(
    'coupID' => array('type'=>'INT', 'primary_key'=>true),
    'description' => array('type'=>'VARCHAR(30)'),
	);

    protected $preferred_db = 'op';

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
    /* END ACCESSOR FUNCTIONS */
}

