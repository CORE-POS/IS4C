<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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
  @class SuperWeeklySalesModel
*/
class SuperWeeklySalesModel extends CoreWarehouseModel
{
    protected $name = "SuperWeeklySales";
    protected $preferred_db = 'plugin:WarehouseDatabase';

    protected $columns = array(
    'superWeeklySalesID' => array('type'=>'INT', 'primary_key'=>true, 'increment'=>true),
    'storeID' => array('type'=>'INT'),
    'superID' => array('type'=>'INT'),
    'startDate' => array('type'=>'DATETIME', 'index'=>true),
    'total' => array('type'=>'MONEY'),
    'segmentation' => array('type'=>'VARCHAR(255)'),
    );

    public function reload($trans_db,$start_month,$start_year,$end_month=False,$end_year=False)
    {
        if (!$end_month) {
            $end_month = $start_month;
        }
        if (!$end_year) {
            $end_year = $start_year;
        }
        $startTS = mktime(0, 0, 0, $start_month, 1, $start_year);
        $endTS = mktime(0, 0, 0, $end_month, 1, $end_year);
        while ($startTS <= $endTS) {
            $this->refresh_data($trans_db, date('n', $startTS), date('Y', $startTS));
            $startTS = mktime(0, 0, 0, date('n', $startTS)+1, 1, date('Y', $startTS));
        }
    }

    public function refresh_data($trans_db, $month, $year, $day=False)
    {
        if ($day) {
            $stamp = mktime(0, 0, 0, $month, $day, $year);
            if (date('N', $stamp) != 7) {
                return false;
            }
            $start = mktime(0, 0, 0, $month, $day - 6, $year);
            $this->calculateWeek(date('Y-m-d', $start));
        } else {
            $ts = mktime(0, 0, 0, $month, 1, $year);
            $start = $ts;
            while (date('N', $start) != 1) {
                $start = mktime(0, 0, 0, date('n', $start), date('j', $start) - 1, date('Y',$start));
            }
            $end = mktime(0, 0, 0, $month, date('t', $ts), $year);
            while (date('N', $end) != 7) {
                $end = mktime(0, 0, 0, date('n', $end), date('j', $end) + 1, date('Y',$end));
            }
            while ($start < $end) {
                $this->calculateWeek(date('Y-m-d', $start));
                $start = mktime(0, 0, 0, date('n', $start), date('j', $start) + 7, date('Y',$start));
                if ($start >= strtotime(date('Y-m-d'))) {
                    break;
                }
            }
        }
    }

    private function calculateWeek($start)
    {
        $startTS = strtotime($start);
        $end = date('Y-m-d', mktime(0, 0, 0, date('n',$startTS), date('j',$startTS) + 6, date('Y',$startTS)));
        echo "Week $start - $end\n";
        $config = FannieConfig::factory();
        $settings = $config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['WarehouseDatabase']);
        $dlog = DTransactionsModel::selectDlog($start, $end);

        $data = array();
        $prep = $dbc->prepare("
            SELECT d.store_id, m.superID, sum(total) AS ttl, MAX(tdate) AS tdate
            FROM {$dlog} AS d
                INNER JOIN " . FannieDB::fqn('MasterSuperDepts', 'op') . " AS m ON d.department=m.dept_ID
            WHERE tdate BETWEEN ? AND ?
                AND m.superID <> 0
                AND trans_type IN ('I', 'D')
            GROUP BY d.store_id, m.superID
            ORDER BY d.store_id, m.superID");
        $endTS = strtotime($end);
        while ($startTS <= $endTS) {
            $res = $dbc->execute($prep, array(date('Y-m-d', $startTS), date('Y-m-d', $startTS) . ' 23:59:59'));
            while ($row = $dbc->fetchRow($res)) {
                $key = $row['store_id'] . ':' . $row['superID'];
                if (!isset($data[$key])) {
                    $data[$key] = array(
                        'total' => 0,
                        'segments' => array(),
                    );
                }
                $data[$key]['total'] += $row['ttl'];
                $dow = date('D', strtotime($row['tdate']));
                $data[$key]['segments'][$dow] = $row['ttl'];
            }
            $startTS = mktime(0, 0, 0, date('n', $startTS), date('j', $startTS) + 1, date('Y', $startTS));
        }
        $keys = array_keys($data);
        for ($i=0; $i<count($keys); $i++) {
            $key = $keys[$i];
            $segs = array_keys($data[$key]['segments']);
            foreach ($segs as $j) {
                $data[$key]['segments'][$j] /= $data[$key]['total'];
            }
        }

        $findP = $dbc->prepare("SELECT superWeeklySalesID
            FROM SuperWeeklySales
            WHERE startDate=?
                AND storeID=?
                AND superID=?");
        $upP= $dbc->prepare("UPDATE SuperWeeklySales
            SET total=?, segmentation=?
            WHERE superWeeklySalesID=?");
        $insP = $dbc->prepare("
            INSERT INTO SuperWeeklySales (storeID, superID, startDate, total, segmentation)
                VALUES (?, ?, ?, ?, ?)");
        $dbc->startTransaction();
        foreach ($data as $ids => $info) {
            list($store, $super) = explode(':', $ids);
            $json = json_encode($info['segments']);
            $found = $dbc->getValue($findP, array($start, $store, $super));
            if ($found) {
                $dbc->execute($upP, array($info['total'], $json, $found));
            } else {
                $dbc->execute($insP, array($store, $super, $start, $info['total'], $json));
            }
        }
        $dbc->commitTransaction();
    }
}

