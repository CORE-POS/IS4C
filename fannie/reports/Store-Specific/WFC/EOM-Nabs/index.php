<?php
use COREPOS\Fannie\API\item\StandardAccounting;
include('../../../../config.php');
include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');
$dbc = FannieDB::get($FANNIE_OP_DB);

$month = FormLib::get('month', date('n')-1);
$year = FormLib::get('year', date('Y'));
if ($month == 0) {
    $month = 12;
    $year -= 1;
}

if (isset($_GET['excel'])){
    header('Content-Type: application/ms-excel');
    header('Content-Disposition: attachment; filename="dailyReport.xls"');
} else {
    $storeInfo = FormLib::storePicker();
    echo '<p>Current: Monthly  | Switch to: <a href="anydate.php">Any Date(s)</a></p>';
    echo '<form action="index.php" method="get">'
        . $storeInfo['html'] . 
        ' Month <input type="text" name="month" value="' . $month . '" />
        Year <input type="text" name="year" value="' . $year . '" />
        <input type="submit" value="Change" />
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

$ALIGN_RIGHT = 1;
$ALIGN_LEFT = 2;
$ALIGN_CENTER = 4;
$TYPE_MONEY = 8;

?>
<HTML>
<head>
<style type=text/css>
td {
    font-size: .9em;
}
td.left {
    padding-right: 4em;
    text-align: left;
}
td.right {
    padding-left: 4em;
    text-align: right;
}
td.center {
    padding-left: 2em;
    padding-right: 2em;
    text-align: center;
}
</style>
</head>
<?php

echo "<b>";
$stamp = mktime(0, 0, 0, $month, 1, $year);
echo strtoupper(date("F",$stamp));
echo " ";
echo date("Y",$stamp);
$dlog = "trans_archive.dlogBig";
$dtrans = "trans_archive.bigArchive";
echo " NABS</b><br />";
if (!isset($_GET["excel"]))
    echo "<a href=index.php?excel=xls&month=$month&year=$year&store=$store>Save to Excel</a>";
echo "<p />";

$output = \COREPOS\Fannie\API\data\DataCache::getFile('monthly');
if (true || !$output){
    ob_start();

    $start = date("Y-m-01",$stamp);
    $end = date("Y-m-t",$stamp);
    $span = array("$start 00:00:00","$end 23:59:59");

    $accounts = array();
    $accountQ = $dbc->prepare("SELECT CardNo from custdata WHERE memType=4 ORDER BY CardNo");
    $accountR = $dbc->execute($accountQ);
    while($accountW = $dbc->fetch_row($accountR))
        $accounts[] = $accountW['CardNo'];

    $accountStr = "(";
    $args=array();
    foreach($accounts as $a){
        $accountStr .= "?,";
        $args[] = $a;
    }
    $accountStr = rtrim($accountStr,",").")";

    echo "<b>Total by account</b>";
    $totalQ = $dbc->prepare("select l.card_no,
        sum(CASE WHEN department = 990 then -l.total ELSE l.total END),
        sum(CASE WHEN department = 990 then -l.total ELSE l.total END) -
        sum(CASE WHEN department = 990 then -l.total ELSE l.total END * m.margin) AS cost
        FROM $dlog as l left join departments as m on l.department = m.dept_no
        WHERE card_no IN $accountStr
        and l.department <> 0 and l.trans_type <> 'T'
        and tdate BETWEEN ? AND ?
        AND register_no <> 20
        AND " . DTrans::isStoreID($store, 'l') . "
        and (l.department < 600 or l.department = 902 OR l.register_no=35)
        GROUP BY card_no
        ORDER BY card_no");
//        and (l.department < 600 or l.department IN (902, 990))
    $args[] = $span[0];
    $args[] = $span[1];
    $args[] = $store;
    $totalR = $dbc->execute($totalQ,$args);
    $data = array();
    while ($totalW=$dbc->fetch_row($totalR)){
        if (!isset($data["$totalW[0]"])){
            $data["$totalW[0]"] = array($totalW[1],$totalW[2]);
        }
        else {
            $data["$totalW[0]"][0] += $totalW[1];
            $data["$totalW[0]"][1] += $totalW[2];
        }
    }
    echo tablify($data,array(0,1,2),array("Account","Retail","Wholesale"),
        array($ALIGN_LEFT,$ALIGN_RIGHT|$TYPE_MONEY,$ALIGN_RIGHT|$TYPE_MONEY),
        2,array(1,2));

    echo "<br /><b>Total by pCode</b>";
    $totalQ = $dbc->prepare("select d.salesCode,
        sum(CASE WHEN department = 990 then -l.total ELSE l.total END),
        sum(CASE WHEN department = 990 then -l.total ELSE l.total END) -
        sum(CASE WHEN department = 990 then -l.total ELSE l.total END * d.margin) AS cost,
        l.store_id
        FROM $dlog as l left join departments as d on l.department = d.dept_no
        WHERE card_no IN $accountStr
        and l.department <> 0 and l.trans_type <> 'T'
        AND register_no <> 20
        and tdate BETWEEN ? AND ?
        AND " . DTrans::isStoreID($store, 'l') . "
        and (l.department < 600 or l.department = 902 OR l.register_no=35)
        GROUP BY d.salesCode,d.margin,l.store_id
        ORDER BY d.salesCode");
    $totalR = $dbc->execute($totalQ,$args);
    $data = array();
    while ($totalW=$dbc->fetch_row($totalR)){
        $code = StandardAccounting::extend($totalW['salesCode'], $totalW['store_id']);
        if (empty($data[$code])){
            $data[$code] = array($totalW[1],$totalW[2]);
        }
        else {
            $data[$code][0] += $totalW[1];
            $data[$code][1] += $totalW[2];
        }
    }
    $giftQ = $dbc->prepare("select d.salesCode,
        sum(CASE WHEN department = 990 then -l.total ELSE l.total END),
        sum(CASE WHEN department = 990 then -l.total ELSE l.total END) -
        sum(CASE WHEN department = 990 then -l.total ELSE l.total END * d.margin) AS cost,
        l.store_id
        FROM $dlog as l left join departments as d on l.department = d.dept_no
        WHERE card_no IN $accountStr
        and l.department = 902 and l.trans_type <> 'T'
        AND register_no <> 20
        AND numflag=32766
        and tdate BETWEEN ? AND ?
        AND " . DTrans::isStoreID($store, 'l') . "
        GROUP BY d.salesCode,d.margin,l.store_id
        ORDER BY d.salesCode");
    $giftR = $dbc->execute($giftQ,$args);
    while ($totalW=$dbc->fetch_row($giftR)){
        $oldCode = StandardAccounting::extend($totalW['salesCode'], $totalW['store_id']);
        $newCode = StandardAccounting::extend('21206', $totalW['store_id']);
        if (empty($data[$newCode])){
            $data[$newCode] = array($totalW[1],$totalW[2]);
            if (isset($data[$oldCode])) {
                $data[$oldCode][0] -= $totalW[1];
                $data[$oldCode][1] -= $totalW[2];
            }
        }
        else {
            $data[$code][0] += $totalW[1];
            $data[$code][1] += $totalW[2];
            if (isset($data[$oldCode])) {
                $data[$oldCode][0] -= $totalW[1];
                $data[$oldCode][1] -= $totalW[2];
            }
        }
    }
    echo tablify($data,array(0,1,2),array("pCode","Retail","Wholesale"),
        array($ALIGN_LEFT,$ALIGN_RIGHT|$TYPE_MONEY,$ALIGN_RIGHT|$TYPE_MONEY),
        2,array(1,2));

    $totalQ = $dbc->prepare("select d.salesCode,
        sum(CASE WHEN department = 990 then -l.total ELSE l.total END),
        sum(CASE WHEN department = 990 then -l.total ELSE l.total END) -
        sum(CASE WHEN department = 990 then -l.total ELSE l.total END * d.margin) AS cost,
        l.store_id
        FROM $dlog as l left join departments as d on l.department = d.dept_no
        WHERE card_no = ?
        and l.department <> 0 and l.trans_type <> 'T'
        AND register_no <> 20
        and tdate BETWEEN ? AND ?
        AND " . DTrans::isStoreID($store, 'l') . "
        and (l.department < 600 or l.department = 902 OR l.register_no=35)
        GROUP BY d.salesCode,d.margin, l.store_id
        ORDER BY d.salesCode");
    $taxP = $dbc->prepare("SELECT SUM(total) FROM {$dlog} WHERE tdate BETWEEN ? AND ? AND card_no=? AND upc='TAX' AND " . DTrans::isStoreID($store));
    foreach ($accounts as $account){
        echo "<br /><b>Total for $account</b>";
        $totalR = $dbc->execute($totalQ,array($account,$span[0],$span[1],$store));
        $data = array();
        while ($totalW=$dbc->fetch_row($totalR)){
            $code = StandardAccounting::extend($totalW['salesCode'], $totalW['store_id']);
            if (empty($data[$code])){
                $data[$code] = array($totalW[1],$account,$totalW[2]);
            }
            else {
                $data[$code][0] += $totalW[1];
                $data[$code][2] += $totalW[2];
            }
        }
        $taxes = sprintf('%.2f', $dbc->getValue($taxP, array($span[0], $span[1], $account, $store)));
        $data['TAX'] = array(0, $taxes, 0);
        echo tablify($data,array(0,1,2,3),array("pCode","Retail","Account","Wholesale"),
            array($ALIGN_LEFT,$ALIGN_RIGHT|$TYPE_MONEY,$ALIGN_CENTER,$ALIGN_RIGHT|$TYPE_MONEY),
            2,array(1,3));
    }

    $output = ob_get_contents();
    \COREPOS\Fannie\API\data\DataCache::putFile('monthly', $output);
    ob_end_clean();
}

echo $output;

function tablify($data,$col_order,$col_headers,$formatting,$sums=-1,$sum_cols=array()){
    $sum = 0;
    $ret = "";
    
    $ret .= "<table cellspacing=0 cellpadding=4 border=1><tr>";
    $i = 0;
    foreach ($col_headers as $c){
        while ($formatting[$i] == 0) $i++;
        $ret .= cellify("<u>".$c."</u>",$formatting[$i++]&7);
    }
    $ret .= "</tr>";
    
    $ttls = array();
    if ($sums != -1){
        for ($i=0;$i<count($col_order);$i++)
            array_push($ttls,0);
    }

    foreach(array_keys($data) as $k){
        $ret .= "<tr>";
        foreach($col_order as $c){
            if($c == 0) $ret .= cellify($k,$formatting[$c]);
            else $ret .= cellify($data[$k][$c-1],$formatting[$c]);

            if (!empty($sum_cols)){
                foreach($sum_cols as $s){   
                    if ($s == $c)
                        $ttls[$c] += $data[$k][$c-1];
                }
            }
        }
        $ret .= "</tr>";
    }
    if (count($data) == 0){
        $ret .= "<tr>";
        $ret .= "<td colspan=".count($col_headers)." class=center>";
        $ret .= "No results to report"."</td>";
        $ret .= "</tr>";
    }

    if (!empty($sum_cols) && count($data) > 0){
        $ret .= "<tr>";
        foreach($col_order as $c){
            $skip = false;
            foreach ($sum_cols as $s){
                if ($s == $c){
                    $ret .= cellify($ttls[$c],$formatting[$c]);
                    $skip = true;
                }
            }
            if (!$skip)
                $ret .= "<td>&nbsp;</td>";
        }
        $ret .= "</tr>";
    }

    $ret .= "</table>";

    return $ret;
}

function cellify($data,$formatting){
    $ALIGN_RIGHT = 1;
    $ALIGN_LEFT = 2;
    $ALIGN_CENTER = 4;
    $TYPE_MONEY = 8;
    $ret = "";
    if ($formatting & $ALIGN_LEFT) $ret .= "<td class=left>";
    elseif ($formatting & $ALIGN_RIGHT) $ret .= "<td class=right>";
    elseif ($formatting & $ALIGN_CENTER) $ret .= "<td class=center>";

    if ($formatting & $TYPE_MONEY) $ret .= sprintf("%.2f",$data);
    else $ret .= $data;

    $ret .= "</td>";

    return $ret;
}

