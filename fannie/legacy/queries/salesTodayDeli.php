<?php

include('../db.php');

// total cap added by andy
// to offset goofy transaction with odd upc on
// 11/19/05.  register_no 1, trans_num 119-1-28
$query1="SELECT  hour(tdate),
sum(case when t.superID = 3 then total else 0 end) as prodSales,
sum(total)as Sales
FROM is4c_trans.dlog as d left join MasterSuperDepts as t
on d.department = t.dept_ID
WHERE datediff(curdate(),tdate)=0
AND (trans_type ='I' OR trans_type = 'D' or trans_type='M')
AND department < 600
GROUP BY hour(tdate)
order by hour(tdate)";


$query2="SELECT 
sum(case when t.superID=3 then total else 0 end) as produceTotal,
sum(total) as TotalSales
FROM is4c_trans.dlog as d left join MasterSuperDepts as t
on d.department = t.dept_ID
WHERE datediff(curdate(),tdate)=0
AND (trans_type ='I' OR trans_type = 'D' or trans_type='M')
AND department < 600";

$result1=$sql->query($query1,'is4c_op');
$result2=$sql->query($query2,'is4c_op');
$num1 = $sql->num_rows($result1,'is4c_op');
$row2 = $sql->fetch_row($result2,'is4c_op');

echo "<center><h1>Today's <span style=\"color:green;\">Deli</span> Sales!</h1>";
echo "<table>";
echo "<tr><td><b>Hour</b></td><td><b>Sales</b></td></tr>";
while($row1 = $sql->fetch_row($result1,'is4c_op')){
    echo "<tr><td>".$row1[0]."</td><td>".$row1[1]." (".round($row1[1]/$row1[2]*100,2)."%)</td></tr>";
}
echo "<tr><th width=60px align=left>Total</th><td>$row2[0] (".round($row2[0]/$row2[1]*100,2)."%)</td></tr>";
echo "</table>";

