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
  @class CouponCodesModel
*/
class CouponCodesModel extends BasicModel
{

    protected $name = "couponcodes";

    protected $preferred_db = 'op';

    protected $columns = array(
    'Code' => array('type'=>'VARCHAR(4)', 'primary_key'=>true),
    'Qty' => array('type'=>'INT'),
    'Value' => array('type'=>'Real'),
	);

    /* START ACCESSOR FUNCTIONS */

    public function Code()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["Code"])) {
                return $this->instance["Code"];
            } elseif(isset($this->columns["Code"]["default"])) {
                return $this->columns["Code"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["Code"] = func_get_arg(0);
        }
    }

    public function Qty()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["Qty"])) {
                return $this->instance["Qty"];
            } elseif(isset($this->columns["Qty"]["default"])) {
                return $this->columns["Qty"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["Qty"] = func_get_arg(0);
        }
    }

    public function Value()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["Value"])) {
                return $this->instance["Value"];
            } elseif(isset($this->columns["Value"]["default"])) {
                return $this->columns["Value"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["Value"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

