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
$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

class ScaleInput extends Parser {
	function check($str){
		if (substr($str,0,3) == "S11" ||
		    substr($str,0,4) == "S143")
			return True;
		return False;
	}

	function parse($str){
		global $CORE_LOCAL;
		if (substr($str,0,3) == "S11"){
			$weight = substr($str,3);
			if (is_numeric($weight) || $weight < 9999){
				$CORE_LOCAL->set("scale",1);
				$CORE_LOCAL->set("weight",$weight / 100);
			}
		}
		else
			$CORE_LOCAL->set("scale",0);

		$ret = $this->default_json();
		$ret['scale'] = $str;
		return $ret;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td>S11</td>
				<td>Catch scale's input</td>
			</tr>
			<tr>
				<td>S143</td>
				<td>Catch scale's input</td>
			</tr>
			<tr>
				<td colspan=2>These are so the scanner-scale
				can talk to IT CORE. Users wouldn't use these
				key strokes</td>
			</tr>
			</table>";

	}

}

?>
