<?php

/**
 * Queries discountable items purchased by access discounts.
 * Results are per-quarter, per-department.
 * Incidicates whether a given department has wicable items
 */

include(__DIR__ . '/../../../config.php');
include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');

$dbc = FannieDB::get($FANNIE_OP_DB);

$wicR = $dbc->query('SELECT DISTINCT department FROM products WHERE wicable=1');
$wicDepts = array();
while ($wicW = $dbc->fetchRow($wicR)) {
    $wicDepts[$wicW['department']] = true;
}

$quarters = array(
    'Q12016' => array('2016-07-01', '2016-09-30'),
    'Q22016' => array('2016-10-01', '2016-12-31'),
    'Q32016' => array('2017-01-01', '2017-03-31'),
    'Q42016' => array('2017-04-01', '2017-06-30'),
    'Q12017' => array('2017-07-01', '2017-09-30'),
);

$transP = $dbc->prepare("
    SELECT tdate, trans_num 
    FROM trans_archive.dlogBig
    WHERE tdate BETWEEN ? AND ?
        AND memType=5
        AND upc='DISCOUNT'
        AND total <> 0
");

$itemP = $dbc->prepare("
    SELECT t.department,
        d.dept_name,
        SUM(t.total) AS ttl
    FROM trans_archive.dlogBig AS t
        INNER JOIN departments AS d ON t.department=d.dept_no
    WHERE t.tdate BETWEEN ? AND ?
        AND t.trans_num=?
        AND t.trans_type IN ('I', 'D')
        AND t.discountable = 1
    GROUP BY t.department,
        d.dept_name");

foreach ($quarters as $qName => $q) {
    $dates = array($q[0] . ' 00:00:00', $q[1] . ' 23:59:59');
    $transR = $dbc->execute($transP, $dates);
    $num = $dbc->numRows($transR);
    $count = 1;
    $data = array();
    while ($transW = $dbc->fetchRow($transR)) {
        list($tdate,) = explode(' ', $transW['tdate'], 2);
        $args = array($tdate . ' 00:00:00', $tdate . ' 23:59:59', $transW['trans_num']);
        $itemR = $dbc->execute($itemP, $args);
        while ($itemW = $dbc->fetchRow($itemR)) {
            $dept = $itemW['department'];
            if (!isset($data[$dept])) {
                $data[$dept] = array($dept, $itemW['dept_name'], 0);
            }
            $data[$dept][2] += $itemW['ttl'];
        }
        fwrite(STDERR, "$count/$num\r");
        $count++;
    }
    fwrite(STDERR, "\n");
    foreach ($data as $id => $info) {
        echo $qName . ',' . $info[0] . ',"' . $info[1] . '",' . sprintf('%.2f', $info[2]) . ',';
        echo (isset($wicDepts[$id]) ? 'Yes' : 'No') . "\r\n";
    }
}

