<?php
include('../../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include($FANNIE_ROOT.'src/Credentials/OutsideDB.is4c.php');

$header = "Annual Meeting Registration";
$page_title = "Annual Meeting Registration";

include($FANNIE_ROOT.'src/header.html');
if (isset($_REQUEST['card_no'])){
	if (isset($_REQUEST['newBtn'])){
		$q = sprintf("INSERT INTO registrations (tdate,card_no,name,phone,email,
			guest_count,child_count,paid) VALUES (%s,%d,%s,%s,%s,%d,%d,0)",
			$dbc->now(),$_REQUEST['card_no'],$dbc->escape($_REQUEST['fullname']),
			$dbc->escape($_REQUEST['ph']),$dbc->escape($_REQUEST['email']),
			$_REQUEST['guests'],$_REQUEST['kids']);
		$r = $dbc->query($q);
		$dbc->query(sprintf("DELETE FROM regMeals WHERE card_no=%d",$_REQUEST['card_no']));
		$q = sprintf("INSERT INTO regMeals (card_no,type,subtype) VALUES (%d,'OWNER',%d)",
			$_REQUEST['card_no'],$_REQUEST['meals'][0]);
		$r = $dbc->query($q);
		for($i=0;$i<$_REQUEST['guests'];$i++){
			$q = sprintf("INSERT INTO regMeals (card_no,type,subtype) VALUES (%d,'GUEST',1)",
				$_REQUEST['card_no']);
			$r = $dbc->query($q);
		}

		$dbc->query(sprintf("INSERT INTO regNotes VALUES (%d,NOW(),%s)",
			$_REQUEST['card_no'],$dbc->escape($_REQUEST['regNotes'])
		));	
	}
	else if (isset($_REQUEST['upBtn'])){
		$q = sprintf("UPDATE registrations SET name=%s,phone=%s,email=%s,guest_count=%d,
			child_count=%d WHERE card_no=%d",$dbc->escape($_REQUEST['fullname']),
			$dbc->escape($_REQUEST['ph']),$dbc->escape($_REQUEST['email']),
			$_REQUEST['guests'],$_REQUEST['kids'],$_REQUEST['card_no']);
		$r = $dbc->query($q);
		$dbc->query(sprintf("DELETE FROM regMeals WHERE card_no=%d",$_REQUEST['card_no']));
		for($i=0;$i<count($_REQUEST['meals']) && $i < $_REQUEST['guests']+1;$i++){
			$q = sprintf("INSERT INTO regMeals (card_no,type,subtype) VALUES (%d,'%s',%d)",
				$_REQUEST['card_no'],($i==0?'OWNER':'GUEST'),$_REQUEST['meals'][$i]);
			$r = $dbc->query($q);
		}
		for($i=0;$i<count($_REQUEST['kmeals']);$i++){
			if ($_REQUEST['kmeals'][$i] != 1) continue;
			$q = sprintf("INSERT INTO regMeals (card_no,type,subtype) VALUES (%d,'CHILD',1)",
				$_REQUEST['card_no']);
			$r = $dbc->query($q);
		}

		if (empty($_REQUEST['fullname']) && empty($_REQUEST['ph']) && empty($_REQUEST['email'])){
			$dbc->query(sprintf("DELETE FROM registrations WHERE card_no=%d",$_REQUEST['card_no']));
			$dbc->query(sprintf("DELETE FROM regMeals WHERE card_no=%d",$_REQUEST['card_no']));
		}

		$dbc->query(sprintf("DELETE FROM regNotes WHERE card_no=%d",$_REQUEST['card_no']));
		$dbc->query(sprintf("INSERT INTO regNotes VALUES (%d,NOW(),%s)",
			$_REQUEST['card_no'],$dbc->escape($_REQUEST['regNotes'])
		));	
	}
	echo '<i>Registration Saved</i><p />';
	showForm($_REQUEST['card_no']);
}
else if (isset($_REQUEST['memnum'])){
	if (!empty($_REQUEST['memnum'])){
		$q1 = sprintf("SELECT CardNo FROM custdata WHERE CardNo=%d",$_REQUEST['memnum']);
		$r1 = $dbc->query($q1);
		$cn = -1;
		if ($dbc->num_rows($r1) == 0){
			$upc = str_pad($_REQUEST['memnum'],13,'0',STR_PAD_LEFT);
			$q2 = sprintf("SELECT card_no membercards WHERE upc=%s",$dbc->escape($upc));
			$r2 = $dbc->query($q2);
			if ($dbc->num_rows($r2)==0){
				echo 'Account not found<br /><br />';
				echo '<input type="submit" 
					onclick="location=\'index.php\';return false;"
					value="Go Back" />';
			}
			else
				$cn = array_pop($dbc->fetch_row($r2));
		}
		else
			$cn = array_pop($dbc->fetch_row($r1));
		if ($cn != -1)
			showForm($cn);
	}
	else if (!empty($_REQUEST['ln'])){
		$q1 = sprintf("SELECT CardNo,LastName,FirstName FROM custdata WHERE LastName LIKE %s",
			$dbc->escape($_REQUEST['ln'].'%'));
		if (!empty($_REQUEST['fn']))
			$q1 .= sprintf(" AND FirstName LIKE %s",$dbc->escape($_REQUEST['fn'].'%'));
		$r1 = $dbc->query($q1);
		if ($dbc->num_rows($r1) == 1){
			showForm(array_pop($dbc->fetch_row($r1)));
		}
		else if ($dbc->num_rows($r1) == 0){
			$q2 = sprintf("SELECT CardNo,LastName,FirstName FROM custdata WHERE LastName LIKE %s",
				$dbc->escape('%'.$_REQUEST['ln'].'%'));
			if (!empty($_REQUEST['fn']))
				$q2 .= sprintf(" AND FirstName LIKE %s",$dbc->escape('%'.$_REQUEST['fn'].'%'));
			$r2 = $dbc->query($q2);
			if ($dbc->num_rows($r2) == 1){
				showForm(array_pop($dbc->fetch_row($r2)));
			}
			else if ($dbc->num_rows($r2) == 0){
				echo 'Account not found<br /><br />';
				echo '<input type="submit" 
					onclick="location=\'index.php\';return false;"
					value="Go Back" />';
			}
			else
				multipleMatches($r2);
		}
		else
			multipleMatches($r1);
	}
}
else {
	echo '<form action="index.php" method="post">';
	echo '<b># or UPC</b> <input type="text" name="memnum" /><br /><br />';
	echo '<b>Last Name</b> <input type="text" name="ln" /> ';
	echo '<b>First Name</b> <input type="text" name="fn" /> ';
	echo '<br /><br />';
	echo '<input type="submit" value="Submit" />';
	echo '</form>';
}
include($FANNIE_ROOT.'src/footer.html');

