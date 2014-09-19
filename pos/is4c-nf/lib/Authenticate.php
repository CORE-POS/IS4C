<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

/**
  @class Authenticate
  Functions for user authentication
*/
class Authenticate extends LibraryClass 
{
 

/**
  Authenticate an employee by password
  @param $password password from employee table
  @param $activity activity identifier to log
  @return True or False

  If no one is currently logged in, any valid
  password will be accepted. If someone is logged
  in, then only passwords for that user <i>or</i>
  a user with frontendsecurity >= 30 in the
  employee table will be accepted.
*/
static public function checkPassword($password,$activity=1)
{
	global $CORE_LOCAL;

	$password = strtoupper($password);
	$password = str_replace("'", "", $password);
	$password = str_replace(",", "", $password);
	$paswword = str_replace("+", "", $password);

	if ($password == "TRAINING") {
        $password = 9999; // if password is training, change to '9999'
    }

	$query_g = "select LoggedIn,CashierNo from globalvalues";
	$db_g = Database::pDataConnect();
	$result_g = $db_g->query($query_g);
	$row_g = $db_g->fetch_array($result_g);
	$password = $db_g->escape($password);

	if ($row_g["LoggedIn"] == 0) {
		$query_q = "select emp_no, FirstName, LastName, "
			.$db_g->yeardiff($db_g->now(),'birthdate')." as age "
			."from employees where EmpActive = 1 "
			."and CashierPassword = '".$password."'";
		$result_q = $db_g->query($query_q);
		$num_rows_q = $db_g->num_rows($result_q);

		if ($num_rows_q > 0) {
			$row_q = $db_g->fetch_array($result_q);

			Database::loadglobalvalues();

			$transno = Database::gettransno($row_q["emp_no"]);
			$globals = array(
				"CashierNo" => $row_q["emp_no"],
				"Cashier" => $row_q["FirstName"]." ".substr($row_q["LastName"], 0, 1).".",
				"TransNo" => $transno,
				"LoggedIn" => 1
			);
			Database::setglobalvalues($globals);

			CoreState::cashierLogin($transno, $row_q['age']);

		} elseif ($password == 9999) {
			Database::loadglobalvalues();

			$transno = Database::gettransno(9999);
			$globals = array(
				"CashierNo" => 9999,
				"Cashier" => "Training Mode",
				"TransNo" => $transno,
				"LoggedIn" => 1
			);
			Database::setglobalvalues($globals);

			CoreState::cashierLogin($transno, 0);
		} else {
            return False;
        }
	} else {
		// longer query but simpler. since someone is logged in already,
		// only accept password from that person OR someone with a high
		// frontendsecurity setting
		$query_a = "select emp_no, FirstName, LastName, "
			.$db_g->yeardiff($db_g->now(),'birthdate')." as age "
			."from employees "
			."where EmpActive = 1 and "
			."(frontendsecurity >= 30 or emp_no = ".$row_g["CashierNo"].") "
			."and (CashierPassword = '".$password."' or AdminPassword = '".$password."')";

		$result_a = $db_g->query($query_a);	

		$num_rows_a = $db_g->num_rows($result_a);

		if ($num_rows_a > 0) {

			Database::loadglobalvalues();
			$row = $db_g->fetch_row($result_a);
			CoreState::cashierLogin(False, $row['age']);
		} elseif ($row_g["CashierNo"] == "9999" && $password == "9999") {
			Database::loadglobalvalues();
			CoreState::cashierLogin(False, 0);
		} else {
            return false;
        }
	}

	return true;
}

} // end class Authenticate

