<?php
include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');
include('functMem.php');
include('headerTest.php');

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

$query="SELECT datepart(mm,tdate),datepart(dd,tdate),datepart(yy,tdate),
        CASE WHEN charges <> 0 THEN charges ELSE Payments END as tendertotal,
        card_no,trans_num,
        (CASE WHEN payments <> 0
           THEN 'P'
           ELSE '' END) as payment,
        (CASE WHEN charges <> 0
           THEN 'C'
           ELSE '' END) as charge,
        datepart(mi,tdate)
        from ar_history_today
        WHERE card_no = $mem
	
	union all
	
 SELECT datepart(mm,tdate),datepart(dd,tdate),datepart(yy,tdate),
        CASE WHEN charges <> 0 THEN charges ELSE Payments END as tendertotal,
        card_no,trans_num,
        (CASE WHEN payments <> 0
           THEN 'P'
           ELSE '' END) as payment,
        (CASE WHEN charges <> 0
           THEN 'C'
           ELSE '' END) as charge,
        datepart(mi,tdate)
        from ar_history
        WHERE card_no = $mem
        order by datepart(yy,tdate) desc, datepart(mm,tdate) desc, datepart(dd,tdate) desc";


trans_to_table($query,1,$col);


?>
