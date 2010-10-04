<?php

class Suspension extends MemberModule {

	function ShowEditForm($memNum){
		global $FANNIE_URL;

		$dbc = $this->db();
		
		$infoQ = sprintf("SELECT CASE WHEN s.type = 'I' THEN 'Inactive' ELSE 'Terminated' END as status,
				s.suspDate,
				CASE WHEN s.reasoncode = 0 THEN s.reason ELSE r.textStr END as reason
				FROM suspensions AS s LEFT JOIN reasonCodes AS r
				ON s.reasoncode & r.mask <> 0
				WHERE s.cardno=%d",$memNum);
		$infoR = $dbc->query($infoQ);

		$status = "Active";
		$date = "";
		$reason = "";
		if ($dbc->num_rows($infoR) > 0){
			while($infoW = $dbc->fetch_row($infoR)){
				$status = $infoW['status'];
				$date = $infoW['suspDate'];
				$reason .= $infoW['reason'].", ";
			}		
			$reason = rtrim($reason,", ");
		}

		$ret = "<fieldset><legend>Active Status</legend>";
		$ret .= "<table class=\"MemFormTable\" 
			border=\"0\">";

		$ret .= "<tr><th>Current Status</th>";
		$ret .= "<td>$status</td>";
		if (!empty($reason)){
			$ret .= "<th>Reason</th>";
			$ret .= "<td>$reason</td></tr>";
		}
		$ret .= "<tr><td><a href=\"\">History</a></td>";
		$ret .= "<td><a href=\"\">Change Status</a></td></tr>";

		$ret .= "</table></fieldset>";
		return $ret;
	}
}

?>
