<?php
include('../../config.php');
include_once($FANNIE_ROOT.'src/SQLManager.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
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
$auditQ = "insert custUpdate select now(),$uid,1,* from custdata where cardno=$memID";
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

//echo $memID;
//echo $lName;
//echo $fName;
//echo $_POST['fName'] . "THIS IS IT";
$MI_FIELDS = array();

$memNum = $_POST['memNum'];
$fname = str_replace("'","",$_POST['fName']);
$lName = str_replace("'","",$_POST['lName']);
$blueline = $memNum . " " . $_POST['lName'];
$bladd = "";
if ($_POST['status'] == "ACTIVE"){
	$bladd = " Coup(".$_POST['memcoupons'].")";
}
$blueline .= $bladd;
$MI_FIELDS['street'] = $_POST['address1'] . (!empty($_POST['address2']) ? "\n".$_POST['address2'] : '');
$MI_FIELDS['city'] = $_POST['city'];
$MI_FIELDS['state'] = $_POST['state'];
$MI_FIELDS['zip'] = $_POST['zip'];
$MI_FIELDS['phone'] = $_POST['phone'];
$MI_FIELDS['email_2'] = $_POST['phone2'];
$MI_FIELDS['email_1'] = $_POST['email'];
$MI_FIELDS['ads_OK'] = $_POST['mailflag'];
$fnames = $_POST["hfname"];
$lnames = $_POST["hlname"];
for($i=0;$i<count($fnames);$i++){
	$fnames[$i] = str_replace("'","",$fnames[$i]);
}
for($i=0;$i<count($lnames);$i++){
	$lnames[$i] = str_replace("'","",$lnames[$i]);
}

$cards = new MemberCardsModel($sql);
$cards->card_no($memNum);
// delete existing records
foreach($cards->find() as $obj) {
    $obj->delete();
}
// add record with correct upc
$cards->upc(str_pad($_REQUEST['cardUPC'], 13, '0', STR_PAD_LEFT));
$cards->save();

// update top name
$cust = new CustdataModel($sql);
$cust->CardNo($memNum);
$cust->personNum(1);
$cust->LastName($lName);
$cust->FirstName($fname);
$cust->blueLine($blueline);
$cust->save();

for($i=0;$i<3;$i++){
	if ($fnames[$i]=="''") $fnames[$i] = "";
	if ($lnames[$i]=="''") $lnames[$i] = "";
}

$count = 2;
// load settings for person 1, update just names on other records
$cust->reset();
$cust->CardNo($memNum);
$cust->personNum(1);
$cust->load();
for($i=0; $i<count($lnames); $i++) {
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
