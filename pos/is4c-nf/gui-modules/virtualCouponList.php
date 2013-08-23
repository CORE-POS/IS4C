<?php
/*******************************************************************************

   Copyright 2001, 2004 Wedge Community Co-op

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
if (!class_exists("MainFramePage")) include_once($_SESSION["INCLUDE_PATH"]."/gui-class-lib/MainFramePage.php");
if (!function_exists("tDataConnect")) include($_SESSION["INCLUDE_PATH"]."/lib/connect.php");
if (!function_exists("setMember")) include($_SESSION["INCLUDE_PATH"]."/lib/prehkeys.php");
if (!function_exists("changeBothPages")) include($_SESSION["INCLUDE_PATH"]."/gui-base.php");
if (!isset($CORE_LOCAL)) include($_SESSION["INCLUDE_PATH"]."/lib/LocalStorage/conf.php");

class virtualCouponList extends MainFramePage {

	var $temp_result;
	var $entered;
	var $db;

	function preprocess(){
		global $CORE_LOCAL;

		if (isset($_POST["selectlist"])){
			$id = $_POST["selectlist"];

			if ($id != "CL")
				addVirtualCoupon($id);

			changeBothPages("/gui-modules/input.php","/gui-modules/pos2.php");
			return False;
		}

		$sql = pDataConnect();
		$cardno = $CORE_LOCAL->get("memberID");
		$discountQ = "select flag,name from virtualcoupon as v
				left join custdata as c 
				on c.memcoupons & v.flag <> 0
				left join
				(select cast(upc as unsigned) as upc,sum(quantity) as quantity
				from translog.localtemptrans where trans_type='I' and 
				trans_subtype='CP' group by upc) as l
				on v.flag=l.upc
				where c.cardno=$cardno and c.personnum=1
				and (l.upc is null or l.quantity=0)";
		$discountR = $sql->query($discountQ);
		$this->temp_result = $discountR;
		$this->db = $sql;

		if ($sql->num_rows($discountR) == 0){
			$CORE_LOCAL->set("boxMsg","No virtual coupons available");
			changeBothPages("/gui-modules/input.php","/gui-modules/boxMsg2.php");
			return False;

		}

		return True;
	} // END preprocess() FUNCTION

	function body_tag() {
		echo "<body onLoad='document.selectform.selectlist.focus()'>";
	}

	function head(){
		global $CORE_LOCAL;
		// Javascript is only needed if there are results
		?>
		<script type="text/javascript" >
		var prevKey = -1;
		var prevPrevKey = -1;
		function processkeypress(e) {
			var jsKey;
			if(!e)e = window.event;
			if (e.keyCode) // IE
				jsKey = e.keyCode;
			else if(e.which) // Netscape/Firefox/Opera
				jsKey = e.which;
			if (jsKey==13) {
				if ( (prevPrevKey == 99 || prevPrevKey == 67) &&
				(prevKey == 108 || prevKey == 76) ){ //CL<enter>
					document.selectform.selectlist[0].value = 'CL';
					document.selectform.selectlist.selectedIndex = 0;
				}
				document.selectform.submit();
			}
			prevPrevKey = prevKey;
			prevKey = jsKey;
		}
		</script> 
		<?php
	} // END head() FUNCTION

	function body_content(){
		global $CORE_LOCAL;
		$result = $this->temp_result;
		$db = $this->db;

		echo "<div class=\"baseHeight\">"
			."<div class=\"listbox\">"
			."<form name='selectform' method='post' action='".$_SERVER["PHP_SELF"]."'>"
			."<select onkeypress='processkeypress(event);' name='selectlist' size='15' "
			."onBlur='document.selectform.selectlist.focus()'>";

		$selectFlag = 0;
		for ($i = 0; $i < $db->num_rows($result); $i++) {
			$row = $db->fetch_array($result);
			if( $i == 0 && $selectFlag == 0) {
				$selected = "selected";
			} else {
				$selected = "";
			}
			echo "<option value='".$row["flag"]."' ".$selected.">"
				.$row["name"]."</option>";
		}
		echo "</select></form></div>"
			."<div class=\"centerOffset listboxText coloredText\">"
			."use arrow keys to navigate<p>[esc] to cancel</div>"
			."<div class=\"clear\"></div>";
		echo "</div>";
	} // END body_content() FUNCTION
}

new virtualCouponList();

?>
