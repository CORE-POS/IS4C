<?php
include('print.php');
include('db.php');

ini_set('display_errors','on');
set_time_limit(0);

/*
$in = array();
$in['meals'] = array('meat');
$in['card_no'] = 10000;

print_info($in);
*/

if (isset($_REQUEST['cn'])){
	$cn = $_REQUEST['cn'];
	$found = False;
	$db = db();
	if (is_numeric($cn)){
		$q = sprintf("SELECT CardNo FROM custdata
			WHERE CardNo=%d",$cn);
		$r = $db->query($q);
		if ($db->num_rows($r) > 0)
			$found = array_pop($db->fetch_row($r));
		else {
			$q2 = sprintf("SELECT card_no FROM
				membercards WHERE upc='%s'",
				str_pad($cn,13,'0',STR_PAD_LEFT));
			$r2 = $db->query($q2);
			if ($db->num_rows($r2) > 0)
				$found = array_pop($db->fetch_row($r2));
		}
	}
	else {
		$q = sprintf("SELECT CardNo,LastName,FirstName 
			FROM custdata WHERE LastName LIKE '%s%%'
			ORDER BY LastName,FirstName",
			$db->escape($cn));
		$r = $db->query($q);
		if ($db->num_rows($r) > 0){
			$found = True;
		?>
		<form action="index.php" method="get">
		<b>Multiple Matches</b>:<br />
		<select name="cn">
		<?php while($w = $db->fetch_row($r)){
			printf('<option value="%d">%s, %s</option>',
				$w['CardNo'],$w['LastName'],
				$w['FirstName']);
		} ?>
		</select>
		<input type="submit" value="Proceed" />
		</form>
		<?php
		}
	}

	if ($found !== False){
		if ($found !== True){
			$chkQ = sprintf("SELECT card_no FROM
				registrations WHERE card_no=%d",
				$found);
			$chkR = $db->query($chkQ);
			if ($db->num_rows($chkR) == 0)
				header("Location: new.php?cn=".$found);
			else
				header("Location: edit.php?cn=".$found);
		}
		exit;
	}
	else {
		echo '<div><i>No owner found</i></div>';
	}
}
?>
<body onload="document.getElementById('cn').focus();">
<form action="index.php" method="get">
<b>Enter owner# or card# or last name</b>:<br />
<input type="text" id="cn" name="cn" />
<input type="submit" value="Proceed" />
</form>
</body>
