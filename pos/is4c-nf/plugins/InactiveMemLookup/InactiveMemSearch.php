<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

class InactiveMemSearch extends MemberLookup {

	public function lookup_by_number($num){
		global $CORE_LOCAL;
		$dbc = Database::pDataConnect();
		$query = $dbc->prepare_statement('SELECT CardNo, personNum,
			LastName, FirstName FROM custdata
			WHERE CardNo=? 
			AND Type=\'INACT\'
			ORDER BY personNum');
		$result = $dbc->exec_statement($query, array($num));

		$ret = $this->default_value();
		$i = 1;
		while($w = $dbc->fetch_row($result)){
			if ($CORE_LOCAL->get('InactiveMemUsage') == 1)
				$key = $CORE_LOCAL->get('defaultNonMem').'::'.$i;
			else
				$key = $w['CardNo'].'::'.$w['personNum'];
			$val = $w['CardNo'].'(CSC) '.$w['LastName'].', '.$w['FirstName'];
			$ret['results'][$key] = $val;
			$i++;
		}
		return $ret;
	}

	public function lookup_by_text($text){
		global $CORE_LOCAL;
		$dbc = Database::pDataConnect();
		$query = $dbc->prepare_statement('SELECT CardNo, personNum,
			LastName, FirstName FROM custdata
			WHERE LastName LIKE ? 
			AND Type = \'INACT\'
			ORDER BY LastName, FirstName');
		$result = $dbc->exec_statement($query, array($text.'%'));	
		$ret = $this->default_value();
		$i=1;
		while($w = $dbc->fetch_row($result)){
			if ($CORE_LOCAL->get('InactiveMemUsage') == 1)
				$key = $CORE_LOCAL->get('defaultNonMem').'::'.$i;
			else
				$key = $w['CardNo'].'::'.$w['personNum'];
			$val = $w['CardNo'].'(CSC) '.$w['LastName'].', '.$w['FirstName'];
			$ret['results'][$key] = $val;
			$i++;
		}
		return $ret;
	}

}

?>
