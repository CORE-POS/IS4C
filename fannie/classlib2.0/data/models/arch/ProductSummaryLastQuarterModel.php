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
  @class ProductSummaryLastQuarterModel
*/
class ProductSummaryLastQuarterModel extends BasicModel
{

    protected $name = "productSummaryLastQuarter";
    protected $preferred_db = 'arch';

    protected $columns = array(
    'productSummaryLastQuarterID' => array('type'=>'INT', 'increment'=>true, 'index'=>true),
    'upc' => array('type'=>'VARCHAR(13)', 'primary_key'=>true),
    'storeID' => array('type'=>'INT', 'primary_key'=>true),
    'qtyThisWeek' => array('type'=>'DECIMAL(10,2)'),
    'totalThisWeek' => array('type'=>'MONEY'),
    'qtyLastQuarter' => array('type'=>'DECIMAL(10,2)'),
    'totalLastQuarter' => array('type'=>'MONEY'),
    'percentageStoreSales' => array('type'=>'DECIMAL(10,5)'),
    'percentageSuperDeptSales' => array('type'=>'DECIMAL(10,5)'),
    'percentageDeptSales' => array('type'=>'DECIMAL(10,5)'),
    );

    public function doc()
    {
        return '
Depends on:
* productWeeklyLastQuarter
* weeksLastQuarter

Use:
Provides per-item sales for the previous quarter.
See weeksLastQuarter for more information about
how the quarter is defined.

Quantity columns are number of items sold; total
columns are in monetary value. Percentages are
calculated in terms of monetary value.

Percentages in this table represent a weighted
average of sales - i.e., sales last week count more
heavily than sales ten weeks ago. The primary purpose
of this table and everything that feeds into it is
to forecast margin. The percentage captures how an
individual item contributes to margin, and weighting
over a longer period should capture long-term trends
while smoothing over random fluctuations.
        ';
    }
}

