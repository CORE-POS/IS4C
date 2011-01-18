<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!class_exists("SpecialUPC")) include($IS4C_PATH."lib/Scanning/SpecialUPC.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

if (!function_exists('mDataConnect')) include($IS4C_PATH."lib/connect.php");
if (!function_exists('boxMsg')) include($IS4C_PATH."lib/drawscreen.php");
if (!function_exists('addItem')) include($IS4C_PATH."lib/additem.php");
if (!function_exists('lastpage')) include($IS4C_PATH."lib/listitems.php");

// special order upc format:
// prefix orderID transID
// 00454  xxxxxx  xx
//
// e.g., orderID #1, transID #1:
// 0045400000101

class SpecialOrder extends SpecialUPC {

	function is_special($upc){
		if (substr($upc,0,5) == "00454")
			return true;

		return false;
	}

	function handle($upc,$json){
		global $IS4C_LOCAL,$IS4C_PATH;

		$orderID = substr($upc,5,6);
		$transID = substr($upc,11,2);

		if ((int)$transID === 0){
			$json['output'] = boxMsg("Not a valid order");
			return $json;
		}

		$db = mDataConnect();
		$query = sprintf("SELECT upc,description,department,
				quantity,unitPrice,total,regPrice,d.dept_tax,d.dept_fs,ItemQtty
				FROM PendingSpecialOrder as p LEFT JOIN
				departments AS d ON p.department=d.dept_no
				WHERE order_id=%d AND trans_id=%d",
				$orderID,$transID);
		$result = $db->query($query);

		if ($db->num_rows($result) != 1){
			$json['output'] = boxMsg("Order not found");
			return $json;
		}

		$row = $db->fetch_array($result);
		addItem($row['upc'],$row['description'],'I','','',$row['department'],$row['quantity'],
			$row['unitPrice'],$row['total'],$row['regPrice'],0,$row['dept_tax'],
			$row['dept_fs'],0.00,0.00,0,0,$row['ItemQtty'],0,0,0,'',0,0,0.00,0,'');
		$json['output'] = lastpage();

		return $json;
	}
}

?>
