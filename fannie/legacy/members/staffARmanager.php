<?php
include('../../config.php');

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../db.php');
$mysql = new SQLManager('mysql.wfco-op.store','MYSQL','payroll1','root');

/* delete is easy
 * just delete from staffID and staffAR
 */
if (isset($_POST['remove'])){
	$cardno = $_POST['cardno'];
	$delQ = "delete from staffID where cardno=$cardno";
	$delR = $sql->query($delQ);
	$delQ = "delete from staffAR where cardno=$cardno";
	$delQ = $sql->query($delQ);
	echo "Member #$cardno removed from staff AR<p />";
}
/* add an employee to staffAR
 * this requires an ADP ID, which I attempt to find using
 * first and last names, otherwise the user is prompted to
 * enter the ADP ID 
 * redeux: just lastname. employees on nexus tends to have full[er] names
 * and middle initials
 */
if (isset($_POST['add'])){
	$cardno = $_POST['cardno'];
	
	$namesQ = "select firstname,lastname from custdata where cardno=$cardno and personnum=1";
	$namesR = $sql->query($namesQ);
	$namesW = $sql->fetch_array($namesR);
	$fname = $namesW[0];
	$lname = $namesW[1];
	
	$findQ = "select adpID from employees where lastName='$lname'";
	//$mysql->select_db('payroll1');
	$findR = $mysql->query($findQ);
	switch($mysql->num_rows($findR)){
	case 0: // no employee found with that first & last name
		echo "Enter the employee's ADP ID#<br />";
		echo "<form method=post action=staffARmanager.php>";
		echo "<input type=text name=adpID value=100 /> ";
		echo "<input type=submit value=Submit />";
		echo "<input type=hidden name=cardno value=$cardno />";
		echo "</form>";
		return; // not done adding yet
		break;
	case 1: // one employee found - use that adp id
		$findW = $mysql->fetch_array($findR);
		$adpID = '10'.$findW[0];
		$insQ = "insert into staffID values ($cardno,$adpID,1)";
		$insR = $sql->query($insQ);
		balance($cardno);
		echo "Member #$cardno added to staff AR";
		break;
	default: // more than 1 found - offer a choice amongst the
			// adp ids (or none of them)
		echo "Which of these adpIDs is correct?<br />";
		echo "<form method=post action=staffARmanager.php>";
		echo "<select name=adpID>";
		while ($findW = $mysql->fetch_array($findR))
			echo "<option>10".$findW[0]."</option>";
		echo "<option>None of these</option>";
		echo "</select> ";
		echo "<input type=submit value=Submit />";
		echo "<input type=hidden name=cardno value=$cardno />";
		echo "</form>";
		return; // not done adding so don't display main form'
		break;
	}
}
/* adp id wasn't found, so a form of
 * some kind was submitted to fill it in
 */
 if (isset($_POST['adpID'])){
	$cardno = $_POST['cardno'];
	$adpID = $_POST['adpID'];
	// the user provided an adp id
	if ($adpID != 'None of these'){
		$insQ = "insert into staffID values ($cardno,$adpID,1)";
		$insR = $sql->query($insQ);
		balance($cardno);
		echo "Member #$cardno added to staff AR";
	}
	// the user didn't like the possible choices presented, give
	// manual entry form
	else {
		echo "Enter the employee's ADP ID#<br />";
		echo "<form method=post action=staffARmanager.php>";
		echo "<input type=text name=adpID value=100 /> ";
		echo "<input type=submit value=Submit />";
		echo "<input type=hidden name=cardno value=$cardno />";
		echo "</form>";
		return; // not done adding yet
	}
}

// add the correct balance for the cardno to staffAR
function balance($cardno){
	global $sql;
	$balanceQ = "INSERT INTO staffAR
                 	SELECT
                 	cardno,
                 	lastname,
                 	firstname,
                 	balance as Ending_Balance
                 	from custdata where cardno=$cardno and personnum=1";
	$balanceR = $sql->query($balanceQ);
}

// main insert / delete form follows
?>
<form action=staffARmanager.php method=post>
<b>Add employee</b>:<br />
Member number: <input type=text name=cardno /> 
<input type=hidden name=add value=add />
<input type=submit value=Add />
</form>
<hr />
<form action=staffARmanager.php method=post>
<b>Remove employee</b>:<br />
Member number: <input type=text name=cardno /> 
<input type=hidden name=remove value=remove />
<input type=submit value=Remove />
</form>
