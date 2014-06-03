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

class RRR extends Parser {
	function check($str){
		if ($str == "RRR" || substr($str,-4)=="*RRR"){
			return True;
		}
		return False;
	}

	function parse($str){
		global $CORE_LOCAL;
		$ret = $this->default_json();
		$qty = 1;
		if ($str != "RRR"){
			$split = explode("*",$str);
			if (!is_numeric($split[0])) return True;
			$qty = $split[0];
		}
		$this->add($qty);

		$ret['output'] = DisplayLib::lastpage();
		$ret['udpmsg'] = 'goodBeep';

		Database::getsubtotals();
		if ($CORE_LOCAL->get("runningTotal") == 0) {
            TransRecord::finalizeTransaction(true);
		}

		return $ret;
	}

	// gross misuse of field!
	// quantity is getting shoved into the volume special
	// column so that basket-size stats aren't skewed
	function add($qty) {
        TransRecord::addRecord(array(
            'upc' => 'RRR',
            'description' => $qty . ' RRR DONATED',
            'trans_type' => 'I',
            'VolSpecial' => $qty,
        ));
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td>RRR</td>
				<td>Add donated RRR card punch</td>
			</tr>
			<tr>
				<td><i>number</i>*RRR</td>
				<td>Add multiple donated punches</td>
			</tr>
			</table>";
	}

}

?>
