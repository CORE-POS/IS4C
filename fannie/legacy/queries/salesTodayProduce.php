<?php
include('../../config.php');

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../db.php');


// total cap added by andy
// to offset goofy transaction with odd upc on
// 11/19/05.  register_no 1, trans_num 119-1-28
$query1="SELECT  datepart(hh,tDate),
sum(case when t.superID = 6 then total else 0 end) as prodSales,
sum(total)as Sales
FROM dlog as d left join MasterSuperDepts as t
on d.department = t.dept_ID
WHERE datediff(dd,getdate(),tDate)=0
AND (trans_type ='I' OR Trans_type = 'D' or trans_type='M')
AND department < 600
GROUP BY datepart(hh,tDate)
order by datepart(hh,tDate)";


$query2="SELECT 
sum(case when t.superID=6 then total else 0 end) as produceTotal,
sum(total) as TotalSales
FROM dlog as d left join MasterSuperDepts as t
on d.department = t.dept_ID
WHERE datediff(dd,getdate(),tDate)=0
AND (trans_type ='I' OR Trans_type = 'D' or trans_type='M')
AND department < 600";

$result1=$sql->query($query1);
$result2=$sql->query($query2);
$num1 = $sql->num_rows($result1);
$row2 = $sql->fetch_row($result2);

echo "<center><h1>Today's <span style=\"color:green;\">Produce</span> Sales!</h1>";
echo "<table>";
echo "<tr><td><b>Hour</b></td><td><b>Sales</b></td></tr>";
while($row1 = $sql->fetch_row($result1)){
	echo "<tr><td>".$row1[0]."</td><td>".$row1[1]." (".round($row1[1]/$row1[2]*100,2)."%)</td></tr>";
}
echo "<tr><th width=60px align=left>Total</th><td>$row2[0] (".round($row2[0]/$row2[1]*100,2)."%)</td></tr>";
echo "</table>";

?>
