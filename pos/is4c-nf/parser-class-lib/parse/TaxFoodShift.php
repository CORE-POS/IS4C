<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

    This file is part of IS4C.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!class_exists("Parser")) include_once($IS4C_PATH."parser-class-lib/Parser.php");
if (!function_exists("tDataConnect")) include_once($IS4C_PATH."lib/connect.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

class TaxFoodShift extends Parser {

	function check($str){
		global $IS4C_LOCAL;
		$id = $IS4C_LOCAL->get("currentid");
		if ($str == "TFS" && $id > 0){
			return True;
		}
		return False;
	}

	function parse($str){
		global $IS4C_LOCAL;
		$id = $IS4C_LOCAL->get("currentid");

		$db = tDataConnect();

		$q = "SELECT trans_type,tax,foodstamp FROM localtemptrans WHERE trans_id=$id";
		$r = $db->query($q);
		if ($db->num_rows($r) == 0) return True; // shouldn't ever happen
		$row = $db->fetch_row($r);

		// 1. notax fs
		// 2. regtax nofs
		// 3. delitax nofs
		$q = "";
		if ($row['tax'] == 0 && $row['foodstamp'] == 1){
			$q = "UPDATE localtemptrans set tax=1,foodstamp=0 WHERE trans_id=$id";
		}
		else if ($row['tax'] == 1 && $row['foodstamp'] == 0){
			$q = "UPDATE localtemptrans set tax=2,foodstamp=0 WHERE trans_id=$id";
		}
		else {
			$q = "UPDATE localtemptrans set tax=0,foodstamp=1 WHERE trans_id=$id";
		}
		$db->query($q);	
		
		$db->db_close();
		
		$ret = $this->default_json();
		$ret['output'] = listitems($IS4C_LOCAL->get("currenttopid"),$id);
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
