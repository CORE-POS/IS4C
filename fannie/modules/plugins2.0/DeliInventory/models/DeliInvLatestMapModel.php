<?php

/*******************************************************************************

    Copyright 2020 Whole Foods Co-op

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
  @class DeliInvLatestMapModel
*/
class DeliInvLatestMapModel extends BasicModel
{
    protected $name = "DeliInvLatestMap";

    protected $columns = array(
    'vendorID' => array('type'=>'INT', 'primary_key'=>true),
    'sku' => array('type'=>'VARCHAR(13)', 'primary_key'=>true),
    'orderID' => array('type'=>'INT'),
    'unitSize' => array('type'=>'VARCHAR(25)'),
    'caseSize' => array('type'=>'DOUBLE'),
    'quantity' => array('type'=>'DECIMAL(10,2)'),
    'unitCost' => array('type'=>'MONEY'),
    'receivedDate' => array('type'=>'DATETIME'),
    'receivedQty' => array('type'=>'DOUBLE'),
    'receivedTotalCost' => array('type'=>'MONEY'),
    );
}

