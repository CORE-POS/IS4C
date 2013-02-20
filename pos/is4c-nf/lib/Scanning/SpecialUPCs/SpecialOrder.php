<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/**
   @class SpecialOrder
   WFC Electronic Special Orders

   Special order upc format:
   prefix orderID transID
   00454  xxxxxx  xx
  
   e.g., orderID #1, transID #1:
   0045400000101

   These IDs are used to locate the
   special order record in the 
   PendingSpecialOrder table on
   the server database
*/

class SpecialOrder extends SpecialUPC {

	function is_special($upc){
		if (substr($upc,0,5) == "00454")
			return true;

		return false;
	}

	function handle($upc,$json){
		global $CORE_LOCAL;

		$orderID = substr($upc,5,6);
		$transID = substr($upc,11,2);

		if ((int)$transID === 0){
			$json['output'] = DisplayLib::boxMsg(_("Not a valid order"));
			return $json;
		}

		$db = Database::mDataConnect();
		$query = sprintf("SELECT upc,description,department,
				quantity,unitPrice,total,regPrice,d.dept_tax,d.dept_fs,ItemQtty
				FROM PendingSpecialOrder as p LEFT JOIN
				departments AS d ON p.department=d.dept_no
				WHERE order_id=%d AND trans_id=%d",
				$orderID,$transID);
		$result = $db->query($query);

		if ($db->num_rows($result) != 1){
			$json['output'] = DisplayLib::boxMsg(_("Order not found"));
			return $json;
		}

		$row = $db->fetch_array($result);
		TransRecord::addItem($row['upc'],$row['description'],'I','','',$row['department'],$row['quantity'],
			$row['unitPrice'],$row['total'],$row['regPrice'],0,$row['dept_tax'],
			$row['dept_fs'],0.00,0.00,0,0,$row['ItemQtty'],0,0,0,$orderID,$transID,0,0.00,0,'SO');
		$json['output'] = DisplayLib::lastpage();
		$json['udpmsg'] = 'goodBeep';
		$json['redraw_footer'] = True;

		return $json;
	}
}

?>
