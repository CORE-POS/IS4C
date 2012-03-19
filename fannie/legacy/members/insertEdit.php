<?php
include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');

include('memAddress.php');
include('header.html');

$username = validateUserQuiet('editmembers');

if(isset($_GET['memNum'])){
	$memID = $_GET['memNum'];
}else{
	$memID = $_POST['memNum'];
}

//$lName = $_POST['lastName'];

/* audit logging */
$uid = getUID($username);
$auditQ = "insert custUpdate select ".$sql->now().",$uid,1,
	CardNo,personNum,LastName,FirstName,
	CashBack,Balance,Discount,MemDiscountLimit,ChargeOK,
	WriteChecks,StoreCoupons,Type,memType,staff,SSI,Purchases,
	NumberOfChecks,memCoupons,blueLine,Shown,id from custdata where cardno=$memID";
//$auditR = $sql->query($auditQ);

?>
<html>
<head>
</head>
<body 
	bgcolor="#66CC99" 
	leftmargin="0" topmargin="0" 
	marginwidth="0" marginheight="0" 
	onload="MM_preloadImages(
		'../images/memOver.gif',
		'../images/memUp.gif',
		'../images/repUp.gif',
		'../images/itemsDown.gif',
		'../images/itemsOver.gif',
		'../images/itemsUp.gif',
		'../images/refUp.gif',
		'../images/refDown.gif',
		'../images/refOver.gif',
		'../images/repDown.gif',
		'../images/repOver.gif'
	)"
>

<table width="660" height="111" border="0" cellpadding="0" cellspacing="0" bgcolor="#66cc99">
  <tr>
    <td colspan="2"><h1><img src="../images/newLogo_small1.gif" /></h1></td>
    <!-- <td colspan="9" valign="middle"><font size="+3" face="Papyrus, Verdana, Arial, Helvetica, sans-serif">PI Killer</font></td>
  --> </tr>
  <tr>
    <td colspan="11" bgcolor="#006633"><!--<a href="memGen.php">-->
	<img src="../images/general.gif" width="72" height="16" border="0" />
	<a href="testDetails.php?memID=<?php echo $memID; ?>">
		<img src="../images/equity.gif" width="72" height="16" border="0" />
	</a>
	<a href="memARTrans.php?memID=<?php echo $memID; ?>">
		<img src="../images/AR.gif" width="72" height="16" border="0" />
	</a>
	<a href="memControl.php">
		<img src="../images/control.gif" width="72" height="16" border="0" />
	</a>
	<a href="memTrans.php?memID=<?php echo $memID; ?>">
		<img src="../images/detail.gif" width="72" height="16" border="0" />
	</a>
   </td>
  </tr>
  <tr>
    <td colspan="9"><a href="mainMenu.php" target="_top" onclick="MM_nbGroup('down','group1','Members','../images/memDown.gif',1)" onmouseover="MM_nbGroup('over','Members','../images/memOver.gif','../images/memUp.gif',1)" onmouseout="MM_nbGroup('out')"><img src="../images/memDown.gif" alt="" name="Members" border="0" id="Members" onload="MM_nbGroup('init','group1','Members','../images/memUp.gif',1)" /></a><a href="javascript:;" target="_top" onclick="MM_nbGroup('down','group1','Reports','../images/repDown.gif',1)" onmouseover="MM_nbGroup('over','Reports','../images/repOver.gif','../images/repUp.gif',1)" onmouseout="MM_nbGroup('out')"><img src="../images/repUp.gif" alt="" name="Reports" width="81" height="62" border="0" id="Reports" onload="" /></a><a href="javascript:;" target="_top" onClick="MM_nbGroup('down','group1','Items','../images/itemsDown.gif',1)" onMouseOver="MM_nbGroup('over','Items','../images/itemsOver.gif','../images/itemsUp.gif',1)" onMouseOut="MM_nbGroup('out')"><img name="Items" src="../images/itemsUp.gif" border="0" alt="Items" onLoad="" /></a><a href="memDocs.php?memID=<?php echo $memID; ?>" target="_top" onClick="MM_nbGroup('down','group1','Reference','../images/refDown.gif',1)" onMouseOver="MM_nbGroup('over','Reference','../images/refOver.gif','../images/refUp.gif',1)" onMouseOut="MM_nbGroup('out')"><img name="Reference" src="../images/refUp.gif" border="0" alt="Reference" onLoad="" /></a></td>

</tr>
</table>

<?php 

//echo $memID;
//echo $lName;

