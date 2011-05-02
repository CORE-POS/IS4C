<?php
include('../../config.php');
include("funct1Mem.php");
/* 

  In extending this to deal with like code groups, I wondered if php
  would allow assigning a function name to a variable.  So I tried it
  and it worked.  Nifty.

*/
$upc = '';
if (isset($_GET['upc'])){
  $function = "item_sales_month";
  $function_last_month = "item_sales_last_month";
  $upc = $_GET['upc'];
}
else if (isset($_GET['likecode'])){
  $function = "item_sales_month_like";
  $function_last_month = "item_sales_last_month_like";
  $upc = $_GET['likecode'];
}
$period = 'mm';
$time0 = 0;
$time1 = -1;
$time2 = -2;
$time3 = -3;
$week = "ww";
$day = "dd";
echo "<table><th>&nbsp;<th>Qty<th>Sales<tr>";
/*echo "<td><font color=blue>Today</font></td>";
item_sales_month($upc,$day,$time0);
echo "</td><tr>*/echo "<td><font color=blue>Yesterday</font></td>";
$function($upc,$day,$time1);
echo "</td><tr><td><font color=blue>2 Days ago</font></td>";
$function($upc,$day,$time2);
echo "</td><tr><td><font color=blue>3 Days ago</font></td>";
$function($upc,$day,$time3);
echo "</td><tr><td><font color=blue>This Week</font></td>";
$function($upc,$week,$time0);
echo "</tr><tr><td><font color=blue>Last Week</font></td>";
$function($upc,$week,$time1);
echo "</td><tr><td><font color=blue>This Month</font></td>";
$function($upc,$period,$time0);
echo "</td><tr><td><font color=blue>Last Month</font></td>";
$function_last_month($upc,$period,$time1);
echo "</tr></table>";
?>
