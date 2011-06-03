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

class Notes extends MemberModule {

	function ShowEditForm($memNum){
		global $FANNIE_URL;

		$dbc = $this->db();
		
		$infoQ = sprintf("SELECT note,stamp FROM memberNotes
				WHERE cardno=%d ORDER BY stamp DESC",$memNum);
		$infoR = $dbc->query($infoQ);

		$note = "";
		$date = "";
		if ($dbc->num_rows($infoR) > 0){
			$infoW = $dbc->fetch_row($infoR);
			$note = str_replace("<br />","\n",$infoW['note']);
			$date = $infoW['stamp'];
		}

		$ret = "<fieldset><legend>Notes</legend>";
		$ret .= "<table class=\"MemFormTable\" 
			border=\"0\">";

		$ret .= "<tr><th>Additional Notes</th>";
		$ret .= "<td><a href=\"\">History</a></td></tr>";
		$ret .= "<tr><td colspan=\"2\"><textarea name=\"Notes_text\" rows=\"4\" cols=\"25\">";
		$ret .= $note;
		$ret .= "</textarea></td></tr>";

		$ret .= "</table></fieldset>";
		return $ret;
	}
}

?>
