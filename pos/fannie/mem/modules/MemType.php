<?php

class MemType extends MemberModule {

	function ShowEditForm($memNum){
		global $FANNIE_URL;

		$dbc = $this->db();
		
		$infoQ = sprintf("SELECT c.memType,n.memType,n.memDesc,c.discount
				FROM custdata AS c, 
				memtype AS n 
				WHERE c.CardNo=%d AND c.personNum=1
				ORDER BY n.memType",$memNum);
		$infoR = $dbc->query($infoQ);

		$ret = "<fieldset><legend>Membership Type</legend>";
		$ret .= "<table class=\"MemFormTable\" 
			border=\"0\">";

		$ret .= "<tr><th>Type</th>";
		$ret .= '<td><select name="MemType_type">';
		$disc = 0;
		while($infoW = $dbc->fetch_row($infoR)){
			$ret .= sprintf("<option value=%d %s>%s</option>",
				$infoW[1],
				($infoW[0]==$infoW[1]?'selected':''),
				$infoW[2]);
			$disc = $infoW[3];
		}
		$ret .= "</select></td>";
		
		$ret .= "<th>Discount</th>";
		/*
		$ret .= sprintf('<td><input name="MemType_discount" value="%d"
				size="4" /></td></tr>',$disc);	
		*/
		$ret .= sprintf('<td>%d%%</td></tr>',$disc);

		$ret .= "</table></fieldset>";
		return $ret;
	}
}

?>
