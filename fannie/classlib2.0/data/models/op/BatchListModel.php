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
  @class BatchListModel
*/
class BatchListModel extends BasicModel 
{

    protected $name = "batchList";
    protected $preferred_db = 'op';

    protected $columns = array(
    'listID' => array('type'=>'INT', 'primary_key'=>True, 'increment'=>True),
    'upc' => array('type'=>'VARCHAR(13)','index'=>True),
    'batchID' => array('type'=>'INT','index'=>True),
    'salePrice' => array('type'=>'MONEY'),
    'groupSalePrice' => array('type'=>'MONEY'),
    'active' => array('type'=>'TINYINT'),
    'pricemethod' => array('type'=>'SMALLINT','default'=>0),
    'quantity' => array('type'=>'SMALLINT','default'=>0),
    'signMultiplier' => array('type'=>'TINYINT', 'default'=>1),
    );

    protected $unique = array('batchID','upc');

    public function doc()
    {
        return '
Depends on:
* batches (table)

Use:
This table has a list of items in a batch.
In most cases, salePrice maps to
products.special_price AND products.specialgroupprice,
pricemethod maps to products.specialpricemethod,
and quantity maps to products.specialquantity.

WFC has some weird exceptions. The main on is that 
upc can be a likecode, prefixed with \'LC\'
        ';
    }
}

