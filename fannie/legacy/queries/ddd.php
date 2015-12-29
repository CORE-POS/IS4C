<?php

include('../db.php');

echo "<table cellspacing=0 cellpadding=4 border=1>";

$q = "SELECT month,day,year,upc,description,dept_no,dept_name,quantity,total
    FROM is4c_trans.dddItems
    order by year desc, month desc, day desc, description";
$r = $sql->query($q);
echo "<tr><th>Date</th><th>UPC</th><th>Item</th><th>Dept#</th>
<th>Dept name</th><th>Qty</th><th>$</th></tr>";
while($w = $sql->fetch_row($r)){
    printf("<tr><td>%d/%d/%d</td><td><a href=productTest.php?upc=%s>%s</a>
        </td><td>%s</td><td>%d</td>
        <td>%s</td><td>%.2f</td><td>%.2f</td></tr>",
        $w[0],$w[1],$w[2],$w[3],$w[3],$w[4],$w[5],$w[6],$w[7],$w[8]);
}
echo "</table>";

