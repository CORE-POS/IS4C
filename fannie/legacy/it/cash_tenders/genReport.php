<?php

if (!isset($_GET['date'])){
  echo "<body bgcolor=baabaa>";
  echo "Cash tender report for what date (YYYY-MM-DD)?<br />";
  echo "<form action=".$_SERVER["PHP_SELF"]." method=GET>";
  echo "<input type=text name=date> ";
  echo "<input type=submit value=\"Get Report\">";
  echo "<br /><input type=checkbox name=excel>Excel";
  echo "</form>";
  echo "</body>";
  return;
}

include('../../../config.php');
if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");

include('../../db.php');
include($FANNIE_ROOT.'src/select_dlog.php');

if (isset($_GET['excel']) && $_GET['excel'] == 'on'){
  header('Content-Type: application/ms-excel');
  header('Content-Disposition: attachment; filename="cashTenderReport.xls"');
}

$date = $_GET['date'];
echo "<h3>Cash Tender Report for $date</h3>";

$dlog = select_dlog($date);

// make me a custom tender tape
// for the correct day
$tempQ =  "select tdate,emp_no,register_no,trans_no,
          (case when trans_subtype = 'mi' then -1*total else 0 end) as miTender,
          (case when trans_subtype = 'ck' then -1*total else 0 end) as ckTender,
          (case when trans_subtype = 'ef' or trans_subtype = 'ec' or trans_subtype = 'ta' then -1 * total else 0 end) as ebtTender,
          (case when trans_subtype = 'ca' and total > 0 then -1*total else 0 end) as changeGiven,
          (case when trans_subtype = 'ca' and total < 0 then -1*total else 0 end) as cashTender
          into tempTenderTape
          from $dlog
          where datediff(dd, tdate, '$date') = 0";
$tempR = $sql->query($tempQ);

$empnosQ = "select emp_no from tempTenderTape group by emp_no order by emp_no";
$empnosR = $sql->query($empnosQ);

$sheet_sum = 0;
while ($empnosRow = $sql->fetch_array($empnosR)){
  $diff = 0;
  $diff2 = 0;
  $cur_emp = $empnosRow[0];
  echo "<b>Employee no:</b> $cur_emp<br />";
  echo "Cash taken in:<br />";
  $inQ = "select tdate,register_no,trans_no,cashTender from tempTenderTape
          where emp_no = $cur_emp and cashTender <> 0 order by tdate";
  $inR = $sql->query($inQ);
  if ($sql->num_rows($inR) > 0){
    echo "<table border=1 cellspacing=2 cellpadding=2>";
    echo "<tr><th>Timestamp</th><th>Register</th><th>Transaction</th><th>Total</th></tr>";
    while ($inRow = $sql->fetch_array($inR)){
      echo "<tr>";
      echo "<td>$inRow[0]</td><td>$inRow[1]</td><td>$inRow[2]</td><td>$inRow[3]</td>";
      echo "</tr>";
    }
    $totalQ = "select sum(cashTender) from tempTenderTape where emp_no = $cur_emp";
    $totalR = $sql->query($totalQ);
    $totalRow = $sql->fetch_array($totalR);
    echo "<tr><td>Total</td><td /><td /><td>$totalRow[0]</td></tr>";
    $diff = $totalRow[0];
    echo "</table>";
  }
  else {
    echo "No cash in<p />";
  }
  echo "Change given out:<br />";
  $outQ = "select tdate,register_no,trans_no,changeGiven from tempTenderTape
           where emp_no = $cur_emp and changeGiven <> 0 order by tdate";
  $outR = $sql->query($outQ);
  if ($sql->num_rows($outR) > 0){
    echo "<table border=1 cellspacing=2 cellpadding=2>";
    echo "<tr><th>Timestamp</th><th>Register</th><th>Transaction</th><th>Total</th></tr>";
    while ($outRow = $sql->fetch_array($outR)){
      echo "<tr>";
      echo "<td>$outRow[0]</td><td>$outRow[1]</td><td>$outRow[2]</td><td>$outRow[3]</td>";
      echo "</tr>";
    }
    $totalQ = "select sum(changeGiven) from tempTenderTape where emp_no = $cur_emp";
    $totalR = $sql->query($totalQ);
    $totalRow = $sql->fetch_array($totalR);
    echo "<tr><td>Total</td><td /><td /><td>$totalRow[0]</td></tr>";
    $diff2 = $totalRow[0];
    echo "</table>";
  }
  else {
    echo "No change given<p />";
  }
  $difference = $diff + $diff2;
  echo "<br /><b>Difference:</b> $difference";
  $sheet_sum += $difference;
  echo "<hr />";
  //break;
}
echo "<h4>Report total: $sheet_sum</h4>";

// get rid of the extra table
$dropQ = "drop table tempTenderTape";
$dropR = $sql->query($dropQ);

?>
