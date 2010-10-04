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
// Household Editing Page
// A page to view and edit a household's details.
$page_title='Fannie - Member Management Module';
$header='Edit An Account';
include('../src/header.html');
include ('./includes/header.html');

// Check for a valid user ID, through GET or POST.

$cn = 0;
if (isset($_REQUEST['cardno']) && is_numeric($_REQUEST['cardno'])){
	$cn = $_REQUEST['cardno'];
} else { // No valid Card Number, ask for one.
	echo '<form action="modify_household.php" method="post"><br /><br />
	<h3><center>Which household would you like to modify?</center></h3>
	<h3><center><input type="text" name="cardno" size="5" maxlength="5" /></center><br /><br /></h3>
	<center><input type="submit" name="submit" value="Submit!" /></center>
	</form>';
	include ('./includes/footer.html');
	include('../src/footer.html');
	exit();
	
}

require_once ('../src/mysql_connect.php'); // Connect to the database.

if (isset($_POST['submitted'])) { // If the form has been submitted, check the new data and update the record.
	
	// Initialize the errors array.
	$errors = array();
	
	$fnames = $_POST['first_name'];
	$lnames = $_POST['last_name'];
	$id = $_POST['id'];
	$psWriteCheck = 0;
	if (($_POST['checks_ok']) == 'on') {$psWriteCheck = 1;}
	$pscharge = 0;
	$pslimit = 0;
	if ($_POST['charge_ok'] == 'on') {$pscharge = 1; $pslimit=9999;}
	$psd = 0;
	if (!empty($_POST['discount']) && is_numeric($_POST['discount']))
		$psd = $_POST['discount'];
	$psmemtype = $_POST['memtype'];
	$pstype = 'REG';
	if ($psmemtype == 1 or $mspemtype == 3)
		$pstype = 'PC';
	$limit = 0;
	if (isset($_POST['limit']) && is_numeric($_POST['limit']))
		$limit = $_POST['limit'];

	$addr1 = $_POST['address1'];
	$addr2 = $_POST['address2'];
	$city = $_POST['city'];
	$state = $_POST['state'];
	$zip = $_POST['zip'];
	$ph1 = $_POST['phone'];
	$ph2 = $_POST['phone2'];
	$email = $_POST['email'];
	$getsmail = (isset($_POST['mailings']))?1:0;
		
	if (empty($errors)) {
		$psid = $_POST['psid'];
		$updateQ = "UPDATE custdata SET Type='$pstype',memType=$psmemtype,Discount=$psdiscount,
				MemDiscLimit=$limit
				WHERE CardNo=$cn";
		$success = $dbc->query($updateQ);

		$nameQ = "UPDATE custdata SET FirstName='$fnames[0]',LastName='$lnames[0]',
				blueLine='$cn $lnames[0]'
				WHERE CardNo=$cn AND id=$id[0]";
		$success = $dbc->query($nameQ);

		$contactQ = "UPDATE meminfo SET street='$addr1',last_name='$addr2',city='$city',
				state='$state',zip='$zip',phone='$ph1',email_1='$email',
				email_2='$ph2',ads_OK=$getsmail WHERE card_no=$cn";
		$success = $dbc->query($contactQ);

		$success = $dbc->query("DELETE FROM custdata WHERE CardNo=$cn AND personNum > 1");
		$pn = 0;
		for($i=0;$i<count($fnames);$i++){
			$pn++;

			if($i == 0) continue;
			if(empty($fnames[$i]) and empty($lnames[$i])) continue;	

			$insQ = "INSERT INTO custdata (CardNo,personNum,LastName,FirstName,CashBack,Balance,Discount,
				MemDiscountLimit,ChargeOk,WriteChecks,StoreCoupons,Type,memType,staff,SSI,
				Purchases,NumberOfChecks,memCoupons,blueLine,Shown)
				SELECT CardNo,$pn,'".$lnames[$i]."','".$fnames[$i]."',CashBack,Balance,Discount,MemDiscountLimit,
				ChargeOk,WriteChecks,StoreCoupons,Type,memType,staff,SSI,Purchases,
				NumberOfChecks,memCoupons,'$cn ".$lnames[$i]."',Shown FROM custdata WHERE
				CardNo=$cn AND personNum=1";
			$success = $dbc->query($insQ);
		}

			
		if ($success) { // If the query was successful.
				
			echo '<div id="alert"><p>The account has been edited.</p></div>';
			// echo "$query1";	
		} else { // The query was unsuccessful.
				
			echo '<div id="alert"><p class="error">There are two possibilities:<br />
			<b>1.)</b> The household could not be edited due to a system error.<br />
			<b>2.)</b> Nothing was changed.</p></div>';
			// echo '<p>' . mysql_error() . '<br /><br />Query: ' . $query1 . '</p>';
		}
	} else { // Report the errors.
		
		echo '<div id="alert"><p class="error">Error!!<br />
		The following error(s) occurred:<br />';
		foreach ($errors as $msg) { // Print each error.
			echo " - $msg<br />\n";
		}
		echo '</p><p>Please try again.</p><p><br /></p></div>';
			
	} // End of if (empty($errors)) IF.
		
} // End of submit conditional.

