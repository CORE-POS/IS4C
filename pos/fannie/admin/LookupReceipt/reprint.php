<?php
include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'src/functions.php');

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
   global $FANNIE_ARCHIVE_DB, $FANNIE_TRANS_DB, $FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DBMS, $FANNIE_ARCHIVE_REMOTE;
   $dbconn = ($FANNIE_ARCHIVE_DBMS=='MSSQL')?'.dbo.':'.';
   if (!$FANNIE_ARCHIVE_REMOTE)
	   $dbconn = ($FANNIE_SERVER_DBMS=='MSSQL')?'.dbo.':'.';

   $totime = strtotime($date);
   $month = date('m',$totime);
   $year = date('Y',$totime);
   $day = date('j',$totime);
   $transact = explode('-',$trans);
   $emp_no = $transact[0];
   $trans_no = $transact[2];
   $reg_no = $transact[1];
   $head = $FANNIE_ARCHIVE_DB.$dbconn.'rp_receipt_header_'.$year.$month;
   $rp= $FANNIE_ARCHIVE_DB.$dbconn.'rp_dt_receipt_'.$year.$month;

   $queryHead = "SELECT * FROM $head WHERE "
               ."day(datetimestamp) = '$day'"
               ." AND month(datetimestamp) = '$month'"
               ." and trans_num = '$trans' ";
   
   $query1 = "SELECT description,comment,total,status FROM $rp"
           ." WHERE day(datetime) = '$day' and month(datetime) = $month"
           ." and trans_num = '$trans'"
           ." ORDER BY trans_id";
   //echo $query1;
   receipt_to_table($query1,$queryHead,0,'FFFFFF');
}

$border = 0;
//$color = #000000

if (isset($_GET['receipt']) || isset($_POST['receipt']))
	receiptHeader($date1,$transNum);

?>
