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

namespace COREPOS\pos\lib\models\op;
use COREPOS\pos\lib\models\BasicModel;

/**
  @class HouseVirtualCouponsModel
*/
class HouseVirtualCouponsModel extends BasicModel
{

    protected $name = "houseVirtualCoupons";
    protected $preferred_db = 'op';

    protected $columns = array(
    'card_no' => array('type'=>'INT', 'primary_key'=>true),
    'coupID' => array('type'=>'INT', 'primary_key'=>true),
    'description' => array('type'=>'VARCHAR(100)'),
    'start_date' => array('type'=>'DATETIME'),
    'end_date' => array('type'=>'DATETIME'),
    );

    public function doc()
    {
        return '
Use:
Assign house coupons to members so
they can be applied without scanning
a barcode
        ';
    }
}

