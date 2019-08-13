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
  @class CashierByDayModel
*/
class CashierByDayModel extends CoreWarehouseModel
{
    protected $name = "CashierByDay";
    protected $preferred_db = 'plugin:WarehouseDatabase';

    protected $columns = array(
    'date_id' => array('type'=>'INT', 'primary_key'=>true),
    'emp_no' => array('type'=>'SMALLINT', 'index'=>true),
    'trans_num' => array('type'=>'VARCHAR(25)', 'primary_key'=>true),
    'startTime' => array('type'=>'DATETIME'),
    'endTime' => array('type'=>'DATETIME'),
    'transInterval' => array('type'=>'INT'),
    'items' => array('type'=>'FLOAT'),
    'rings' => array('type'=>'INT'),
    'Cancels' => array('type'=>'INT'),
    'card_no' => array('type'=>'INT'),
    );

    public function refresh_data($trans_db, $month, $year, $day=False)
    {
        list($start_id, $start_date, $end_id, $end_date) = $this->dates($month, $year, $day);

        $target_table = DTransactionsModel::selectDlog($start_date, $end_date);

        $prep = $this->connection->prepare("DELETE FROM " . $this->name . " WHERE date_id BETWEEN ? AND ?");
        $this->connection->execute($prep, array($start_id, $end_id));

        $cashierPerformanceSQL = "
            INSERT INTO {$this->name}
            SELECT
            DATE_FORMAT(tdate, '%Y%m%d') as date_id,
            max(emp_no) as emp_no,
            max(trans_num) as Trans_Num,
            min(tdate) as startTime,
            max(tdate) as endTime,
            CASE WHEN ".$this->connection->seconddiff('min(tdate)', 'max(tdate)')." =0 
                then 1 else 
                ".$this->connection->seconddiff('min(tdate)', 'max(tdate)') ."
            END as transInterval,
            sum(CASE WHEN abs(quantity) > 30 THEN 1 else abs(quantity) END) as items,
            Count(upc) as rings,
            SUM(case when trans_status = 'V' then 1 ELSE 0 END) AS Cancels,
            max(card_no) as card_no
            from {$target_table} 
            where trans_type IN ('I','D','0','C')
                AND tdate BETWEEN ? AND ?
                AND department <> 701
            group by DATE_FORMAT(tdate,'%Y%m%d'), trans_num";
        $prep = $this->connection->prepare($cashierPerformanceSQL);
        $this->connection->execute($prep, array($start_date, $end_date . ' 23:59:59'));
    }

    public function doc()
    {
        return '
Use:
Stores cashier performance metrics to
speed up reporting. 
        ';
    }
}

