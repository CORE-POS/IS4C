<?php
include('../../../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
$dbc = FannieDB::get($FANNIE_OP_DB);

$q = $dbc->prepare_statement("select year(tdate),month(tdate),day(tdate),count(*) From is4c_trans.dlog_90_view as d 
    left join custdata as c ON d.card_no=c.CardNo and c.personNum=1 
    where upc='DISCOUNT' and total <> 0 and c.Discount=0 
    and tdate > '2012-11-29 00:00:00'
    AND c.Type in ('PC','REG')
    group by year(tdate),month(tdate),day(tdate)
    order by year(tdate),month(tdate),day(tdate)");
$r = $dbc->exec_statement($q);
echo '<h3>Rebate Checks</h3>';
echo '<table cellspacing="0" cellpadding="4" border="1">';
$sum = 0;
while ($w = $dbc->fetch_row($r)){
    printf('<tr><td>%d/%d/%d</td><td>%d</td></tr>',
        $w[1],$w[2],$w[0],$w[3]);
    $sum += $w[3];
}
echo '<tr><th>Total</th><td>'.$sum.'</td></tr>';
echo '</table>';

$q = $dbc->prepare_statement("select year(tdate),month(tdate),day(tdate),sum(quantity) From is4c_trans.dlog_90_view as d 
    where upc='0049999900021' 
    and tdate > '2012-11-29 00:00:00'
    group by year(tdate),month(tdate),day(tdate)
    order by year(tdate),month(tdate),day(tdate)");
$r = $dbc->exec_statement($q);
echo '<h3>Rebate Coupons</h3>';
echo '<table cellspacing="0" cellpadding="4" border="1">';
$sum = 0;
while ($w = $dbc->fetch_row($r)){
    printf('<tr><td>%d/%d/%d</td><td>%d</td></tr>',
        $w[1],$w[2],$w[0],$w[3]);
    $sum += $w[3];
}
echo '<tr><th>Total</th><td>'.$sum.'</td></tr>';
echo '</table>';

?>
