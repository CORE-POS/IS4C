<?php
include('../../config.php');
include_once($FANNIE_ROOT.'src/SQLManager.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
$dbc = FannieDB::get($FANNIE_OP_DB);
$sql = $dbc;

include_once($FANNIE_ROOT.'auth/login.php');
if (!validateUserQuiet('editmembers') && !validateUserQuiet('editmembers_csc') && !validateUserQuiet('viewmembers')){
	$url = $FANNIE_URL.'auth/ui/loginform.php?redirect='.$_SERVER['PHP_SELF'];
	header('Location: '.$url);
	exit;
}
//include('../db.php');

include('memAddress.php');
include('header.html');

$username = validateUserQuiet('editmembers');

if(isset($_GET['memNum'])){
	$memID = $_GET['memNum'];
}else{
	$memID = $_POST['memNum'];
}

/* audit logging */
$uid = getUID($username);
$auditQ = "insert custUpdate select ".$sql->now().",$uid,1,
	CardNo,personNum,LastName,FirstName,
	CashBack,Balance,Discount,ChargeLimit,ChargeOK,
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
    <a href="<?php echo $FANNIE_URL; ?>modules/plugins2.0/PIKiller/PIEquityPage.php?id=<? echo $memID; ?>">
		<img src="../images/equity.gif" width="72" height="16" border="0" />
	</a>
    <a href="<?php echo $FANNIE_URL; ?>modules/plugins2.0/PIKiller/PIArPage.php?id=<? echo $memID; ?>">
		<img src="../images/AR.gif" width="72" height="16" border="0" />
	</a>
	<a href="memControl.php?memID=<?php echo $memID ?>">
		<img src="../images/control.gif" width="72" height="16" border="0" />
	</a>
    <a href="<?php echo $FANNIE_URL; ?>modules/plugins2.0/PIKiller/PIPurchasesPage.php?id=<? echo $memID; ?>">
		<img src="../images/detail.gif" width="72" height="16" border="0" />
	</a>
   </td>
  </tr>
  <tr>
    <td colspan="9"><a href="mainMenu.php" target="_top" onclick="MM_nbGroup('down','group1','Members','../images/memDown.gif',1)" onmouseover="MM_nbGroup('over','Members','../images/memOver.gif','../images/memUp.gif',1)" onmouseout="MM_nbGroup('out')"><img src="../images/memDown.gif" alt="" name="Members" border="0" id="Members" onload="MM_nbGroup('init','group1','Members','../images/memUp.gif',1)" /></a><a href="javascript:;" target="_top" onclick="MM_nbGroup('down','group1','Reports','../images/repDown.gif',1)" onmouseover="MM_nbGroup('over','Reports','../images/repOver.gif','../images/repUp.gif',1)" onmouseout="MM_nbGroup('out')"><img src="../images/repUp.gif" alt="" name="Reports" width="81" height="62" border="0" id="Reports" onload="" /></a><a href="javascript:;" target="_top" onClick="MM_nbGroup('down','group1','Items','../images/itemsDown.gif',1)" onMouseOver="MM_nbGroup('over','Items','../images/itemsOver.gif','../images/itemsUp.gif',1)" onMouseOut="MM_nbGroup('out')"><img name="Items" src="../images/itemsUp.gif" border="0" alt="Items" onLoad="" /></a><a href="memDocs.php?memID=<?php echo $memID; ?>" target="_top" onClick="MM_nbGroup('down','group1','Reference','../images/refDown.gif',1)" onMouseOver="MM_nbGroup('over','Reference','../images/refOver.gif','../images/refUp.gif',1)" onMouseOut="MM_nbGroup('out')"><img name="Reference" src="../images/refUp.gif" border="0" alt="Reference" onLoad="" /></a></td>

</tr>
</table>

<?php 

$MI_FIELDS = array();

$memNum = $_POST['memNum'];
$MI_FIELDS['street'] = $_POST['address1'] . (!empty($_POST['address2']) ? "\n".$_POST['address2'] : '');
$MI_FIELDS['city'] = $_POST['city'];
$MI_FIELDS['state'] = $_POST['state'];
$MI_FIELDS['zip'] = $_POST['zip'];
$MI_FIELDS['phone'] = $_POST['phone'];
$MI_FIELDS['email_2'] = $_POST['phone2'];
$MI_FIELDS['email_1'] = $_POST['email'];
$MI_FIELDS['ads_OK'] = $_POST['mailflag'];

$cust = new CustdataModel($dbc);
$cust->CardNo($memNum);
$cust->personNum(1);
$cust->load(); // get all current values
$cust->MemDiscountLimit($_POST['chargeLimit']);
$cust->ChargeLimit($_POST['chargeLimit']);
$cust->memType($_POST['discList']);
$cust->Type('REG');
$cust->Staff(0);
$cust->Discount(0);

MemberCardsModel::update($memNum,$_REQUEST['cardUPC']);

$mcP = $sql->prepare("UPDATE memContact SET pref=? WHERE card_no=?");
$sql->execute($mcP, array($MI_FIELDS['ads_OK'], $memNum));

if ($cust->memType() == 1 || $cust->memType() == 3){
	$cust->Type('PC');
}
if ($cust->memType() == 3 || $cust->memType() == 9){
	$cust->Discount(12);
	$cust->Staff(1);
}

$cust->FirstName($_POST['fName']);
$cust->LastName($_POST['lName']);
$cust->BlueLine( $cust->CardNo().' '.$cust->LastName() );
$cust->save(); // save personNum=1

$lnames = $_REQUEST['hhLname'];
$fnames = $_REQUEST['hhFname'];
$count = 2;
for($i=0;$i<count($lnames);$i++){
	if (empty($lnames[$i]) && empty($fnames[$i])) continue;

	$cust->personNum($count);
	$cust->FirstName($fnames[$i]);
	$cust->LastName($lnames[$i]);
	$cust->BlueLine( $cust->CardNo().' '.$cust->LastName() );
	$cust->save(); // save next personNum

	$count++;
}
// remove names that were blank on the form
for($i=$count;$i<5;$i++){
	$cust->personNum($i);
	$cust->delete();
}

MeminfoModel::update($memNum, $MI_FIELDS);
MemDatesModel::update($memNum, $_POST['startDate'], $_POST['endDate']);

// FIRE ALL UPDATE
include('custUpdates.php');
updateCustomerAllLanes($memNum);

/* general note handling */
$notetext = $_POST['notetext'];
$notetext = preg_replace("/\n/","<br />",$notetext);
$notetext = preg_replace("/\'/","''",$notetext);
$checkQ = $sql->prepare("select * from memberNotes where note=? and cardno=?");
$checkR = $sql->execute($checkQ, array($notetext, $memNum));
if ($sql->num_rows($checkR) == 0){
	$noteQ = $sql->prepare("insert into memberNotes (cardno, note, stamp, username) VALUES (?, ?, ".$sql->now().", ?)");
	$noteR = $sql->execute($noteQ, array($memNum, $notetext, $username));
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
