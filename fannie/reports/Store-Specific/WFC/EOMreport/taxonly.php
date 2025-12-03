<?php
use COREPOS\Fannie\API\item\StandardAccounting;
//header('Content-Type: application/ms-excel');
//header('Content-Disposition: attachment; filename="EOMreport.xls"');
include('../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__).'/../../../../classlib2.0/FannieAPI.php');
}
include(__DIR__ . '/../../../../src/functions.php');
$dbc = FannieDB::get($FANNIE_OP_DB);

if (isset($_GET["excel"])){
header('Content-Type: application/ms-excel');
header('Content-Disposition: attachment; filename="EOMreport.xls"');
$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF']; // grab excel from cache
$_SERVER['REQUEST_URI'] = str_replace("taxonly.php","",$_SERVER['REQUEST_URI']);
} else {
    $storeInfo = FormLib::storePicker();
    echo '<form action="taxonly.php" method="get">'
        . $storeInfo['html'] . 
        '<input type="submit" value="Change" />
        </form>';
    echo '<p><a href="../../../../modules/plugins2.0/CoreWarehouse/reports/EOMReport.php">Or use the newer one</a></p>';
}

$store = FormLib::get('store', false);
if ($store === false) {
    $clientIP = filter_input(INPUT_SERVER, 'REMOTE_ADDR');
    foreach ($FANNIE_STORE_NETS as $storeID => $range) {
        if (
            class_exists('\\Symfony\\Component\\HttpFoundation\\IpUtils')
            && \Symfony\Component\HttpFoundation\IpUtils::checkIp($clientIP, $range)
            ) {
            $store = $storeID;
        }
    }
    if ($store === false) {
        $store = 0;
    }
}

$today = date("m/j/y");
$uoutput = "<html>
<body bgcolor='#ffffff'> <font size=2>";
$uoutput .= '<br>Report run ' . $today; 
echo $uoutput;

