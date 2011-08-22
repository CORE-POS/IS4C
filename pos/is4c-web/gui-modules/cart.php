<?php
/*******************************************************************************

    Copyright 2007,2011 Whole Foods Co-op

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

if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

if (!class_exists('BasicPage')) include($IS4C_PATH.'gui-class-lib/BasicPage.php');
if (!function_exists('pDataConnect')) include($IS4C_PATH.'lib/connect.php');
if (!function_exists('getUID')) include($IS4C_PATH.'auth/login.php');
if (!function_exists('addUPC')) include($IS4C_PATH.'lib/additem.php');
if (!function_exists('SetExpressCheckout')) include($IS4C_PATH.'lib/paypal.php');

class cart extends BasicPage {

	function js_content(){
		?>
		$(document).ready(function(){
			$('#searchbox').focus();
		});
		<?php
	}

	function main_content(){
		global $IS4C_PATH,$IS4C_LOCAL;
		$db = tDataConnect();
		$empno = getUID(checkLogin());

		$q = "SELECT * FROM cart WHERE emp_no=$empno";
		$r = $db->query($q);
		
		echo '<form action="cart.php" method="post">';
		echo "<table id=\"carttable\" cellspacing='0' cellpadding='4' border='1'>";
		echo "<tr><th>&nbsp;</th><th>Item</th><th>Qty</th><th>Price</th>
			<th>Total</th><th>&nbsp;</th></tr>";
		$ttl = 0.0;
		while($w = $db->fetch_row($r)){
			printf('<tr>
				<td><input type="checkbox" name="selections[]" value="%s" /></td>
				<td>%s %s</td>
				<td><input type="hidden" name="upcs[]" value="%s" /><input type="text"
				size="4" name="qtys[]" value="%.2f" /><input type="hidden" name="scales[]"
				value="%d" /><input type="hidden" name="orig[]" value="%.2f" /></td>
				<td>$%.2f</td><td>$%.2f</td><td>%s</td></tr>',
				$w['upc'],
				$w['brand'],$w['description'],
				$w['upc'],$w['quantity'],$w['scale'],$w['quantity'],
				$w['unitPrice'],$w['total'],
				(empty($w['saleMsg'])?'&nbsp;':$w['saleMsg'])
			);
			$ttl += $w['total'];
		}
		printf('<tr><th colspan="4" align="right">Subtotal</th>
			<td>$%.2f</td><td>&nbsp;</td></tr>',$ttl);
		$taxQ = "SELECT taxes FROM taxTTL WHERE emp_no=$empno";
		$taxR = $db->query($taxQ);
		$taxes = 0;
		if ($db->num_rows($taxR) > 0)
			$taxes = round(array_pop($db->fetch_row($taxR)),2);
		printf('<tr><th colspan="4" align="right">Taxes</th>
			<td>$%.2f</td><td>&nbsp;</td></tr>',$taxes);
		printf('<tr><th colspan="4" align="right">Total</th>
			<td>$%.2f</td><td>&nbsp;</td></tr>',$taxes+$ttl);
		echo "</table><br />";
		echo '<input type="submit" name="delbtn" value="Delete Selected Items" />';
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		echo '<input type="submit" name="qtybtn" value="Update Quantities" />';
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		echo '<input type="submit" name="cobtn" value="Proceed to Checkout" />';
	}

	function preprocess(){
		global $IS4C_LOCAL;
		$db = tDataConnect();
		$empno = getUID(checkLogin());

		if (isset($_REQUEST['qtybtn'])){
			for($i=0; $i<count($_REQUEST['qtys']);$i++){
				if (!is_numeric($_REQUEST['qtys'][$i])) continue;
				if ($_REQUEST['qtys'][$i] == $_REQUEST['orig'][$i]) continue;

				$upc = $db->escape($_REQUEST['upcs'][$i]);
				$qty = round($_REQUEST['qtys'][$i]);
				if ($_REQUEST['scales'][$i] == 1)
					$qty = number_format(round($_REQUEST['qtys'][$i]*4)/4,2);
				if ($qty == $_REQUEST['orig'][$i]) continue;

				$q1 = sprintf("DELETE FROM localtemptrans WHERE
					upc='%s' AND emp_no=%d",$upc,$empno);
				$db->query($q1);
				if ($qty > 0)
					addUPC($upc,$qty);
			}
		}
		if (isset($_REQUEST['delbtn'])){
			if (isset($_REQUEST['selections'])){
				foreach($_REQUEST['selections'] as $upc){
					$upc = $db->escape($upc);
					$q1 = sprintf("DELETE FROM localtemptrans WHERE
						upc='%s' AND emp_no=%d",$upc,$empno);
					$db->query($q1);
				}
			}
		}
		if (isset($_REQUEST['cobtn'])){
			$dbc = tDataConnect();
			$email = checkLogin();
			$empno = getUID($email);
			$sub = $dbc->query("SELECT sum(total) FROM cart WHERE emp_no=".$empno);
			$sub = array_pop($dbc->fetch_row($sub));
			$tax = $dbc->query("SELECT taxes FROM taxTTL WHERE emp_no=$empno");
			$tax = array_pop($dbc->fetch_row($tax));
	
			return SetExpressCheckout(round($sub+$tax,2),
				round($tax,2),$email);
		}

		return True;
	}
}

new cart();

?>
