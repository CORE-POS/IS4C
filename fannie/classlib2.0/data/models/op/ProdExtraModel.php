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
  @class ProdExtraModel
*/
class ProdExtraModel extends BasicModel
{

    protected $name = "prodExtra";

    protected $columns = array(
    'upc' => array('type'=>'VARCHAR(13)', 'primary_key'=>true),
    'distributor' => array('type'=>'VARCHAR(100)'),
    'manufacturer' => array('type'=>'VARCHAR(100)'),
    'cost' => array('type'=>'DECIMAL(10,3)'),
    'margin' => array('type'=>'DOUBLE'),
    'variable_pricing' => array('type'=>'TINYINT'),
    'location' => array('type'=>'VARCHAR(30)'),
    'case_quantity' => array('type'=>'VARCHAR(15)'),
    'case_cost' => array('type'=>'MONEY'),
    'case_info' => array('type'=>'VARCHAR(100)'),
    );

    public function doc()
    {
        return '
Depends on:
* products (table)

Use:
Don\'t add to it.
As of 20Oct2012 it is still used by item/productList.php.

Deprecated. This mess dates back to trying to stay
lock-step with the Wedge\'s products table (which didn\'t
work anyway). The thinking was "Need a new field? Toss it
in prodExtra". 
        ';
    }
}

