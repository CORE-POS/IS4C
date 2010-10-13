<?php
include('../../config.php');
if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../db.php');

$query = "SELECT upc,description,normal_price,department,tax,foodstamp,scale,qttyEnforced,inUse,modified FROM products where datediff(ww,modified,getdate()) < 2 and department > 199 order by modified,description";

$result = $sql->query($query);

echo "<table><tr><th>upc<th>description<th>price<th>dept<th>tax<th>FS<th>scale<th>qttyEnforce<th>inUse</tr><tr>";
while($row = $sql->fetch_row($result)){
   echo "<td>$row[0]</td><td>$row[1]</td><td>$row[2]</td><td>$row[3]</td><td align=center>$row[4]</td><td align=center>$row[5]</td><td align=center>$row[6]</td><td align=center>$row[7]</td><td align=center>$row[8]</tr>";
}

echo "</table>";

?>
