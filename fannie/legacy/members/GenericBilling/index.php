<html>
<head>
	<title>Generic AR Billing</title>
</head>
<body>
<?php
include('../../../config.php');

require($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');
$LANE_NO=30;
$EMP_NO=1001;

if (isset($_REQUEST['memnum'])){  
	if(isset($_REQUEST['amount']) && empty($_REQUEST['desc'])){
		echo "<i>Billing description (For) required</i><p />";
		unset($_REQUEST['amount']);
	}
	elseif(empty($_REQUEST['amount']) && isset($_REQUEST['desc'])){
		echo "<i>Billing amount required</i><p />";
		unset($_REQUEST['desc']);
	}
}

regularDisplay();

if (isset($_REQUEST['amount']))
	bill($_REQUEST['memnum'],$_REQUEST['amount'],$_REQUEST['desc']);
elseif (isset($_REQUEST['memnum']))
	billingDisplay($_REQUEST['memnum']);

function regularDisplay(){
	global $sql;
	$value = isset($_REQUEST['memnum'])?$_REQUEST['memnum']:'6000';
	echo "<form action=index.php method=get>
		<b>Member #</b>:
		<input type=text id=memnum name=memnum value=$value />
		<select onchange=\"document.getElementById('memnum').value=this.value;\">";
	$numsQ = "SELECT cardno,lastname FROM custdata WHERE
		memtype = 2
		AND personnum=1
		ORDER BY cardno";
	$numsR = $sql->query($numsQ);
	while($numsW = $sql->fetch_row($numsR)){
		if ($value == trim($numsW[0]))
			printf("<option value=%d selected>%d %s</option>",$numsW[0],$numsW[0],$numsW[1]);	
		else
			printf("<option value=%d>%d %s</option>",$numsW[0],$numsW[0],$numsW[1]);	
	}
	echo "</select>
		<input type=submit value=Submit />
		</form><hr />";
}

function billingDisplay($cardno){
	global $sql;

	$query = "SELECT c.CardNo,c.LastName,n.balance
		FROM custdata AS c LEFT JOIN
		is4c_trans.ar_live_balance AS n ON c.CardNo=n.card_no
		WHERE c.CardNo=$cardno AND c.personNum=1";
	$result = $sql->query($query);
	$row = $sql->fetch_row($result);

	printf("<form action=index.php method=post>
		<table cellpadding=4 cellspacing=0 border=1>
		<tr>
			<th>Member</th>
			<td>%d<input type=hidden name=memnum value=%d /></td>
			<th>Name</th>
			<td>%s</td>
		</tr>
		<tr>
			<th>Current Balance</th>
			<td>%.2f</td>
			<th>Bill</th>
			<td>$<input type=text size=5 name=amount /></td>
		</tr>
		<tr>
			<th>For</th>
			<td colspan=3><input type=text maxlength=35 name=desc /></td>
		</tr>
		</table>
		<input type=submit value=\"Bill Account\" />
		</form>",
		$row[0],$row[0],$row[1],$row[2]);
}

function bill($cardno,$amt,$desc){
	global $sql,$EMP_NO,$LANE_NO,$FANNIE_TRANS_DB;
	$sql->query("use $FANNIE_TRANS_DB");

	$desc = str_replace("'","''",$desc);

	$transQ = "SELECT MAX(trans_no) FROM dtransactions
		WHERE emp_no=$EMP_NO AND register_no=$LANE_NO";
	$transR = $sql->query($transQ);
	$t_no = array_pop($sql->fetch_array($transR));
	if ($t_no == "") $t_no = 1;
	else $t_no++;

	$insQ = "INSERT INTO dtransactions VALUES (
		".$sql->now().",0,0,$LANE_NO,$EMP_NO,$t_no,
		'{$amt}DP703','$desc','D','','',703,
		1.0,0,0.00,$amt,$amt,$amt,0,0,.0,.0,
		0,0,0,NULL,0.0,0,0,.0,0,0,0,0,0,'',
		$cardno,1)";
	$amt *= -1;
	$insQ2 = "INSERT INTO dtransactions VALUES (
		".$sql->now().",0,0,$LANE_NO,$EMP_NO,$t_no,
		0,'InStore Charges','T','MI',0,0,
		0.0,0,0.00,.0,$amt,.0,0,0,.0,.0,
		0,0,0,NULL,0.0,0,0,.0,0,0,0,0,0,'',
		$cardno,2)";
	$sql->query($insQ);
	$sql->query($insQ2);

	printf("Member <b>%d</b> billed <b>$%.2f</b>.<br />
		Receipt is %d-%d-%d.",$cardno,$amt*-1,
		$EMP_NO,$LANE_NO,$t_no);
}

?>

</body>
</html>
