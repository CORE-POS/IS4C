<?php
use COREPOS\Fannie\API\item\StandardAccounting;
//header('Content-Type: application/ms-excel');
//header('Content-Disposition: attachment; filename="EOMreport.xls"');
include('../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__).'/../../../../classlib2.0/FannieAPI.php');
}
include($FANNIE_ROOT.'src/functions.php');
$dbc = FannieDB::get($FANNIE_OP_DB);

if (isset($_GET["excel"])){
header('Content-Type: application/ms-excel');
header('Content-Disposition: attachment; filename="EOMreport.xls"');
$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF']; // grab excel from cache
$_SERVER['REQUEST_URI'] = str_replace("index.php","",$_SERVER['REQUEST_URI']);
} else {
    $storeInfo = FormLib::storePicker();
    echo '<form action="index.php" method="get">'
        . $storeInfo['html'] . 
        '<input type="submit" value="Change" />
        </form>';
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

$year = date('Y');
$month = date('n');
$stamp = mktime(0,0,0,$month-1,1,$year);
$dlog = "is4c_trans.dlog_90_view";
$start = date("Y-m-01",$stamp);
$end = date("Y-m-t",$stamp);
$args = array($start.' 00:00:00',$end.' 23:59:59', $store);

$output = \COREPOS\Fannie\API\data\DataCache::getFile("monthly");
if (!$output || isset($_REQUEST['recache'])){
    if (isset($_REQUEST['recache'])) {
        $_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF']; // remove recache from URI
        $_SERVER['REQUEST_URI'] = str_replace("index.php","",$_SERVER['REQUEST_URI']);
    }
    ob_start();

    $date = substr($start,0,strpos($start,":")-3);
    $date1 = substr($end,0,strpos($end,":")-3);
    echo ' for period <br>from: <b>'. $date . '</b> to: <b>' . $date1 . '</b><br>';

    $query1="select t.department,
    s.superID,
    d.salesCode,d.dept_name,
    SUM(t.total),
    t.store_id
    FROM $dlog as t 
        INNER JOIN departments as d ON t.department = d.dept_no
        LEFT JOIN MasterSuperDepts AS s ON s.dept_ID = d.dept_no    
    WHERE tdate BETWEEN ? AND ?
        AND " . DTrans::isStoreID($store, 't') . "
        AND t.department <> 0
        AND t.trans_type <> 'T'
        AND t.trans_type IN ('I', 'D')
    GROUP BY
    s.superID,t.department,d.dept_name,d.salesCode,t.store_id
    order by s.superID,t.department";

    $query2 = "SELECT 
        CASE WHEN d.description='WIC' THEN 'WIC' ELSE t.TenderName END as TenderName,
        -sum(d.total) as total, COUNT(d.total)
    FROM $dlog AS d
        left join tenders as t ON d.trans_subtype=t.TenderCode
    WHERE d.tdate BETWEEN ? AND ?
        AND " . DTrans::isStoreID($store, 'd') . "
    AND d.trans_type='T'
    AND d.trans_subtype <> 'MA'
    and t.TenderName <> 'MAD Coupon'
    AND d.trans_subtype <> 'IC'
    and d.total <> 0
    GROUP BY CASE WHEN d.description='WIC' THEN 'WIC' ELSE t.TenderName END";

    $queryStoreCoupons = "
        SELECT 
            CASE 
                WHEN h.description is NOT NULL THEN h.description
                WHEN d.upc <> '0' THEN d.upc
                ELSE 'Generic InStore Coupon'
            END as TenderName,
            -sum(d.total) as total, 
            COUNT(d.total)
        FROM $dlog AS d
            LEFT JOIN houseCoupons AS h ON d.upc=concat('00499999', lpad(convert(h.coupID, char), 5, '0'))
        WHERE d.tdate BETWEEN ? AND ?
            AND " . DTrans::isStoreID($store, 'd') . "
            AND d.trans_type='T'
            AND d.trans_subtype = 'IC'
            and d.total <> 0
        GROUP BY TenderName";

    $query3 = "SELECT c.salesCode,s.superID,sum(l.total) as total 
    FROM $dlog as l left join MasterSuperDepts AS s ON
    l.department = s.dept_ID INNER JOIN departments AS c
    ON l.department = c.dept_no
    WHERE l.tdate BETWEEN ? AND ?
        AND " . DTrans::isStoreID($store, 'l') . "
    AND l.department < 600 AND l.department <> 0
    AND l.trans_type <> 'T'
    AND l.trans_type IN ('I','D')
    GROUP BY c.salesCode,s.superID
    order by c.salesCode,s.superID";

    $query13 = "SELECT   m.memDesc,SUM(d.total) AS Sales
    FROM         $dlog d INNER JOIN
                  custdata c ON d.card_no = c.CardNo INNER JOIN
                  memtype m ON c.memType = m.memtype
    WHERE d.tdate BETWEEN ? AND ?
        AND " . DTrans::isStoreID($store, 'd') . "
    AND (d.department < 600) AND d.department <> 0 AND (c.personnum= 1 or c.personnum is null)
    AND d.trans_type <> 'T'
    GROUP BY m.memDesc
    ORDER BY m.memDesc";

    $query21 = "SELECT m.memdesc, COUNT(d.card_no)
    FROM is4c_trans.transarchive AS d left join memtype m on d.memType = m.memtype
    WHERE datetime BETWEEN ? AND ? AND (d.memType <> 4)
        AND " . DTrans::isStoreID($store, 'd') . "
    AND register_no<>99 and emp_no<>9999 AND trans_status NOT IN ('X','Z')
    AND trans_id=1 AND upc <> 'RRR'
    GROUP BY m.memdesc";

    $query20 = "SELECT   SUM(d.total) AS Sales 
            FROM $dlog d LEFT JOIN
            custdata c ON d.card_no = c.CardNo LEFT JOIN
            memtype m ON c.memType = m.memtype
            WHERE d.tdate BETWEEN ? AND ?
            AND " . DTrans::isStoreID($store, 'd') . "
            AND (d.department < 600) AND d.department <> 0 
            AND d.trans_type <> 'T'
            AND (c.personnum= 1 or c.personnum is null)";

    $query8 = "SELECT     m.memDesc, SUM(d.total) AS Discount 
    FROM         $dlog d INNER JOIN
                  custdata c ON d.card_no = c.CardNo INNER JOIN
                  memtype m ON c.memType = m.memtype
    WHERE d.tdate BETWEEN ? AND ?
    AND " . DTrans::isStoreID($store, 'd') . "
    AND (d.upc = 'DISCOUNT') AND c.personnum= 1
    GROUP BY c.memType, m.memDesc, d.upc
    ORDER BY c.memType";

    $query9 = "SELECT     d.upc, SUM(d.total) AS discount
    FROM         $dlog d INNER JOIN
                  custdata c ON d.card_no = c.CardNo INNER JOIN
                  memtype m ON c.memType = m.memtype
    WHERE d.tdate BETWEEN ? AND ?
    AND " . DTrans::isStoreID($store, 'd') . "
    AND (d.upc = 'DISCOUNT') AND c.personnum = 1
    GROUP BY d.upc";

    $query11 = "SELECT  sum(total) as tax_collected
    FROM $dlog as d 
    WHERE d.tdate BETWEEN ? AND ?
    AND " . DTrans::isStoreID($store, 'd') . "
    AND (d.upc = 'tax')
    GROUP BY d.upc";

    $query23="SELECT d.salesCode,sum(l.total) as total,card_no, 
    (sum(l.total)-(sum(l.total) * d.margin)) as cost
    FROM $dlog as l left join departments as d on l.department = d.dept_no
        INNER JOIN custdata AS c ON c.CardNo=l.card_no AND c.personNum=1
    WHERE l.tdate BETWEEN ? AND ?
    AND " . DTrans::isStoreID($store, 'l') . "
    AND (l.department < 600 or l.department = 902) AND l.department <> 0
    AND l.trans_type <> 'T'
    AND card_no BETWEEN 5500 AND 5950
    AND c.memType=4
    GROUP BY d.salesCode,card_no,d.margin
    order by card_no,d.salesCode";

    $queryRRR = "
    SELECT sum(case when volSpecial is null then 0 
        when volSpecial > 100 then 1
        else volSpecial end) as qty
    from
    is4c_trans.transarchive as t
    where upc = 'RRR'
    and t.datetime BETWEEN ? AND ?
    AND " . DTrans::isStoreID($store, 't') . "
    and emp_no <> 9999 and register_no <> 99
    and trans_status <> 'X'";


    echo '<font size = 3>';
    echo '<br>';
    echo 'Sales by department';
    echo '<br>---------------------------';
    echo '<table><td width=120><u><font size=2><b>Dept No</b></u></font></td>
          <td width=120><u><font size=2><b>Department</b></u></font></td>
          <td width=120><u><font size=2><b>pCode</b></u></font></td>
        <td width=120><u><font size=2><b>Group</b></u></font></td>
          <td width=120><u><font size=2><b>Sales</b></u></font></td>
        </table>';
    $prep = $dbc->prepare($query1);
    $res =  $dbc->execute($query1, $args);
    $depts = array();
    $supers = array();
    $misc = array();
    while ($w = $dbc->fetchRow($res)) {
        $code = StandardAccounting::extend($w['salesCode'], $w['store_id']);
        $w['salesCode'] = $code;
        $w[2] = $code;
        $s = $w['superID'];
        if ($s > 0) {
            $depts[] = $w;
        } else {
            $misc[] = $w;
        }
        if (!isset($supers[$s])) {
            $supers[$s] = array($s, 0.0);
        } 
        $supers[$s][1] += $w[4];
    }
    unset($supers[0]);
    select_to_table3($depts,5,0,'ffffff');
    echo '<b>Total Sales by Group</b>';
    select_to_table3($supers,2,0,'ffffff');

    echo '<font size = 2>';
    echo '<br>';
    echo 'Tenders';
    echo '<br>------------------------------';
    echo '<table><td width=120><u><font size=2><b>Type</b></u></font></td>
          <td width=120><u><font size=2><b>Amount</b></u></font></td>
          <td width=120><u><font size=2><b>Count</b></u></font></td></table>';
    select_to_table($query2,$args,0,'ffffff', true);
    select_to_table($queryStoreCoupons,$args,0,'ffffff');
    echo '<br>';
    echo 'Sales';
    echo '<br>------------------------------';
    echo '<table><td width=120><u><font size=2><b>pCode</b></u></font></td>
          <td width=120><u><font size=2><b>Sales</b></u></font></td></table>';
    $prep = $dbc->prepare($query3);
    $res = $dbc->execute($query3, $args);
    $sales = array();
    $ttl = 0.0;
    while ($w = $dbc->fetchRow($res)) {
        $sales[] = $w;
        $ttl += $w[2];
    }
    select_to_table3($sales,3,0,'ffffff');
    echo '<b>Total Sales</b>';

    select_to_table3(array(array($ttl)),1,0,'ffffff');

    echo '<br>';
    echo 'Other income';
    echo '<br>------------------------------';
    echo '<table><td width=120><u><font size=2><b>Dept</b></u></font></td>
          <td width=120><u><font size=2><b>Description</b></u></font></td>
          <td width=120><u><font size=2><b>Amount</b></u></font></td></table>';
    select_to_table3($misc,5,0,'ffffff');
    echo 'Discounts';
    echo '<br>------------------------------';
    echo '<table><td width=120><u><font size=2><b>Mem Type</b></u></font></td>
          <td width=120><u><font size=2><b>Discounts</b></u></font></td></table>';
    select_to_table($query8,$args,0,'ffffff');
    select_to_table($query9,$args,0,'ffffff');
    echo '<br>';
    echo 'Member Sales';
    echo '<br>------------------------------';
    echo '<table><td width=120><u><font size=2><b>Mem Type</b></u></font></td>
          <td width=120><u><font size=2><b>Sales</b></u></font></td></table>';
    $prep = $dbc->prepare($query13);
    $res = $dbc->execute($prep, $args);
    $mems = array();
    $ttl = 0.0;
    while ($w = $dbc->fetchRow($res)) {
        $mems[] = $w;
        $ttl += $w[1];
    }
    select_to_table3($mems,2,0,'ffffff');
    select_to_table3(array(array($ttl)),1,0,'ffffff');
    echo '<br>';
    echo 'Nabs';
    echo '<br>------------------------------';
    echo '<table><td width=120><u><font size=2><b>pCode</b></u></font></td>
          <td width=120><u><font size=2><b>Retail</b></u></font></td>
          <td>Dept Number</td><td>WholeSale</td></table>';
    $prep = $dbc->prepare($query23);
    $res = $dbc->execute($prep, $args);
    $nabs = array();
    $bycode = array();
    while ($w = $dbc->fetchRow($res)) {
        $nabs[] = $w;
        $code = $w[0];
        if (!isset($bycode[$code])) {
            $bycode[$code] = array($code, 0.0, 0.0);
        }
        $bycode[$code][1] += $w['total'];
        $bycode[$code][2] += $w['cost'];
    }
    select_to_table3($nabs,4,0,'ffffff');
    select_to_table3($bycode,3,0,'ffffff');
    echo '<br>';
    echo 'Transactions';
    echo '<br>------------------------------';
    echo '<table><td width=120><u><font size=2><b>Mem Type</b></u></font></td>
          <td width=120><u><font size=2><b>Transactions</b></u></font></td></table>';
    select_to_table($query21,$args,0,'ffffff');
    echo '<br>';
    echo '<br>';
    echo '<br>';
    echo '<b>Actual Tax Collected</b>';
    select_to_table($query11,$args,0,'ffffff');

    echo '<br>';
    echo '<b>RRR Coupons Redeemed</b>';
    select_to_table($queryRRR,$args,0,'ffffff');

    echo '</font>';
    echo "</font>
        </body>
        </html>";

    $output = ob_get_contents();
    \COREPOS\Fannie\API\data\DataCache::putFile("monthly",$output);
    ob_end_clean();
}
echo $output;

    $newTaxQ = 'SELECT description,
                    SUM(regPrice) AS ttl,
                    numflag AS taxID
                FROM is4c_trans.transarchive AS t
                WHERE datetime BETWEEN ? AND ?
                    AND ' . DTrans::isStoreID($store, 't') . '
                    AND upc=\'TAXLINEITEM\'
                    AND ' . DTrans::isNotTesting() . '
                GROUP BY taxID, description';
    $sql = FannieDB::get($FANNIE_OP_DB);
    $prep = $sql->prepare($newTaxQ);
    $res = $sql->execute($prep, $args);
    $collected = array(1 => 0.00, 2=>0.00);
    while ($row = $sql->fetch_row($res)) {
        $collected[$row['taxID']] = $row['ttl'];
    }
    $state = 0.06875;
    $city = 0.01;
    $deli = 0.0225;
    $county = 0.005;
    echo '<table border="1" cellspacing="0" cellpadding="4">';
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

