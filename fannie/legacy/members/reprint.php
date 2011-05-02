<?php
include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');
include('../queries/funct1Mem.php');

?>

<form action=reprint.php method=post>
Date: <input type=text name=date><br>
Receipt Num: <input type=text name=receipt><br>
<input type=submit name=submit>
<?php
if(isset($_GET['receipt'])){
    $receipt = $_GET['receipt'];

    if(strlen($_GET['month'])<2){
	   $month = '0'.$_GET['month'];
    }else{
	   $month = $_GET['month'];
    }
    $day = $_GET['day'];
    $year = $_GET['year'];
    $transNum = $_GET['receipt'];
    $date1 = $year."-".$month."-".$day;
}elseif(isset($_POST['submit'])){
   $date = $_POST['date'];
   $transNum= $_POST['receipt'];
   $month = substr($date,0,2);
   $day = substr($date,3,2);
   $year = substr($date,6,4);
   $date1 = $year."-".$month."-".$day;
}else{

}

function receiptHeader($date,$trans){
   $totime = strtotime($date);
   $month = date('m',$totime);
   $year = date('Y',$totime);
   $day = date('j',$totime);
   $transact = explode('-',$trans);
   $emp_no = $transact[0];
   $trans_no = $transact[2];
   $reg_no = $transact[1];
   if($year == date('Y') && $month >= date('m')-1){
      $head = 'rp_receipt_header_90';
      $rp = 'rp_dt_receipt_90';
   }else{
      $head = 'trans_archive.dbo.rp_receipt_header_'.$year.$month;
      $rp= 'trans_archive.dbo.rp_dt_receipt_'.$year.$month;
   }

   $queryHead = "SELECT * FROM $head WHERE "
               ."datepart(d,datetimestamp) = '$day'"
               ." AND datepart(m,datetimestamp) = '$month'"
               ." and trans_num = '$trans' ";
   log_info('queryHead',$queryHead);
   
   
   $query1 = "SELECT description,comment,total,status FROM $rp"
           ." WHERE datepart(dd,datetime) = '$day' and datepart(mm,datetime) = $month"
           ."and trans_num = '$trans'"
           ." ORDER BY trans_id";
   //echo $query1;
   receipt_to_table($query1,$queryHead,0,'FFFFFF');
}

$border = 0;
//$color = #000000

if (isset($_GET['receipt']) || isset($_POST['receipt']))
	receiptHeader($date1,$transNum);

?>
