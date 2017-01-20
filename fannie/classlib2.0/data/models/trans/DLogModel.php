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
class DLogModel extends DTransactionsModel
{
    protected $name = "dlog";
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
        $this->columns['store_row_id']['index'] = false;
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

    public function create()
    {
        ob_start();
        $this->normalizeLog($this->name, 'dtransactions', BasicModel::NORMALIZE_MODE_APPLY);
        ob_end_clean();

        if ($this->connection->tableExists($this->name)) {
            return true;
        } else {
            return false;
        }
    }

    public function save()
    {
        return false;
    }

    public function delete()
    {
        return false;
    }

    public function doc()
    {
        return '
Depends on:
* dtransactions (table)

Use:
This view presents simplified access to
dtransactions. It omits rows from canceled
transactions and testing lane(s)/cashier(s)
        ';
    }
}

