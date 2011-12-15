<?php
include('../../../config.php');

require($FANNIE_ROOT."src/SQLManager.php");
include($FANNIE_ROOT.'legacy/db.php');
include($FANNIE_ROOT.'src/tmp_dir.php');
require('csv_parser.php');

$PHONE = 2;
$MEMBER = 3;
$SIZE = 4;
$COLOR = 5;
$CONTACT = 0;

$EMP_NO = 1001;
$LANE_NO = 30;

$BILLING_MEMBER = array(
	"A1B/W" => 41.25,
	"A2B/W" => 41.25,
	"B1B/W" => 48.75,
	"B2B/W" => 48.75,
	"CB/W"  => 86.25,
	"DB/W"  => 78.75,
	"EB/W"  => 123.75,
	"FB/W"  => 221.25,
	"GB/W"  => 221.25,
	"1/20B/W" => 41.25,
	"1/15B/W" => 56.25,
	"1/10B/W" => 82.50,
	"1/5B/W" => 165,
	"1/ 5B/W" => 165,
	"1/2B/W" => 412.50,
	"1/ 2B/W" => 412.50,
	"A1FULL" => 56.25,
	"A2FULL" => 56.25,
	"B1FULL" => 63.75,
	"B2FULL" => 63.75,
	"CFULL"  => 101.25,
	"DFULL"  => 93.75,
	"1/20FULL" => 56.25,
	"1/15FULL" => 82.50,
	"1/10FULL"  => 112.50,
	"1/5FULL" => 225,
	"1/ 5FULL" => 225,
	"1/2FULL" => 562.50,
	"1/ 2FULL" => 562.50,
	"EFULL"  => 138.75,
	"FFULL"  => 236.25,	
	"GFULL"  => 236.25
);

$BILLING_NONMEMBER = array(
	"A1B/W" => 55,
	"A2B/W" => 55,
	"B1B/W" => 75,
	"CB/W" => 115,
	"DB/W"  => 110,
	"EB/W"  => 220,
	"FB/W"  => 550,
	"1/20B/W" => 55,
	"1/15B/W" => 75,
	"1/10B/W" => 110,
	"1/5B/W" => 220,
	"1/ 5B/W" => 220,
	"1/2B/W" => 550,
	"1/2 B/W" => 550,
	"A1FULL" => 75,
	"A2FULL" => 75,
	"B1FULL" => 110,
	"B2FULL" => 110,
	"DFULL"  => 150,
	"1/20FULL" => 75,
	"1/15FULL" => 110,
	"1/10FULL"  => 150,
	"1/5FULL" => 300,
	"1/ 5FULL" => 300,
	"1/2FULL" => 750,
	"1/ 2FULL" => 750,
	"EFULL"  => 300,
	"FFULL"  => 750,
	"CFULL"  => 135
);

