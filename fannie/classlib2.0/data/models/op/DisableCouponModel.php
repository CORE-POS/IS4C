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
  @class DisableCouponModel
*/
class DisableCouponModel extends BasicModel 
{

    protected $name = "disableCoupon";

    protected $columns = array(
    'upc' => array('type'=>'VARCHAR(13)','primary_key'=>True),
    'threshold' => array('type'=>'SMALLINT','default'=>0),
    'reason' => array('type'=>'text')
    );

    public function doc()
    {
        return '
Use:
Maintain a list of manufacturer coupons
the store does not accept. Most common
usage is coupons where a store does carry
products from that manufacturer but does
not carry any products the meet coupon
requirements. In theory family codes
address this situation better, but
obtaining and maintaing those codes isn\'t
feasible.
        ';
    }
}