function showForm($cn){
	global $dbc;
	$chkQ = sprintf("SELECT * FROM registrations WHERE card_no=%d",$cn);
	$chkR = $dbc->query($chkQ);
	if ($dbc->num_rows($chkR)==0){
		showNewForm($cn);
	}
	else{
		$data = $dbc->fetch_row($chkR);
		showUpdateForm($data);
	}
}

function showNewForm($cn){
	echo '<form action="index.php" method="post">';
	printf('<input type="hidden" name="card_no" value="%d" />',$cn);
	echo '<table cellpadding="4" cellspacing="4" border="0">';
	echo '<tr><th>Owner #</th><td>'.$cn.'</td></tr>';
	echo '<tr><th>Name</th>';
	echo '<td><input type="text" name="fullname" /></td></tr>';
	echo '<tr><th>Ph #</th>';
	echo '<td><input type="text" name="ph" /></td></tr>';
	echo '<tr><th>Email</th>';
	echo '<td><input type="text" name="email" /></td></tr>';
	echo '<tr><th># Guests</th>';
	echo '<td><input type="text" name="guests" /></td></tr>';
	echo '<tr><th># Kids</th>';
	echo '<td><input type="text" name="kids" /></td></tr>';
	echo '<tr><th>Notes</th>';
	echo '<td><textarea name="regNotes" rows="3" cols="25"></textarea></td>';
	echo '</table><hr />';
	echo '<b>Owner Meal</b>: <select name=meals[]>';
	echo '<option value=1>Chicken</option>';
	echo '<option value=2>Curry</option>';
	echo '</select>';
	echo '<br /><br />';
	echo '<input type="submit" name="newBtn" value="Save Registration" />';
	echo '&nbsp;&nbsp;&nbsp;&nbsp;';
	echo '<input type="submit" value="Registration Home" onclick="location=\'index.php\';return false;" />';
	echo '</form>';
}

