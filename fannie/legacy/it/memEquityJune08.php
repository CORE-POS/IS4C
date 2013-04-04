<?php
include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');

include($FANNIE_ROOT.'src/functions.php');
header('Content-Type: application/ms-excel');
header('Content-Disposition: attachment; filename="memEquityJune08.xls"');

$q = "select c.cardno,c.firstname,c.lastname,
      n.payments - sum(case when datediff(dd,tdate,'2008-06-30') < 0
		then stockPurchase else 0 end) as payments
      from custdata as c
      left outer join newBalanceStockToday_test
      as n on n.memnum = c.cardno
      left join stockPurchases as s
      on c.cardno = s.card_no
      where c.personnum = 1 and 
      c.type <> 'TERM' and c.cardno <> 11
      group by c.cardno,c.firstname,c.lastname,n.payments
      having n.payments - sum(case when datediff(dd,tdate,'2008-06-30') < 0
		then stockPurchase else 0 end) <> 0
      order by convert(int,c.cardno)";

select_to_table2($q,array(),1,'#ffffff',120,0,0,array('Mem#','First','Last','Equity'));


?>
