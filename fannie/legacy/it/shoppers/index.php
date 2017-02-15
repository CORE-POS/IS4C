<html><head>
<title>Customer traffic</title>
<link href="../../styles.css" rel="stylesheet" type="text/css">
</head>
<?php

include('../../../config.php');
if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

$hourlyQ = "select year(tdate),month(tdate),day(tdate),
        hour(tdate),count(distinct trans_num) from dlog_15 where
        ".$sql->datediff('tdate',$sql->now())." >= -7
        and hour(tdate) between 7 and 20
        group by year(tdate),month(tdate),
        day(tdate),hour(tdate)
        order by year(tdate),month(tdate),
        day(tdate),hour(tdate)";
$hourlyR = $sql->query($hourlyQ);

echo "Customers per hour, last 7 days";
$curyear = '';
$curmonth = '';
$curday = '';
$accumulator = array();
$curmax = 0;
$i = 0;
echo "<table border=1>";
echo "<tr><th>Date</th><th>7[am]</th><th>8</th><th>9</th><th>10</th><th>11</th><th>12[pm]</th>";
echo "<th>1</th><th>2</th><th>3</th><th>4</th><th>5</th><th>6</th><th>7</th><th>8</th><th>Total</th></tr>";
/*
    what's this accumulator mess:
    the query returns rows like this: <date> <hour> <customer count>
    so there are multiple rows for each <date>
    the point of the accumulator is to find the biggest per day
    so it can be bolded when it's dumped out
    as well as sum each day's customer count for a total
*/
while ($hourlyW = $sql->fetchRow($hourlyR)){
    if ($curyear != $hourlyW[0] || $curmonth != $hourlyW[1] || $curday != $hourlyW[2]){
        if ($curyear != ''){
            $sum = 0;
            for ($j = 0; $j < $i; $j++){
                if ($j == $curmax)
                    echo "<th>$accumulator[$j]</th>";
                else
                    echo "<td>$accumulator[$j]</th>";
                $sum += $accumulator[$j];
            }
            echo "<td>$sum</td></tr>";
            $accumulator = array();
            $curmax = 0;
            $i = 0;    
        }
        echo "<tr>";
        echo "<td>$hourlyW[1]/$hourlyW[2]/$hourlyW[0]</td>";
        $curyear = $hourlyW[0];
        $curmonth = $hourlyW[1];
        $curday = $hourlyW[2];
    }
    $accumulator[$i] = $hourlyW[4];
    if ($hourlyW[4] > $accumulator[$curmax])
        $curmax = $i;
    $i++;
}
// write out the last date's data
$sum = 0;
for ($j = 0; $j < $i; $j++){
    if ($j == $curmax)
        echo "<th>$accumulator[$j]</th>";
    else
        echo "<td>$accumulator[$j]</th>";
    $sum += $accumulator[$j];
}
echo "<td>$sum</td></tr></table>";


$monthlyQ = "select datepart(yy,tdate),datepart(mm,tdate),datepart(dd,tdate),
             min(datepart(dw,tdate)),count(distinct trans_num) from dlog_90_view
             where datepart(hh,tdate) between 7 and 20
             group by datepart(yy,tdate),datepart(mm,tdate),datepart(dd,tdate),
             datepart(hh,tdate)
             order by datepart(yy,tdate),datepart(mm,tdate),datepart(dd,tdate)";    
$monthlyR = $sql->query($monthlyQ);

$days = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
echo "<p />Customers per day, last 30 days";
echo "<table border=1>";
echo "<tr><th>Day</th><th>Date</th><th>Total</th></tr>";
$curyear = '';
$curmonth = '';
$curday = '';
$curdw = '';
$sum = 0;
/*
    what's all this nonsense? why is it being grouped pointlessly by hour?
    this is to be consistent with the above hourly chart. the way the hourly
    chart works, a customer that begins his transaction at x:59 (or less) and
    finishes the transaction at y:00 (or more) gets counted as two customers.
    hourly grouping is used here so that the totals are consistent for the days
    on both charts. so technically the totals are off a little bit, but variance
    introduced isn't much - the trends still show through.
*/
while($monthlyW = $sql->fetchRow($monthlyR)){
    if ($curyear != $monthlyW[0] || $curmonth != $monthlyW[1] || $curday != $monthlyW[2]){
        if ($curyear != '')
            echo "<td>$sum</td></tr>";
        echo "<tr>";
        $curdw = $days[$monthlyW[3]-1];
        $curyear = $monthlyW[0];
        $curmonth = $monthlyW[1];
        $curday = $monthlyW[2];
        $sum = 0;
        echo "<tr><td>$curdw</td><td>$curmonth/$curday/$curyear</td>";
    }
    $sum += $monthlyW[4];
}
echo "<td>$sum</td></tr></table>";

