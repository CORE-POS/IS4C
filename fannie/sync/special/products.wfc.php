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
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

// fire DTS packages to sync MSSQL lanes with server
//$dbc->query("exec productsUpdateAll");

// Run DTS to export server data to a CSV file
//$dbc->query("exec master..xp_cmdshell 'dtsrun /S IS4CSERV\IS4CSERV /U $FANNIE_SERVER_USER /P $FANNIE_SERVER_PW /N CSV_products',no_output",$FANNIE_OP_DB);

// on each MySQL lane, load the CSV file
foreach($FANNIE_LANES as $lane){

    if ($lane['type'] != 'MYSQL') continue;

    $dbc->add_connection($lane['host'],$lane['type'],$lane['op'],
            $lane['user'],$lane['pw']);
    if ($dbc->connections[$lane['op']] !== False){

        if (!is_readable('/pos/csvs/products.csv')) break;
        
        $dbc->query("TRUNCATE TABLE products",$lane['op']);

        $dbc->query("LOAD DATA LOCAL INFILE '/pos/csvs/products.csv' INTO TABLE
            products FIELDS TERMINATED BY ',' OPTIONALLY
            ENCLOSED BY '\"' LINES TERMINATED BY '\\r\\n'",$lane['op']);
    }
}

echo "<li>Product table synched</li>";

?>
