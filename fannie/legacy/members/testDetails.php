<?php
include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');
include('functMem.php');
include('headerTest.php');

$mem = $_GET['memID'];
$col='#FFFF99';
$sql->query("USE $FANNIE_TRANS_DB");

//new query based on stockpurchases table
$query = "SELECT month(tdate),day(tdate),year(tdate),stockpurchase,card_no,trans_num
          from stockpurchases
          where card_no = $mem
          order by tdate DESC";
trans_to_table($query,1,$col);


?>
