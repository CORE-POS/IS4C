<?php
include('../../config.php');

$transNum= isset($_REQUEST['receipt'])?$_REQUEST['receipt']:'';
$date1 = "";
if(isset($_REQUEST['month'])){
    $receipt = $_GET['receipt'];

    if(strlen($_GET['month'])<2){
	   $month = '0'.$_GET['month'];
    }else{
	   $month = $_GET['month'];
    }
    $day = $_GET['day'];
    $year = $_GET['year'];
    $date1 = $year."-".$month."-".$day;
}elseif(isset($_REQUEST['date'])){
   $date = $_REQUEST['date'];
   $tmp = explode("-",$date);
   if (is_array($tmp) && count($tmp)==3){
	$year = strlen($tmp[0]==2)?'20'.$tmp[0]:$tmp[0];
	$month = str_pad($tmp[1],2,'0',STR_PAD_LEFT);
	$day = str_pad($tmp[2],2,'0',STR_PAD_LEFT);
	$date1 = $year."-".$month."-".$day;
   }
   else {
	$tmp = explode("/",$date);
	if (is_array($tmp) && count($tmp)==3){
		$year = strlen($tmp[2]==2)?'20'.$tmp[2]:$tmp[2];
		$month = str_pad($tmp[0],2,'0',STR_PAD_LEFT);
		$day = str_pad($tmp[1],2,'0',STR_PAD_LEFT);
		$date1 = $year."-".$month."-".$day;
	}
	else $date1 = $date;
   }
}

$url = $FANNIE_URL.'admin/LookupReceipt/';
if ($transNum != '') {
    list($y, $m, $d) = explode('-', $date1, 3);
    $url .= sprintf('RenderReceiptPage.php?receipt=%s&month=%d&day=%d&year=%d',
                $trans_num, $m, $d, $y);
}
header('Location: '.$url);

