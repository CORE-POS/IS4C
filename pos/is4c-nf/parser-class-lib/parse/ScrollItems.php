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

class ScrollItems extends Parser {
	function check($str){
		if ($str == "U" || $str == "D")
			return True;
		elseif(($str[0] == "U" || $str[0] == "D")
			&& is_numeric(substr($str,1)))
			return True;
		return False;
	}

	function parse($str){
		global $CORE_LOCAL;
		$ret = $this->default_json();
		if ($str == "U")
			$ret["output"] = DisplayLib::listitems($CORE_LOCAL->get("currenttopid"), $CORE_LOCAL->get("currentid") -1);
		elseif ($str == "D")
			$ret["output"] = DisplayLib::listitems($CORE_LOCAL->get("currenttopid"), $CORE_LOCAL->get("currentid") +1);
		else {
			$change = (int)substr($str,1);
			$curID = $CORE_LOCAL->get("currenttopid");
			$newID = $CORE_LOCAL->get("currentid");
			if ($str[0] == "U")
				$newID -= $change;
			else
				$newID += $change;
			if ($newID == $curID || $newID == $curID+11)
				$curID = $newID-5;
			if ($curID < 1) $curID = 1;
			$ret["output"] = DisplayLib::listitems($curID, $newID);
		}
		return $ret;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td>U</td>
				<td>Scroll up</td>
			</tr>
			<tr>
				<td>D</td>
				<td>Scroll down</td>
			</tr>
			</table>";
	}
}

?>
