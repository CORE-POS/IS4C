<?php
header('Content-Type: application/ms-excel');
header('Content-Disposition: attachment; filename="expiringMem.xls"');

include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');
include('functMem.php');

$query="SELECT DISTINCT m.lname,m.fname,e.*
	FROM expingMems as e, memnames as m
	WHERE e.memnum = m.memnum
	AND m.personnum = 1 
	AND m.active = 1
	order by e.memnum";

select_to_table($query,0,'ffffff');

?>



