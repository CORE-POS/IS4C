<?php

include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'src/JsonLib.php');
$ret = array();

$search = isset($_REQUEST['term']) ? $_REQUEST['term'] : '';
if (strlen($search) > 2){
	$sd = isset($_REQUEST['super']) ? (int)$_REQUEST['super'] : 0;
	$search = $dbc->escape("%".$search."%");
	$q = "SELECT MIN(p.upc),
		CASE WHEN u.description IS NULL THEN p.description ELSE u.description END as goodDesc
		FROM products AS p LEFT JOIN productUser AS u ON p.upc=u.upc ";
	if ($sd != 0)
		$q .= "LEFT JOIN superdepts AS s ON p.department=s.dept_ID ";
	$q .=  "WHERE (u.description LIKE $search OR
		(u.description IS NULL and p.description LIKE $search)) ";
	if ($sd != 0)
		$q .= "AND s.superID=$sd ";
	$q .=  "GROUP BY goodDesc
		ORDER BY goodDesc";
	$r = $dbc->query($q);
	while($w = $dbc->fetch_row($r)){
		$ret[] = array('label'=>$w[1],'value'=>$w[0]);
	}
}

echo JsonLib::array_to_json($ret);

?>
