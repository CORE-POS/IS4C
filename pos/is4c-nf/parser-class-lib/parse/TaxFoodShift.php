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

class TaxFoodShift extends Parser {

	function check($str){
		global $CORE_LOCAL;
		$id = $CORE_LOCAL->get("currentid");
		if ($str == "TFS" && $id > 0){
			return True;
		}
		return False;
	}

	function parse($str){
		global $CORE_LOCAL;
		$id = $CORE_LOCAL->get("currentid");

		$db = Database::tDataConnect();

		$q = "SELECT trans_type,tax,foodstamp FROM localtemptrans WHERE trans_id=$id";
		$r = $db->query($q);
		if ($db->num_rows($r) == 0) return True; // shouldn't ever happen
		$row = $db->fetch_row($r);

		$q = "SELECT MAX(id) FROM taxrates";
		$r = $db->query($q);
		$tax_cap = 0;
		if ($db->num_rows($r)>0){
			$max = array_pop($db->fetch_row($r));
			if (!empty($max)) $tax_cap = $max;
		}
		$db->query($q);	

		$next_tax = $row['tax']+1;
		$next_fs = 0;
		if ($next_tax > $max){
			$next_tax = 0;
			$next_fs = 1;
		}

		$q = "UPDATE localtemptrans 
			set tax=$next_tax,foodstamp=$next_fs 
			WHERE trans_id=$id";
		$db->query($q);	
		
		$ret = $this->default_json();
		$ret['output'] = DisplayLib::listItems($CORE_LOCAL->get("currenttopid"),$id);
		return $ret; // maintain item cursor position
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td>TFS</td>
				<td>Roll through tax/foodstamp settings
				on the current item</td>
			</tr>
			</table>";
	}
}

?>
