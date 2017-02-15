<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
  @class VendorSRPsModel
*/
class VendorSRPsModel extends BasicModel
{

    protected $name = "vendorSRPs";
    protected $preferred_db = 'op';

    protected $columns = array(
    'vendorID' => array('type'=>'INT', 'primary_key'=>true),
    'upc' => array('type'=>'VARCHAR(13)', 'primary_key'=>true),
    'srp' => array('type'=>'MONEY'),
    );

    public function doc()
    {
        return '
Depends on:
* vendorItems (table)
* vendorDepartments (table)

Use:
This table contains SRPs for items
from a given vendor.

This could be calculated as items are imported
and stored in vendorItems, but in practice some
vendor catalogs are really big. Calculating SRPs
afterwards in a separate step reduces the chances
of hitting a PHP time or memory limit.
        ';
    }
}

