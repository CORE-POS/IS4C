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

class storefront extends BasicPage {

	function js_content(){
		?>
		$(document).ready(function(){
			$('#searchbox').focus();
		});
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
		echo '<div id="sidebar">';
		echo $this->sidebar();
		echo '</div>';
		echo '<div id="browsearea">';
		echo $this->itemlist();
		echo '</div>';
		echo '<div style="clear:both;"></div>';
	}

	function itemlist(){
		global $IS4C_LOCAL;
		$super = isset($_REQUEST['sup'])?$_REQUEST['sup']:-1;
		$sub = isset($_REQUEST['sub'])?$_REQUEST['sub']:-1;
		$d = isset($_REQUEST['d'])?$_REQUEST['d']:-1;
		$brand = isset($_REQUEST['bid'])?base64_decode($_REQUEST['bid']):-1;

		$limit = 50;
		$page = isset($_REQUEST['pg'])?((int)$_REQUEST['pg']):0;
		$offset = $page*$limit;

		$sort = "u.brand,u.description";

		$dbc = pDataConnect();
		$empno = getUID(checkLogin());
		if ($empno===False) $empno=-999;
	
		$q = "SELECT p.upc,p.normal_price,
			CASE WHEN p.discounttype IN (1) then p.special_price
				ELSE 0
				END as sale_price,
			u.description,u.brand,
			CASE WHEN l.upc IS NULL THEN 0 ELSE 1 END AS inCart
			FROM products AS p INNER JOIN productUser
			AS u ON p.upc=u.upc LEFT JOIN ".$IS4C_LOCAL->get("tDatabase").".localtemptrans
			AS l ON p.upc=l.upc AND l.emp_no=$empno 
			LEFT JOIN productOrderLimits AS o ON p.upc=o.upc ";
		if ($super != -1)
			$q .= "INNER JOIN superdepts AS s ON p.department=s.dept_ID ";
		if ($sub != -1)
			$q .= "INNER JOIN subdepts AS b ON p.department=b.dept_ID ";
		$q .= "WHERE p.inUse=1 AND u.enableOnline=1 AND (o.available IS NULL or o.available > 0) ";
		if ($super != -1)
			$q .= sprintf("AND s.superID=%d ",$super);
		if ($d != -1)
			$q .= sprintf("AND p.department=%d ",$d);
		if ($sub != -1)
			$q .= sprintf("AND b.subdept_no=%d ",$sub);
		if ($brand != -1)
			$q .= sprintf("AND u.brand='%s' ",$dbc->escape($brand));
		$q .= "ORDER BY $sort LIMIT $offset,$limit";
		
		$ret = '<table cellspacing="4" cellpadding="4" id="browsetable">';
		$ret .= '<tr><th>Brand</th><th>Product</th><th>Price</th><th>&nbsp;</th></tr>';
		$r = $dbc->query($q);
		while($w = $dbc->fetch_row($r)){
			$ret .= sprintf('<tr><td>%s</td>
					<td><a href="item.php?upc=%s">%s</a></td>
					<td>$%.2f</td>
					<td>%s</td>',
					$w['brand'],
					$w['upc'],$w['description'],
					($w['sale_price']==0?$w['normal_price']:$w['sale_price']),
					($w['sale_price']==0?'&nbsp;':'ON SALE!')
			);
			if ($w['inCart'] == 0 && $empno != -999){
					$ret .= sprintf('<td id="btn%s">
						<input type="submit" value="Add to cart" onclick="addItem(\'%s\');" />
						</td></tr>',
						$w['upc'],$w['upc']);
			}
			else if ($empno != -999){
					$ret .= sprintf('<td id="btn%s">
						<a href="cart.php">In Cart</a>
						</td></tr>',
						$w['upc']);
			}
			else $ret .= '<td></td></tr>';
		}
		$ret .= '</table>';
		return $ret;
	}

