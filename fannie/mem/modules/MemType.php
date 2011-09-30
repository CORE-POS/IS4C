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

	function SaveFormData($memNum){
		$dbc = $this->db();

		$mtype = isset($_REQUEST['MemberType_type']) ? $_REQUEST['MemberType_type'] : 0;
		$q = sprintf("SELECT discount,staff,SSI,cd_type FROM memdefaults
			WHERE memtype=%d",$typem);
		$r = $dbc->query($q);

		$type='REG';
		$discount=0;
		$staff=0;
		$SSI=0;
		if ($dbc->num_rows($r) > 0){
			$w = $dbc->fetch_row($r);
			$type = $w['cd_type'];
			$discount = $w['discount'];
			$staff = $w['staff'];
			$SSI = $w['SSI'];
		}

		$upQ = sprintf("UPDATE custdata SET Type=%s,staff=%d,SSI=%d,Discount=%d,
			memType=%d WHERE CardNo=%d",$dbc->escape($type),$staff,$SSI,
			$discount,$mtype,$memNum);
		$upR = $dbc->query($upQ);

		if ($upR === False )
			return "Error: problem saving Member Type<br />";
		else
			return "";
	}
}

?>
