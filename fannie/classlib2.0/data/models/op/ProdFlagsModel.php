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
  @class ProdFlagsModel
*/
class ProdFlagsModel extends BasicModel
{

    protected $name = "prodFlags";
    protected $preferred_db = 'op';

    protected $columns = array(
    'bit_number' => array('type'=>'TINYINT', 'primary_key'=>true),
    'description' => array('type'=>'VARCHAR(50)'),
    'active' => array('type'=>'TINYINT', 'default'=>1),
    );

    public function doc()
    {
        return '
Depends on:
* products (table)

Use:
Properties for the product table\'s
numflag column
        ';
    }
}

