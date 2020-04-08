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
  @class PickupOrdersModel
*/
class PickupOrdersModel extends BasicModel
{
    protected $name = "PickupOrders";

    protected $columns = array(
    'pickupOrderID' => array('type'=>'INT', 'primary_key'=>true,  'increment'=>true),
    'orderNumber' => array('type'=>'VARCHAR(255)'),
    'name' => array('type'=>'VARCHAR(255)'),
    'phone' => array('type'=>'VARCHAR(255)'),
    'vehicle' => array('type'=>'VARCHAR(255)'),
    'pDate' => array('type'=>'DATETIME'),
    'pTime' => array('type'=>'VARCHAR(255)'),
    'notes' => array('type'=>'TEXT'),
    'closed' => array('type'=>'TINYINT', 'default'=>0),
    'deleted' => array('type'=>'TINYINT', 'default'=>0),
    'storeID' => array('type'=>'INT'),
    'status' => array('type'=>'VARCHAR(255)'),
    'cardNo' => array('type'=>'INT'),
    );
}

