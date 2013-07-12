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

include('../../../config.php');
if (!class_exists('FannieAPI'))
	include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class PISearchPage extends PIKillerPage {

	protected $title = 'Search';

	private $results = False;

	function get_handler(){
		global $FANNIE_OP_DB;
		if ($this->id !== False){
			$dbc = FannieDB::get($FANNIE_OP_DB);
			$memNum = FormLib::get_form_value('id');
			$first = FormLib::get_form_value('firstName');
			$last = FormLib::get_form_value('lastName');
			$this->results = array();

			if (empty($memNum) && empty($last)) 
				return True; // invalid search	
			
			if (!empty($memNum)){
				$custdata = new CustdataModel($dbc);
				$custdata->CardNo($memNum);
				if (count($custdata->find()) > 0){
					$this->card_no = $memNum;
					header('Location: PIMemberPage.php?id='.$this->card_no);
					return False;
				}
				$cards = new MemberCardsModel($dbc);
				$cards->card_no($memNum);
				if (count($cards->find()) > 0){
					$w = $dbc->fetch_row($r);
					$this->card_no = $w['card_no'];
					header('Location: PIMemberPage.php?id='.$this->card_no);
					return False;
				}
			}
			else {
				$q = $dbc->prepare_statement('SELECT CardNo, LastName, FirstName FROM
					custdata WHERE LastName LIKE ? AND FirstName LIKE ?
					ORDER BY LastName,FirstName,CardNo');
				$r = $dbc->exec_statement($q, array($last.'%',$first.'%'));
				while($w = $dbc->fetch_row($r)){
					$this->results[] = $w;
				}
				if (count($this->results)==1){
					header('Location: PIMemberPage.php?id='.$this->card_no);
					return False;
				}
			}
		}
		return True;
	}

	function get_show_view(){
		ob_start();
		?>
		<tr>
		<form name="memNum" id="memNum" method="get" action="PISearchPage.php">
		<td width="1" align="right">&nbsp;</td>
		<td width="47" align="right" valign="middle"><font size="2" face="Papyrus, Verdana, Arial, Helvetica, sans-serif">Owner
		# or UPC:</font></td>
		<td>
      		<font size="2" face="Papyrus, Verdana, Arial, Helvetica, sans-serif">
      		<input name="id" type="text" id="memNum_t" size="5" maxlength="12" />
      		</font>
		</td>
		<td width="82" valign="middle"><font size="2" face="Papyrus, Verdana, Arial, Helvetica, sans-serif">Last Name</font></td>
		<td colspan="5">
		<font size="2" face="Papyrus, Verdana, Arial, Helvetica, sans-serif">
		<input name="lastName" type="text" id="lastName3" size="25" maxlength="50" />
		</font>
		</td>
		<td width="75" valign="middle"><font size="2" face="Papyrus, Verdana, Arial, Helvetica, sans-serif">First
		Name:</font></td><td>
		<input name="firstName" type="text" id="firstName" size="20" maxlength="50" /></td>
		<td><input type="submit" name="submit" value="submit">
		</form></td>
		</tr>
		<?php
		$this->add_onload_command('$(\'#memNum_t\').focus();');
		return ob_get_clean();
	}

	function get_id_view(){
		if (count($this->results) == 0){
			echo '<tr><td colspan="9"><p>No results from search</p></td></tr>';
			return $this->get_show_view();
		}
		$ret = '<tr><td colspan="9"><p>There is more than one result</p>';
		$ret .= '<form action="PISearchPage.php" method="get">';
		$ret .= '<select name="id" id="memNum_s">';
		foreach($this->results as $row){
			$ret .= sprintf('<option value="%d">%d %s %s</option>',
				$row['CardNo'],$row['CardNo'],
				$row['FirstName'],$row['LastName']);
		}
		$ret .= '</select> ';
		$ret .= '<input type="submit" value="submit" />';
		$ret .= '</form></td></tr>';
		$this->add_onload_command('$(\'#memNum_s\').focus();');
		return $ret;
	}
}

FannieDispatch::go();
