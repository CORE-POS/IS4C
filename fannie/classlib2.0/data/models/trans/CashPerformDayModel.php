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
  @class CashPerformDayModel
*/
class CashPerformDayModel extends BasicModel
{

    protected $name = "CashPerformDay";

    protected $columns = array(
    'proc_date' => array('type'=>'DATETIME'),
    'emp_no' => array('type'=>'SMALLINT', 'index'=>true),
    'trans_num' => array('type'=>'VARCHAR(25)'),
    'startTime' => array('type'=>'DATETIME'),
    'endTime' => array('type'=>'DATETIME'),
    'transInterval' => array('type'=>'INT'),
    'items' => array('type'=>'FLOAT'),
    'rings' => array('type'=>'INT'),
    'Cancels' => array('type'=>'INT'),
    'card_no' => array('type'=>'INT'),
    );
    protected $preferred_db = 'trans';

    public function doc()
    {
        return '
Use:
Stores cashier performance metrics to
speed up reporting. 
        ';
    }
}

