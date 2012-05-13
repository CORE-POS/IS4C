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

class SigTermCommands extends Parser {

	function check($str){
		global $CORE_LOCAL;
		if ($str == "TRESET"){
			$CORE_LOCAL->set("ccTermOut","reset");
			return True;
		}
		if ($str == "TSIG"){
			$CORE_LOCAL->set("ccTermOut","sig");
			return True;
		}
		return False;
	}

	function parse($str){
		global $CORE_PATH,$CORE_LOCAL;
		$ret = $this->default_json();
		$ret['udpmsg'] = $CORE_LOCAL->get("ccTermOut");
		if ($ret['udpmsg'] == "sig")
			$ret['main_frame'] = $CORE_PATH.'gui-modules/paycardSignature.php';
		return $ret;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td>WAKEUP</td>
				<td>Try to coax a stuck scale back
				into operation</td>
			</tr>
			<tr>
				<td>WAKEUP2</td>
				<td>Different method, same goal</td>
			</tr>
			</table>";
	}
}

?>
