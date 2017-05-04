<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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
  @class ArHistoryModel
*/
class ArHistoryModel extends BasicModel 
{

    protected $name = "ar_history";

    protected $columns = array(
    'ar_history_id' => array('type'=>'INT', 'primary_key'=>true, 'increment'=>true),
    'card_no' => array('type'=>'INT','index'=>True),
    'charges' => array('type'=>'MONEY', 'default'=>0),
    'payments' => array('type'=>'MONEY', 'default'=>0),
    'tdate' => array('type'=>'DATETIME'),
    'trans_num' => array('type'=>'VARCHAR(50)')
    );

    protected $preferred_db = 'trans';

    public function doc()
    {
        return '
Depends on:
* transarchive (table), i.e. dlog_15 (table)
* was: dlog (view)

Depended on by:
* table ar_history_backup and its descendents
* view ar_history_sum and its descendents

Use:
  This table stores charges and payments on
   a customer\'s in-store charge account.

Maintenance:
This table should be updated in conjunction with
 any day-end polling system to copy appropriate
 rows from transarchive to ar_history
cron/nightly.ar.php appends selected columns from
 appropriate rows from dlog_15 (i.e. dtransactions)
        ';
    }
}

