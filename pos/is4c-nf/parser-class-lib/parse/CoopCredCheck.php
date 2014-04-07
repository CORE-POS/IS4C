<?php
/*******************************************************************************

    Copyright 2007 Whole Foods Co-op

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

class CoopCredCheck extends Parser {
	function check($str){
		if ($str == "CQ")
			return True;
		return False;
	}

	function parse($str){
		global $CORE_LOCAL;
		$ret = $this->default_json();
        // Sets $balance and $availBal.
		$chargeOk = PrehLib::chargeOk();
        // $memChargeCommitted isn't used here.
		$memChargeCommitted=$CORE_LOCAL->get("availBal") - $CORE_LOCAL->get("memChargeTotal");
        $message = "<p style='font-weight:bold; text-align:center; margin: 0em 0em 0em -1.0em;'>".
            _("Member")." #". $CORE_LOCAL->get("memberID")."<br />";
        if ($chargeOk) {
            $message .= _("Available Coop Cred") . "<br />" .
            _("Balance is:") . "<br />" .
            "<span style='font-size:1.4em;'>" . " ".$CORE_LOCAL->get("availBal") . "</span>";
        } else {
            $message .= _("Is not authorized to use") . "<br />" . _("Coop Cred");
        }
        $message .= "</p>";
        $ret['output'] = DisplayLib::boxMsg("$message","",True);
		return $ret;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td>CQ</td>
				<td>Display Coop Cred balance for
				currently entered member</td>
			</tr>
			</table>";
	}

}

?>
