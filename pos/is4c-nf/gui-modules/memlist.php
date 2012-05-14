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

ini_set('display_errors','1');

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class memlist extends NoInputPage {

	var $temp_result;
	var $temp_num_rows;
	var $entered;
	var $db;

	function preprocess(){
		global $CORE_LOCAL;
		$CORE_LOCAL->set("away",1);
		$entered = "";
		if ($CORE_LOCAL->get("idSearch") && strlen($CORE_LOCAL->get("idSearch")) > 0) {
			$entered = $CORE_LOCAL->get("idSearch");
			$CORE_LOCAL->set("idSearch","");
		}
		elseif (isset($_REQUEST['search'])){
			$entered = strtoupper(trim($_REQUEST["search"]));
			$entered = str_replace("'", "''", $entered);
		}
		else return True;

		if (substr($entered, -2) == "ID") $entered = substr($entered, 0, strlen($entered) - 2);

		$personNum = 1;
		$selected_name = False;
		if (strstr($entered,"::") !== False){
			$tmp = explode("::",$entered);
			$entered = $tmp[0];
			$personNum = $tmp[1];
			$selected_name = True;
		}

		// No input available, stop
		if (!$entered || strlen($entered) < 1 || $entered == "CL") {
			$CORE_LOCAL->set("mirequested",0);
			$CORE_LOCAL->set("scan","scan");
			$CORE_LOCAL->set("reprintNameLookup",0);
			$this->change_page($this->page_url."gui-modules/pos2.php");
			return False;
		}

		$memberID = $entered;
		$db_a = Database::pDataConnect();

		$query = "select CardNo,personNum,LastName,FirstName,CashBack,Balance,Discount,
			MemDiscountLimit,ChargeOk,WriteChecks,StoreCoupons,Type,memType,staff,
			SSI,Purchases,NumberOfChecks,memCoupons,blueLine,Shown,id from custdata 
			where CardNo = '".$entered."' order by personNum";
		if (!is_numeric($entered)) {
			$query = "select CardNo,personNum,LastName,FirstName from custdata 
				where LastName like '".$entered."%' order by LastName, FirstName";
		}

		$result = $db_a->query($query);
		$num_rows = $db_a->num_rows($result);

		// if there's on result and either
		// a. it's the default nonmember account or
		// b. it's been confirmed in the select box
		// then set the member number
		if (($num_rows == 1 && $entered == $CORE_LOCAL->get("defaultNonMem"))
			||
		    (is_numeric($entered) && is_numeric($personNum) && $selected_name) ){
			$row = $db_a->fetch_array($result);
			PrehLib::setMember($row["CardNo"], $personNum,$row);
			$CORE_LOCAL->set("scan","scan");
			if ($entered != $CORE_LOCAL->get("defaultNonMem") && check_unpaid_ar($row["CardNo"]))
				$this->change_page($this->page_url."gui-modules/UnpaidAR.php");
			else
				$this->change_page($this->page_url."gui-modules/pos2.php");
			return False;
		}

		$this->temp_result = $result;
		$this->temp_num_rows = $num_rows;
		$this->entered = $entered;
		$this->db = $db_a;
		return True;
	} // END preprocess() FUNCTION

	function head_content(){
		global $CORE_LOCAL;
		$this->add_onload_command("\$('#search').focus();\n");
		if ($this->temp_num_rows > 0)
			$this->add_onload_command("\$('#search').keypress(processkeypress);\n");
		?>
		<script type="text/javascript">
		var prevKey = -1;
		var prevPrevKey = -1;
		function processkeypress(e) {
			var jsKey;
			if (e.keyCode) // IE
				jsKey = e.keyCode;
			else if(e.which) // Netscape/Firefox/Opera
				jsKey = e.which;
			if (jsKey==13) {
				if ( (prevPrevKey == 99 || prevPrevKey == 67) &&
				(prevKey == 108 || prevKey == 76) ){ //CL<enter>
					$('#search option:selected').each(function(){
						$(this).val('');
					});
				}
				$('#selectform').submit();
			}
			prevPrevKey = prevKey;
			prevKey = jsKey;
		}
		</script> 
		<?php
	} // END head() FUNCTION

	function body_content(){
		global $CORE_LOCAL;
		$num_rows = $this->temp_num_rows;
		$result = $this->temp_result;
		$entered = $this->entered;
		$db = $this->db;

		echo "<div class=\"baseHeight\">"
			."<form id=\"selectform\" method=\"post\" action=\"{$_SERVER['PHP_SELF']}\">";

		/* for no results, just throw up a re-do
		 * otherwise, put results in a select box
		 */
		if ($num_rows < 1){
			echo "
			<div class=\"colored centeredDisplay\">
				<span class=\"larger\">
				no match found<br />next search or member number
				</span>
				<input type=\"text\" name=\"search\" size=\"15\"
			       	onblur=\"\$('#search').focus();\" id=\"search\" />
				<br />
				press [enter] to cancel
			</div>";
		}
		else {
			echo "<div class=\"listbox\">"
				."<select name=\"search\" size=\"15\" "
				."onblur=\"\$('#search').focus()\" id=\"search\">";

			$selectFlag = 0;
			if (!is_numeric($entered) && $CORE_LOCAL->get("memlistNonMember") == 1) {
				echo "<option value='3::1' selected> 3 "
					."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Customer";
				$selectFlag = 1;
			}

			for ($i = 0; $i < $num_rows; $i++) {
				$row = $db->fetch_array($result);
				if( $i == 0 && $selectFlag == 0) {
					$selected = "selected";
				} else {
					$selected = "";
				}
				echo "<option value='".$row["CardNo"]."::".$row["personNum"]."' ".$selected.">"
					.$row["CardNo"]." ".$row["LastName"].", ".$row["FirstName"]."\n";
			}
			echo "</select></div>"
				."<div class=\"listboxText centerOffset\">"
				."use arrow keys to navigate<p>[clear] to cancel</div>"
				."<div class=\"clear\"></div>";
		}
		echo "</form></div>";
	} // END body_content() FUNCTION
}

new memlist();

?>
