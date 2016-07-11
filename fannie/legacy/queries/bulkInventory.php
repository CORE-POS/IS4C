<?php 

header('Content-Type: application/ms-excel');
header('Content-Disposition: attachment; filename="bulkList.xls"');

echo "<body>";
include('../db.php');

$query = "SELECT p.upc,p.description,p.department,p.normal_price,d.superID,
CASE WHEN x.distributor is null or x.distributor='' then '&nbsp;' else x.distributor end as distributor
FROM products as p LEFT JOIN MasterSuperDepts as d ON p.department=d.dept_ID
LEFT JOIN prodExtra as x on p.upc = x.upc
WHERE (d.superID = 1 or superID = 5 or superID = 9) AND scale = 1
order by d.superID,p.department";

//select_to_table($query,0,';#ffffff');

$result = $sql->query($query);

echo '<table>';
echo '<tr><td>PLU</td><td>Desc</td><td>Dept</td><td>Price</td><td>Buyer</td><td>Distributor</td>
      </td></tr>';
while($row = $sql->fetch_row($result)){
   echo '<tr><td>'. $row[0].'</td><td>'.$row[1];
   echo '</td><td>'.$row[2].'</td><td>'.$row[3];
   echo '</td><td>'.$row[4].'</td><td>'.$row[5];
   echo '</td></tr>';
}

?>

</body>
</html>
