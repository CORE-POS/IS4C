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

class CCMenu extends PreParser {
	var $remainder;
	
	function check($str){
		global $CORE_LOCAL;
		if ($str == "CC"){
//			$this->remainder = "QM1";
			return True;
		}
		elseif ($str == "MANUALCC"){
			$this->remainder = ("".$CORE_LOCAL->get("runningTotal") * 100)."CC";
			return True;
		}
		return False;
	}

	function parse($str){
		return $this->remainder;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td><i>discount</i>DI<i>item</i></td>
				<td>Set a percent discount <i>discount</i>
				for just one item <i>item</i></td>
			</tr>
			<tr>
				<td><i>discount</i>PD<i>item</i></td>
				<td>Same as DI above</td>
			</tr>
			</table>";
	}
}

?>
