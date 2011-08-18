<?php
/*******************************************************************************

    Copyright 2007,2010 Whole Foods Co-op

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

class itemPage extends BasicPage {

	function js_content(){
		?>
		function addItem(upc){
			$.ajax({
				url: '../ajax-callbacks/ajax-add-item.php',
				type: 'post',
				data: 'upc='+upc,
				success: function(resp){
					$('#btn'+upc).html('<a href="cart.php">In Cart</a>');
				}
			});
		}
		<?php
	}

	function main_content(){
		global $IS4C_PATH;
		$upc = $_REQUEST['upc'];
		$upc = str_pad($upc,13,'0',STR_PAD_LEFT);

		$empno = getUID(checkLogin());
                if ($empno===False) $empno=-999;

		$dbc = pDataConnect();
		$q = sprintf("SELECT p.upc,p.normal_price,p.special_price,
			p.discounttype,u.description,u.brand,u.long_text
			FROM products AS p INNER JOIN productUser AS u
			ON p.upc=u.upc WHERE p.upc='%s'",
			$dbc->escape($upc));
		$r = $dbc->query($q);

		if ($dbc->num_rows($r)==0){
			echo "Item not found";
			return;
		}

		$w = $dbc->fetch_row($r);
		
		echo '<div class="itemBox">';

		echo '<div class="itemMain">';
		echo '<span class="itemDesc">'.$w['description'].'</span><br />';
		echo '<span class="itemBrand">by '.$w['brand'].'</span>';
		echo '<p />';
		echo $w['long_text'];
		echo '</div>';

		echo '<div class="itemPrice">';
		echo '<span class="itemPriceNormal">';
		printf('$%.2f',($w['discounttype']==1?$w['special_price']:$w['normal_price']));
		echo '</span><br />';
		echo '<span class="itemPriceAddOn">';
		if ($w['discounttype']==1) echo 'On Sale!';
		else if ($w['discounttype']==2)
			printf('Owner price: $%.2f',$w['special_price']);
		echo '</span>';
		echo '<br /><br />';
		if ($empno == -999){
			echo '<a href="loginPage.php">Login</a> or ';
			echo '<a href="createAccount.php">Create an Account</a> ';
			echo 'to add items to your cart.';
		}
		else {
			$chkQ = sprintf("SELECT upc FROM localtemptrans WHERE
				upc='%s' AND emp_no=%d",$dbc->escape($w['upc']),$empno);
			$chkR = $dbc->query($chkQ);
			if ($dbc->num_rows($chkR) == 0){
				printf('<span id="btn%s">
					<input type="submit" value="Add to cart" onclick="addItem(\'%s\');" />
					</span>',
					$w['upc'],$w['upc']);
			}
			else {
				printf('<span id="btn%s">
					<a href="cart.php">In Cart</a>
					</span>',$w['upc']);
			}
		}
		echo '</div>';

		echo  '</div>'; // end itemBox

		echo '<div class="itemCart">';

		echo '</div>';
	}

	function preprocess(){
		global $IS4C_PATH;
		if (!isset($_REQUEST['upc'])){
			header("Location: {$IS4C_PATH}gui-modules/storefront.php");
			return False;
		}
		return True;
	}
}

new itemPage();

?>
