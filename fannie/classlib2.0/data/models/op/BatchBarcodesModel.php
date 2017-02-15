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
  @class BatchBarcodesModel
*/
class BatchBarcodesModel extends BasicModel
{

    protected $name = "batchBarcodes";
    protected $preferred_db = 'op';

    protected $columns = array(
    'upc' => array('type'=>'VARCHAR(13)', 'primary_key'=>true),
    'description' => array('type'=>'VARCHAR(30)'),
    'normal_price' => array('type'=>'MONEY'),
    'brand' => array('type'=>'VARCHAR(50)'),
    'sku' => array('type'=>'VARCHAR(14)'),
    'size' => array('type'=>'VARCHAR(50)'),
    'units' => array('type'=>'VARCHAR(15)'),
    'vendor' => array('type'=>'VARCHAR(50)'),
    'pricePerUnit' => array('type'=>'VARCHAR(50)'),
    'batchID' => array('type'=>'INT', 'primary_key'=>true),
    );

    public function doc()
    {
        return '
Depends on:
* batches (table)

Use:
This table has information for generating shelf tags
for a batch. This makes sense primarily when working
with batches that update items\' regular price rather
than sale batches.

Note: size relates to an indivdual product.
Units relates to a case. So a case of beer has 24
units, each with a size of 12 oz.
        ';
    }
}

