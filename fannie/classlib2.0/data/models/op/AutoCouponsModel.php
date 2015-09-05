<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of CORE-POS.

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

    public function doc()
    {
        return '
Depends on:
* houseCoupons
* houseCouponItems

Use:
Apply coupons to transactions automatically
**Deprecated. The houseCoupons table has the same
functionality built in**.
        ';
    }

    /* START ACCESSOR FUNCTIONS */

    public function coupID()
    {
        if(func_num_args() == 0) {
            return $this->getColumn('coupID');
        } else if (func_num_args() > 1) {
            $literal = (func_num_args() > 2 && func_get_arg(2) === true) ? true : false;
            $this->filterColumn('coupID', func_get_arg(0), func_get_arg(1), $literal);
        } else {
            $this->setColumn('coupID', func_get_arg(0));
        }
        return $this;
    }

    public function description()
    {
        if(func_num_args() == 0) {
            return $this->getColumn('description');
        } else if (func_num_args() > 1) {
            $literal = (func_num_args() > 2 && func_get_arg(2) === true) ? true : false;
            $this->filterColumn('description', func_get_arg(0), func_get_arg(1), $literal);
        } else {
            $this->setColumn('description', func_get_arg(0));
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

