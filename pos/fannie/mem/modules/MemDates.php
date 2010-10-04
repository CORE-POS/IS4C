<?php

class MemDates extends MemberModule {

	function ShowEditForm($memNum){
		global $FANNIE_URL;

		$dbc = $this->db();
		
		$infoQ = sprintf("SELECT start_date,end_date
				FROM memDates
				WHERE card_no=%d",$memNum);
		$infoR = $dbc->query($infoQ);
		$infoW = $dbc->fetch_row($infoR);

		$ret = "<script type=\"text/javascript\"
			src=\"{$FANNIE_URL}src/CalendarControl.js\"
			</script>";
		$ret .= "<fieldset><legend>Membership Dates</legend>";
		$ret .= "<table class=\"MemFormTable\" 
			border=\"0\">";

		$ret .= "<tr><th>Start Date</th>";
		$ret .= sprintf('<td><input name="MemDates_start" size="10"
				maxlength="10" value="%s" onclick="showCalendarControl(this);"
				/></td>',$infoW['start_date']);	
		$ret .= "<th>End Date</th>";
		$ret .= sprintf('<td><input name="MemDates_end" size="10"
				maxlength="10" value="%s" onclick="showCalendarControl(this);"
				/></td></tr>',$infoW['end_date']);	


		$ret .= "</table></fieldset>";
		return $ret;
	}

	function SaveFormData($memNum){
		$dbc = $this->db();
		
		$start = !empty($_REQUEST['MemDates_start'])?$dbc->escape($_REQUEST['MemDates_start']):'NULL';
		$end = !empty($_REQUEST['MemDates_end'])?$dbc->escape($_REQUEST['MemDates_end']):'NULL';
		
		$saveQ = sprintf("UPDATE memDates SET start_date=%s,end_date=%s
				WHERE card_no=%d",$start,$end,$memNum);
		$test = $dbc->query($saveQ);

		if ($test === False)
			return "Error: problem saving start/end dates<br />";
		else
			return "";
	}
}

?>
