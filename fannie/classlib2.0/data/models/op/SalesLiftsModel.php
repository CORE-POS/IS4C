<?php

/*******************************************************************************

    Copyright 2018 Whole Foods Co-op

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
  @class SalesLiftsModel
*/
class SalesLiftsModel extends BasicModel
{

    protected $name = "SalesLifts";
    protected $preferred_db = 'op';

    protected $columns = array(
    'salesLiftID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'upc' => array('type'=>'VARCHAR(13)', 'index'=>true),
    'batchID' => array('type'=>'INT', 'index'=>true),
    'storeID' => array('type'=>'INT'),
    'saleDate' => array('type'=>'DATETIME'),
    'saleQty' => array('type'=>'MONEY'),
    'saleTotal' => array('type'=>'MONEY'),
    'compareDate' => array('type'=>'DATETIME'),
    'compareQty' => array('type'=>'MONEY'),
    'compareTotal' => array('type'=>'MONEY'),
    );

    public function doc()
    {
        return '
Compare an item\'s performance during a sales batch to the same-length
preceeding period (i.e., if it was on sale for 7 days the comparison period
will be the 7 days immediately prior to the sale). This is stored here
because calculating it on-demand as reports run isn\'t feasible.
';
    }
}

