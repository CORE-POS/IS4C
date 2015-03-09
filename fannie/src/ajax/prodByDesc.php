<?php

include('../../config.php');
if (!class_exists('FannieAPI')) {
        include(dirname(__FILE__).'/../classlib2.0/FannieAPI.php');
}
$dbc = FannieDB::get($FANNIE_OP_DB);
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
	$q .=  "WHERE (u.description LIKE ? OR
		(u.description IS NULL and p.description LIKE ?)) ";
	$args = array('%'.$search.'%','%'.$search.'%');
	if ($sd != 0){
		$q .= "AND s.superID=? ";
		$args[] = $sd;
	}
	$q .=  "GROUP BY goodDesc
		ORDER BY goodDesc";
	$p = $dbc->prepare_statement($q);
	$r = $dbc->exec_statement($p,$args);
	while($w = $dbc->fetch_row($r)){
		$ret[] = array('label'=>$w[1],'value'=>$w[0]);
	}
}

echo json_encode($ret);

