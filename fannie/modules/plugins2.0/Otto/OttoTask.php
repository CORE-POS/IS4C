<?php

if (!class_exists('Gohanman\\Otto\\Otto')) {
    include(__DIR__ . '/noauto/Otto.php');
}
if (!class_exists('Gohanman\\Otto\\Message')) {
    include(__DIR__ . '/noauto/Message.php');
}

class OttoTask extends FannieTask
{

    public function run()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $url = $settings['OttoStatUrl'];

        $otto = new Gohanman\Otto\Otto($url);
        $msg = new Gohanman\Otto\Message();
        $yesterday = strtotime('yesterday');
        $tdate = date('Y-m-d', $yesterday);
        $msg->title('Sales for ' . date('D, M jS', $yesterday));

        // walk back to monday
        $monday = $yesterday;
        while (date('N', $monday) != 1) {
            $monday = mktime(0, 0, 0, date('n', $monday), date('j', $monday) - 1, date('Y', $monday));
        }
        // subtract one year
        $ly = mktime(0, 0, 0, date('n', $monday), date('j', $monday), date('Y', $monday) - 1);
        // walk forward until a monday
        while (date('N', $ly) != 1) {
            $ly = mktime(0, 0, 0, date('n', $ly), date('j', $ly) + 1, date('Y', $ly));
        }
        // find corresponding weekday
        $dayNo = date('N', $yesterday);
        $inc = $dayNo < date('N', $ly) ? -1  : 1;
        $max = 0;
        while (date('N', $ly) != $dayNo) {
            $ly = mktime(0,0,0, date('n', $ly), date('j', $ly) + $inc, date('Y', $ly));
            $max++;
            if ($max > 6) {
                $ly = strtotime('1 year ago');
                break;
            }
        }
        $lastYear = date('Y-m-d', $ly);

        $dlog = DTransactionsModel::selectDlog($tdate);
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $nabs = DTrans::memTypeIgnore($dbc);
        $prep = $dbc->prepare("
            SELECT s.storeID, s.description,
                SUM(total) as ttl
            FROM {$dlog} AS d
                INNER JOIN MasterSuperDepts AS m ON d.department=m.dept_ID
                INNER JOIN Stores AS s ON s.storeID=d.store_id
            WHERE s.hasOwnItems=1
                AND m.superID <> 0
                AND d.trans_type IN ('I', 'D')
                AND d.tdate BETWEEN ? AND ?
                AND d.memType NOT IN {$nabs}
            GROUP BY s.storeID, s.description
            ORDER BY s.description");

        $dlogLY = DTransactionsModel::selectDlog($lastYear);
        $lyP = $dbc->prepare("
            SELECT SUM(total) as ttl
            FROM {$dlogLY} AS d
                INNER JOIN MasterSuperDepts AS m ON d.department=m.dept_ID
                INNER JOIN Stores AS s ON s.storeID=d.store_id
            WHERE s.hasOwnItems=1
                AND m.superID <> 0
                AND d.trans_type IN ('I', 'D')
                AND d.tdate BETWEEN ? AND ?
                AND d.memType NOT IN {$nabs}
                AND d.store_id=?");

        $res = $dbc->execute($prep, array($tdate, $tdate . ' 23:59:59'));
        $body = '';
        $org = 0;
        $orgLY = 0;
        while ($row = $dbc->fetchRow($res)) {
            $salesLY = $dbc->getValue($lyP, array($lastYear, $lastYear . ' 23:59:59', $row['storeID']));
            $growth = ($row['ttl'] - $salesLY) / $salesLY;
            $orgLY += $salesLY;
            $pctGrowth = sprintf('%.1f%%', $growth * 100);
            $body .= '**' . $row['description'] . '**: $' . number_format($row['ttl']) . ' (' . $pctGrowth . ")\n\n";
            $org += $row['ttl'];
        }
        $growth = ($org - $orgLY) / $orgLY;
        $pctGrowth = sprintf('%.1f%%', $growth * 100);
        $body .= '**Organization**: $' . number_format($org) . ' (' . $pctGrowth . ')';

        $msg->body($body);
        var_dump($otto->post($msg));

        if (date('N', $yesterday) == 7) {
            $wkEnd = $tdate;
            $wkStart = date('Y-m-d', mktime(0, 0, 0, date('n', $yesterday), date('j',$yesterday) - 6, date('Y', $yesterday)));
            $lyEnd = $lastYear;
            $ly = strtotime($lastYear);
            $lyStart = date('Y-m-d', mktime(0, 0, 0, date('n', $ly), date('j',$ly) - 6, date('Y', $ly)));

            $msg = new Gohanman\Otto\Message();
            $msg->title('Sales for the week of ' . date('D, M jS', strtotime($wkStart)));

            $res = $dbc->execute($prep, array($wkStart, $wkEnd . ' 23:59:59'));
            $body = '';
            $org = 0;
            $orgLY = 0;
            while ($row = $dbc->fetchRow($res)) {
                $salesLY = $dbc->getValue($lyP, array($lyStart, $lyEnd . ' 23:59:59', $row['storeID']));
                $growth = ($row['ttl'] - $salesLY) / $salesLY;
                $orgLY += $salesLY;
                $pctGrowth = sprintf('%.1f%%', $growth * 100);
                $body .= '**' . $row['description'] . '**: $' . number_format($row['ttl']) . ' (' . $pctGrowth . ")\n\n";
                $org += $row['ttl'];
            }
            $growth = ($org - $orgLY) / $orgLY;
            $pctGrowth = sprintf('%.1f%%', $growth * 100);
            $body .= '**Organization**: $' . number_format($org) . ' (' . $pctGrowth . ')';

            $msg->body($body);
            var_dump($otto->post($msg));
        }
    }
}
