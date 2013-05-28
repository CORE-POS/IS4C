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
	"1/20B/W" => 45.00, 
	"1/15B/W" => 60.00,
	"1/10B/W" => 90.00,
	"1/5B/W" => 187.50,
	"1/ 5B/W" => 187.50,
	"1/2B/W" => 412.50,
	"1/ 2B/W" => 412.50,
	"1/20FULL" => 63.75,
	"1/15FULL" => 75.00,
	"1/10FULL"  => 112.50,
	"1/5FULL" => 225,
	"1/ 5FULL" => 225,
	"1/2FULL" => 562.50,
	"1/ 2FULL" => 562.50
);

$BILLING_NONMEMBER = array(
	"1/20B/W" => 60,
	"1/15B/W" => 80,
	"1/10B/W" => 120,
	"1/5B/W" => 250,
	"1/ 5B/W" => 250,
	"1/2B/W" => 550,
	"1/ 2B/W" => 550,
	"1/20FULL" => 85,
	"1/15FULL" => 100,
	"1/10FULL"  => 150,
	"1/5FULL" => 300,
	"1/ 5FULL" => 300,
	"1/2FULL" => 750,
	"1/ 2FULL" => 750
);

if (isset($_POST['cardnos'])){
	echo "<b>Date</b>: ".date("m/d/Y")."<br />
		<i>Summary of charges</i><br />
		<table cellspacing=0 cellpadding=3 border=1>
		<tr><th>Account</th><th>Charge</th><th>Receipt #</th></tr>";
	$sql->query("use $FANNIE_TRANS_DB");
	foreach($_POST['cardnos'] as $cardno){
		$amt = $_POST['billable'.$cardno];
		$transQ = "SELECT MAX(trans_no) FROM dtransactions
			WHERE emp_no=$EMP_NO AND register_no=$LANE_NO";
		$transR = $sql->query($transQ);
		$t_no = array_pop($sql->fetch_array($transR));
		if ($t_no == "") $t_no = 1;
		else $t_no++;
		$desc = isset($_POST['desc'.$cardno]) ? $_POST['desc'.$cardno] : '';
		$desc = substr($desc,0,24);

		$insQ = "INSERT INTO dtransactions VALUES (
			now(),$LANE_NO,$EMP_NO,$t_no,
			'{$amt}DP703','Gazette Ad {$desc}','D','','',703,
			1.0,0,0,$amt,$amt,$amt,0,0,.0,.0,
			0,0,0,NULL,0.0,0,0,.0,0,0,0,0,
			0,'',$cardno,1)";
		$amt *= -1;
		$insQ2 = "INSERT INTO dtransactions VALUES (
			now(),$LANE_NO,$EMP_NO,$t_no,
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

		$ph = $data[$PHONE];
		if (strstr($ph," OR "))
			$ph = array_pop(explode(" OR ",$ph));
		$ph = str_replace(" ","",$ph);
		$cn = $sql->escape($data[$CONTACT]);
		$sz = trim(strtoupper($data[$SIZE]));
		$clr = trim(strtoupper($data[$COLOR]));
		if ($clr[0] == "B") $clr = "B/W";
		elseif($clr == "COLOR") $clr = "FULL";

		$desc = "($sz, ".($clr=="FULL" ? "color" : "b&w");
		$desc .= ((substr($data[$MEMBER],0,3)=="YES") ? ', owner' : '').")";

		$searchQ = "SELECT m.card_no,c.lastname FROM
			meminfo as m left join custdata as c
			on m.card_no=c.cardno and c.personnum=1
			left join suspensions as s on
			m.card_no = s.cardno
			WHERE (c.memtype = 2 or s.memtype1 = 2)
			and (m.phone='$ph' OR m.email_1='$ph' OR m.email_2='$ph'
			or c.lastname=$cn)";
		$searchR = $sql->query($searchQ);

		if ($sql->num_rows($searchR) > 1){
			$tmp = explode(" ",$data[$CONTACT]);
			$searchQ = "SELECT m.card_no,c.lastname FROM
				meminfo as m left join custdata as c
				on m.card_no=c.cardno and c.personnum=1
				WHERE c.memtype = 2
				AND c.lastname like '$tmp[0]%' and
				(m.phone='$ph' OR m.email_1='$ph' OR m.email_2='$ph')";
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
				<input type=hidden name=desc%d value=\"%s\" />
				<input type=hidden name=cardnos[] value=%d />",
				$row[0],$row[1],$data[$SIZE],
				$data[$COLOR],
				(substr($data[$MEMBER],0,3)=="YES")?
				'MEMBER':'NON-MEMBER',
				$row[0],
				(substr($data[$MEMBER],0,3)=="YES")?
				$BILLING_NONMEMBER[$sz.$clr]*0.75:
				$BILLING_NONMEMBER[$sz.$clr],
				$row[0],$desc,$row[0]);
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