$memNum = $_POST['memNum'];
$fName = $sql->escape($_POST['fName']);
$lName = $sql->escape($_POST['lName']);
$blueline = $memNum . " " . $_POST['lName'];
$bladd = "";
if ($_POST['status'] == "ACTIVE"){
	$bladd = " Coup(".$_POST['memcoupons'].")";
}
$blueline .= $bladd;
$blueline = $dbc->escape($blueline);
$address1 = $_POST['address1'];
$address2 = $_POST['address2'];
$city = $_POST['city'];
$state = $_POST['state'];
$zip = $_POST['zip'];
$startDate = $_POST['startDate'];
$arLimit = $_POST['chargeLimit'];
$phone = $_POST['phone'];
$phone2 = $_POST['phone2'];
$email = $_POST['email'];
$discList=$_POST['discList'];
//$charge1 = $_POST['charge1'];
//$checks1 = $_POST['checks1'];
//$charge2 = $_POST['charge2'];
//$checks2 = $_POST['checks2'];
//$charge3 = $_POST['charge3'];
//$checks3 = $_POST['checks3'];
$enddate = $_POST['endDate'];
$curDiscLimit = $_POST['curDiscLimit'];
$mailflag = $_POST['mailflag'];

add_second_server();
$sql->query_all(sprintf("DELETE FROM memberCards WHERE card_no=%d",$memNum));
if (isset($_REQUEST['cardUPC']) && is_numeric($_REQUEST['cardUPC'])){
	$sql->query_all(sprintf("INSERT INTO memberCards VALUES (%d,'%s')",
		$memNum,str_pad($_REQUEST['cardUPC'],13,'0',STR_PAD_LEFT)));
}

$sql->query_all("UPDATE meminfo SET ads_OK=$mailflag WHERE card_no=$memNum");
$sql->query_all("UPDATE memContact SET pref=$mailflag WHERE card_no=$memNum");

//echo $charge1."<br />".$charge2."<br />".$charge3."<br />".$checks1."<br />".$checks2."<br />".$checks3."<br />";
$charge1=$charge2=$charge3=0;
$checks1=$checks2=$checks3=0;
/*
if ($charge1 == 'on')
     $charge1 = 1;
else
     $charge1 = 0;
if ($charge2 == 'on')
     $charge2 = 1;
else
     $charge2 = 0;
if ($charge3 == 'on')
     $charge3 = 1;
else
     $charge3 = 0;
if ($checks1 == 'on')
     $checks1 = 1;
else
     $checks1 = 0;
if ($checks2 == 'on')
     $checks2 = 1;
else
     $checks2 = 0;
if ($checks3 == 'on')
     $checks3 = 1;
else
     $checks3 = 0;
*/

//echo $fname1.$fname2.$fname3."<br>";
//echo $memNum."<br>";
//echo $lName."<br>";
//echo $address1."<br>";
//echo $address2."<br>";
//echo $city."<br>";
//echo $state."<br>";
//echo $zip."<br>";
//echo $startDate."<br>";
//echo $enddate."<br>";
//echo $arLimit."<br>";
//echo "discList:".$discList."<br>";
//echo $lname1."<br>";
//echo $curDiscLimit."<br>";

if ($discList == '')
     $discList = $curDiscLimit;

$staff=0;
$disc = 0;
$mem = "REG";
if ($discList == 1 || $discList == 3)
	$mem = "PC";
if ($discList == 3 || $discList == 9){
	$disc = 12;
	$staff=1;
}

if (isset($discount) && isset($doDiscount))
	$disc = $discount;

// update top name
//echo "<br>";
$custdataQ = "Update custdata set firstname = $fName, lastname = $lName, blueline=$blueline where cardNo = $memNum and personnum = 1";
$memNamesQ = "Update memNames set fname = $fName, lname = $lName where memNum = $memNum and personnum = 1";
//echo $memNamesQ."<br>";
$custdataR = $sql->query_all($custdataQ);
//$memNamesR = $sql->query($memNamesQ);

// update other stuff
if(isset($discList)){
  $discMstrQ = "UPDATE mbrmastr SET DiscountPerc = $disc, memType=$discList, DiscountType = $discList WHERE memNum = $memNum";
  $discCORE = "UPDATE custdata SET memdiscountlimit = $arLimit,memType = $discList,Discount = $disc,staff=$staff WHERE cardNo = $memNum";
  $typeQ = "UPDATE custdata set type = '$mem' where cardNo=$memNum and type <> 'INACT' and type <> 'TERM'";
  //$discRes1 = $sql->query($discMstrQ);
  $discRes2 = $sql->query_all($discCORE);
  $typeR = $sql->query_all($typeQ);
}

