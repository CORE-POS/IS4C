<?php
include('../../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include($FANNIE_ROOT.'src/Credentials/OutsideDB.is4c.php');

$header = "Annual Meeting Confirmation";
$page_title = "Annual Meeting Confirmation";


if (isset($_REQUEST['card_no'])){
	showForm($_REQUEST['card_no']);
}

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
	echo 'No registration found!';
}

function showUpdateForm($row){
	global $dbc;
	echo '<h2>2012 Annual Meeting Confirmation</h2>';
        printf('<input type="hidden" name="card_no" value="%d" />',$row['card_no']);
        echo '<table cellpadding="4" cellspacing="4" border="0">';
        echo '<tr><th>Owner #</th><td>'.$row['card_no'].'</td></tr>';
        echo '<tr><th>Name</th>';
        printf('<td>%s</td></tr>',$row['name']);
        echo '<tr><th>Ph #</th>';
        printf('<td>%s</td></tr>',$row['phone']);
        echo '<tr><th>Email</th>';
        printf('<td>%s</td></tr>',$row['email']);
        echo '<tr><th># Guests</th>';
        printf('<td>%d</td></tr>',$row['guest_count']);
        echo '<tr><th># Kids</th>';
        printf('<td>%d</td></tr>',$row['child_count']);
	$nQ = "SELECT notes FROM regNotes WHERE card_no=".$row['card_no'];
	$nR = $dbc->query($nQ);
	$notes = array_pop($dbc->fetch_row($nR));
	echo '<tr><th>Notes</th>';
	echo '<td>'.str_replace("\n","<br />",$notes).'</td>';
        echo '</table><hr />';
	$m1Q = sprintf("SELECT subtype FROM regMeals WHERE card_no=%d AND type='OWNER'",$row['card_no']);
	$m2Q = sprintf("SELECT subtype FROM regMeals WHERE card_no=%d AND type='GUEST'",$row['card_no']);
	$m3Q = sprintf("SELECT * FROM regMeals WHERE card_no=%d AND type='CHILD'",$row['card_no']);
	$m1R = $dbc->query($m1Q);
	$m2R = $dbc->query($m2Q);
	$m3R = $dbc->query($m3Q);
	$meals = array(1=>"Chicken",2=>"Curry");
        echo '<b>Owner Meal</b>: ';
	$om = array_pop($dbc->fetch_row($m1R));
	foreach($meals as $k=>$v){
		if ($k==$om) echo $v;
	}
        echo '<br /><br />';
	for($i=0;$i<$dbc->num_rows($m2R);$i++){
		$w = $dbc->fetch_row($m2R);
		echo '<b>Guest Meal '.($i+1).'</b>: ';
		foreach($meals as $k=>$v){
			if ($k==$w[0]) echo $v;
		}
		echo '<br /><br />';
	}
	$kids = 0;
	for($kids;$kids<$dbc->num_rows($m3R);$kids++){
		echo '<b>Child Meal '.($kids+1).'</b>: Spaghetti';
		echo '<br /><br />';
	}
	for($kids;$kids<$row['child_count'];$kids++){
		echo '<b>Child Meal '.($kids+1).'</b>: None';
		echo '<br /><br />';
	}
}

?>
