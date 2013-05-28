<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op
  
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
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

// Run DTS to export server data to a CSV file
$ms = new SQLManager('129.103.2.10','MSSQL','WedgePOS','sa',$FANNIE_SERVER_PW);
//$ms->query("exec master..xp_cmdshell 'dtsrun /S IS4CSERV\IS4CSERV /U sa /P $FANNIE_SERVER_PW /N CSV_products',no_output",'WedgePOS');

if (!is_readable('/pos/csvs/products.csv')) echo 'Problem exporting product table';
else {
	$dbc->add_connection($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
		$FANNIE_SERVER_USER,$FANNIE_SERVER_PW);
	$dbc->query("TRUNCATE TABLE products",$FANNIE_OP_DB);

	$dbc->query("LOAD DATA LOCAL INFILE '/pos/csvs/products.csv' INTO TABLE
		products FIELDS TERMINATED BY ',' OPTIONALLY
		ENCLOSED BY '\"' LINES TERMINATED BY '\\r\\n'",$FANNIE_OP_DB);
	echo "<li>Product table synched</li>";
}

?>
