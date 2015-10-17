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
  @class SpecialOrderHistoryModel
*/
class SpecialOrderHistoryModel extends BasicModel
{

    protected $name = "SpecialOrderHistory";
    protected $preferred_db = 'trans';

    protected $columns = array(
    'specialOrderHistoryID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'order_id' => array('type'=>'INT', 'index'=>true),
    'entry_type' => array('type'=>'VARCHAR(20)'),
    'entry_date' => array('type'=>'DATETIME'),
    'entry_value' => array('type'=>'TEXT'),
    );

    public function doc()
    {
        return '
Depends on:
* PendingSpecialOrder

Use:
This table is for a work-in-progress special
order tracking system. Conceptually, it will
work like a partial suspended transactions,
where rows with a given order_id can be
pulled in at a register when someone picks up
their special order.

This table stores a dated history for the order
        ';
    }
}

