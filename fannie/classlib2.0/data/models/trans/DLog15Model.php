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
  @class DLogModel
*/
class DLog15Model extends DTransactionsModel
{

    protected $name = "dlog_15";
    protected $preferred_db = 'trans';

    public function __construct($con)
    {
        unset($this->columns['datetime']);
        $tdate = array('tdate'=>array('type'=>'datetime','index'=>True));
        $date_id = array('date_id'=>array('type'=>'INT'));
        $trans_num = array('trans_num'=>array('type'=>'VARCHAR(25)'));
        $this->columns = $tdate + $date_id + $this->columns + $trans_num;
        $this->columns['store_row_id']['increment'] = false;
        $this->columns['store_row_id']['primary_key'] = false;
        $this->columns['pos_row_id']['index'] = false;

        parent::__construct($con);
    }

    /**
      Use DTransactionsModel to normalize same-schema tables
    */
    public function normalize($db_name, $mode=BasicModel::NORMALIZE_MODE_CHECK, $doCreate=false)
    {
        return 0;
    }

    public function doc()
    {
        return '
Depends on:
* dlog_90_view (view)

Use:
This is just a look-up table. It contains the
past 15 days worth of dlog entries. For reports
on data within that time frame, it\'s faster to
use this small table.

Maintenance:
Truncated and populated by cron/nightly.dtrans.php
        ';
    }
}

