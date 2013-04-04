<?php
include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'src/select_dlog.php');

$header = "Customers per Hour";
$page_title = "Fannie : Customers per Hour";
include($FANNIE_ROOT.'src/header.html');
?>
<script src="<?php echo $FANNIE_URL; ?>src/CalendarControl.js"
        language="javascript"></script>
<form method=get action=<?php echo $_SERVER["PHP_SELF"]; ?> >
Get transactions per hour for what date (YYYY-MM-DD)?<br />
<input type=text onfocus="showCalendarControl(this);" name=date />&nbsp;
<input type=submit value=Generate />
</form>

<?php
if (isset($_GET['date'])){
  $date = $_GET['date'];
  $dlog = select_dlog($date);

  $q = $dbc->prepare_statement("select datepart(hh,tdate) as hour,
        count(distinct trans_num)
        from $dlog where
        tdate BETWEEN ? AND ?
        group by datepart(hh,tdate)
        order by datepart(hh,tdate)");
  $r = $dbc->exec_statement($q,array($date.' 00:00:00',$date.' 23:59:59'));

  echo "Report for $date<br />";
?>
<table><tr><th>Hour</th><th>Transactions</th></tr>
<?php
  $total = 0;
  while($row = $dbc->fetch_array($r)){
    echo "<tr>";
    $hour = $row[0];
    $num = $row[1];
    $total += $num;
    $newhour = $hour;
    if ($hour > 12){
      $newhour -= 12;
    }
    if ($hour < 12){
      $newhour .= ":00 am";
    }
    else {
      $newhour .= ":00 pm";
    }
    echo "<td align=right>$newhour</td><td align=center>$num</td>";
    echo "</tr>";
  }
  echo "<tr><td align=right>Total</td><td align=center>$total</td></tr>";
  echo "</table>";
}
include($FANNIE_ROOT.'src/footer.html');
?>
