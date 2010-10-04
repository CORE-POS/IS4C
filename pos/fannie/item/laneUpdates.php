<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

if (!isset($FANNIE_ROOT))
	require('../config.php');
if (!class_exists('SQLManager'))
	require($FANNIE_ROOT.'src/SQLManager.php');

$laneupdate_sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
				$FANNIE_SERVER_USER, $FANNIE_SERVER_PW);

function addProductAllLanes($upc){
	global $laneupdate_sql, $FANNIE_LANES, $FANNIE_OP_DB, $FANNIE_SERVER_DBMS;

	for ($i = 0; $i < count($FANNIE_LANES); $i++){
		$laneupdate_sql->add_connection($FANNIE_LANES[$i]['host'],$FANNIE_LANES[$i]['type'],
			$FANNIE_LANES[$i]['op'],$FANNIE_LANES[$i]['user'],
			$FANNIE_LANES[$i]['pw']);
		$selQ = "SELECT upc,description,normal_price,pricemethod,groupprice,quantity,
			special_price,specialpricemethod,specialgroupprice,specialquantity,start_date,end_date,
			department,size,tax,foodstamp,scale,mixmatchcode,modified,advertised,tareweight,discount,
			discounttype,unitofmeasure,wicable,qttyEnforced,inUse,scaleprice,idEnforced,cost,
			numflag,subdept,deposit,local,id FROM products WHERE upc='$upc'";	
		$ins = "INSERT INTO products (upc,description,normal_price,pricemethod,groupprice,quantity,
			special_price,specialpricemethod,specialgroupprice,specialquantity,start_date,end_date,
			department,size,tax,foodstamp,scale,mixmatchcode,modified,advertised,tareweight,discount,
			discounttype,unitofmeasure,wicable,qttyEnforced,inUse,scaleprice,idEnforced,cost,
			numflag,subdept,deposit,local,id)";
		$laneupdate_sql->transfer($FANNIE_OP_DB,$selQ,$FANNIE_LANES[$i]['op'],$ins);
	}
}

function deleteProductAllLanes($upc){
	global $laneupdate_sql, $FANNIE_OP_DB, $FANNIE_LANES;

	for ($i = 0; $i < count($FANNIE_LANES); $i++){
		$tmp = new SQLManager($FANNIE_LANES[$i]['host'],$FANNIE_LANES[$i]['type'],
			$FANNIE_LANES[$i]['op'],$FANNIE_LANES[$i]['user'],
			$FANNIE_LANES[$i]['pw']);
		$delQ = "DELETE FROM products WHERE upc='$upc'";
		$delR = $tmp->query($delQ,$FANNIE_LANES[$i]['op']);
	}
}

function updateProductAllLanes($upc){
	deleteProductAllLanes($upc);
	addProductAllLanes($upc);
}

?>
