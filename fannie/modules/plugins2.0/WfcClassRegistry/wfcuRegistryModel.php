<?php

/*******************************************************************************

    Copyright 2016 Whole Foods Co-op

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
  @class wfcuRegistryModel
*/
class wfcuRegistryModel extends BasicModel
{

    protected $name = "wfcuRegistry";
    protected $preferred_db = 'op';

    protected $columns = array(
    'upc' => array('type'=>'VARCHAR(13)'),
    'class' => array('type'=>'VARCHAR(255)'),
    'first_name' => array('type'=>'VARCHAR(30)'),
    'last_name' => array('type'=>'VARCHAR(30)'),
    'first_opt_name' => array('type'=>'VARCHAR(30)'),
    'last_opt_name' => array('type'=>'VARCHAR(30)'),
    'phone' => array('type'=>'VARCHAR(30)'),
    'opt_phone' => array('type'=>'VARCHAR(30)'),
    'card_no' => array('type'=>'INT(11)'),
    'payment' => array('type'=>'VARCHAR(30)'),
    'refunded' => array('type'=>'INT(1)'),
    'modified' => array('type'=>'DATETIME'),
    'store_id' => array('type'=>'SMALLINT(6)'),
    'start_time' => array('type'=>'TIME'),
    'date_paid' => array('type'=>'DATETIME'),
    'seat' => array('type'=>'INT(50)'),
    'seatType' => array('type'=>'INT(5)'),
    'details' => array('type'=>'TEXT'),
    'id' => array('type'=>'INT(6)','primary_key'=>TRUE),
    'refund' => array('type'=>'VARCHAR(30)'),
    'amount' => array('type'=>'DECIMAL(10,2)'),
    );
}