	function sidebar(){
		$super = isset($_REQUEST['sup'])?$_REQUEST['sup']:-1;
		$sub = isset($_REQUEST['sub'])?$_REQUEST['sub']:-1;
		$d = isset($_REQUEST['d'])?$_REQUEST['d']:-1;

		$ret = '<ul id="superList">';
		$dbc = pDataConnect();
		$r = $dbc->query("SELECT superID,super_name FROM
			superDeptNames ORDER BY super_name");
		$sids = array();
		while($w = $dbc->fetch_row($r)){
			$sids[$w['superID']] = $w['super_name'];
		}
		if (count($sids)==1)
			$super = array_pop(array_keys($sids));

		if ($sub != -1 && $d != -1 && $super != -1){
			// browsing subdept

		}
		else if ($d != -1 && $super != -1){
			// browsing dept
			$q = sprintf("SELECT subdept_no,subdept_name FROM subdepts
				WHERE dept_ID=%d",$d);
			$r = $dbc->query($q);
			$subs = True;
			if ($dbc->num_rows($r) == 0){
				// no subdepts; skip straight to brands
				$subs = False;
				$q = sprintf("SELECT u.brand FROM products AS p
					INNER JOIN productUser AS u ON p.upc=u.upc
					WHERE p.department=%d AND u.brand <> ''
					AND u.brand IS NOT NULL GROUP BY u.brand",$d);
				$r = $dbc->query($q);
			}

			foreach($sids as $id=>$name){
				$ret .= sprintf('<li><a href="%s?sup=%d">%s</a>',
					$_SERVER['PHP_SELF'],$id,$name);
				if ($id == $super){
					$ret .= '<ul id="deptlist">';
					$dR = $dbc->query(sprintf("SELECT dept_no,dept_name FROM departments
						as d INNER JOIN superdepts as s ON d.dept_no=s.dept_ID
						WHERE s.superID=%d",$super));
					while($w = $dbc->fetch_row($dR)){
						$ret .= sprintf('<li><a href="%s?sup=%d&d=%d">%s</a>',
							$_SERVER['PHP_SELF'],$id,$w['dept_no'],$w['dept_name']);
						if ($w['dept_no'] == $d){
							$ret .= '<ul id="sidebar3">';
							while($w = $dbc->fetch_row($r)){
								$ret .= sprintf('<li><a href="%s?sup=%d&d=%d',
									$_SERVER['PHP_SELF'],$id,$d);
								if ($subs){
									$ret .= sprintf('&sub=%d">%s</a></li>',
										$w['subdept_no'],$w['subdept_name']);
								}
								else {
									$ret .= sprintf('&bid=%s">%s</a></li>',
										base64_encode($w['brand']),
										$w['brand']);
								}
							}
							$ret .= '</ul>';
						}
						$ret .= '</li>';
					}
					$ret .= '</ul>';
				}
			}
			
		}
		else if ($super != -1){
			// browsing super
			foreach($sids as $id=>$name){
				$ret .= sprintf('<li><a href="%s?sup=%d">%s</a>',
					$_SERVER['PHP_SELF'],$id,$name);
				if ($id == $super){
					$ret .= '<ul id="deptlist">';
					$r = $dbc->query(sprintf("SELECT dept_no,dept_name FROM departments
						as d INNER JOIN superdepts as s ON d.dept_no=s.dept_ID
						WHERE s.superID=%d",$super));
					while($w = $dbc->fetch_row($r)){
						$ret .= sprintf('<li><a href="%s?sup=%d&d=%d">%s</a></li>',
							$_SERVER['PHP_SELF'],$id,$w['dept_no'],$w['dept_name']);
					}
					$ret .= '</ul>';
				}
				$ret .= '</li>';
			}
		}
		else {
			// top level browsing
			foreach($sids as $id=>$name){
				$ret .= sprintf('<li><a href="%s?sup=%d">%s</a></li>',
					$_SERVER['PHP_SELF'],$id,$name);
			}
		}

		$ret .= '</ul>';

		return $ret;
	}

	function preprocess(){
		global $IS4C_PATH;
		if (isset($_REQUEST['email'])){
			if (!isEmail($_REQUEST['email'])){
				echo '<div class="errorMsg">';
				echo 'Not a valid e-mail address: '.$_REQUEST['email'];
				echo '</div>';
				return True;
			}
			else if (!login($_REQUEST['email'],$_REQUEST['passwd'])){
				echo '<div class="errorMsg">';
				echo 'Incorrect e-mail address or password';
				echo '</div>';
				return True;
			}
			else {
				header("Location: {$IS4C_PATH}gui-modules/storefront.php");
				return False;
			}
		}
		return True;
	}
}

new storefront();

?>
