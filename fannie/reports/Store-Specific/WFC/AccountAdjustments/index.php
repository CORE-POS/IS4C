<?php
include('../../../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
$dbc = FannieDB::get($FANNIE_OP_DB);

if (!isset($_GET['excel'])){
?>
<html>
<head><title>Account Adjstment</title>
<script type=text/javascript>
function doDaily() {
    document.getElementById('format').innerHTML="<i>Format: m/d/y</i>";
}
function doMonthly() {
    document.getElementById('format').innerHTML="<i>Format: m/y</i>";
}
</script>
<body bgcolor=#99cccc>
<form action=index.php method=get>
Daily: <input type=radio name=type value=daily checked onclick="doDaily();" /> 
Monthly: <input type=radio name=type value=monthly onclick="doMonthly();" /><br />
Date: <input type=text name=date /> 
<span id="format"><i>Format: m/d/y</i></span><br /><br />
<input type=submit value=Submit /> Excel <input type=checkbox name=excel />
</form>
<?php
}
else {
    header('Content-Type: application/ms-excel');
    header('Content-Disposition: attachment; filename="accountAdjustReport.xls"');
    echo "<body bgcolor=#99cccc>";
}

include($FANNIE_ROOT.'src/functions.php');

/* defaults */
$type = "daily";
$today = date("m/j/y");
// Calculate the previous day's date, old method just gave zero - Andy
$date = date('m/j/y', mktime(0, 0, 0, date("m") , date("d") - 1, date("Y")));

if (isset($_GET['type'])){
    $type = $_GET['type'];
    $date = $_GET['date'];  

    if (!strstr($date,"/")){
        echo "Sorry, date format needs to be m/d/y for daily or m/y for monthly<br />";
        echo "Slashes are essential";
        return;
    }
}

echo '<br>'.strtoupper(substr($type,0,1)).substr($type,1,strlen($type)).' report run ' . $today. ' for ' . $date."<br />";
if (!isset($_GET['excel'])){
    echo "(<a href=index.php?type=$type&date=$date&excel=yes>This report in excel</a>)<br />";
}
echo "<br />";

$ddiff="";
if ($type=="daily"){
    list($month,$day,$year) = explode("/",$date);
    $date = str_pad($year,4,'20',STR_PAD_LEFT)."-".
        str_pad($month,2,'0',STR_PAD_LEFT)."-".
        str_pad($day,2,'0',STR_PAD_LEFT);
    $ddiff = $dbc->date_equals('tdate',$date);
}
else {
    list($month,$year) = explode("/",$date);
    $date = str_pad($year,4,'20',STR_PAD_LEFT)."-".
        str_pad($month,2,'0',STR_PAD_LEFT)."-01";
    $date2 = date("Y-m-t",mktime(0,0,0,$month,1,$year));
    $ddiff = " (tdate BETWEEN '$date 00:00:00' AND '$date2 23:59:59') ";
}
    
$dlog = DTransactionsModel::selectDlog($date);
$args = array($date.' 00:00:00',$date.' 23:59:59');

$otherQ = "SELECT d.department,t.dept_name, sum(total) as total 
    FROM $dlog as d join departments as t ON d.department = t.dept_no
    WHERE tdate BETWEEN ? AND ?
    AND (d.department >300)AND d.Department <> 0 AND d.register_no = 20
    GROUP BY d.department, t.dept_name order by d.department";
$stockQ = "SELECT d.card_no,t.dept_name, sum(total) as total 
    FROM $dlog as d join departments as t ON d.department = t.dept_no
    WHERE tdate BETWEEN ? AND ?
    AND (d.department IN(991,992))AND d.Department <> 0 and d.register_no = 20
    GROUP BY d.card_no, t.dept_name ORDER BY d.card_no, t.dept_name";
$arQ = "SELECT d.card_no,CASE WHEN d.department = 990 THEN 'AR PAYMENT' ELSE 'STORE CHARGE' END as description, 
    sum(total) as total, count(card_no) as transactions FROM $dlog as d 
    WHERE tdate BETWEEN ? AND ?
    AND (d.department =990 OR d.trans_subtype = 'MI') and d.register_no = 20 
    GROUP BY d.card_no,d.department order by department,card_no";

echo 'Other';
echo '<br>------------------------------';
echo '<table><td width=120><u><font size=2><b>Dept</b></u></font></td>
      <td width=120><u><font size=2><b>Description</b></u></font></td>
      <td width=120><u><font size=2><b>Amount</b></u></font></td></table>';
select_to_table($otherQ,$args,0,'99cccc');
echo '<br>';
echo 'Equity Payments by Member Number';
echo '<br>------------------------------';
echo '<table><td width=120><u><font size=2><b>MemNum</b></u></font></td>
      <td width=120><u><font size=2><b>Description</b></u></font></td>
      <td width=120><u><font size=2><b>Amount</b></u></font></td></table>';
select_to_table($stockQ,$args,0,'99cccc');
echo '<br>';
echo 'AR Activity by Member Number';
echo '<br>------------------------------';
echo '<table><td width=120><u><font size=2><b>MemNum</b></u></font></td>
      <td width=120><u><font size=2><b>Description</b></u></font></td>
      <td width=120><u><font size=2><b>Amount</b></u></font></td>
      <td width=120><u><font size=2><b>Transactions</b></u></font></td></tr></table>';

select_to_table($arQ,$args,0,'99cccc');
echo '<br>';

?>
</body>
</html>
