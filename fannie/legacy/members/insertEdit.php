<?php
include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include($FANNIE_ROOT.'classlib2.0/data/FannieDB.php');
include($FANNIE_ROOT.'classlib2.0/data/controllers/CustdataController.php');
include($FANNIE_ROOT.'classlib2.0/data/controllers/MeminfoController.php');
include($FANNIE_ROOT.'classlib2.0/data/controllers/MemDatesController.php');
include($FANNIE_ROOT.'classlib2.0/data/controllers/MemberCardsController.php');
$dbc = FannieDB::get($FANNIE_OP_DB);
$sql = $dbc;
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
	<a href="memControl.php?memID=<?php echo $memID ?>">
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

$CUST_FIELDS = array('personNum'=>array(1),'LastName'=>array(),'FirstName'=>array());
$MI_FIELDS = array();

$memNum = $_POST['memNum'];
$CUST_FIELDS['FirstName'][] = $_POST['fName'];
$CUST_FIELDS['LastName'][] = $_POST['lName'];
$MI_FIELDS['street'] = $_POST['address1'] . (!empty($_POST['address2']) ? "\n".$_POST['address2'] : '');
$MI_FIELDS['city'] = $_POST['city'];
$MI_FIELDS['state'] = $_POST['state'];
$MI_FIELDS['zip'] = $_POST['zip'];
$CUST_FIELDS['MemDiscountLimit'] = $_POST['chargeLimit'];
$MI_FIELDS['phone'] = $_POST['phone'];
$MI_FIELDS['email_2'] = $_POST['phone2'];
$MI_FIELDS['email_1'] = $_POST['email'];
$CUST_FIELDS['memType'] = $_POST['discList'];
$CUST_FIELDS['Type'] = 'REG';
$CUST_FIELDS['Staff'] = 0;
$CUST_FIELDS['Discount'] = 0;
$MI_FIELDS['ads_OK'] = $_POST['mailflag'];

MemberCardsController::update($memNum,$_REQUEST['cardUPC']);

$sql->query_all("UPDATE memContact SET pref=".$MI_FIELDS['ads_OK']." WHERE card_no=$memNum");

if ($CUST_FIELDS['memType'] == 1 || $CUST_FIELDS['memType'] == 3){
	$CUST_FIELDS['Type'] = 'PC';
}
if ($CUST_FIELDS['memType'] == 3 || $CUST_FIELDS['memType'] == 9){
	$CUST_FIELDS['Discount'] = 12;
	$CUST_FIELDS['Staff'] = 1;
}

$lnames = $_REQUEST['hhLname'];
$fnames = $_REQUEST['hhFname'];
$count = 2;
for($i=0;$i<count($lnames);$i++){
	if (empty($lnames[$i]) && empty($fnames[$i])) continue;

	$CUST_FIELDS['personNum'][] = $count;
	$CUST_FIELDS['LastName'][] = $lnames[$i];
	$CUST_FIELDS['FirstName'][] = $fnames[$i];

	$count++;
}

CustdataController::update($memNum, $CUST_FIELDS);
MeminfoController::update($memNum, $MI_FIELDS);
MemDatesController::update($memNum, $_POST['startDate'], $_POST['endDate']);

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