function showUpdateForm($row){
	global $dbc;
	if (True || $row['paid']==0){
		echo '<form action="index.php" method="post">';
	}
	else {
		echo '<em>This registration was paid online</em>';
	}
        printf('<input type="hidden" name="card_no" value="%d" />',$row['card_no']);
        echo '<table cellpadding="4" cellspacing="4" border="0">';
        echo '<tr><th>Owner #</th><td>'.$row['card_no'].'</td></tr>';
        echo '<tr><th>Name</th>';
        printf('<td><input type="text" name="fullname" value="%s" /></td></tr>',$row['name']);
        echo '<tr><th>Ph #</th>';
        printf('<td><input type="text" name="ph" value="%s" /></td></tr>',$row['phone']);
        echo '<tr><th>Email</th>';
        printf('<td><input type="text" name="email" value="%s" /></td></tr>',$row['email']);
        echo '<tr><th># Guests</th>';
        printf('<td><input type="text" name="guests" value="%d" /></td></tr>',$row['guest_count']);
        echo '<tr><th># Kids</th>';
        printf('<td><input type="text" name="kids" value="%d" /></td></tr>',$row['child_count']);
	$nQ = "SELECT notes FROM regNotes WHERE card_no=".$row['card_no'];
	$nR = $dbc->query($nQ);
	$notes = array_pop($dbc->fetch_row($nR));
	echo '<tr><th>Notes</th>';
	echo '<td><textarea name="regNotes" rows="3" cols="25">'.$notes.'</textarea></td>';
        echo '</table><hr />';
	$m1Q = sprintf("SELECT subtype FROM regMeals WHERE card_no=%d AND type='OWNER'",$row['card_no']);
	$m2Q = sprintf("SELECT subtype FROM regMeals WHERE card_no=%d AND type='GUEST'",$row['card_no']);
	$m3Q = sprintf("SELECT * FROM regMeals WHERE card_no=%d AND type='CHILD'",$row['card_no']);
	$m1R = $dbc->query($m1Q);
	$m2R = $dbc->query($m2Q);
	$m3R = $dbc->query($m3Q);
	$meals = array(1=>"Chicken",2=>"Curry");
        echo '<b>Owner Meal</b>: <select name=meals[]>';
	$om = array_pop($dbc->fetch_row($m1R));
	foreach($meals as $k=>$v){
		printf("<option value=%d %s>%s</option>",$k,
			($k==$om?'selected':''),$v);
	}
        echo '</select>';
        echo '<br /><br />';
	for($i=0;$i<$dbc->num_rows($m2R);$i++){
		$w = $dbc->fetch_row($m2R);
		echo '<b>Guest Meal '.($i+1).'</b>: <select name=meals[]>';
		foreach($meals as $k=>$v){
			printf("<option value=%d %s>%s</option>",$k,
				($k==$w[0]?'selected':''),$v);
		}
		echo '</select>';
		echo '<br /><br />';
	}
	$kids = 0;
	for($kids;$kids<$dbc->num_rows($m3R);$kids++){
		echo '<b>Child Meal '.($kids+1).'</b>: <select name=kmeals[]>';
		echo '<option value=1 selected>Spaghetti</option>';
		echo '<option value=2>None</option>';
		echo '</select>';
		echo '<br /><br />';
	}
	for($kids;$kids<$row['child_count'];$kids++){
		echo '<b>Child Meal '.($kids+1).'</b>: <select name=kmeals[]>';
		echo '<option value=1>Spaghetti</option>';
		echo '<option value=2 selected>None</option>';
		echo '</select>';
		echo '<br /><br />';
	}

	if (True || $row['paid'] == 0){
		echo '<input type="submit" value="Save Registration" name="upBtn" />';
		echo '&nbsp;&nbsp;&nbsp;&nbsp;';
		echo '<input type="submit" value="Registration Home" onclick="location=\'index.php\';return false;" />';
		echo '&nbsp;&nbsp;&nbsp;&nbsp;';
		echo '<input type="submit" value="Print Confirmation" onclick="location=\'confirm.php?card_no='.$row['card_no'].'\';return false;" />';
		echo '</form>';
	}
}

function multipleMatches($r){
	global $dbc;
	echo '<b>Multiple matching accounts: </b>';
	echo '<select onchange="location=\'index.php?card_no=\'+this.value;">';
	echo '<option>Choose...</option>';
	while($w = $dbc->fetch_row($r)){
		printf("<option value=%d>%d %s %s</option>",$w['CardNo'],
			$w['CardNo'],$w['FirstName'],$w['LastName']);
	}
	echo '</select><br /><br />';
	echo '<input type="submit" 
		onclick="location=\'index.php\';return false;"
		value="Go Back" />';
}

?>
