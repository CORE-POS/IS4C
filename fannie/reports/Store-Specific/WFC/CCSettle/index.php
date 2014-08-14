<?php

include('../../../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
$dbc = FannieDB::get($FANNIE_TRANS_DB);
include('fetchLib.php');

$date = isset($_REQUEST['date']) ? $_REQUEST['date'] : date("Y-m-d", time() - 86400);

$info = getProcessorInfo($date);

$dlog = DTransactionsModel::selectDlog($date);
list($y,$m,$d) = explode("-",$date);

$q = $dbc->prepare_statement("SELECT d.tdate,-d.total as total,d.trans_num,q.refNum,d.card_no
    FROM $dlog AS d LEFT JOIN efsnetRequest as q
    ON d.register_no=q.laneNo AND d.emp_no=q.cashierNo
    AND d.trans_no = q.transNo and d.trans_id=q.transID
    AND q.date=?
    WHERE tdate BETWEEN ? AND ?
    AND d.trans_subtype='CC'
    ORDER BY d.tdate");
$r = $dbc->exec_statement($q,array($y.$m.$d, $date.' 00:00:00', $date.'23:59:59'));

if (!isset($_REQUEST['excel'])){
    echo '<style type="text/css">
        tr.one td { background-color: #ffffff; }
        tr.two td { background-color: #ffffcc; }
    </style>';
    echo '<link rel="STYLESHEET" href="../../../../src/style.css" type="text/css">';
    echo '<link rel="STYLESHEET" href="../../../../src/javascript/jquery-ui.css" type="text/css">';
    echo '<script src="../../../../src/javascript/jquery.js"
        type="text/javascript"></script>';
    echo '<script src="../../../../src/javascript/jquery-ui.js"
        type="text/javascript"></script>';
    echo '<form action="index.php" method="get">';
    echo '<b>Date</b>: <input type="text" name="date" id="date" />';
    echo ' <input type="submit" value="Get Report" />';
    echo '</form><hr />';
    echo '<a href="index.php?date='.$date.'&excel=yes">Download Report</a>';
    echo '<script type="text/javascript">
        $(document).ready(function(){ $(\'#date\').datepicker({dateFormat:\'yy-mm-dd\'}); });
        </script>';
}
else {
    header('Content-Type: application/ms-excel');
    header('Content-Disposition: attachment; filename="ccReport '.$date.'.xls"');
}
echo '<table cellspacing="0" cellpadding="4" border="1">';
echo '<tr><th>Auth Time</th><th>Receipt#</th><th>Auth Amt.</th><th>Settle Amt.</th>
    <th>Settle Time</th><th>Approved</th><th>V/C</th><th>Card</th><th>Type</th>
    <th>Mem#</th>
    </tr>';
$totals = array(
    "pos"=>0.0,
    "manual"=>0.0,
    "settled"=>0.0,
    "Visa"=>0.0,
    "MasterCard"=>0.0,
    "American Express"=>0.0,
    "Discover"=>0.0
);
$colors = array("one","two");
$c = 1;
while($w = $dbc->fetch_row($r)){
    printf('<tr class="%s"><td>%s</td><td>%s</td><td>%.2f</td>',
        $colors[$c],$w['tdate'],$w['trans_num'],$w['total']);
    $totals["pos"] += $w['total'];
    if (empty($w['refNum'])){
        echo '<td colspan="6">Not integrated transaction</td>'; 
        $totals['manual'] += $w['total'];
    }
    else if (!isset($info[$w['refNum']])){
        echo '<td colspan="6">Integrated but no info found</td>';
    }
    else {
        $data = $info[$w['refNum']];
        printf('<td>%.2f</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td>',
            $data['settle_amt'],
            (isset($data['settle_dt'])?$data['settle_dt']:'Unknown'),
            ($data['success']==1?'Yes':'No'),$data['reversal'],
            ltrim($data['card'],'*'),$data['ctype']);
        $totals['settled'] += $data['settle_amt'];
        $totals[$data['ctype']] += $data['settle_amt'];
        unset($info[$w['refNum']]);
    }
    echo '<td>'.$w['card_no'].'</td>';
    echo '</tr>';
    $c = ($c+1) % 2;
}
foreach($info as $data){
    if (!isset($data['auth_amt'])){
        // pos trans never reached the processor
        continue;
    }
    printf('<tr class="%s"><td colspan="2">Non-POS</td>',$colors[$c]);
    printf('<td>%.2f</td><td>%.2f</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td>',
        $data['auth_amt'],$data['settle_amt'],
        (isset($data['settle_dt'])?$data['settle_dt']:'None'),
        ($data['success']==1?'Yes':'No'),$data['reversal'],
        ltrim($data['card'],'*'),$data['ctype']);
    echo '<td>N/A</td></tr>';
    $c = ($c+1) % 2;
}
printf('<tr><th align="right" colspan="9">POS Total</th><td>%.2f</td></tr>
        <tr><th align="right" colspan="9">Non-Integrated Total</th><td>%.2f</td></tr>
        <tr><th align="right" colspan="9">Settle Total</th><td>%.2f</td></tr>
        <tr><th align="right" colspan="9">Visa Total</th><td>%.2f</td></tr>
        <tr><th align="right" colspan="9">MC Total</th><td>%.2f</td></tr>
        <tr><th align="right" colspan="9">Discover Total</th><td>%.2f</td></tr>',
    $totals['pos'],$totals['manual'],
    $totals['settled'],$totals['Visa'],$totals['MasterCard'],
    $totals['Discover']);
echo '</table>';

?>