$dlog = "trans_archive.dlogBig";
$start = '2017-07-01';
$end = '2017-07-31';
$args = array($start.' 00:00:00',$end.' 23:59:59', $store);

    $date = substr($start,0,strpos($start,":")-3);
    $date1 = substr($end,0,strpos($end,":")-3);
    echo ' for period <br>from: <b>'. $date . '</b> to: <b>' . $date1 . '</b><br>';


    $sql = FannieDB::get($FANNIE_OP_DB);
    if ($store == 50) {
        for ($i=1; $i<=2; $i++) {
            $tranP = $sql->prepare("SELECT YEAR(tdate), MONTH(tdate), DAY(tdate), emp_no, register_no, trans_no
                FROM {$dlog} WHERE trans_subtype='CM' AND description='STORE {$i}'
                    AND tdate BETWEEN ? AND ?
                    AND " . DTrans::isStoreID($store));
            $tranR = $sql->execute($tranP, $args);
            $collected = array(1 => 0, 2 => 0, 3 => 0);
            $taxP = $sql->prepare("SELECT total FROM {$dlog} WHERE tdate BETWEEN ? AND ?
                    AND emp_no=? AND register_no=? AND trans_no=? AND upc='TAX'");
            while ($w = $sql->fetchRow($tranR)) {
                $tdate = date('Y-m-d', mktime(0, 0, 0, $w[1], $w[2], $w[0]));
                $tax = $sql->getValue($taxP, array($tdate, $tdate . ' 23:59:59', $w['emp_no'], $w['register_no'], $w['trans_no']));
                $collected[2] += $tax;
            }
            $state = 0.06875;
            $city = 0.015;
            $deli = 0.0225;
            $county = 0.005;
            $canna = 0.15;
            $startDT = new DateTime($start);
            $noCounty = new DateTime('2017-10-01');
            if ($startDT >= $noCount) {
                //$county = 0;
            }
            echo '<table border="1" cellspacing="0" cellpadding="4">';
            echo '<tr><th colspan="4">' . ($i == 1 ? 'Hillside' : 'Denfeld') . ' Sales Tax</th></tr>';
            echo '<tr><th>Tax Collected on Regular rate items</th>
                    <th>' . sprintf('%.2f', $collected[1]) . '</th>
                    <th>Regular Taxable Sales</th>
                    <th>' . sprintf('%.2f', $collected[1]/($state+$city+$county)) . '</th>
                    </tr>';
            $stateTax = $collected[1] * ($state/($state+$city+$county));
            $cityTax = $collected[1] * ($city/($state+$city+$county));
            $countyTax = $collected[1] * ($county/($state+$city+$county));
            echo '<tr>
                <td align="right">State Tax Amount</td>
                <td>' . sprintf('%.2f', $stateTax) . '</td>
                <td align="right">State Taxable Sales</td>
                <td>' . sprintf('%.2f', $stateTax / $state) . '</td>
                </tr>';
            echo '<tr>
                <td align="right">City Tax Amount</td>
                <td>' . sprintf('%.2f', $cityTax) . '</td>
                <td align="right">City Taxable Sales</td>
                <td>' . sprintf('%.2f', $cityTax / $city) . '</td>
                </tr>';
            echo '<tr>
                <td align="right">County Tax Amount</td>
                <td>' . sprintf('%.2f', $countyTax) . '</td>
                <td align="right">County Taxable Sales</td>
                <td>' . sprintf('%.2f', $countyTax / $county) . '</td>
                </tr>';

            echo '<tr><th>Tax Collected on Deli rate items</th>
                    <th>' . sprintf('%.2f', $collected[2]) . '</th>
                    <th>Deli Taxable Sales</th>
                    <th>' . sprintf('%.2f', $collected[2]/($state+$city+$deli+$county)) . '</th>
                    </tr>';
            $stateTax = $collected[2] * ($state/($state+$city+$deli+$county));
            $cityTax = $collected[2] * ($city/($state+$city+$deli+$county));
            $deliTax = $collected[2] * ($deli/($state+$city+$deli+$county));
            $countyTax = $collected[2] * ($county/($state+$city+$deli+$county));
            echo '<tr>
                <td align="right">State Tax Amount</td>
                <td>' . sprintf('%.2f', $stateTax) . '</td>
                <td align="right">State Taxable Sales</td>
                <td>' . sprintf('%.2f', $stateTax / $state) . '</td>
                </tr>';
            echo '<tr>
                <td align="right">City Tax Amount</td>
                <td>' . sprintf('%.2f', $cityTax) . '</td>
                <td align="right">City Taxable Sales</td>
                <td>' . sprintf('%.2f', $cityTax / $city) . '</td>
                </tr>';
            echo '<tr>
                <td align="right">County Tax Amount</td>
                <td>' . sprintf('%.2f', $countyTax) . '</td>
                <td align="right">County Taxable Sales</td>
                <td>' . sprintf('%.2f', $countyTax / $county) . '</td>
                </tr>';
            echo '<tr>
                <td align="right">Deli Tax Amount</td>
                <td>' . sprintf('%.2f', $deliTax) . '</td>
                <td align="right">Deli Taxable Sales</td>
                <td>' . sprintf('%.2f', $deliTax / $deli) . '</td>
                </tr>';
            echo '<tr><th>Tax Collected on Cannabis rate items</th>
                    <th>' . sprintf('%.2f', $collected[3]) . '</th>
                    <th>Cannabis Taxable Sales</th>
                    <th>' . sprintf('%.2f', $collected[3]/($canna)) . '</th>
                    </tr>';

            $stateTax = ($collected[1] * ($state/($state+$city+$county))) 
                        + ($collected[2] * ($state/($state+$city+$deli+$county)));
            $cityTax = ($collected[1] * ($city/($state+$city+$county))) 
                        + ($collected[2] * ($city/($state+$city+$deli+$county)));
            $countyTax = ($collected[1] * ($county/($state+$city+$county))) 
                        + ($collected[2] * ($county/($state+$city+$deli+$county)));
            $deliTax = $collected[2] * ($deli/($state+$city+$deli+$county));
            echo '<tr><th colspan="4">State Totals</th></tr>';
            echo '<tr>
                <td align="right">Tax Collected</td>
                <td>' . sprintf('%.2f', $stateTax) . '</td>
                <td align="right">Taxable Sales</td>
                <td>' . sprintf('%.2f', $stateTax / $state) . '</td>
                </tr>';
            echo '<tr><th colspan="4">City Totals</th></tr>';
            echo '<tr>
                <td align="right">Tax Collected</td>
                <td>' . sprintf('%.2f', $cityTax) . '</td>
                <td align="right">Taxable Sales</td>
                <td>' . sprintf('%.2f', $cityTax / $city) . '</td>
                </tr>';
            echo '<tr><th colspan="4">County Totals</th></tr>';
            echo '<tr>
                <td align="right">Tax Collected</td>
                <td>' . sprintf('%.2f', $countyTax) . '</td>
                <td align="right">Taxable Sales</td>
                <td>' . sprintf('%.2f', $countyTax / $county) . '</td>
                </tr>';
            echo '<tr><th colspan="4">Deli Totals</th></tr>';
            echo '<tr>
                <td align="right">Tax Collected</td>
                <td>' . sprintf('%.2f', $deliTax) . '</td>
                <td align="right">Taxable Sales</td>
                <td>' . sprintf('%.2f', $deliTax / $deli) . '</td>
                </tr>';
            echo '</table><br />';
        }
    } else {
    $newTaxQ = 'SELECT MAX(description) AS description,
                    SUM(regPrice) AS ttl,
                    numflag AS taxID
                FROM trans_archive.bigArchive AS t
                WHERE datetime BETWEEN ? AND ?
                    AND ' . DTrans::isStoreID($store, 't') . '
                    AND upc=\'TAXLINEITEM\'
                    AND ' . DTrans::isNotTesting() . '
                GROUP BY taxID';
    $prep = $sql->prepare($newTaxQ);
    $res = $sql->execute($prep, $args);
    $collected = array(1 => 0.00, 2=>0.00);
    while ($row = $sql->fetch_row($res)) {
        $collected[$row['taxID']] = $row['ttl'];
    }
    $state = 0.06875;
    $city = 0.010;
    $deli = 0.0225;
    $county = 0.005;
    var_dump($state + $city + $county);
    var_dump($state + $city + $county + $deli);
    $startDT = new DateTime($start);
    $noCounty = new DateTime('2017-10-01');
    if ($startDT >= $noCount) {
        //$county = 0;
    }
    $stateTax = $collected[1] * ($state/($state+$city+$county));
    $cityTax = $collected[1] * ($city/($state+$city+$county));
    $countyTax = $collected[1] * ($county/($state+$city+$county));
    echo '<table border="1" cellspacing="0" cellpadding="4">';
    echo '<tr>
            <th>Tax Collected on Regular rate items</th>
            <th>Regular Taxable Sales</th>
            <th>Regular State Amount</th>
            <th>Regular City Amount</th>
            <th>Regular County Amount</th>
            <th>Tax Collected on Deli rate items</th>
            <th>Deli Taxable Sales</th>
            <th>Deli State Amount</th>
            <th>Deli City Amount</th>
            <th>Deli County Amount</th>
            <th>Deli Prepared Foods Amount</th>
            </tr>
            <tr>
            <th>' . sprintf('%.2f', $collected[1]) . '</th>
            <th>' . sprintf('%.2f', $collected[1]/($state+$city+$county)) . '</th>
            <td>' . sprintf('%.2f', $stateTax) . '</td>
            <td>' . sprintf('%.2f', $cityTax) . '</td>
            <td>' . sprintf('%.2f', $countyTax) . '</td>';
    $stateTax = $collected[2] * ($state/($state+$city+$deli+$county));
    $cityTax = $collected[2] * ($city/($state+$city+$deli+$county));
    $deliTax = $collected[2] * ($deli/($state+$city+$deli+$county));
    $countyTax = $collected[2] * ($county/($state+$city+$deli+$county));
            echo '
                <th>' . sprintf('%.2f', $collected[2]) . '</th>
                <th>' . sprintf('%.2f', $collected[2]/($state+$city+$deli+$county)) . '</th>
                <td>' . sprintf('%.2f', $stateTax) . '</td>
                <td>' . sprintf('%.2f', $cityTax) . '</td>
                <td>' . sprintf('%.2f', $countyTax) . '</td>
                <td>' . sprintf('%.2f', $deliTax) . '</td>
            </tr>';

    $stateTax = $collected[1] * ($state/($state+$city+$county));
    $cityTax = $collected[1] * ($city/($state+$city+$county));
    $countyTax = $collected[1] * ($county/($state+$city+$county));
    echo '<tr>
        <td align="right">State Tax Amount</td>
        <td>' . sprintf('%.2f', $stateTax) . '</td>
        <td align="right">State Taxable Sales</td>
        <td>' . sprintf('%.2f', $stateTax / $state) . '</td>
        </tr>';
    echo '<tr>
        <td align="right">City Tax Amount</td>
        <td>' . sprintf('%.2f', $cityTax) . '</td>
        <td align="right">City Taxable Sales</td>
        <td>' . sprintf('%.2f', $cityTax / $city) . '</td>
        </tr>';
    echo '<tr>
        <td align="right">County Tax Amount</td>
        <td>' . sprintf('%.2f', $countyTax) . '</td>
        <td align="right">County Taxable Sales</td>
        <td>' . sprintf('%.2f', $countyTax / $county) . '</td>
        </tr>';

    echo '<tr><th>Tax Collected on Deli rate items</th>
            <th>' . sprintf('%.2f', $collected[2]) . '</th>
            <th>Deli Taxable Sales</th>
            <th>' . sprintf('%.2f', $collected[2]/($state+$city+$deli+$county)) . '</th>
            </tr>';
    $stateTax = $collected[2] * ($state/($state+$city+$deli+$county));
    $cityTax = $collected[2] * ($city/($state+$city+$deli+$county));
    $deliTax = $collected[2] * ($deli/($state+$city+$deli+$county));
    $countyTax = $collected[2] * ($county/($state+$city+$deli+$county));
    echo '<tr>
        <td align="right">State Tax Amount</td>
        <td>' . sprintf('%.2f', $stateTax) . '</td>
        <td align="right">State Taxable Sales</td>
        <td>' . sprintf('%.2f', $stateTax / $state) . '</td>
        </tr>';
    echo '<tr>
        <td align="right">City Tax Amount</td>
        <td>' . sprintf('%.2f', $cityTax) . '</td>
        <td align="right">City Taxable Sales</td>
        <td>' . sprintf('%.2f', $cityTax / $city) . '</td>
        </tr>';
    echo '<tr>
        <td align="right">County Tax Amount</td>
        <td>' . sprintf('%.2f', $countyTax) . '</td>
        <td align="right">County Taxable Sales</td>
        <td>' . sprintf('%.2f', $countyTax / $county) . '</td>
        </tr>';
    echo '<tr>
        <td align="right">Deli Tax Amount</td>
        <td>' . sprintf('%.2f', $deliTax) . '</td>
        <td align="right">Deli Taxable Sales</td>
        <td>' . sprintf('%.2f', $deliTax / $deli) . '</td>
        </tr>';

    $stateTax = ($collected[1] * ($state/($state+$city+$county))) 
                + ($collected[2] * ($state/($state+$city+$deli+$county)));
    $cityTax = ($collected[1] * ($city/($state+$city+$county))) 
                + ($collected[2] * ($city/($state+$city+$deli+$county)));
    $countyTax = ($collected[1] * ($county/($state+$city+$county))) 
                + ($collected[2] * ($county/($state+$city+$deli+$county)));
    $deliTax = $collected[2] * ($deli/($state+$city+$deli+$county));
    echo '<tr><th colspan="4">State Totals</th></tr>';
    echo '<tr>
        <td align="right">Tax Collected</td>
        <td>' . sprintf('%.2f', $stateTax) . '</td>
        <td align="right">Taxable Sales</td>
        <td>' . sprintf('%.2f', $stateTax / $state) . '</td>
        </tr>';
    echo '<tr><th colspan="4">City Totals</th></tr>';
    echo '<tr>
        <td align="right">Tax Collected</td>
        <td>' . sprintf('%.2f', $cityTax) . '</td>
        <td align="right">Taxable Sales</td>
        <td>' . sprintf('%.2f', $cityTax / $city) . '</td>
        </tr>';
    echo '<tr><th colspan="4">County Totals</th></tr>';
    echo '<tr>
        <td align="right">Tax Collected</td>
        <td>' . sprintf('%.2f', $countyTax) . '</td>
        <td align="right">Taxable Sales</td>
        <td>' . sprintf('%.2f', $countyTax / $county) . '</td>
        </tr>';
    echo '<tr><th colspan="4">Deli Totals</th></tr>';
    echo '<tr>
        <td align="right">Tax Collected</td>
        <td>' . sprintf('%.2f', $deliTax) . '</td>
        <td align="right">Taxable Sales</td>
        <td>' . sprintf('%.2f', $deliTax / $deli) . '</td>
        </tr>';
    echo '</table>';
    }

