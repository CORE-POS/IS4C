<?php
include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');
include('functMem.php');
include('headerTest.php');

$sql->query("USE $FANNIE_TRANS_DB");

$mem = $_GET['memID'];
$col='#FFFF99';
/*
$query="SELECT datepart(mm,dateTimeStamp),datepart(dd,dateTimeStamp),datepart(yy,dateTimeStamp),
	tendertotal,memberID,trans_num,
	(CASE WHEN ARPayment <> '0' 
		THEN 'P'
		ELSE '' END) as payment,
	(CASE WHEN chargeTotal <> 0 
		THEN 'C'
		ELSE '' END) as charge,
	datepart(mi,dateTimeStamp) 
	FROM rp_dt_header 
	where memberID=$mem
	AND (ARPayment<> 0 or chargetotal <> 0) 
	order by dateTimeStamp DESC";
*/

$query="SELECT month(tdate) as tm,day(tdate) as td,year(tdate) as ty,
        CASE WHEN charges <> 0 THEN charges ELSE Payments END as tendertotal,
        card_no,trans_num,
        (CASE WHEN payments <> 0
           THEN 'P'
           ELSE '' END) as payment,
        (CASE WHEN charges <> 0
           THEN 'C'
           ELSE '' END) as charge
        from ar_history_today
        WHERE card_no = $mem
	
	union all
	
 SELECT month(tdate) as tm,day(tdate) as td,year(tdate) as ty,
        CASE WHEN charges <> 0 THEN charges ELSE Payments END as tendertotal,
        card_no,trans_num,
        (CASE WHEN payments <> 0
           THEN 'P'
           ELSE '' END) as payment,
        (CASE WHEN charges <> 0
           THEN 'C'
           ELSE '' END) as charge
        from ar_history
        WHERE card_no = $mem

        order by ty desc, tm desc, td desc";


trans_to_table($query,1,$col);


?>
