<?php
include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
$header = "Miscellaneous Payment";
$page_title = "Fannie :: Misc Payment";
include($FANNIE_ROOT.'src/header.html');

$LANE_NO=30;
$EMP_NO=1001;
$DEFAULT_DEPT=703;
$CARD_NO=11;

if (isset($_REQUEST['init'])){
	$errors = "";
	if (!isset($_REQUEST['desc']) || empty($_REQUEST['desc'])){
		$errors	.= "Error: Description required<br />";
	}
	if (!isset($_REQUEST['amount']) || !is_numeric($_REQUEST['amount'])){
		$errors .= "Error: amount is required<br />";
	}
	if (!isset($_REQUEST['dept'])){
		$errors .= "Error: department is required<br />";
	}
	if (!isset($_REQUEST['tender'])){
		$errors .= "Error: tender is required<br />";
	}

	if (empty($errors)){
		billingDisplay();
	}
	else {
		echo "<blockquote><i>".$errors."</i></blockquote>";
		regularDisplay();
	}
}
elseif (isset($_REQUEST['confirm'])){
	// these tests should always pass unless someone is
	// POST-ing data without using the form
	$errors = "";
	if (!isset($_REQUEST['desc']) || empty($_REQUEST['desc'])){
		$errors	.= "Error: Description required<br />";
	}
	if (!isset($_REQUEST['amount']) || !is_numeric($_REQUEST['amount'])){
		$errors .= "Error: amount is required<br />";
	}
	if (!isset($_REQUEST['dept'])){
		$errors .= "Error: department is required<br />";
	}
	if (!isset($_REQUEST['tender'])){
		$errors .= "Error: tender is required<br />";
	}

	if (empty($errors)){
		bill($_REQUEST['amount'],$_REQUEST['desc'],
			$_REQUEST['dept'],$_REQUEST['tender']);
	}
	else {
		echo "<blockquote><i>".$errors."</i></blockquote>";
		regularDisplay();
	}
}
else {
	regularDisplay();
}

function regularDisplay(){
	global $dbc,$DEFAULT_DEPT;
	echo "<form action=index.php method=post>
		<table><tr><td>
		<b>Description</b></td><td>
		<input maxlength=30 type=text id=desc name=desc />
		</td></tr><tr><td><b>Amount</b></td><td>
		\$<input type=text name=amount /></td></tr>
		<tr><td><b>Department</b></td>
		<td><select name=dept>";
	$numsQ = "SELECT dept_no,dept_name FROM departments 
		ORDER BY dept_no";
	$numsR = $dbc->query($numsQ);
	while($numsW = $dbc->fetch_row($numsR)){
		printf("<option value=%d %s>%d %s</option>",
			$numsW[0],
			($numsW[0]==$DEFAULT_DEPT?'selected':''),
			$numsW[0],$numsW[1]);	
	}
	echo "</select></td></tr>
		<tr><td><b>Tender Type</b></td>
		<td><select name=tender>";
	$numsQ = "SELECT TenderCode,TenderName FROM tenders 
		ORDER BY TenderName";
	$numsR = $dbc->query($numsQ);
	while($numsW = $dbc->fetch_row($numsR)){
		printf("<option value=%s>%s</option>",$numsW[0],$numsW[1]);	
	}
	echo "</select></td></tr><tr><td>
		<input type=submit name=init value=Submit />
		</td></tr></table></form>";
}

function billingDisplay(){
	printf("<form action=index.php method=post>
		<table cellpadding=4 cellspacing=0 border=1>
		<tr>
			<th>Description</th>
			<td>%s<input type=hidden name=desc value=\"%s\" /></td>
			<th>Department</th>
			<td>%d<input type=hidden name=dept value=\"%d\" /></td>
		</tr>
		<tr>
			<th>Tender</th>
			<td>%s<input type=hidden name=tender value=\"%s\" /></td>
			<th>Amount</th>
			<td>%.2f<input type=hidden name=amount value=\"%.2f\" /></td>
		</tr>
		</table>
		<input type=submit value=\"Make Payment\" name=confirm />
		</form>",
		$_REQUEST['desc'],$_REQUEST['desc'],
		$_REQUEST['dept'],$_REQUEST['dept'],
		$_REQUEST['tender'],$_REQUEST['tender'],
		$_REQUEST['amount'],$_REQUEST['amount']);
}

function bill($amt,$desc,$dept,$tender){
	global $dbc,$EMP_NO,$LANE_NO,$CARD_NO;

	$transQ = "SELECT MAX(trans_no) FROM dtransactions
		WHERE emp_no=$EMP_NO AND register_no=$LANE_NO";
	$transR = $dbc->query($transQ);
	$t_no = array_pop($dbc->fetch_array($transR));
	if ($t_no == "") $t_no = 1;
	else $t_no++;

	$tnQ = "SELECT TenderName FROM tenders WHERE TenderCode=".$dbc->escape($tender);
	$tnR = $dbc->query($tnQ);
	$tn = array_pop($dbc->fetch_array($tnR));

	$insQ = sprintf("INSERT INTO dtransactions VALUES (
		%s,$LANE_NO,$EMP_NO,$t_no,
		'%.2fDP%d',%s,'D','','',%d,
		1.0,0,0.00,%.2f,%.2f,%.2f,0,0,.0,.0,
		0,0,0,NULL,0.0,0,0,.0,0,0,0,0,0,'',
		%d,1)",$dbc->now(),$amt,$dept,$dbc->escape($desc),
		$dept,$amt,$amt,$amt,$CARD_NO);
	$amt *= -1;
	$insQ2 = sprintf("INSERT INTO dtransactions VALUES (
		%s,$LANE_NO,$EMP_NO,$t_no,
		0,%s,'T',%s,0,0,
		0.0,0,0.00,.0,%.2f,.0,0,0,.0,.0,
		0,0,0,NULL,0.0,0,0,.0,0,0,0,0,0,'',
		%d,2)",$dbc->now(),$dbc->escape($tn),$dbc->escape($tender),
		$amt,$CARD_NO);
	$dbc->query($insQ);
	$dbc->query($insQ2);

	printf("Receipt is %d-%d-%d.",
		$EMP_NO,$LANE_NO,$t_no);
}

include($FANNIE_ROOT.'src/footer.html');
?>
