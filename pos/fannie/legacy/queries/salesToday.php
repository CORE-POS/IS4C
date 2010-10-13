<?php
include('../../config.php');

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../db.php');


// total cap added by andy
// to offset goofy transaction with odd upc on
// 11/19/05.  register_no 1, trans_num 119-1-28
$query1="SELECT  datepart(hh,tDate),sum(total)as Sales
FROM dLog
WHERE datediff(dd,getdate(),tDate)=0
AND (trans_type ='I' OR Trans_type = 'D' or trans_type='M')
AND department < 600
GROUP BY datepart(hh,tDate)
order by datepart(hh,tDate)";


$query2="SELECT sum(total) as TotalSales
FROM dLog
WHERE datediff(dd,getdate(),tDate)=0
AND (trans_type ='I' OR Trans_type = 'D' or trans_type='M')
AND department < 600";

$result1=$sql->query($query1);
$result2=$sql->query($query2);
$num1 = $sql->num_rows($result1);
$row2 = $sql->fetch_row($result2);

echo "<center><h1>Today's Sales!</h1>";
echo "<table>";
echo "<tr><td><b>Hour</b></td><td><b>Sales</b></td></tr>";
while($row1 = $sql->fetch_row($result1)){	
	echo "<tr><td>".$row1[0]."</td><td>".$row1[1]."</td></tr>";
}
echo "<tr><td>$row2[0]</td></tr>";
echo "</table>";

?>
