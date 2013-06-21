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
if (!function_exists('customer_confirmation')) include($IS4C_PATH.'lib/notices.php');
if (!function_exists('GetExpressCheckoutDetails')) include($IS4C_PATH.'lib/paypal.php');

class confirm extends BasicPage {

	var $mode;
	var $msgs;

	function js_content(){
		?>
		$(document).ready(function(){
			$('#searchbox').focus();
		});
		<?php
	}

	function main_content(){
		if ($this->mode == 0)
			$this->confirm_content(False);
		else
			$this->confirm_content(True);
	}

	function confirm_content($receiptMode=False){
		global $IS4C_PATH,$IS4C_LOCAL;
		$db = tDataConnect();
		$empno = getUID(checkLogin());

		$q = "SELECT * FROM cart WHERE emp_no=$empno";
		$r = $db->query($q);
		
		if (!$receiptMode){
			echo '<form action="confirm.php" method="post">';
		}
		else {
			echo '<blockquote>Your order has been processed</blockquote>';
		}
		if (!empty($this->msgs)){
			echo '<blockquote>'.$this->msgs.'</blockquote>';
		}
		echo "<table id=\"carttable\" cellspacing='0' cellpadding='4' border='1'>";
		echo "<tr><th>Item</th><th>Qty</th><th>Price</th>
			<th>Total</th><th>&nbsp;</th></tr>";
		$ttl = 0.0;
		while($w = $db->fetch_row($r)){
			printf('<tr>
				<td>%s %s</td>
				<td><input type="hidden" name="upcs[]" value="%s" />%.2f
				<input type="hidden" name="scales[]"
				value="%d" /><input type="hidden" name="orig[]" value="%.2f" /></td>
				<td>$%.2f</td><td>$%.2f</td><td>%s</td></tr>',
				$w['brand'],$w['description'],
				$w['upc'],$w['quantity'],$w['scale'],$w['quantity'],
				$w['unitPrice'],$w['total'],
				(empty($w['saleMsg'])?'&nbsp;':$w['saleMsg'])
			);
			$ttl += $w['total'];
		}
		printf('<tr><th colspan="3" align="right">Subtotal</th>
			<td>$%.2f</td><td>&nbsp;</td></tr>',$ttl);
		$taxQ = "SELECT taxes FROM taxTTL WHERE emp_no=$empno";
		$taxR = $db->query($taxQ);
		$taxes = round(array_pop($db->fetch_row($taxR)),2);
		printf('<tr><th colspan="3" align="right">Taxes</th>
			<td>$%.2f</td><td>&nbsp;</td></tr>',$taxes);
		printf('<tr><th colspan="3" align="right">Total</th>
			<td>$%.2f</td><td>&nbsp;</td></tr>',$taxes+$ttl);
		echo "</table><br />";
		if (!$receiptMode){
			printf('<input type="hidden" name="token" value="%s" />',$_REQUEST['token']);
			echo '<b>Phone Number (incl. area code)</b>: ';
			echo '<input type="text" name="ph_contact" /> (<span style="color:red;">Required</span>)<br />';
			echo '<blockquote>We require a phone number because some email providers
				have trouble handling .coop email addresses. A phone number ensures
				we can reach you if there are any questions about your order.</blockquote>';
			echo '<b>Additional attendees</b>: ';
			echo '<input type="text" name="attendees" /><br />';
			echo '<blockquote>If you are purchasing a ticket for someone else, please
				enter their name(s) so we know to put them on the list.</blockquote>';
			echo '<input type="submit" name="confbtn" value="Finalize Order" />';
			echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			echo '<input type="submit" name="backbtn" value="Go Back" />';
		}
		else {
			/* refactor idea: clear in preprocess()
			   and print receipt from a different script
			*/
			$endQ = "INSERT INTO localtrans SELECT l.* FROM
				localtemptrans AS l WHERE emp_no=$empno";
			$endR = $db->query($endQ);
			$endQ = "INSERT INTO pendingtrans SELECT l.* FROM
				localtemptrans AS l WHERE emp_no=$empno";
			$endR = $db->query($endQ);
			if ($endR !== False){
				$clearQ = "DELETE FROM localtemptrans WHERE emp_no=$empno";
				$db->query($clearQ);
			}
		}
	}

	function preprocess(){
		global $IS4C_LOCAL;
		$this->mode = 0;
		$this->msgs = "";

		if (isset($_REQUEST['backbtn'])){
			header("Location: cart.php");
			return False;
		}
		else if (isset($_REQUEST['confbtn'])){
			/* confirm payment with paypal
			   if it succeeds, add tax and tender
			   shuffle order to pendingtrans table
			   send order notifications
			*/
			$ph = $_REQUEST['ph_contact'];
			$ph = preg_replace("/[^\d]/","",$ph);
			if (strlen($ph) != 10){
				$this->msgs = 'Phone number with area code is required';
				return True;
			}
			$attend = isset($_REQUEST['attendees']) ? $_REQUEST['attendees'] : '';
			if (isset($_REQUEST['token'])){
				$pp1 = GetExpressCheckoutDetails($_REQUEST['token']);

				$pp2 = DoExpressCheckoutPayment($pp1['TOKEN'],
					$pp1['PAYERID'],
					$pp1['PAYMENTREQUEST_0_AMT']);

				if ($pp2['ACK'] == 'Success') {
					$this->mode=1;

					/* get tax from db and add */
					$db = tDataConnect();
					$email = checkLogin();
					$empno = getUID($email);
					$taxQ = "SELECT taxes FROM taxTTL WHERE emp_no=$empno";
					$taxR = $db->query($taxQ);
					$taxes = round(array_pop($db->fetch_row($taxR)),2);
					addtax($taxes);
					
					/* add paypal tender */
					addtender("Paypal","PP",-1*$pp1['PAYMENTREQUEST_0_AMT']);

					/* send notices */
					$cart = customer_confirmation($empno,$email,$pp1['PAYMENTREQUEST_0_AMT']);
					admin_notification($empno,$email,$ph,$pp1['PAYMENTREQUEST_0_AMT'],$cart);

					$addrQ = sprintf("SELECT e.email_address FROM localtemptrans
						as l INNER JOIN superdepts AS s ON l.department=s.dept_ID
						INNER JOIN superDeptEmails AS e ON s.superID=e.superID
						WHERE l.emp_no=%d GROUP BY e.email_address",$empno);
					$addrR = $db->query($addrQ);
					$addr = array();
					while($addrW = $db->fetch_row($addrR))
						$addr[] = $addrW[0];
					if (count($addr) > 0)
						mgr_notification($addr,$email,$ph,$pp1['PAYMENTREQUEST_0_AMT'],$attend,$cart);
				}
			}
		}

		return True;
	}
}

new confirm();

?>
