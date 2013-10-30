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

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	*  5Oct2012 Eric Lee Added:
	*                    + A WEFC_Toronto-only chunk for collecting Member Card#
	*                    + A general facility for displaying an error encountered in preprocess()
	*                       in body_content() using temp_message.

*/

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class memlist extends NoInputPage {

	var $temp_result;
	var $temp_num_rows;
	var $entered;
	var $db;
	var $temp_message;

	private $results = array();
	private $submitted = False;

	function preprocess(){
		global $CORE_LOCAL;

		// set variable ahead of time
		// so we know if lookup found no one
		// vs. lookup didn't happen
		$this->submitted = False;

		$entered = "";
		if (isset($_REQUEST['idSearch']) && strlen($_REQUEST['idSearch']) > 0){
			$entered = $_REQUEST['idSearch'];
		}
		elseif (isset($_REQUEST['search'])){
			$entered = strtoupper(trim($_REQUEST["search"]));
			$entered = str_replace("'", "''", $entered);
		}
		else return True;

		if (substr($entered, -2) == "ID") $entered = substr($entered, 0, strlen($entered) - 2);

		$personNum = False;
		$memberID = False;
		if (strstr($entered,"::") !== False){
			// Values of memlist items are "CardNo::personNum"
			list($memberID,$personNum) = explode("::",$entered);
			$this->submitted = True;
		}
		// No input available, stop
		if (!$entered || strlen($entered) < 1 || $entered == "CL") {
			$this->change_page($this->page_url."gui-modules/pos2.php");
			return False;
		}
		else if ($memberID === False && $personNum === False){
			// find the member
			$lookups = AutoLoader::ListModules('MemberLookup', True);
			foreach($lookups as $class){
				if (!class_exists($class)) continue;
				$obj = new $class();
				if(is_numeric($entered) && !$obj->handle_numbers())
					continue;
				elseif(!is_numeric($entered) && !$obj->handle_text())
					continue;
				elseif(is_numeric($entered)){
					$chk = $obj->lookup_by_number($entered);
					if ($chk['url'] !== False){
						$this->change_page($chk['url']);
						return False;		
					}
					foreach($chk['results'] as $key=>$val)
						$this->results[$key] = $val;
				}
				elseif(!is_numeric($entered)){
					$chk = $obj->lookup_by_text($entered);
					if ($chk['url'] !== False){
						$this->change_page($chk['url']);
						return False;		
					}
					foreach($chk['results'] as $key=>$val)
						$this->results[$key] = $val;
				}
			}
			$this->submitted = True;
		}

		// if theres only 1 match don't show the memlist
		// when it's the default non-member account OR
		// when name verification is disabled
		if (count($this->results) == 1 && 
		    ($CORE_LOCAL->get("verifyName")==0 || $entered == $CORE_LOCAL->get('defaultNonMem'))) {
			$key = array_pop(array_keys($this->results));
			list($memberID, $personNum) = explode('::',$key);
		}

		// we have exactly one row and 
		// don't need to confirm any further
		if ($memberID !== False && $personNum !== False){
			if ($memberID == $CORE_LOCAL->get('defaultNonMem'))
				$personNum = 1;
			$db_a = Database::pDataConnect();
			$query = $db_a->prepare_statement('SELECT CardNo, personNum,
				LastName, FirstName,CashBack,Balance,Discount,
				MemDiscountLimit,ChargeOk,WriteChecks,StoreCoupons,Type,
				memType,staff,SSI,Purchases,NumberOfChecks,memCoupons,
				blueLine,Shown,id FROM custdata WHERE CardNo=?
				AND personNum=?');
			$result = $db_a->exec_statement($query,array($memberID, $personNum));
			$row = $db_a->fetch_row($result);
			PrehLib::setMember($row["CardNo"], $personNum, $row);

			// WEFC_Toronto: If a Member Card # was entered when the choice from the list was made,
			// add the memberCards record.
			if ( $CORE_LOCAL->get('store') == "WEFC_Toronto" ) {
				$mmsg = "";
				if ( isset($_REQUEST['memberCard']) && $_REQUEST['memberCard'] != "" ) {
					$memberCard = $_REQUEST['memberCard'];
					if ( !is_numeric($memberCard) || strlen($memberCard) > 5 || $memberCard == 0 ) {
						$mmsg = "Bad Member Card# format >{$memberCard}<";
					}
					else {
						$upc = sprintf("00401229%05d", $memberCard);
						// Check that it isn't already there, perhaps for someone else.
						$mQ = "SELECT card_no FROM memberCards where card_no = {$row['CardNo']}";
						$mResult = $db_a->query($mQ);
						$mNumRows = $db_a->num_rows($mResult);
						if ( $mNumRows > 0 ) {
							$mmsg = "{$row['CardNo']} is already associated with another Member Card";
						}
						else {
							$mQ = "INSERT INTO memberCards (card_no, upc) VALUES ({$row['CardNo']}, '$upc')";
							$mResult = $db_a->query($mQ);
							if ( !$mResult ) {
								$mmsg = "Linking membership to Member Card failed.";
							}
						}
					}
				}
				if ( $mmsg != "" ) {
					// Prepare to display the error.
					$this->temp_message = $mmsg;
					return True;
				}
			// /WEFC_Toronto bit.
			}

			// don't bother with unpaid balance check if there is no balance
			if ($entered != $CORE_LOCAL->get("defaultNonMem") && $CORE_LOCAL->get('balance') > 0){
				$unpaid = PrehLib::check_unpaid_ar($row["CardNo"]);
				if ($unpaid)
					$this->change_page($this->page_url."gui-modules/UnpaidAR.php");
				else
					$this->change_page($this->page_url."gui-modules/pos2.php");
			}
			else
				$this->change_page($this->page_url."gui-modules/pos2.php");
			return False;
		}

		// Prepare to display the memlist (list to choose from).
		$this->temp_message = "";
		return True;

	} // END preprocess() FUNCTION

	function head_content(){
		global $CORE_LOCAL;
		if (count($this->results) > 0){
			$this->add_onload_command("\$('#search').keypress(processkeypress);\n");
			$this->add_onload_command("\$('#search').focus();\n");
		} else {
			$this->default_parsewrapper_js('reginput','selectform');
			$this->add_onload_command("\$('#reginput').focus();\n");
		}
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
		$message = $this->temp_message;

		echo "<div class=\"baseHeight\">"
			."<form id=\"selectform\" method=\"post\" action=\"{$_SERVER['PHP_SELF']}\">";

		// First check for a problem found in preprocess.
		if ( $message != "" ) {
			echo "
			<div class=\"colored centeredDisplay\">
				<span class=\"larger\">
			{$message}<br />".
			_("enter member number or name").
			"</span>
				<input type=\"text\" name=\"search\" size=\"15\"
			       	onblur=\"\$('#reginput').focus();\" id=\"reginput\" />
				<br />press [enter] to cancel
			</div>";
		}
		/* for no results, just throw up a re-do
		 * otherwise, put results in a select box
		 */
		elseif (count($this->results) < 1){
			echo "
			<div class=\"colored centeredDisplay\">
				<span class=\"larger\">";
			if (!$this->submitted)
				echo _("member search")."<br />"._("enter member number or name");
			else
				echo _("no match found")."<br />"._("next search or member number");
			echo "</span>
				<input type=\"text\" name=\"search\" size=\"15\"
			       	onblur=\"\$('#reginput').focus();\" id=\"reginput\" />
				<br />
				press [enter] to cancel
			</div>";
		}
		else {
			echo "<div class=\"listbox\">"
				."<select name=\"search\" size=\"15\" "
				."onblur=\"\$('#search').focus();\" ondblclick=\"document.forms['selectform'].submit();\" id=\"search\">";

			$selectFlag = 0;
			// I don't know what !$entered was supposed to do here, 
			// but the variable isn't defined
			if (False && $CORE_LOCAL->get("memlistNonMember") == 1) {
				echo "<option value='3::1' selected> 3 "
					."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Customer";
				$selectFlag = 1;
			}

			foreach($this->results as $optval => $label){
				echo '<option value="'.$optval.'"';
				if ($selectFlag == 0){
					echo ' selected';
					$selectFlag = 1;
				}
				echo '>'.$label.'</option>';
			}
			echo "</select></div><!-- /.listbox -->"
				."<div class=\"listboxText coloredText centerOffset\">"
				._("use arrow keys to navigate")."<p>"._("clear to cancel")."</div><!-- /.listboxText coloredText .centerOffset -->"
				."<div class=\"clear\"></div>";

			// A textbox for the Member Card number, to be added to the db for the selected member.
			if ( $CORE_LOCAL->get('store') == "WEFC_Toronto" ) {
				echo "<div style='text-align:left; margin-top: 0.5em;'>
				<p style='margin: 0.2em 0em 0.2em 0em; font-size:0.8em;'>To link the member chosen above to a Member Card:</p>";
				echo "<span style='font-weight:bold;'>Member Card#:</span> <input name='memberCard' id='memberCard' width='20' title='The digits after 01229, no leading zeroes, not the final, small check-digit' />";
				echo "<p style='margin-top: 0.2em; font-size:0.8em;'>If the back of the card has: '4 01229 00125 7' enter 125
				<br />Then the card should be recognized in the scan.</p>";
				echo "</div>";
			}

		}
		echo "</form></div>";
	} // END body_content() FUNCTION

// /class memlist
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF']))
	new memlist();

?>
