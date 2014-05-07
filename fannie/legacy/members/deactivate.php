<?php
include('../../config.php');

if (!class_exists("SQLManager")) include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');

include('memAddress.php');
include('header.html');

if(isset($_GET['memNum'])){
	$memID = $_GET['memNum'];
}else{
	$memID = $_POST['memNum'];
}
$memNum = $memID;

?>
<html>
<head>
</head>
<body 
	bgcolor="#66CC99" 
	leftmargin="0" topmargin="0" 
	marginwidth="0" marginheight="0" 
	onload="MM_preloadImages(
		'images/memOver.gif',
		'images/memUp.gif',
		'images/repUp.gif',
		'images/itemsDown.gif',
		'images/itemsOver.gif',
		'images/itemsUp.gif',
		'images/refUp.gif',
		'images/refDown.gif',
		'images/refOver.gif',
		'images/repDown.gif',
		'images/repOver.gif'
	)"
>

<table width="660" height="111" border="0" cellpadding="0" cellspacing="0" bgcolor="#66cc99">
  <tr>
    <td colspan="2"><h1><img src="images/logoGrnBckSm.gif" width="50" height="47" /></h1></td>
    <!-- <td colspan="9" valign="middle"><font size="+3" face="Papyrus, Verdana, Arial, Helvetica, sans-serif">PI Killer</font></td>
  --> </tr>
  <tr>
    <td colspan="11" bgcolor="#006633"><a href="memGen.php?memID=<?php echo $memNum;?>">
    <img src="../images/general.gif" width="72" height="16" border="0" /></a>
        <a href="<?php echo $FANNIE_URL; ?>modules/plugins2.0/PIKiller/PIEquityPage.php?id=<? echo $memID; ?>">
    <img src="../images/equity.gif" width="72" height="16" border="0" /></a>
        <a href="<?php echo $FANNIE_URL; ?>modules/plugins2.0/PIKiller/PIArPage.php?id=<? echo $memID; ?>">
    <img src="../images/AR.gif" width="72" height="16" border="0" /></a>
    <a href="memControl.php?memID=<?php echo $memNum;?>">
    <img src="../images/control.gif" width="72" height="16" border="0" /></a>
        <a href="<?php echo $FANNIE_URL; ?>modules/plugins2.0/PIKiller/PIPurchasesPage.php?id=<? echo $memID; ?>">
    <img src="../images/detail.gif" width="72" height="16" border="0" /></a></td>
  </tr>
  <tr>
    <td colspan="9"><a href="mainMenu.php" target="_top" onclick="MM_nbGroup('down','group1','Members','images/memDown.gif',1)" onmouseover="MM_nbGroup('over','Members','images/memOver.gif','images/memUp.gif',1)" onmouseout="MM_nbGroup('out')"><img src="images/memDown.gif" alt="" name="Members" border="0" id="Members" onload="MM_nbGroup('init','group1','Members','images/memUp.gif',1)" /></a><a href="javascript:;" target="_top" onclick="MM_nbGroup('down','group1','Reports','images/repDown.gif',1)" onmouseover="MM_nbGroup('over','Reports','images/repOver.gif','images/repUp.gif',1)" onmouseout="MM_nbGroup('out')"><img src="images/repUp.gif" alt="" name="Reports" width="81" height="62" border="0" id="Reports" onload="" /></a><a href="javascript:;" target="_top" onClick="MM_nbGroup('down','group1','Items','images/itemsDown.gif',1)" onMouseOver="MM_nbGroup('over','Items','images/itemsOver.gif','images/itemsUp.gif',1)" onMouseOut="MM_nbGroup('out')"><img name="Items" src="images/itemsUp.gif" border="0" alt="Items" onLoad="" /></a><a href="javascript:;" target="_top" onClick="MM_nbGroup('down','group1','Reference','images/refDown.gif',1)" onMouseOver="MM_nbGroup('over','Reference','images/refOver.gif','images/refUp.gif',1)" onMouseOut="MM_nbGroup('out')"><img name="Reference" src="images/refUp.gif" border="0" alt="Reference" onLoad="" /></a></td>

</tr>
</table>

<?php 

if (!isset($_POST['termType'])){
  echo "&nbsp;&nbsp;&nbsp;Reason for suspending membership $memNum<br />";
  echo "<form action=deactivate.php method=post>";
  echo "&nbsp;&nbsp;&nbsp;<select name=termType>";
  echo "<option value='INACT'>Inactivate</option>";
  echo "<option value='INACT2'>Term (pending)</option>";
  echo "<option value='TERM'>Terminate</option>";
  echo "</select><br />";
  echo "<input type=hidden name=memNum value=$memID>";
  $query = "select textStr,mask from reasoncodes";
  $result = $sql->query($query);
  echo "<table>";
  $i = 1;
  while($row = $sql->fetch_row($result)) {
	  echo "<tr><td><input id=\"checkbox$i\" type=checkbox name=reasoncodes[] value=$row[1] /></td>
            <td><label for=\"checkbox$i\">$row[0]</label></td></tr>";
      $i++;
  }
  echo "</table>";
  echo "&nbsp;&nbsp;&nbsp;<input type=submit value=\"Enter Reason\">";
  echo "</form>";
}
else {
  $memNum = $memID;
  $termType = $_POST['termType'];
  $codes = isset($_REQUEST["reasoncodes"])?$_REQUEST['reasoncodes']:array();
 
  $reasonCode = 0;
  foreach($codes as $c)
	$reasonCode = $reasonCode | ((int)$c);

  deactivate($memNum,$termType,'',$reasonCode);
  
  addressList($memNum);

	// FIRE ALL UPDATE
	include('custUpdates.php');
	updateCustomerAllLanes($memNum);
}

?>

<table>
<tr>
<td><a href="testEdit.php?memnum=<? echo $memNum; ?> ">
Edit Info</a>
</td>
<td>
&nbsp;
</td>
<td>
</td>
<td>
</td>
</tr>
</table>
</body>
</html>
