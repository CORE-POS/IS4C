<?php

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT.'src/SQLManager.php');
include($FANNIE_ROOT.'legacy/db.php');

function addProductAllLanes($upc){
	global $sql,$FANNIE_ROOT,$FANNIE_LANES,$FANNIE_OP_DB;

	rowRestrict();
	foreach($FANNIE_LANES as $lane){
		$sql->add_connection($lane['host'],$lane['type'],$lane['op'],$lane['user'],$lane['pw']);
		$selQ = "SELECT upc,description,normal_price,pricemethod,groupprice,quantity,
			special_price,specialpricemethod,specialgroupprice,specialquantity,start_date,end_date,
			department,size,tax,foodstamp,scale,scaleprice,mixmatchcode,modified,advertised,tareweight,discount,
			discounttype,unitofmeasure,wicable,qttyEnforced,idEnforced,cost,inUse,numflag,subdept,
			deposit,local,id FROM products WHERE upc='$upc'";	
		$ins = "INSERT INTO products (upc,description,normal_price,pricemethod,groupprice,quantity,
			special_price,specialpricemethod,specialgroupprice,specialquantity,start_date,end_date,
			department,size,tax,foodstamp,scale,scaleprice,mixmatchcode,modified,advertised,tareweight,discount,
			discounttype,unitofmeasure,wicable,qttyEnforced,idEnforced,cost,inUse,numflag,subdept,
			deposit,local,id)";
		$sql->transfer($FANNIE_OP_DB,$selQ,$lane['op'],$ins);
	}
	rowRelease();
}

function deleteProductAllLanes($upc){
	global $sql,$FANNIE_ROOT,$FANNIE_LANES,$FANNIE_OP_DB;

	rowRestrict();
	foreach($FANNIE_LANES as $lane){
		$tmp = new SQLManager($lane['host'],$lane['type'],$lane['op'],$lane['user'],$lane['pw']);
		$delQ = "DELETE FROM products WHERE upc='$upc'";
		$delR = $tmp->query($delQ,$lane['op']);
	}
	rowRelease();
}

function updateProductAllLanes($upc){
	deleteProductAllLanes($upc);
	addProductAllLanes($upc);
}

function rowRestrict(){
	global $sql,$FANNIE_SERVER_DBMS;
	if (strtoupper($FANNIE_SERVER_DBMS) != "MSSQL") return;
	$restrictQ = "set rowcount 1";
	$restrictR = $sql->query($restrictQ);
}

function rowRelease(){
	global $sql,$FANNIE_SERVER_DBMS;
	if (strtoupper($FANNIE_SERVER_DBMS) != "MSSQL") return;
	$releaseQ = "set rowcount 0";
	$releaseQ = $sql->query($releaseQ);
}

function syncProductsAllLanes(){
	global $FANNIE_ROOT,$FANNIE_SERVER,$FANNIE_SERVER_USER,$FANNIE_SERVER_PW,$FANNIE_OP_DB,$FANNIE_LANES;
	ob_start();
	$table = 'products';
	include($FANNIE_ROOT.'sync/special/products.php');
	ob_end_clean();
}

?>
