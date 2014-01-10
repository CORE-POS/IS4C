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

header('Location: memGen.php?memNum='.$memNum);

