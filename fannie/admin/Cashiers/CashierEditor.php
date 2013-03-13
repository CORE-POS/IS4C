<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include('../../config.php');
include($FANNIE_ROOT.'classlib2.0/FanniePage.php');
include($FANNIE_ROOT.'classlib2.0/data/FannieDB.php');
include($FANNIE_ROOT.'classlib2.0/lib/FormLib.php');

class CashierEditor extends FanniePage {

	protected $title = "Fannie : Edit Cashier";
	protected $header = "Edit Cashier";
	protected $must_authenticate = True;
	protected $auth_classes = array('editcashiers');

	private $messages = '';

	function preprocess(){
		global $FANNIE_OP_DB;
		$emp_no = FormLib::get_form_value('emp_no',0);

		if (FormLib::get_form_value('fname') !== ''){
			$fn = FormLib::get_form_value('fname');
			$ln = FormLib::get_form_value('lname');
			$passwd = FormLib::get_form_value('passwd');
			$fes = FormLib::get_form_value('fes');
			$active = FormLib::get_form_value('active') !== '' ? 1 : 0;

			$dbc = FannieDB::get($FANNIE_OP_DB);
			$prep = $dbc->prepare_statement("UPDATE employees SET
				FirstName=?,
				LastName=?,
				CashierPassword=?,
				AdminPassword=?,
				frontendsecurity=?,
				backendsecurity=?,
				EmpActive=?
				WHERE emp_no=?");
			$dbc->exec_statement($prep,array($fn,$ln,$passwd,$passwd,
				$fes,$fes,$active,$emp_no));

			$this->messages = "Cashier Updated. <a href=ViewCashiersPage.php>Back to List of Cashiers</a>";
		}
		return True;
	}

	function body_content(){
		global $FANNIE_OP_DB;
		$dbc = FannieDB::get($FANNIE_OP_DB);
		$ret = '';
		if (!empty($this->messages)){
			$ret .= '<blockquote style="background: solid 1x black; 
				padding: 5px; margin: 5px;">';
			$ret .= $this->messages;
			$ret .= '</blockquote>';
		}	

		$emp_no = FormLib::get_form_value('emp_no',0);

		$infoP = $dbc->prepare_statement("SELECT CashierPassword,FirstName,LastName,EmpActive,frontendsecurity
				FROM employees WHERE emp_no=?");
		$infoR = $dbc->exec_statement($infoP, array($emp_no));
		$info = $dbc->fetch_row($infoR);

		$ret .= "<form action=CashierEditor.php method=post>";
		$ret .= "<table cellspacing=4 cellpadding=4>";
		$ret .= "<tr><th>First Name</th><td><input type=text name=fname value=\"$info[1]\" /></td>";
		$ret .= "<th>Last Name</th><td><input type=text name=lname value=\"$info[2]\" /></td></tr>";
		$ret .= "<tr><th>Password</th><td><input type=text name=passwd value=\"$info[0]\" /></td>";
		$ret .= "<th>Privileges</th><td><select name=fes>";
		if ($info[4] <= 20){
			$ret .= "<option value=20 selected>Regular</option>";
			$ret .= "<option value=30>Manager</option>";
		}
		else {
			$ret .= "<option value=20>Regular</option>";
			$ret .= "<option value=30 selected>Manager</option>";
		}
		$ret .= "</select></td></tr>";
		$ret .= "<tr><th>Active</th><td><input type=checkbox name=active ".($info[3]==1?'checked':'')." /></td>";
		$ret .= "<td colspan=2><input type=submit value=Save /></td></tr>";
		$ret .= "<input type=hidden name=emp_no value=$emp_no />";
		$ret .= "</table></form>";

		return $ret;
	}
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)){
	$obj = new CashierEditor();
	$obj->draw_page();
}
?>
