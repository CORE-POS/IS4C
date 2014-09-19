<?php
include(dirname(__FILE__) . '/../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
$dbc = FannieDB::get($FANNIE_TRANS_DB);
/*
this page generates a set of graphs
for cashier performance
*/

/*
include('auth/login.php');
if (!validateUserQuiet('cashierPerformance')){
  header("Location: /auth/ui/loginform.php?redirect=/cash_report.php");
  return;
}
*/

function avg($array){
    $count = 0;
    $sum = 0;
    foreach ($array as $a){
        $sum += $a;
        $count++;
    }
    return (float)$sum / $count;
}

if (!isset($_GET['emp_no'])){
    $page_title = "Fannie :: Cashier Report";
    $header = "Cashier Report";
    include($FANNIE_ROOT.'src/header.html');
?>
<script type="text/javascript">
$(document).ready(function(){
    $('#emp_no').focus();
});
</script>
Enter an employee number<br />
<form method=get action=<?php echo $_SERVER['PHP_SELF'] ?> >
<input type=text id=emp_no name=emp_no size=4 />&nbsp;
<input type=submit value="Get report" />
<input type=checkbox name=pdf /> PDF
</form>
<?php
    include($FANNIE_ROOT.'src/footer.html');
    return;
}

/*
first get the data from the database
*/

$emp_no = $_GET['emp_no'];

$query = "";
$args = array();
if($emp_no==""){
$query = "select
          emp_no,
          ".$dbc->weekdiff($dbc->now(),'proc_date')." as week,
          year(proc_date) as year,
      SUM(Rings) / count(emp_no) as rings,
      ".$dbc->convert('SUM(items)','int')." / count(emp_no) as items,
      COUNT(Rings) / count(emp_no) as Trans,
      SUM(CASE WHEN transinterval = 0 then 1 when transinterval > 600 then 600 else transinterval END) / count(emp_no)  / 60 as minutes,
      SUM(Cancels) / count(emp_no) as cancels,
      MIN(proc_date)
      from CashPerformDay
      GROUP BY emp_no,".$dbc->weekdiff($dbc->now(),'proc_date').",year(proc_date)
      ORDER BY year(proc_date) desc,".$dbc->weekdiff($dbc->now(),'proc_date')." asc";

}
else {
$query = "select
          emp_no,
          ".$dbc->weekdiff($dbc->now(),'proc_date')." as week,
          year(proc_date) as year,
          SUM(Rings) as rings,
      ".$dbc->convert('SUM(items)','int')." as items,
          COUNT(*) as TRANS,
          SUM(CASE WHEN transInterval = 0 THEN 1 when transInterval > 600 then 600 ELSE transInterval END)/60 as minutes,
          SUM(cancels)as cancels,
          MIN(proc_date)
          FROM CashPerformDay
          WHERE emp_no = ?
      GROUP BY emp_no,".$dbc->weekdiff($dbc->now(),'proc_date').",year(proc_date)
      ORDER BY year(proc_date) desc,".$dbc->weekdiff($dbc->now(),'proc_date')." asc";
$args = array($emp_no);
}
if ($dbc->isView('CashPerformDay') && $dbc->tableExists('CashPerformDay_cache')) {
    $query = str_replace('CashPerformDay', 'CashPerformDay_cache', $query);
}
$result = $dbc->exec_statement($query,$args);

$rpm = array(); // rings per minute
$ipm = array(); // items per minute
$tpm = array(); // transactions per minute
$cpr = array(); // cancels per rings
$cpi = array(); // cancels per items
$week = array(); // first day of the week
$i = 0;
/* 
calculate rates
remove the time from the week
*/
while ($row = $dbc->fetch_array($result)){
  $temp = explode(" ",$row[8]);
  $temp = explode("-",$temp[0]);
  $week[$i] = $temp[0]." ".$temp[1]." ".$temp[2];
  $minutes = $row[6];
  // zeroes values where minutes = 0
  if ($minutes == 0)
    $minutes = 999999999;
  $rpm[$i] = $row[3] / $minutes;
  $ipm[$i] = $row[4] / $minutes;
  $tpm[$i] = $row[5] / $minutes;
  if ($row[3] == 0)
    $cpr[$i] = 0;
  else
    $cpr[$i] = ($row[7] / $row[3]) * 100;
  if ($row[4] == 0)
    $cpi[$i] = 0;
  else
    $cpi[$i] = ($row[7] / $row[4]) * 100;
  $i++;
}

include('graph.php');

/* clear out ony ld images */
exec("rm -f image_area/*cash_report*.png");

/* generate a reasonably unique session key */
$session_key = '';
for ($i = 0; $i < 20; $i++){
  $num = rand(97,122);
  $session_key = $session_key . chr($num);
}

/* write graphs in the image_area directory */
$session_key = "image_area/".$session_key;

$width = graph($rpm,$week,$session_key."cash_report_0.png");
graph($ipm,$week,$session_key."cash_report_1.png",10,0,0,255);
graph($tpm,$week,$session_key."cash_report_2.png",60,0,255,0);
graph($cpr,$week,$session_key."cash_report_3.png",90,0,0,255);
graph($cpi,$week,$session_key."cash_report_4.png",90);



if(isset($_GET['pdf'])){
  require('lib/fpdf/fpdf.php');

  $pdf = new FPDF();
  $pdf->AddPage();
  $pdf->SetFont('Arial','B',16);
  $str = "Rings per minute\n";
  $str .= "(average: ".round(avg($rpm),2).")";
  $pdf->MultiCell(0,11,$str,0,'C');
  $pdf->Image($session_key."cash_report_0.png",65,35,$width/2);
  $pdf->AddPage();
  $str = "Items per minute\n";
  $str .= "(average: ".round(avg($ipm),2).")";
  $pdf->MultiCell(0,11,$str,0,'C');
  $pdf->Image($session_key."cash_report_1.png",65,35,$width/2);
  $pdf->AddPage();
  $str = "Transactions per minute\n";
  $str .= "(average: ".round(avg($tpm),2).")";
  $pdf->MultiCell(0,11,$str,0,'C');
  $pdf->Image($session_key."cash_report_2.png",65,35,$width/2);
  $pdf->AddPage();
  $str = "% rings cancelled\n";
  $str .= "(average: ".round(avg($cpr),2).")";
  $pdf->MultiCell(0,11,$str,0,'C');
  $pdf->Image($session_key."cash_report_3.png",65,35,$width/2);
  $pdf->AddPage();
  $str = "% items cancelled\n";
  $str .= "(average: ".round(avg($cpi),2).")";
  $pdf->MultiCell(0,11,$str,0,'C');
  $pdf->Image($session_key."cash_report_4.png",65,35,$width/2);
  $pdf->Output("Cashier_" . $emp_no . "_Report.pdf","D");
}
else {
?>

<html>
<body>
<div align=center><h2>Rings per minute</h2>
(<i>average:</i> <?php echo round(avg($rpm),2) ?>)<br />
<img src=<?php echo $session_key."cash_report_0.png"?> />
</div>
<hr />
<div align=center><h2>Items per minute</h2>
(<i>average:</i> <?php echo round(avg($ipm),2) ?>)<br />
<img src=<?php echo $session_key."cash_report_1.png"?> />
</div>
<hr />
<div align=center><h2>Transactions per minute</h2>
(<i>average:</i> <?php echo round(avg($tpm),2) ?>)<br />
<img src=<?php echo $session_key."cash_report_2.png"?> />
</div>
<hr />
<div align=center><h2>% Rings canceled</h2>
(<i>average:</i> <?php echo round(avg($cpr),2) ?>)<br />
<img src=<?php echo $session_key."cash_report_3.png"?> />
</div>
<hr />
<div align=center><h2>% Items canceled</h2>
(<i>average:</i> <?php echo round(avg($cpi),2) ?>)<br />
<img src=<?php echo $session_key."cash_report_4.png"?> />
</div>

</body>
</html

<?php
}
?>>
