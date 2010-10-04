<?php

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
