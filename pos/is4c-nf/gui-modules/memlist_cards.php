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

	* 24Mar2013 Eric Lee Removed some woodshed comments before saving to github.
	*  5Oct2012 Eric Lee A specialized WEFC_Toronto utility to update Fannie and lanes
	*                    with Member Card numbers linked to members in a CiviCRM database.

	*  5Oct2012 Eric Lee Added:
	*                    + A WEFC_Toronto-only chunk for collecting Member Card#
	*                    + A general facility for displaying an error encountered in preprocess()
	*                       in body_content() using temp_message.

*/

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class memlist_cards extends NoInputPage {

	var $temp_result;
	var $temp_num_rows;
	var $entered;
	var $db;
	var $temp_message;

	function preprocess(){
		global $CORE_LOCAL;

		// set variable ahead of time
		// so we know if lookup found no one
		// vs. lookup didn't happen
		$this->temp_num_rows = -1;

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

		$personNum = 1;
		$selected_name = False;
		// Values of memlist items are "CardNo::personNum"
		if (strstr($entered,"::") !== False){
			$tmp = explode("::",$entered);
			$entered = $tmp[0];
			$personNum = $tmp[1];
			$selected_name = True;
		}

		// No input available, stop
		if (!$entered || strlen($entered) < 1 || $entered == "CL") {
			$this->change_page($this->page_url."gui-modules/memlist_cards.php");
			return False;
		}

		$memberID = $entered;
		$db_a = Database::pDataConnect();

		if (!is_numeric($entered)) {
			$query = "select CardNo,personNum,LastName,FirstName from custdata 
				where LastName like '".$entered."%' order by LastName, FirstName";
		}
		else {
			$query = "select CardNo,personNum,LastName,FirstName,CashBack,Balance,Discount,
				ChargeOk,WriteChecks,StoreCoupons,Type,memType,staff,
				SSI,Purchases,NumberOfChecks,memCoupons,blueLine,Shown,id from custdata 
				where CardNo = '".$entered."' order by personNum";
		}

		$result = $db_a->query($query);
		$num_rows = $db_a->num_rows($result);

		// if theres only 1 match don't show the memlist
		if ($num_rows == 1) {
			$selected_name = True;
			$personNum = 1;
		}

		// if there's one result and either
		// a. it's the default nonmember account or
		// b. it's been confirmed in the select box
		// then set the member number
		// proceed/return to the appropriate next page
		if ( ($num_rows == 1 && $entered == $CORE_LOCAL->get("defaultNonMem"))
				||
		    (is_numeric($entered) && is_numeric($personNum) && $selected_name) ) {
			$row = $db_a->fetch_array($result);
			// Don't want to affect the current trans.  Will it still work?
			// PrehLib::setMember($row["CardNo"], $personNum,$row);

			// WEFC_Toronto: If a Member Card # was entered when the choice from the list was made,
			// add the memberCards record.
			if ( $CORE_LOCAL->get('store') == "WEFC_Toronto" ) {
				$mmsg = "";
				if ( isset($_REQUEST['memberCard']) && $_REQUEST['memberCard'] != "" ) {
					$memberCard = $_REQUEST['memberCard'];
					$upc = sprintf("00401229%05d", $memberCard);
					$card_no = $row['CardNo'];

					// Get the Member Card # from CiviCRM.
					//  By looking up card_no in Civi members to get the contact id and use contact_id to get mcard.
					// Can't do that because MySQL on Civi will only allow access from pos and posdev.
					// Have to get op to enter mcard# again.

					if ( !is_numeric($memberCard) || strlen($memberCard) > 5 || $memberCard == 0 ) {
						$mmsg .= "<br />Bad Member Card# format >{$memberCard}<";
					}
					else {
						/* Check that it isn't already in use, perhaps for someone else.
						*/
						$masterLane = $CORE_LOCAL->get('laneno');
						$currentLane = $masterLane;
						$mQ = "SELECT card_no FROM memberCards where card_no = $card_no";
						$mResult = $db_a->query($mQ);
						$mNumRows = $db_a->num_rows($mResult);
						if ( $mNumRows > 0 ) {
							$mmsg .= "<br />On lane $currentLane {$row['CardNo']} is already associated with a Member Card";
						}
						else {
							$mQ = "INSERT INTO memberCards (card_no, upc) VALUES ({$row['CardNo']}, '$upc')";
							$mResult = $db_a->query($mQ);
							if ( !$mResult ) {
								$mmsg .= "<br />On lane $currentLane linking membership to Member Card failed.";
						 	}
						}

						// Do other lane.
						$otherLane = ($masterLane == 1) ? 2 : 1;
						$currentLane = $otherLane;
						$isLAN = 1;
						if ( $isLAN ) {
							$LANE = "10.0.0.6$otherLane";
							$LANE_PORT = "3306";
						}
						else {
							$LANE = "wefc.dyndns.org";
							$LANE_PORT = "5066$otherLane";
						}
						$LANE_USER = "root";
						$LANE_PW = "wefc1229";
						$LANE_DB = "opdata";
						$db_b = new mysqli("$LANE", "$LANE_USER", "$LANE_PW", "$LANE_DB", "$LANE_PORT");
						if ( $db_b->connect_error != "" ) {
							$mmsg .= "<br />Connection to lane $currentLane failed >". $db_b->connect_error ."<";
						} else {
							$mQ = "SELECT card_no FROM memberCards where card_no = $card_no";
							$mResult = $db_b->query("$mQ");
							$mNumRows = $mResult->$num_rows;
							if ( $mNumRows > 0 ) {
								$mmsg .= "<br />On lane $currentLane member $card_no is already associated with a Member Card";
							}
							else {
								$mQ = "INSERT INTO memberCards (card_no, upc) VALUES ($card_no, '$upc')";
								$mResult = $db_b->query($mQ);
								if ( !$mResult ) {
									$mmsg .= "<br />On lane $currentLane linking membership to Member Card failed.";
								}
							}
						}
						$db_b->close();
					}
				}
				else {
					$mmsg .= "<br />Member Card# absent or empty.";
				}
				if ( $mmsg != "" ) {
					// Prepare to display the error.
					$this->temp_result = $result;
					$this->temp_num_rows = $num_rows;
					$this->entered = $entered;
					$this->db = $db_a;
					$this->temp_message = preg_replace("/^<br />/", "", $mmsg);
					return True;
				}
			// /WEFC_Toronto bit.
			}

			if ($entered != $CORE_LOCAL->get("defaultNonMem") && PrehLib::check_unpaid_ar($row["CardNo"]))
				$this->change_page($this->page_url."gui-modules/UnpaidAR.php");
			else
				$this->change_page($this->page_url."gui-modules/memlist_cards.php");
			return False;
		}

		// Prepare to display the memlist (list to choose from).
		$this->temp_result = $result;
		$this->temp_num_rows = $num_rows;
		$this->entered = $entered;
		$this->db = $db_a;
		$this->temp_message = "";
		return True;

	} // END preprocess() FUNCTION

	function head_content(){
		global $CORE_LOCAL;
		if ($this->temp_num_rows > 0){
			$this->add_onload_command("selectSubmit('#search', '#selectform')\n");
			$this->add_onload_command("\$('#search').focus();\n");
		} else {
			$this->default_parsewrapper_js('reginput','selectform');
			$this->add_onload_command("\$('#reginput').focus();\n");
		}
		?>
        <script type="text/javascript" src="../js/selectSubmit.js"></script>
		<?php
	} // END head() FUNCTION

	function body_content(){
		global $CORE_LOCAL;
		$num_rows = $this->temp_num_rows;
		$result = $this->temp_result;
		$entered = $this->entered;
		$db = $this->db;
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
		elseif ($num_rows < 1) {
			echo "
			<div class=\"colored centeredDisplay\">
				<span class=\"larger\">";
			if ($num_rows == -1)
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
				";
				echo "</div>";
			}

		}
		echo "</form></div>";
	} // END body_content() FUNCTION

// /class memlist
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF']))
	new memlist_cards();

?>
