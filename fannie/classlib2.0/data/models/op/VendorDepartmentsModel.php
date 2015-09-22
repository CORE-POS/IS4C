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
  @class VendorDepartmentsModel
*/
class VendorDepartmentsModel extends BasicModel
{

    protected $name = "vendorDepartments";

    protected $columns = array(
    'vendorID' => array('type'=>'INT', 'primary_key'=>true),
    'deptID' => array('type'=>'INT', 'primary_key'=>true),
    'name' => array('type'=>'VARCHAR(125)'),
    'margin' => array('type'=>'FLOAT'),
    'testing' => array('type'=>'FLOAT'),
    'posDeptID' => array('type'=>'INT'),
    );

    public function doc()
    {
        return '
Depends on:
* vendors (table)

Use:
This table contains a vendors product categorization.
Two float fields, margin and testing, are provided
so you can try out new margins (i.e., calculate SRPs)
in testing without changing the current margin 
setting.

Traditional deptID corresponds to a UNFI\'s category
number. This may differ for other vendors.
        ';
    }
}

