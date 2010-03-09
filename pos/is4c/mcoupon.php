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

function couponcode($upc) {

$man_id = substr($upc, 3, 5);
$fam = substr($upc, 8, 3);
$val = substr($upc, -2);



$db = pDataConnect();
$query = "select * from couponcodes where code = '".$val."'";
$result = sql_query($query, $db);
$num_rows = sql_num_rows($result);

if ($num_rows == 0) {

	boxMsg("coupon type unknown<br>please enter coupon<br>manually");
}
else {
	$row = sql_fetch_array($result);
	$value = $row["Value"];
	$qty = $row["Qty"];


	if ($fam == "992") {
		$value = truncate2($value);
		$_SESSION["couponupc"] = $upc;
		$_SESSION["couponamt"] = $value;

		maindisplay("coupondeptsearch.php");

	} else {
		sql_close($db);
		$fam = substr($fam, 0, 2);
		$query = "select "
                    ."max(unitPrice) as total, "
			  ."max(department) as department, "
			  ."sum(ItemQtty) as qty, "
			  ."sum(case when trans_status = 'C' then -1 else quantity end) as couponqtty "
			  ."from localtemptrans where substring(upc, 4, 5) = '".$man_id."' "
			  ."group by substring(upc, 4, 5)";

		$db = tDataConnect();
		$result = sql_query($query, $db);
		$num_rows = sql_num_rows($result);

		if ($num_rows > 0) {
			$row = sql_fetch_array($result);
			if ($row["couponqtty"] < 1) {
				boxMsg("Coupon already applied<BR>for this item");
			}
			else {

				$dept = $row["department"];
				$act_qty = $row["qty"];

				if ($qty <= $act_qty) {
					if ($value == 0) {
						$value = -1 * $row["total"];
					}
					$value = truncate2($value);


					addcoupon($upc, $dept, $value);
					lastpage();
				}
				else {
					boxMsg("coupon requires ".$qty."items<BR>there are only ".$act_qty." item(s)<BR>in this transaction");
				}
			}
		}
		else {

			boxMsg("product not found<BR>in transaction");
		}

		// sql_close($db);

	}
}
}
?>
