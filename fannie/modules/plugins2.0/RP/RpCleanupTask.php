<?php

class RpCleanupTask extends FannieTask 
{
    public $name = 'RP Cleanup Task';

    public $description = 'Reset Produce Ordering Data';    

    public $default_schedule = array(
        'min' => 0,
        'hour' => 23,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $dbc->query("DELETE FROM RpSessions");
        $dbc->query("UPDATE PurchaseOrder SET placed=1, placedDate=" . $dbc->now() . "
                WHERE userID=-99 AND placed=0");
        $dbc->query('DELETE FROM shelftags WHERE id=6');

        $ts = time();
        while (date('N', $ts) != 1) {
            $ts = mktime(0, 0, 0, date('n', $ts), date('j', $ts) - 1, date('Y', $ts));
        }
        $cutoff = date('Y-m-d', $ts);
        $dlog = DTransactionsModel::selectDlog($cutoff);
        $DoW = $dbc->dayofweek('tdate');
        $prep = $dbc->prepare("SELECT {$DoW} AS DoW, SUM(total) AS ttl
            FROM {$dlog} AS d
                INNER JOIN MasterSuperDepts AS m ON d.department=m.dept_ID
            WHERE d.trans_type in ('I', 'D')
                AND m.superID=6
                AND d.store_id=?
                AND d.tdate >= ?
            GROUP BY {$DoW}
            ORDER BY {$DoW}");
        $segP = $dbc->prepare("UPDATE RpSegments
            SET thisYear=?
            WHERE storeID=?
                AND startDate=?");
        foreach (array(1, 2) as $store) {
            $args = array($store, $cutoff);
            $rows = $dbc->getAllRows($prep, $args);
            $thisYear = array('Mon'=>0, 'Tue'=>0, 'Wed'=>0, 'Thu'=>0, 'Fri'=>0, 'Sat'=>0, 'Sun'=>0);
            foreach ($rows as $row) {
                switch ($row['DoW']) {
                    case 1:
                        $thisYear['Sun'] = round($row['ttl'], 2);
                        break;
                    case 2:
                        $thisYear['Mon'] = round($row['ttl'], 2);
                        break;
                    case 3:
                        $thisYear['Tue'] = round($row['ttl'], 2);
                        break;
                    case 4:
                        $thisYear['Wed'] = round($row['ttl'], 2);
                        break;
                    case 5:
                        $thisYear['Thu'] = round($row['ttl'], 2);
                        break;
                    case 6:
                        $thisYear['Fri'] = round($row['ttl'], 2);
                        break;
                    case 7:
                        $thisYear['Sat'] = round($row['ttl'], 2);
                        break;
                }
            }
            $dbc->execute($segP, array(json_encode($thisYear), $store, $cutoff));
        }

        $retailP = $dbc->prepare("SELECT AVG(CASE WHEN discounttype=1 THEN special_price ELSE normal_price END)
            FROM upcLike AS u
                INNER JOIN products AS p ON u.upc=p.upc
            WHERE u.likeCode=?");
        $upP = $dbc->prepare("UPDATE RpSubTypes SET price=? WHERE upc=?");
        $res = $dbc->query("SELECT upc FROM RpSubTypes");
        $dbc->startTransaction();
        while ($row = $dbc->fetchRow($res)) {
            $price = $dbc->getValue($retailP, array(substr($row['upc'], 2)));
            $dbc->execute($upP, array($price, $row['upc']));
        }
        $dbc->commitTransaction();
    }
}
