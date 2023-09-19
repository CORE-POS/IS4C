<?php

/*******************************************************************************

    Copyright 2023 Whole Foods Co-op

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
  @class WfcTopWeeksModel
*/
class WfcTopWeeksModel extends CoreWarehouseModel
{
    protected $name = "WfcTopWeeks";
    protected $preferred_db = 'plugin:WarehouseDatabase';

    protected $columns = array(
    'date_id' => array('type'=>'INT', 'primary_key'=>true),
    'store_id' => array('type'=>'INT', 'primary_key'=>true),
    'category' => array('type'=>'VARCHAR(255)', 'primary_key'=>true),
    'total' => array('type'=>'MONEY','default'=>0.00),
    );

    public function refresh_data($trans_db, $month, $year, $day=False)
    {
        list($start_id, $start_date, $end_id, $end_date) = $this->dates($month, $year, $day);

        // if it's currently Monday and called for today, go back a week
        // otherwise find the most recent Monday as a starting point
        $startTS = strtotime($start_date);
        if (date('Y-m-d', $startTS) == date('Y-m-d') && date('N') == 1) {
            $startTS = mktime(0,0,0,date('m',$startTS),date('j',$startTS)-7,date('Y',$startTS));
        } else {
            while (date('N', $startTS) != 1) {
                $startTS = mktime(0,0,0,date('m',$startTS),date('j',$startTS)-1,date('Y',$startTS));
            }
        }
        $endTS = strtotime($end_date);

        $config = FannieConfig::factory();
        $settings = $config->get('PLUGIN_SETTINGS');
        $sql = FannieDB::get($settings['WarehouseDatabase']);

        $target_table = DTransactionsModel::selectDlog($start_date, $end_date);
        $excludeNabs = DTrans::memTypeIgnore($this->connection);

        while ($startTS < $endTS) {

            $weekID = date('Ymd', $startTS);
            $this->clearDates($sql, $weekID, $weekID);
            $sunday = mktime(0, 0, 0, date('m',$startTS), date('j',$startTS)+6, date('Y', $startTS));
            $weekStart = date('Y-m-d', $startTS);
            $weekEnd = date('Y-m-d', $sunday);

            $sql = "INSERT INTO ".$this->name."
                SELECT " . date('Ymd', $startTS) . " AS date_id,
                store_id,
                'ALL',
                CONVERT(SUM(total),DECIMAL(10,2)) as total
                FROM $target_table AS t
                    INNER JOIN " . FannieDB::fqn('MasterSuperDepts', 'op') . " AS m ON t.department=m.dept_ID
                WHERE m.superID <> 0
                    AND tdate BETWEEN ? AND ?
                    AND trans_type IN ('I','D')
                    AND memType NOT IN {$excludeNabs}
                GROUP BY store_id";
            $prep = $this->connection->prepare($sql);
            $result = $this->connection->execute($prep, array($weekStart.' 00:00:00',$weekEnd.' 23:59:59'));

            $sql = "INSERT INTO ".$this->name."
                SELECT " . date('Ymd', $startTS) . " AS date_id,
                store_id,
                'DELI',
                CONVERT(SUM(total),DECIMAL(10,2)) as total
                FROM $target_table AS t
                    INNER JOIN " . FannieDB::fqn('MasterSuperDepts', 'op') . " AS m ON t.department=m.dept_ID
                WHERE m.superID = 3
                    AND tdate BETWEEN ? AND ?
                    AND trans_type IN ('I','D')
                    AND memType NOT IN {$excludeNabs}
                GROUP BY store_id";
            $prep = $this->connection->prepare($sql);
            $result = $this->connection->execute($prep, array($weekStart.' 00:00:00',$weekEnd.' 23:59:59'));

            $sql = "INSERT INTO ".$this->name."
                SELECT " . date('Ymd', $startTS) . " AS date_id,
                store_id,
                'PRODUCE',
                CONVERT(SUM(total),DECIMAL(10,2)) as total
                FROM $target_table AS t
                    INNER JOIN " . FannieDB::fqn('MasterSuperDepts', 'op') . " AS m ON t.department=m.dept_ID
                WHERE m.superID = 6
                    AND tdate BETWEEN ? AND ?
                    AND trans_type IN ('I','D')
                    AND memType NOT IN {$excludeNabs}
                GROUP BY store_id";
            $prep = $this->connection->prepare($sql);
            $result = $this->connection->execute($prep, array($weekStart.' 00:00:00',$weekEnd.' 23:59:59'));

            $sql = "INSERT INTO ".$this->name."
                SELECT " . date('Ymd', $startTS) . " AS date_id,
                store_id,
                'GROCERY',
                CONVERT(SUM(total),DECIMAL(10,2)) as total
                FROM $target_table AS t
                    INNER JOIN " . FannieDB::fqn('MasterSuperDepts', 'op') . " AS m ON t.department=m.dept_ID
                WHERE m.superID IN (1,4,5,7,8,9,13,17,18)
                    AND tdate BETWEEN ? AND ?
                    AND trans_type IN ('I','D')
                    AND memType NOT IN {$excludeNabs}
                GROUP BY store_id";
            $prep = $this->connection->prepare($sql);
            $result = $this->connection->execute($prep, array($weekStart.' 00:00:00',$weekEnd.' 23:59:59'));

            // advance to next week
            $startTS = mktime(0, 0, 0, date('m',$startTS), date('j',$startTS)+7, date('Y', $startTS));
        }
        $this->doCutoffs('ALL');
        $this->doCutoffs('DELI');
        $this->doCutoffs('PRODUCE');
        $this->doCutoffs('GROCERY');
    }

    private function doCutoffs($category)
    {
        foreach (array(1, 2) as $storeID) {
            $cutoff = 0;
            $cutoffP = $this->connection->prepare("SELECT total
                FROM {$this->name}
                WHERE store_id=?
                    AND category=?
                ORDER BY total DESC");
            $cutoffR = $this->connection->execute($cutoffP, array($storeID, $category));
            $count = 1;
            while ($row = $this->connection->fetchRow($cutoffR)) {
                $count++;
                if ($count >= 100) {
                    $cutoff = $row['total'];
                    break;
                }
            }
            $prep = $this->connection->prepare("DELETE FROM {$this->name}
                WHERE store_id=?
                    AND category=?
                    AND total < ?");
            $res = $this->connection->execute($prep, array($storeID, $category, $cutoff));
        }
    }

}

