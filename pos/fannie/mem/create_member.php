<?php
/*******************************************************************************

    Copyright 2007 Alberta Cooperative Grocery, Portland, Oregon.

    This file is part of Fannie.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
// A page to create a member.
$page_title='Fannie - Member Management Module';
$header='Create A New Member';
include('../src/header.html');
include ('./includes/header.html');

// Check for a valid user ID, through GET or POST.

require_once('../src/mysql_connect.php'); // Connect to the database.

if (isset($_POST['submitted'])) { // If the form has been submitted, check the data and create the record.
	
	// Initialize the errors array.
	$errors = array();
	$cn = $_POST['card_no'];
	$query = "SELECT * FROM custdata WHERE cardno=" . $cn;
	$result = $dbc->query($query);
	if ($dbc->num_rows($result) != 0) {
		$errors[] = 'This member number is already in use, please select a different number, or <a href="modify_household.php?cardno=' . $cn . '">edit</a> the existing household.';
	}
	if (empty($errors)) {
		$type = 'REG';
		if ($_POST['memtype'] == 1 or $_POST['memtype'] == 3) $type='PC';
		$staff = 0;
		if ($_POST['memtype'] == 3 or $_POST['memtype'] == 9) $staff = 1;
		$query = sprintf("INSERT INTO custdata (CardNo,personNum,LastName,FirstName,CashBack,Balance,Discount,MemDiscountLimit,
				ChargeOk,WriteChecks,StoreCoupons,Type,memType,staff,SSI,Purchases,NumberOfChecks,memCoupons,
				blueLine,Shown) VALUES (%d,1,'%s','%s',999.99,0,%s,0,1,1,0,'%s',%d,%d,0,0,999,0,'%d %s',1)",
				$cn,$dbc->escape($_POST['ps_last_name']),$dbc->escape($_POST['ps_first_name']),
				$_POST['ps_discount'],$type,$_POST['ps_memtype'],$staff,
				$cn.' '.$dbc->escape($_POST['ps_last_name']));
		$result = $dbc->query($query);
	
		echo '<div id="alert">';

		if ($result){
			echo '<p>Member number ' . $cn . ' been created.</p>';
		} else { // The query was unsuccessful.
			echo '<p class="error">The member could not be edited due to a system error.<br />';
		}

		echo '</div>';	
		
	} else { // Report the errors.
		
		echo '<div id="alert">';		
		echo '<p class="error">The following error(s) occurred:<br />';
		foreach ($errors as $msg) { // Print each error.
			echo " - $msg<br />\n";
		}
		echo '</p><p>Please try again.</p>';
		echo '</div>';	
		
	} // End of if (empty($errors)) IF.
		
} // End of submit conditional.

// Always show the form.

// Retrieve the user's information.
$query2 = "SELECT memtype, memDesc FROM memtype ORDER BY memtype ASC";
$query3 = "SELECT max(CardNo) AS max FROM custdata"; 
$result2 = $dbc->query($query2);
$result3 = $dbc->query($query3);

// Show the form.
	
	// Get the user's information.

	$row3 = $dbc->fetch_array($result3);
	$max = $row3['max'];
	$max = $max + 1;
	// Create the form.

	echo '<h2>Create a Member.</h2><br />
	<form action="mem_create.php" name="create_member" method="post">
	<p>Card Number: <input type="text" name="card_no" size="6" value="' . $max . '" /></p>
	<p>First Name: <input type="text" name="ps_first_name" size="20" maxlength="30" value="WELCOME" /></p>
	<p>Last Name: <input type="text" name="ps_last_name" size="20" maxlength="30" /></p>';
	echo '<p>Discount: <input type="text" name="ps_discount" size="3" maxlength="2" value="0" />%</p>
	<p>Member Type: <select name="ps_memtype">';
	while ($row2 = $dbc->fetch_array($result2)) {
		echo '<option value='. $row2['memtype'];
		if ($row2['memtype'] == 1) {echo ' SELECTED';}
		echo '>' . $row2['memDesc'];
	}
	echo '</select><br />';
	echo '<p><input type="submit" name="submit" value="Submit" /></p>
	<input type="hidden" name="submitted" value="TRUE" />
	</form>';

include('../src/footer.html');
?>