// Always show the form.

// Retrieve the user's information.
$query = "SELECT * FROM custdata WHERE cardno=$cn ORDER BY personNum ASC";
$result = $dbc->query($query);

$query = "SELECT street,last_name as street2,city,state,zip,phone,email_1,email_2,ads_OK
	FROM meminfo WHERE card_no=$cn";
$contactR = $dbc->query($query);
$contact = $dbc->fetch_row($contactR);

$num_rows = $dbc->num_rows($result);
echo '<h2>Edit a Household.</h2>
<h3>Card Number: ' . $cn . '</h3>';
// Get the user's information.
$row = $dbc->fetch_array($result);
$query3 = "SELECT memtype, memDesc FROM memtype ORDER BY memtype ASC";
$result3 = $dbc->query($query3);
$balQ = "SELECT balance FROM memchargebalance WHERE cardNo=$cn";
$bal = array_pop($dbc->fetch_row($dbc->query($balQ)));

echo '<form action="modify_household.php" method="post">
<fieldset><legend>Primary shareholder</legend>
<table><tr>
<td>First Name:</td><td><input type="text" name=first_name[] size="15" maxlength="15" value="' . $row["FirstName"] . '" /></td>
<td>Last Name:</td><td><input type="text" name=last_name[] size="15" maxlength="30" value="' . $row["LastName"] . '" /></td>
</tr><tr>
<td>Address 1:</td><td colspan=3><input type="text" name=address1 size=25 maxlength=30 value="'.$contact['street'].'" /></td>
<td>Gets mailings:</td><td><input type=checkbox name=mailings '.($contact['ads_OK']==1?'checked':'').' /></td>
</tr><tr>
<td>Address 2:</td><td colspan=3><input type="text" name=address2 size=25 maxlength=30 value="'.$contact['street2'].'" /></td>
</tr><tr>
<td>City:</td><td><input type=text name=city size=15 maxlength=20 value="'.$contact['city'].'" /></td>
<td>State:</td><td><input type=text name=state size=15 maxlength=2 value="'.$contact['state'].'" /></td>
<td>Zip:</td><td><input type=text name=zip size=8 maxlength=10 value="'.$contact['zip'].'" /></td>
</tr><tr>
<td>Phone:</td><td><input type=text name=phone size=15 maxlength=30 value="'.$contact['phone'].'" /></td>
<td>Email:</td><td colspan=3><input type=text name=email size=25 maxlength=50 value="'.$contact['email_1'].'" /></td>
</tr></tr>
<td>Alt. Phone:</td><td><input type=text name=phone2 size=15 maxlength=30 value="'.$contact['email_2'].'" /></td>
<td>Type:</td><td><select name="memtype">';
while ($row3 = $dbc->fetch_array($result3)) {
	echo '<option value='. $row3['memtype'];
	if ($row3['memtype'] == $row['memType']) {echo ' SELECTED';}
	echo '>' . $row3['memDesc'];
}
echo '</select></td>';
echo '<td>Discount:</td><td><input type=text name=discount size=8 value="'.$row["Discount"].'" /></td>
</tr><tr>
<td>Charge Limit:</td><td><input type=text name=limit size=8 value="'.$row["MemDiscountLimit"].'" /></td>
<td>Balance:</td><td>'.sprintf("%.2f",$bal).'</td>
</tr>
</table>
</fieldset>';
echo '<input type="hidden" name="id[]" value="' . $row['id'] . '" /><br />';

echo '<fieldset><legend>Household members</legend>';
echo '<table>';
for($i=1; $i<$FANNIE_NAMES_PER_MEM; $i++){
	$fn = "";
	$ln = "";
	$id = "";
	$row = $dbc->fetch_array($result);
	if ($row){
		$fn = $row['FirstName'];
		$ln = $row['LastName'];
		$id = $row['id'];
	}
	printf("<tr><td>First name:</td><td><input type=\"text\" name=first_name[] size=15 maxlength=15 value=\"%s\" /></td>
		<td>Last name:</td><td><input type=\"text\" name=last_name[] size=15 maxlength=30 value=\"%s\" /></td></tr>
		<input type=hidden name=id[] value=\"%s\" />",$fn,$ln,$id);
}
echo '</table></fieldset>';
echo '<p><input type="submit" name="submit" value="Submit" /></p>
<input type="hidden" name="submitted" value="TRUE" />
<input type="hidden" name="cardno" value="' . $cn . '" /></form><br /><br />';

include ('./includes/footer.html');
include('../src/footer.html');
?>
