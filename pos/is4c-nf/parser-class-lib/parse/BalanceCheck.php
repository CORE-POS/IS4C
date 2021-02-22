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

class BalanceCheck extends Parser {
	function check($str){
		if ($str == "BQ")
			return True;
		return False;
	}

	function parse($str){
		global $CORE_LOCAL;
		$ret = $this->default_json();
		PrehLib::chargeOk();
        $bal = $CORE_LOCAL->get('InvertAR') ? -1*$CORE_LOCAL->get('balance') : $CORE_LOCAL->get('balance');
        $avail = $CORE_LOCAL->get('InvertAR') ? -1*$CORE_LOCAL->get('availBal') : $CORE_LOCAL->get('availBal');
		$ret['output'] = DisplayLib::boxMsg(_("Member")." #". $CORE_LOCAL->get("memberID")."<br />".
					_(sprintf("Current AR balance is %s", $bal)) . "<br />".
					_(sprintf("Available AR balance is %s", $avail)) . "</b>","",True);
		return $ret;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td>BQ</td>
				<td>Display store charge balance for
				currently entered member</td>
			</tr>
			</table>";
	}

}

?>
