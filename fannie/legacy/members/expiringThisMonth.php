<?php
header('Content-Type: application/ms-excel');
header('Content-Disposition: attachment; filename="expiringMem.xls"');

include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');
include('functMem.php');

$query = "SELECT e.memnum,c.firstname,c.lastname,e.enddate,
	e.payments,
	case when 100-e.payments<0 then 0 else 100-e.payments end as owed,
	e.address1+' '+e.address2,e.city,e.state,e.zipcode
	from expingMems_thisMonth as e left join
	custdata as c on e.memnum=c.cardno
	left join memDates as m on m.card_no=e.memnum
	where c.personnum=1 and c.type='PC'
	and e.payments < 100
	and isdate(m.end_date)=1
	order by e.memnum";

echo "<table cellspacing=0 cellpadding=4 border=1>
<tr><th>Mem#</th><th>First</th><th>Last</th><th>End date</th>
<th>Owned</th><th>Due</th><th colspan=4>Address</th></tr>";
$result = $sql->query($query);
while($row = $sql->fetch_row($result)){
	echo "<tr>";
	for($i=0;$i<$sql->num_fields($result);$i++) echo "<td>$row[$i]</td>";
	echo "</tr>";
}

?>



