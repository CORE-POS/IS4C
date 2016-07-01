<?php
include('../../../config.php');

if (isset($_GET['excel'])){
    $weekstamp = date("W y");
    header('Content-Type: application/ms-excel');
    header("Content-Disposition: attachment; filename=\"weeklyTransactionReport $weekstamp.xls\"");
}
else {
    echo "<a href=index.php?excel=yes>Save to Excel</a><br /><br />";
}

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

$totalsByType = array();
$itemsByType = array();
$salesByType = array();
$memTypes = array('Member', 'Non Member', 'Staff Member', 'Staff NonMem', 'Work/Dis Mem', 'Sen Non Member', 'Senior Member', 'On-Call Staff', 'Promo');

foreach($memTypes as $m){
    $totalsByType[$m] = 0;
    $itemsByType[$m] = 0;
    $salesByType[$m] = 0;
}

$DoW = date("w");

for ($datediff = -7 - $Dow; $datediff <= -1 - $DoW; $datediff++){

    $dailyTotals = array();
    $dailySales = array();
    $dailyItems = array();
    foreach($memTypes as $m){
        $dailyTotals[$m] = 0;
        $dailyItems[$m] = 0;
        $dailySales[$m] = 0;
    }

    $transQ = $sql->prepare("select q.trans_num,sum(q.quantity) as items,sum(q.total) as sales,q.transaction_type from
        (
        select trans_num,card_no,quantity,total,
        m.memdesc as transaction_type
        from dlog_15 as d
        left join custdata as c on d.card_no = c.cardno
        left join memtypeid as m on c.memtype = m.memtypeid
        where ".$sql->datediff('tdate',$sql->now())."=? and trans_type='I'
        ) as q 
        group by q.trans_num,q.transaction_type");
    $transR = $sql->execute($transQ, array($datediff));
    
    while($transW = $sql->fetchRow($transR)){
        $dailyTotals[$transW[3]] += 1;
        $dailyItems[$transW[3]] += $transW[1];
        $dailySales[$transW[3]] += $transW[2];

        $totalsByType[$transW[3]] += 1;
        $itemsByType[$transW[3]] += $transW[1];
        $salesByType[$transW[3]] += $transW[2];
    }

    $date = date("D, M j, Y", time() + (60*60*24*$datediff));

    echo "<b>$date</b><br />";
    echo "<table cellspacing=0 cellpadding=3 border=1>";
    echo "<tr><th>Type</th><th>Transactions</th><th>Items</th><th>Avg</th><th>Sales</th><th>Avg</th></tr>";
    $trans = 0;
    $items = 0;
    $sales = 0;
    foreach($memTypes as $m){
        if ($dailyTotals[$m] != 0){
            echo "<tr>";
            echo "<td>$m</td>";
            echo "<td>".$dailyTotals[$m]."</td>";
            echo "<td>".$dailyItems[$m]."</td>";
            echo "<td>".round($dailyItems[$m]/$dailyTotals[$m],2)."</td>";
            echo "<td>".$dailySales[$m]."</td>";
            echo "<td>".round($dailySales[$m]/$dailyTotals[$m],2)."</td>";
            echo "</tr>";
            
            $trans += $dailyTotals[$m];
            $items += $dailyItems[$m];
            $sales += $dailySales[$m];
        }
    }
    echo "<tr>";
    echo "<th>Total</th>";
    echo "<td>$trans</td>";
    echo "<td>$items</td>";
    echo "<td>".round($items/$trans,2)."<t/d>";
    echo "<td>$sales</td>";
    echo "<td>".round($sales/$trans,2)."<t/d>";
    echo "</tr>";
    echo "</table>";
    echo "<br />";
}

echo "<b>Overall</b><br />";
echo "<table cellspacing=0 cellpadding=3 border=1>";
echo "<tr><th>Type</th><th>Transactions</th><th>Items</th><th>Avg</th><th>Sales</th><th>Avg</th></tr>";
$trans = 0;
$items = 0;
$sales = 0;
foreach($memTypes as $m){
    if ($totalsByType[$m] != 0){
        echo "<tr>";
        echo "<td>$m</td>";
        echo "<td>".$totalsByType[$m]."</td>";
        echo "<td>".$itemsByType[$m]."</td>";
        echo "<td>".round($itemsByType[$m]/$totalsByType[$m],2)."</td>";
        echo "<td>".$salesByType[$m]."</td>";
        echo "<td>".round($salesByType[$m]/$totalsByType[$m],2)."</td>";
        echo "</tr>";
        
        $trans += $totalsByType[$m];
        $items += $itemsByType[$m];
        $sales += $salesByType[$m];
    }
}
echo "<tr>";
echo "<th>Total</th>";
echo "<td>$trans</td>";
echo "<td>$items</td>";
echo "<td>".round($items/$trans,2)."<t/d>";
echo "<td>$sales</td>";
echo "<td>".round($sales/$trans,2)."<t/d>";
echo "</tr>";
echo "</table>";

