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
  @class StockpurchasesModel
*/
class StockpurchasesModel extends BasicModel 
{

    protected $name = "stockpurchases";

    protected $preferred_db = 'trans';

    protected $columns = array(
    'card_no' => array('type'=>'INT','index'=>True),
    'stockPurchase' => array('type'=>'MONEY'),
    'tdate' => array('type'=>'DATETIME'),
    'trans_num' => array('type'=>'VARCHAR(50)'),
    'trans_id' => array('type'=>'INT', 'default'=>0),
    'dept' => array('type'=>'INT')
    );

    public function doc()
    {
        return '
Depends on:
* dlog (table)

Use:
This table equity related transaction info.
This table should be updated in conjunction with
any day-end polling system to copy appropriate
rows from dtransactions to stockpurchases
        ';
    }
}

