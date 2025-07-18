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
  @class SpecialOrdersModel
*/
class SpecialOrdersModel extends BasicModel
{

    protected $name = "SpecialOrders";

    protected $preferred_db = 'trans';

    protected $columns = array(
    'specialOrderID' => array('type'=>'INT', 'primary_key'=>true, 'increment'=>true),
    'statusFlag' => array('type'=>'INT'),
    'subStatus' => array('type'=>'INT'),
    'notes' => array('type'=>'TEXT'),
    'noteSuperID' => array('type'=>'INT'),
    'firstName' => array('type'=>'VARCHAR(30)'),
    'lastName' => array('type'=>'VARCHAR(30)'),
    'street' => array('type'=>'VARCHAR(255)'),
    'city' => array('type'=>'VARCHAR(20)'),
    'state' => array('type'=>'VARCHAR(2)'),
    'zip' => array('type'=>'VARCHAR(10)'),
    'phone' => array('type'=>'VARCHAR(30)'),
    'altPhone' => array('type'=>'VARCHAR(30)'),
    'email' => array('type'=>'VARCHAR(50)'),
    'storeID' => array('type'=>'INT'),
    'sendEmails' => array('type'=>'TINYINT', 'default'=>8),
    'onlineID' => array('type'=>'INT'),
    'noDuplicate' => array('type'=>'TINYINT', 'default'=>0),
    'tagNotes' => array('type'=>'TEXT', 'default'=>'{}'),
    );

    public function doc()
    {
        return '
This table stores general information about the order
and the customer who is placing it.
        ';
    }
}

