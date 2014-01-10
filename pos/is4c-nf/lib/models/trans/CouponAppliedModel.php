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
  @class CouponAppliedModel
*/
class CouponAppliedModel extends BasicModel
{

    protected $name = "couponApplied";

    protected $preferred_db = 'trans';

    protected $columns = array(
    'emp_no' => array('type'=>'INT', 'primary_key'=>true),
    'trans_no' => array('type'=>'INT', 'primary_key'=>true),
    'quantity' => array('type'=>'FLOAT'),
    'trans_id' => array('type'=>'INT', 'primary_key'=>true),
	);

    /* START ACCESSOR FUNCTIONS */

    public function emp_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["emp_no"])) {
                return $this->instance["emp_no"];
            } elseif(isset($this->columns["emp_no"]["default"])) {
                return $this->columns["emp_no"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["emp_no"] = func_get_arg(0);
        }
    }

    public function trans_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["trans_no"])) {
                return $this->instance["trans_no"];
            } elseif(isset($this->columns["trans_no"]["default"])) {
                return $this->columns["trans_no"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["trans_no"] = func_get_arg(0);
        }
    }

    public function quantity()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["quantity"])) {
                return $this->instance["quantity"];
            } elseif(isset($this->columns["quantity"]["default"])) {
                return $this->columns["quantity"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["quantity"] = func_get_arg(0);
        }
    }

    public function trans_id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["trans_id"])) {
                return $this->instance["trans_id"];
            } elseif(isset($this->columns["trans_id"]["default"])) {
                return $this->columns["trans_id"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["trans_id"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