if (isset($_POST['cardnos'])){
	echo "<b>Date</b>: ".date("m/d/Y")."<br />
		<i>Summary of charges</i><br />
		<table cellspacing=0 cellpadding=3 border=1>
		<tr><th>Account</th><th>Charge</th><th>Receipt #</th></tr>";
	foreach($_POST['cardnos'] as $cardno){
		$amt = $_POST['billable'.$cardno];
		$transQ = "SELECT MAX(trans_no) FROM dtransactions
			WHERE emp_no=$EMP_NO AND register_no=$LANE_NO";
		$transR = $sql->query($transQ);
		$t_no = array_pop($sql->fetch_array($transR));
		if ($t_no == "") $t_no = 1;
		else $t_no++;

		$insQ = "INSERT INTO dtransactions VALUES (
			getdate(),$LANE_NO,$EMP_NO,$t_no,
			'{$amt}DP703','Gazette Ad','D','','',703,
			1.0,0,0,$amt,$amt,$amt,0,0,.0,.0,
			0,0,0,NULL,0.0,0,0,.0,0,0,0,0,
			0,'',$cardno,1)";
		$amt *= -1;
		$insQ2 = "INSERT INTO dtransactions VALUES (
			getdate(),$LANE_NO,$EMP_NO,$t_no,
			0,'InStore Charges','T','MI',0,0,
			0.0,0,0,.0,$amt,.0,0,0,.0,.0,
			0,0,0,NULL,0.0,0,0,.0,0,0,0,0,
			0,'',$cardno,2)";
		$sql->query($insQ);
		$sql->query($insQ2);

		printf("<tr><td>%d</td><td>$%.2f</td><td>%s</td></tr>",
			$cardno,$amt*-1,$EMP_NO."-".$LANE_NO."-".$t_no);
	}
}
else if (isset($_POST['MAX_FILE_SIZE'])){
	$file = tempnam(sys_get_temp_dir(),"GGB");
	move_uploaded_file($_FILES['upload']['tmp_name'],$file);
	$fp = fopen($file,"r");
	echo "<b>Gazette Billing Preview</b><br />
		<table cellspacing=0 cellpadding=3 border=1><tr>
		<th>#</th><th>Name</th><th>Type</th><th>Cost</th>
		</tr>
		<form action=index.php method=post>";
	while(!feof($fp)){
		$line = fgets($fp);
		$data = csv_parser($line);

		if (!isset($data[$PHONE])) continue;
		if (!is_numeric($data[$PHONE][0])) continue;

		$ph = $data[$PHONE];
		if (strstr($ph," OR "))
			$ph = array_pop(explode(" OR ",$ph));
		$ph = str_replace(" ","",$ph);
		$cn = $sql->escape($data[$CONTACT]);
		$sz = trim(strtoupper($data[$SIZE]));
		$clr = trim(strtoupper($data[$COLOR]));
		if ($clr[0] == "B") $clr = "B/W";
		elseif($clr == "COLOR") $clr = "FULL";

		$searchQ = "SELECT m.card_no,c.lastname FROM
			meminfo as m left join custdata as c
			on m.card_no=c.cardno and c.personnum=1
			left join suspensions as s on
			m.card_no = s.cardno
			WHERE (c.memtype = 2 or s.memtype1 = 2)
			and (m.phone='$ph' OR m.email_2='$ph'
			or c.lastname=$cn)";
		$searchR = $sql->query($searchQ);

		if ($sql->num_rows($searchR) > 1){
			$tmp = explode(" ",$data[$CONTACT]);
			$searchQ = "SELECT m.card_no,c.lastname FROM
				meminfo as m left join custdata as c
				on m.card_no=c.cardno and c.personnum=1
				WHERE c.memtype = 2
				AND c.lastname like '$tmp[0]%' and
				(m.phone='$ph' OR m.email_2='$ph')";
			$searchR = $sql->query($searchQ);
		}
		
		if ($sql->num_rows($searchR) == 0){
			printf("<i>Warning: no membership found for %s (%s)<br />",
				$data[$CONTACT],$ph);
		}
		elseif ($sql->num_rows($searchR) > 1){
			printf("<i>Warning: multiple memberships found for %s (%s)<br />",
				$data[$CONTACT],$ph);

		}
		else {
			$row = $sql->fetch_row($searchR);
			printf("<tr><td>%d</td><td>%s</td>
				<td>%s %s (%s)</td><td><input type=text 
				size=5 name=billable%d value=%.2f /></td></tr>
				<input type=hidden name=cardnos[] value=%d />",
				$row[0],$row[1],$data[$SIZE],
				$data[$COLOR],
				(substr($data[$MEMBER],0,3)=="YES")?
				'MEMBER':'NON-MEMBER',
				$row[0],
				(substr($data[$MEMBER],0,3)=="YES")?
				$BILLING_NONMEMBER[$sz.$clr]*0.75:
				$BILLING_NONMEMBER[$sz.$clr],
				$row[0]);
			if (!isset($BILLING_NONMEMBER[$sz.$clr])){
				var_dump($sz.$clr);
			}
		}
	}
	echo "</table>";
	echo "<input type=submit value=\"Charge Accounts\" />";
	echo "</form>";
	fclose($fp);
	unlink($file);
}
else {
?>
<html>
<head>
<title>Upload Invoice</title>
</head>
<body>
<h3>Gazette Billing</h3>
<form enctype="multipart/form-data" action="index.php" method="post">
<input type="hidden" name="MAX_FILE_SIZE" value="2097152" />
Filename: <input type="file" id="file" name="upload" />
<input type="submit" value="Upload File" />
</form>
</body>
</html>

<?php
}
?>
