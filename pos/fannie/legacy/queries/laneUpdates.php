<?php

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT.'src/SQLManager.php');
include($FANNIE_ROOT.'legacy/db.php');

function addProductAllLanes($upc){
	global $sql,$FANNIE_ROOT;
	include($FANNIE_ROOT.'legacy/lanedefs.php');

	rowRestrict();
	for ($i = 0; $i < $numlanes; $i++){
		if ($types[$i] == "MSSQL"){
			$addQ = "insert $lanes[$i].$dbs[$i].dbo.products
                                select * from products where upc='$upc'";
			$addR = $sql->query($addQ);
		}
		else {
			$sql->add_connection($lanes[$i],$types[$i],$dbs[$i],'root','is4c');
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
			$sql->transfer('WedgePOS',$selQ,$dbs[$i],$ins);
		}
	}
	rowRelease();
}

function deleteProductAllLanes($upc){
	global $sql,$FANNIE_ROOT;
	include($FANNIE_ROOT.'legacy/lanedefs.php');

	rowRestrict();
	for ($i = 0; $i < $numlanes; $i++){
		if ($types[$i] == "MSSQL"){
			$delQ = "delete from $lanes[$i].$dbs[$i].dbo.products where upc='$upc'";
			$delR = $sql->query($delQ);
		}
		else {
			$tmp = new SQLManager($lanes[$i],$types[$i],$dbs[$i],'root','is4c');
			$delQ = "DELETE FROM products WHERE upc='$upc'";
			$delR = $tmp->query($delQ);
		}
	}
	rowRelease();
}

function updateProductAllLanes($upc){
	deleteProductAllLanes($upc);
	addProductAllLanes($upc);
}

function rowRestrict(){
	global $sql;
	$restrictQ = "set rowcount 1";
	$restrictR = $sql->query($restrictQ);
}

function rowRelease(){
	global $sql;
	$releaseQ = "set rowcount 0";
	$releaseQ = $sql->query($releaseQ);
}

function syncProductsAllLanes(){
	global $sql,$FANNIE_ROOT,$FANNIE_SERVER_USER,$FANNIE_SERVER_PW;
	include($FANNIE_ROOT.'legacy/lanedefs.php');
	
	$mysqllanes = array();
	for($i=0;$i<$numlanes;$i++){
		if ($types[$i] == "MYSQL")
			array_push($mysqllanes,$lanes[$i]);
	}

	if (count($mysqllanes > 0)){
		$sql->query("exec master..xp_cmdshell 'dtsrun /S IS4CSERV\IS4CSERV /U {$FANNIE_SERVER_USER} /P {$FANNIE_SERVER_PW} /N CSV_products',no_output","WedgePOS");
		foreach ($mysqllanes as $lane){
			$sql->add_connection($lane,"MYSQL","opdata","root","is4c");

			if (!is_readable('/pos/csvs/products.csv')) break;

			$result = $sql->query("TRUNCATE TABLE Products","opdata");

			$sql->query("LOAD DATA LOCAL INFILE '/pos/csvs/products.csv' INTO TABLE
				products FIELDS TERMINATED BY ',' OPTIONALLY
				ENCLOSED BY '\"' LINES TERMINATED BY '\\r\\n'","opdata");

		}
	}
}

?>
