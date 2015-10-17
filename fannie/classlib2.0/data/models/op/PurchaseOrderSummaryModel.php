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
  @class PurchaseOrderSummaryModel
*/
class PurchaseOrderSummaryModel extends BasicModel
{

    protected $name = "PurchaseOrderSummary";
    protected $preferred_db = 'op';

    protected $columns = array(
    'vendorID' => array('type'=>'INT', 'primary_key'=>true),
    'sku' => array('type'=>'VARCHAR(13)', 'primary_key'=>true),
    'totalReceived' => array('type'=>'INT'),
    'casesReceived' => array('type'=>'INT'),
    'numOrders' => array('type'=>'INT'),
    'numCredits' => array('type'=>'INT'),
    'oldest' => array('type'=>'DATETIME'),
    'newest' => array('type'=>'DATETIME'),
    );

    public function doc()
    {
        return '
Depends on:
* PurchaseOrder
* PurchaseOrderItems

Use:
Stores total quantities ordered for recent
orders where "recent" covers the same
timeframe as transarchive. Calculating this
on the fly can be prohibitively slow.

totalReceived is in individual units for comparison
against sales records. casesReceived is in cases.

numOrders indicates how many times the item has
been ordered. Credits are counted separately as
numCredits. oldest and newest are bounds on when
the item has been ordered.
        ';
    }
}

