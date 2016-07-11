<?php

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    include(dirname(__FILE__) . '/../config.php');
    if (!class_exists('FannieAPI')) {
        include(dirname(__FILE__) . '/../classlib2.0/FannieAPI.php');
    }

    function line2csv($arr)
    {
        $arr = array_map(function($i){ return '"' . str_replace('"', '', $i) . '"'; }, $arr);
        $line = array_reduce($arr, function($carry, $i){ return $carry . $i . ','; });
        return substr($line, 0, strlen($line)-1) . "\r\n";
    }

    $dbc = FannieDB::get($FANNIE_OP_DB);

    $query = '
        SELECT p.upc,
            p.brand,
            p.description,
            u.brand AS goodBrand,
            u.description AS goodDescription,
            COALESCE(n.vendorName, x.distributor) AS vendor,
            p.last_sold,
            p.normal_price,
            m.super_name,
            p.department,
            d.dept_name,
            d.salesCode
        FROM products AS p
            LEFT JOIN vendors AS n ON p.default_vendor_id=n.vendorID
            LEFT JOIN productUser as u ON p.upc=u.upc
            LEFT JOIN prodExtra AS x ON p.upc=x.upc
            LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
            LEFT JOIN departments AS d ON p.department=d.dept_no
        WHERE p.store_id=1
            AND p.inUse=1
            AND m.superID > 0';

    $ts1 = strtotime('yesterday');
    $date2 = date('Y-m-d 23:59:59', $ts1);
    $date1 = date('Y-m-d 00:00:00', mktime(0,0,0,date('n',$ts1),date('j',$ts1),date('Y',$ts1)-1));
    $salesP = $dbc->prepare('
        SELECT SUM(total) AS ttl,
            ' . DTrans::sumQuantity() . ' AS qty
        FROM ' . $FANNIE_ARCHIVE_DB . $dbc->sep() . 'dlogBig AS d
        WHERE d.upc=?
            AND d.tdate BETWEEN ? AND ?
            AND d.charflag <> \'SO\'');
    $unfiP = $dbc->prepare('
        SELECT v.sku
        FROM vendorItems AS v
        WHERE v.vendorID=1
            AND v.upc=?');

    $res = $dbc->query($query);
    while ($row = $dbc->fetchRow($res)) {
        $line = array(
            $row['upc'],
            $row['brand'],
            $row['description'],
            $row['goodBrand'],
            $row['goodDescription'],
            $row['vendor'],
            sprintf('%.2f', $row['normal_price']),
            $row['super_name'],
            $row['department'],
            $row['dept_name'],
            $row['salesCode'],
            $row['last_sold'],
        );
        $sales = $dbc->getRow($salesP, array($row['upc'], $date1, $date2));
        if ($sales) {
            $line[] = sprintf('%.2f', $sales[0]);
            $line[] = sprintf('%.2f', $sales[1]);
        } else {
            $line[] = 0;
            $line[] = 0;
        }
        $sku = $dbc->getValue($unfiP, array($row['upc']));
        if ($sku) {
            $line[] = $sku;
        } else {
            $line[] = 'n/a';
        }
        if ($sku || $row['vendor'] == 'UNFI') {
            $line[] = 'Yes';
        } else {
            $line[] = 'No';
        }

        echo line2csv($line);
    }
}