$memDiscQ = "select memDiscountLimit,balance,type,memType,staff,SSI,discount,chargeOk,memCoupons from custdata where cardno=$memNum and personnum = 1";
$memDiscR = $sql->query($memDiscQ);
$memDiscRow = $sql->fetch_row($memDiscR);
// ideally, memdiscountlimit 0 would stop charges
// unfortunately, it stops ALL charges right now
$cd_charge1 = $memDiscRow[0];// * $charge1;
$cd_charge2 = $memDiscRow[0];// * $charge2;
$cd_charge3 = $memDiscRow[0];// * $charge3;
$balance = $memDiscRow[1];
$type = $memDiscRow[2];
$memType = $memDiscRow[3];
$staff = $memDiscRow[4];
$SSI = $memDiscRow[5];
$discount = $memDiscRow[6];
$can_charge = $memDiscRow[7];
$mcoup = $memDiscRow[8];

$delCQ = "delete from custdata where cardno=$memNum and personnum > 1";
$delMQ = "delete from memnames where memnum=$memNum and personnum > 1";
$delCR = $sql->query_all($delCQ);
//$delMR = $sql->query($delMQ);

$lnames = $_REQUEST['hhLname'];
$fnames = $_REQUEST['hhFname'];
$count = 2;
for($i=0;$i<count($lnames);$i++){
	if (empty($lnames[$i]) && empty($fnames[$i])) continue;

	$fname1 = $sql->escape($fnames[$i]);
	$lname1 = $sql->escape($lnames[$i]);
	$blue1 = $sql->escape($memNum.' '.$lnames[$i]);

	$houseCustUpQ1 = "Insert into custdata (lastname,firstname,blueline,cardno,personnum,chargeok,
			writechecks,shown,memDiscountLimit,type,memType,staff,SSI,balance,discount,
			CashBack,StoreCoupons,Purchases,NumberOfChecks,memCoupons) values ($lname1,
			$fname1,$blue1,$memNum,$count,1,$checks1,1,$cd_charge1,'$type',$memType,$staff,
			$SSI,$balance,$discount,0,0,0,999,$mcoup)";

	$houseMemUpQ1 = "insert into memnames (lname,fname,memnum,personnum,charge,checks,active) values ($lname1,$fname1,$memNum,$count,$charge1,$checks1,1)";

	$sql->query_all($houseCustUpQ1);
	//$sql->query($houseMemUpQ1);

	$count++;
}

$mbrQ =    "UPDATE mbrmastr SET zipCode = '$zip',phone ='$phone',address1='$address1',address2='$address2',arLimit=$arLimit,city='$city',state='$state',startdate='$startDate',enddate = '$enddate',notes='$phone2',emailaddress='$email'  WHERE memNum = $memNum";
//$result=$sql->query($mbrQ);
$meminfoQ = sprintf("UPDATE meminfo SET street='%s',city='%s',state='%s',zip='%s',phone='%s',email_1='%s',email_2='%s'
		WHERE card_no=%d",(!empty($address2)?"$address1\n$address2":$address1),
			$city,$state,$zip,
			$phone,$email,$phone2,$memNum);
$sql->query_all($meminfoQ);

$datesQ = "UPDATE memDates SET start_date='$startDate',end_date='$enddate' WHERE card_no=$memNum";
$sql->query_all($datesQ);

// FIRE ALL UPDATE
include('custUpdates.php');
updateCustomerAllLanes($memNum);

/* general note handling */
$notetext = $_POST['notetext'];
$notetext = preg_replace("/\n/","<br />",$notetext);
$notetext = preg_replace("/\'/","''",$notetext);
$checkQ = "select * from memberNotes where note='$notetext' and cardno=$memNum";
$checkR = $sql->query($checkQ);
if ($sql->num_rows($checkR) == 0){
	$noteQ = "insert into memberNotes values ($memNum,'$notetext',".$sql->now().",'$username')";
	$noteR = $sql->query($noteQ);
}

addressList($memNum);

?>

<table>
<tr>
<?php
if (!$username){
  echo "<td><a href=\"{$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/members/memGen.php?memNum=$memID\">Login to edit</a> | </td>";
}
else {
  echo "<td><a href=testEdit.php?memnum=$memID>[ Logged in ] Edit Info</a> | </td>";
}
?>
<td>

</td>
<td>
&nbsp;
</td>
<td>
<a href="memGen.php?memNum=<?php echo ($memID-1); ?> ">
Prev Mem</a>
</td>
<td>
<a href="memGen.php?memNum=<?php echo ($memID+1); ?> ">
Next Mem
</a>
</td>
</tr>
</table>
</body>
</html>
