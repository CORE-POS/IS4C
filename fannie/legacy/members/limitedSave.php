<?php
include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');

include('memAddress.php');
include('header.html');

$username = validateUserQuiet('editmembers');
if (!$username) $username = validateUserQuiet('editmembers_csc');

if(isset($_GET['memNum'])){
	$memID = $_GET['memNum'];
}else{
	$memID = $_POST['memNum'];
}

//$lName = $_POST['lastName'];
//$fName = $_POST['firstName'];

/* audit logging */
$uid = getUID($username);
$auditQ = "insert custUpdate select getdate(),$uid,1,* from custdata where cardno=$memID";
$auditR = $sql->query($auditQ);

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
//echo $fName;
//echo $_POST['fName'] . "THIS IS IT";

$memNum = $_POST['memNum'];
$fname = $sql->escape($_POST['fName']);
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
$phone = $_POST['phone'];
$phone2 = $_POST['phone2'];
$email = $_POST['email'];
$fnames = $_POST["hfname"];
$lnames = $_POST["hlname"];
for($i=0;$i<count($fnames);$i++)
	$fnames[$i] = $sql->escape($fnames[$i]);
for($i=0;$i<count($lnames);$i++)
	$lnames[$i] = $sql->escape($lnames[$i]);

$sql->query(sprintf("DELETE FROM memberCards WHERE card_no=%d",$memNum));
if (isset($_REQUEST['cardUPC']) && is_numeric($_REQUEST['cardUPC'])){
	$sql->query(sprintf("INSERT INTO memberCards VALUES (%d,'%s')",
		$memNum,str_pad($_REQUEST['cardUPC'],13,'0',STR_PAD_LEFT)));
}

// update top name
$custdataQ = "Update custdata set lastname = $lName, firstname = $fname, blueline=$blueline where cardNo = $memNum and personnum = 1";
$memNamesQ = "Update memNames set lname = $lName, fname=$fname where memNum = $memNum and personnum = 1";
add_second_server();
$custdataR = $sql->query_all($custdataQ);
//echo $memNamesQ."<br />";
$memNamesR = $sql->query($memNamesQ);

for($i=0;$i<3;$i++){
	if ($fnames[$i]=="''") $fnames[$i] = "";
	if ($lnames[$i]=="''") $lnames[$i] = "";
}
for($i=0; $i<3; $i++){
	$sql->query_all("DELETE FROM custdata WHERE cardno=$memNum and personnum=".($i+2));
	$sql->query("DELETE FROM memnames WHERE memNum=$memNum and personnum=".($i+2));
	if (is_array($fnames) && isset($fnames[$i]) && 
	    is_array($lnames) && isset($lnames[$i]) &&
	    !empty($lnames[$i]) && !empty($fnames[$i])){
		$custQ = sprintf("INSERT INTO custdata (CardNo,personNum,LastName,FirstName,CashBack,Balance,
				Discount,MemDiscountLimit,ChargeOk,WriteChecks,StoreCoupons,Type,
				memType,staff,SSI,Purchases,NumberOfChecks,memCoupons,blueLine,Shown)
				SELECT cardno,%d,%s,%s,CashBack,Balance,
				Discount,MemDiscountLimit,ChargeOk,WriteChecks,StoreCoupons,Type,
				memType,staff,SSI,Purchases,NumberOfChecks,memCoupons,'%s',1 FROM
				custdata WHERE CardNo=%d AND personNum=1",($i+2),$lnames[$i],$fnames[$i],
				($memNum.' '.trim($lnames[$i],"'").$bladd),$memNum);
		$memQ = sprintf("INSERT INTO memNames SELECT %s,%s,memNum,%d,checks,charge,
				active,'%s' FROM memNames where memNum=%d and personnum=1",
				$lnames[$i],$fnames[$i],
				($i+2),($memNum.'.'.($i+2).'.1'),$memNum);
		$sql->query_all($custQ);
		$sql->query($memQ);
	}
}

$mbrQ =    "UPDATE mbrmastr SET zipCode = '$zip',phone ='$phone',address1='$address1',address2='$address2',city='$city',state='$state',notes='$phone2',emailaddress='$email'  WHERE memNum = $memNum";
//$result=$sql->query($mbrQ);

$meminfoQ = sprintf("UPDATE meminfo SET street='%s',city='%s',state='%s',zip='%s',phone='%s',email_1='%s',email_2='%s'
		WHERE card_no=%d",(!empty($address2)?"$address1\n$address2":$address1),
			$city,$state,$zip,
			$phone,$email,$phone2,$memNum);
$sql->query($meminfoQ);

// FIRE ALL UPDATE
include('custUpdates.php');
updateCustomerAllLanes($memNum);

addressList($memNum);

?>

<table>
<tr>
<?php
if (!$username){
  echo "<td><a href=\"{$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/members/memGen.php?memNum=$memID\">Login to edit</a> | </td>";
}
else {
  echo "<td><a href=limitedEdit.php?memnum=$memID>[ Logged in ] Edit Info</a> | <a href=\"{$FANNIE_URL}auth/ui/loginform.php?logout=yes\">Log out</a></td>";
}
?>
<td>

</td>
<td>
&nbsp;
</td>
</tr>
</table>
</body>
</html>
