<?php

include(__DIR__ . '/../../../config.php');
include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');

$dbc = FannieDB::get($FANNIE_OP_DB);

function date2qtr($date)
{
    $ts = strtotime($date);
    $month = date('n', $ts);
    $year = date('Y', $ts);

    if ($month < 7) {
        return $month < 4 ? $year . 'Q3' : $year . 'Q4';
    }

    return $month < 10 ? ($year+1 . 'Q1') : ($year+1) . 'Q2';
}

$wicR = $dbc->query('SELECT DISTINCT department FROM products WHERE wicable=1');
$wicDepts = array();
while ($wicW = $dbc->fetchRow($wicR)) {
    $wicDepts[$wicW['department']] = true;
}

$itemP = $dbc->prepare('SELECT upc FROM batchList WHERE batchID=?');
$lcP = $dbc->prepare('SELECT upc FROM upcLike WHERE likeCode=?');

$sales = array();
$batchR = $dbc->query("SELECT batchID, batchName, startDate, endDate FROM batches WHERE (batchName like '%co-op deals%' or batchName like '%fresh deals%') AND startDate >= '2016-07-01'");
while ($batchW = $dbc->fetchRow($batchR)) {
    fwrite(STDERR, 'Processing ' . $batchW['batchName'] . "\n");
    $upcs = array();
    $itemR = $dbc->execute($itemP, array($batchW['batchID']));
    while ($itemW = $dbc->fetchRow($itemR)) {
        if (substr($itemW['upc'], 0, 2) == 'LC') {
            $lcR = $dbc->execute($lcP, array(substr($itemW['upc'], 2)));
            while ($lcW = $dbc->fetchRow($lcR)) {
                $upcs[] = $lcW['upc'];
            }
        } else {
            $upcs[] = $itemW['upc'];
        }
    }

    list($inStr, $args) = $dbc->safeInClause($upcs);
    list($start,) = explode(' ', $batchW['startDate'], 2);
    list($end,) = explode(' ', $batchW['endDate'], 2);
    $args[] = $start . ' 00:00:00';
    $args[] = $end . ' 23:59:59';
    $qtr = date2qtr($start);
    if (!isset($sales[$qtr])) {
        $sales[$qtr] = array();
    }

    $salesP = $dbc->prepare("
        SELECT t.department,
            d.dept_name,
            SUM(quantity * (regPrice - unitPrice)) AS ttl
        FROM trans_archive.dlogBig AS t
            INNER JOIN departments AS d ON t.department=d.dept_no
        WHERE t.upc IN ({$inStr})
            AND t.tdate BETWEEN ? AND ?
            AND t.trans_type='I'
            AND t.discounttype=1
            AND abs(regPrice) > abs(unitPrice)
        GROUP BY t.department,
            d.dept_name");
    $salesR = $dbc->execute($salesP, $args);
    while ($salesW = $dbc->fetchRow($salesR)) {
        $dept = $salesW['department'];
        if (!isset($sales[$qtr][$dept])) {
            $sales[$qtr][$dept] = array($dept, $salesW['dept_name'], 0);
        }
        $sales[$qtr][$dept][2] += $salesW['ttl'];
    }

}

foreach ($sales as $quarter => $depts) {
    foreach ($depts as $data) {
        echo "$quarter,{$data[0]},{$data[1]},";
        printf('%.2f,',$data[2]);
        echo isset($wicDepts[$data[0]]) ? 'Yes' : 'No';
        echo "\r\n";
    }
}

