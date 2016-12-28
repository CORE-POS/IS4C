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
  @class ProductWeeklyLastQuarterModel
*/
class ProductWeeklyLastQuarterModel extends BasicModel
{

    protected $name = "productWeeklyLastQuarter";
    protected $preferred_db = 'arch';

    protected $columns = array(
    'productWeeklyLastQuarterID' => array('type'=>'INT', 'increment'=>true, 'index'=>true),
    'upc' => array('type'=>'VARCHAR(13)', 'primary_key'=>true),
    'storeID' => array('type'=>'INT', 'primary_key'=>true),
    'weekLastQuarterID' => array('type'=>'INT', 'primary_key'=>true),
    'quantity' => array('type'=>'DECIMAL(10,2)'),
    'total' => array('type'=>'MONEY'),
    'percentageStoreSales' => array('type'=>'DECIMAL(10,5)'),
    'percentageSuperDeptSales' => array('type'=>'DECIMAL(10,5)'),
    'percentageDeptSales' => array('type'=>'DECIMAL(10,5)'),
    );

    public function doc()
    {
        return '
Use:
Per-item sales numbers for a given week. As always,
quantity is the number of items sold and total is
the monetary value. Percentages are calculated in
terms of monetary value.

This is essentially an intermediate calculation
for building productSummaryLastQuarter. The results
are saved here on the off-chance they prove useful
for something else.
        ';
    }
}

