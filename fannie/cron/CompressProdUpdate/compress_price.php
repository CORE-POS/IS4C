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

/* HELP

   This script scans prodUpdate for instances
   where a product's price actually changed
   and logs those into prodPriceHistory.

   This is just faster to deal with as prodUpdate
   ends up having a ton of entries.
*/

if (!chdir("CompressProdUpdate")){
    echo "Error: Can't find directory (prod update compress price)";
    exit;
}

include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');

set_time_limit(0);
ini_set('memory_limit','256M');

$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
        $FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

$upc = null;
$prevPrice = null;

$q = "select u.upc,u.modified,price,user from prodUpdate
as u inner join products as p on p.upc=u.upc
order by u.upc,u.modified";
if ($FANNIE_SERVER_DBMS == "MSSQL")
    $q = str_replace("user","[user]",$q);
$r = $sql->query($q);
while($w = $sql->fetch_row($r)){
    if ($upc === null || $upc != $w['upc']){
        // next item, get previous
        // date and price from compressed
        // history if available
        $upc = $w['upc'];
        $prevPrice = null;
        $prevDate = null;
        $chkR = $sql->query("SELECT modified,price FROM
            prodPriceHistory WHERE upc='$upc'
            ORDER BY modified DESC");
        if ($sql->num_rows($chkR) > 0){
            $chk = $sql->fetch_row($chkR);
            $prevDate = $chk['modified'];
            $prevPrice = $chk['price'];
        }
    }
    
    if ($prevPrice != $w['price']){ // price changed
        $ins = sprintf("INSERT INTO prodPriceHistory
            (upc,modified,price,uid)
            VALUES (%s,%s,%.2f,%d)",
            $sql->escape($upc),
            $sql->escape($w['modified']),
            $w['price'],$w['user']);
        $sql->query($ins);
    }

    $prevPrice = $w['price'];
    $prevDate = $w['modified']; 
}


?>
