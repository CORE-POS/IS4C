<?php

/**
 * Queries the access shoppers and looks at how many visits
 * to the store they made with and without the discount
 */

include(__DIR__ . '/../../../config.php');
include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');

$dbc = FannieDB::get('core_warehouse');

function avg($arr) {
    if (count($arr) == 0) return 0;
    return array_sum($arr) / count($arr);
}

$dataP = $dbc->prepare("
    SELECT COUNT(*) AS visits,
        CASE WHEN MAX(memType) = 5 THEN 1 ELSE 0 END AS hasAccess,
        LEFT(date_id, 4) AS year,
        SUBSTRING(date_id, 5, 2) AS month
    FROM transactionSummary
    WHERE date_id >= 20150901
        AND card_no=?
    GROUP BY LEFT(date_id, 4),
        SUBSTRING(date_id, 5, 2)");

$year = 2015;
$month = 9;
$tracked = array();
while (true) {

    $start = date('Ymd', mktime(0,0,0,$month,1,$year));
    $end = date('Ymt', mktime(0,0,0,$month,1,$year));
    $prep = $dbc->prepare('SELECT DISTINCT card_no FROM transactionSummary WHERE memType=5 and discountTotal <> 0 AND date_id BETWEEN ? AND ?');
    $res = $dbc->execute($prep, array($start, $end));
    while ($row = $dbc->fetchRow($res)) {
        if (isset($tracked[$row['card_no']])) continue;
        echo $row['card_no'] . ",";
        $tracked[$row['card_no']] = true;
        $dataR = $dbc->execute($dataP, array($row['card_no']));
        $perMonth = array('with'=>array(), 'without'=>array());
        $first = false;
        $last = false;
        while ($dataW = $dbc->fetchRow($dataR)) {
            if (!$first) {
                $first = $dataW['year'] . '-' . $dataW['month'];
            }
            $last = $dataW['year'] . '-' . $dataW['month'];
            if ($dataW['hasAccess']) {
                $perMonth['with'][] = $dataW[0];
            } else {
                $perMonth['without'][] = $dataW[0];
            }
            //echo $dataW[0] . ",";
        }
        echo $first . "," . $last . ",";
        echo count($perMonth['with']) . ",";
        echo count($perMonth['without']) . ",";
        echo avg($perMonth['with']) . ",";
        echo avg($perMonth['without']) . "\r\n";
    }

    $month += 1;
    if ($month > 12) {
        $year += 1;
        $month = 1;
    }
    if ($year == 2017 && $month == 9) break;
}

