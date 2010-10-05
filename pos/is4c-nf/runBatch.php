<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

    This file is part of IS4C.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
// session_start(); 

if (!function_exists("changeBothPages")) include($_SESSION["INCLUDE_PATH"]."/gui-base.php");
if (!function_exists("mDataConnect")) include($_SESSION["INCLUDE_PATH"]."/lib/connect.php");
if (!class_exists("UPC")) include($_SESSION["INCLUDE_PATH"]."/parser-class-lib/UPC.php");
if (!isset($IS4C_LOCAL)) include($_SESSION["INCLUDE_PATH"]."/lib/LocalStorage/conf.php");

$batchID = "";
if (isset($_POST["selectlist"])) 
	$batchID = $_POST["selectlist"];
if ($batchID == ""){
	changeBothPages("/gui-modules/input.php","/gui-modules/pos2.php");
	return;
}

$db = mDataConnect();
$query = "select case when p.upc is null then u.upc else p.upc end as upc
	from batchlist as b left join products as p on b.upc=p.upc
	left join upclike as u on 'LC'+convert(varchar,u.likecode) = b.upc
	where b.batchID=$batchID and (u.upc is not null or p.upc is not null)";
$result = $db->query($query);
$upc = new UPC();
while ($row = $db->fetch_array($result)){
	$IS4C_LOCAL->set("quantity",1);
	$IS4C_LOCAL->set("scale",1.0);
	$IS4C_LOCAL->set("isMember",1);
	$upc->upcscanned($row["upc"]);
}
$IS4C_LOCAL->set("End",1);
changeBothPages("/gui-modules/input.php","/gui-modules/pos2.php");

?>
