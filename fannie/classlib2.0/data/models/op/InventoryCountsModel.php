<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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
  @class InventoryCountsModel
*/
class InventoryCountsModel extends BasicModel
{
    protected $name = "InventoryCounts";
    protected $preferred_db = 'op';

    protected $columns = array(
    'inventoryCountID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'upc' => array('type'=>'VARCHAR(13)', 'index'=>true),
    'storeID' => array('type'=>'INT'),
    'count' => array('type'=>'DECIMAL(10,2)'),
    'countDate' => array('type'=>'DATETIME'),
    'mostRecent' => array('type'=>'TINYINT', 'default'=>1),
    'uid' => array('type'=>'VARCHAR(4)'),
    'par' => array('type'=>'DECIMAL(10,2)'),
    );

    public function doc()
    {
        return '
Use:
Inventory Counts as the baseline for perpetual inventory.
All calculations related to inventory levels are calculated
starting from the date and time of the most recent count.

The only non-obvious field here is par. Par is used not for
calculated inventory levels but rather when generating orders
based on inventory levels. It\'s often useful to set a par
after counting or re-counting an item since someone just looked
at inventory and can decide if present stock is higher or lower
than desired.
            ';
    }
}

