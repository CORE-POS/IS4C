<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op, Duluth, MN

    This file is part of Fannie.

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

class MemCard extends MemberModule {

	// Return a form segment for display or edit the Member Card#
	function ShowEditForm($memNum){
		global $FANNIE_URL;
		global $FANNIE_MEMBER_UPC_PREFIX;

		$dbc = $this->db();

		$prefix = isset($FANNIE_MEMBER_UPC_PREFIX) ? $FANNIE_MEMBER_UPC_PREFIX : "";
		$plen = strlen($prefix);

		$infoQ = sprintf("SELECT upc
				FROM memberCards
				WHERE card_no=%d",
				$memNum);
		$infoR = $dbc->query($infoQ);
		if ( $infoR === false ) {
			return "Error: problem checking for Member Card<br />";
		}

		$ret = "<fieldset><legend>Membership Card</legend>";
		$ret .= "<table class=\"MemFormTable\" 
			border=\"0\">";

		if ( $dbc->num_rows($infoR) > 0 ) {
			$infoW = $dbc->fetch_row($infoR);
			$upc = $infoW['upc'];
			if ( $prefix && strpos("$upc", "$prefix") === 0 ) {
				$upc = substr($upc,$plen);
				$upc = ltrim($upc,"0");
			}
		} else {
			$upc = "";
		}
		$ret .= "<tr><th>Card#</th>";
		$ret .= "<td><input name='memberCard' size='15' value='{$upc}'></td>";
		$ret .= '</tr>';

		$ret .= "</table></fieldset>";

		return $ret;

	// showEditForm
	}

	// Update, insert or delete the Member Card#.
	// Return "" on success or an error message.
	function SaveFormData($memNum){

		global $FANNIE_MEMBER_UPC_PREFIX;

		$dbc = $this->db();

		$prefix = isset($FANNIE_MEMBER_UPC_PREFIX) ? $FANNIE_MEMBER_UPC_PREFIX : "";
		$plen = strlen($prefix);

		$form_upc = isset($_REQUEST['memberCard']) ? "{$_REQUEST['memberCard']}" : "";
		// Restore prefix and leading 0's to upc.
		if ( $form_upc && strlen($form_upc) < 13 ) {
			$clen = (13 - $plen);
			$form_upc = sprintf("{$prefix}%0{$clen}d", $form_upc);
		}

		//Is there already a memberCards record for this member?
		$infoQ = sprintf("SELECT upc
				FROM memberCards
				WHERE card_no=%d",
				$memNum);
		$infoR = $dbc->query($infoQ);
		if ( $infoR === false ) {
			return "Error: problem checking for Member Card<br />";
		}

		//If yes:
		if ( $dbc->num_rows($infoR) > 0 ) {
			$infoW = $dbc->fetch_row($infoR);
			$db_upc = "{$infoW['upc']}";
			// If the formitem is the same as the db item, do nothing.
			if ( $form_upc == $db_upc ) {
				1;
			}
			// If the formitem is not "", update.
			elseif ( $form_upc != "" ) {
				$upQ = sprintf("UPDATE memberCards SET upc = %s WHERE card_no = %d",
								$dbc->escape("$form_upc"), $memNum);
				$upR = $dbc->query($upQ);
				if ( $upR === false ) {
					return "Error: problem saving Member Card<br />";
				} else {
					return "";
				}
			}
			// If the formitem is "", delete.
			else {
				$upQ = sprintf("DELETE FROM memberCards WHERE card_no = %d",
								$memNum);
				$upR = $dbc->query($upQ);
				if ( $upR === false ) {
					return "Error: problem deleteing Member Card<br />";
				} else {
					return "";
				}
			}
		}
		//If no:
		else {
			// If the formitem is not "", insert.
			if ( $form_upc != "" ) {
				$upQ = sprintf("INSERT INTO memberCards (card_no, upc) VALUES (%d, %s)",
								$memNum, $dbc->escape("$form_upc"));
				$upR = $dbc->query($upQ);
				if ( $upR === false ) {
					return "Error: problem adding Member Card<br />";
				} else {
					return "";
				}
			}
			// If the formitem is "", do nothing.
			else {
				return "";
			}
		}

	// saveFormData
	}

// MemCard
}

?>
