<?php

/*******************************************************************************

    Copyright 2017 Whole Foods Co-op

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
  @class OnlineGiftcardModel
*/
class OnlineGiftcardModel extends BasicModel
{
    protected $name = "onlineGiftcard";
    protected $preferred_db = 'op';

    protected $columns = array(
        'uniqid' => array('type'=>'VARCHAR(25)'),
        'phone' => array('type'=>'VARCHAR(12)'),
        'firstName' => array('type'=>'VARCHAR(65)'),
        'lastName' => array('type'=>'VARCHAR(65)'),
        'cardNo' => array('type'=>'MEDIUMINT(9)'),
        'date' => array('type'=>'TIMESTAMP'),
        'employee' => array('type'=>'VARCHAR(65)'),
        'amount' => array('type'=>'DECIMAL(10,2)'),
        'addr1' => array('type'=>'VARCHAR(255)'),
        'city' => array('type'=>'VARCHAR(65)'),
        'state' => array('type'=>'VARCHAR(2)'),
        'zip' => array('type'=>'MEDIUMINT(9)'),
        'email' => array('type'=>'VARCHAR(255)'),
        'store' => array('type'=>'VARCHAR(25)'),
        'notes' => array('type'=>'TEXT'),
    );
}
