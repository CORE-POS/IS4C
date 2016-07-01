<?php
include('../../config.php');

if (!class_exists('FannieAPI'))
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');

include('memAddress.php');
include('header.html');

$memID="";
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
>

<table width="660" height="111" border="0" cellpadding="0" cellspacing="0" bgcolor="#66cc99">
  <tr>
    <td colspan="2"><h1><img src="../images/logoGrnBckSm.gif" width="50" height="47" /></h1></td>
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
    <td colspan="9"><a href="mainMenu.php" target="_top" onclick="MM_nbGroup('down','group1','Members','../images/memDown.gif',1)" onmouseover="MM_nbGroup('over','Members','../images/memOver.gif','../images/memUp.gif',1)" onmouseout="MM_nbGroup('out')"><img src="../images/memDown.gif" alt="" name="Members" border="0" id="Members" onload="MM_nbGroup('init','group1','Members','../images/memUp.gif',1)" /></a><a href="javascript:;" target="_top" onclick="MM_nbGroup('down','group1','Reports','../images/repDown.gif',1)" onmouseover="MM_nbGroup('over','Reports','../images/repOver.gif','../images/repUp.gif',1)" onmouseout="MM_nbGroup('out')"><img src="../images/repUp.gif" alt="" name="Reports" width="81" height="62" border="0" id="Reports" onload="" /></a><a href="javascript:;" target="_top" onClick="MM_nbGroup('down','group1','Items','../images/itemsDown.gif',1)" onMouseOver="MM_nbGroup('over','Items','../images/itemsOver.gif','../images/itemsUp.gif',1)" onMouseOut="MM_nbGroup('out')"><img name="Items" src="../images/itemsUp.gif" border="0" alt="Items" onLoad="" /></a><a href="javascript:;" target="_top" onClick="MM_nbGroup('down','group1','Reference','../images/refDown.gif',1)" onMouseOver="MM_nbGroup('over','Reference','../images/refOver.gif','../images/refUp.gif',1)" onMouseOut="MM_nbGroup('out')"><img name="Reference" src="../images/refUp.gif" border="0" alt="Reference" onLoad="" /></a></td>

</tr>
</table>

<?php 

$memNum = $memID;
if (!isset($_POST['submit']) && !isset($_GET['fixedaddress'])){
    echo "&nbsp;&nbsp;&nbsp;Reason for suspending membership $memNum<br />";
    echo "<form action=alterstatus.php method=post>";
    echo "<input type=hidden name=memNum value=$memID>";
    $sus = new SuspensionsModel($sql);
    $sus->cardno($memNum);
    $sus->load();
    $curReasonCode = $sus->reasonCode();
    $cust = new CustdataModel($sql);
    $cust->CardNo($memNum);
    $cust->personNum(1);
    $cust->load();
    $curType = $cust->Type();
    $stats = array('INACT'=>'Inactive','TERM'=>'Termed','INACT2'=>'Term pending');
    echo "<select name=status>";
    foreach ($stats as $k=>$v){
        echo "<option value=".$k;
        if ($k == $curType) echo " selected";
        echo ">".$v."</option>";
    }
    echo "</select>";
    $query = "select textStr,mask from reasoncodes";
    $result = $sql->query($query);
    echo "<table>";
    $i=1;
    while($row = $sql->fetch_row($result)){
      echo "<tr><td><input id=\"checkbox$i\" type=checkbox name=reasoncodes[] value=$row[1]";
      if ($curReasonCode & ((int)$row[1])) echo " checked";
      echo " /></td><td><label for=\"checkbox$i\">$row[0]</label></td></tr>";
      $i++;
    }
    echo "</table>";
    echo "<input type=submit name=submit value=Update />";
    echo "</form>";
}
else if (validateUserQuiet('editmembers')){
    $memNum = $_POST["memNum"];
    $codes = array();
    if (isset($_POST["reasoncodes"]))
        $codes = $_POST["reasoncodes"];
    $status = $_POST["status"];

    $reasonCode = 0;
    foreach($codes as $c)
        $reasonCode = $reasonCode | ((int)$c);
    
    alterReason($memNum,$reasonCode,$status);

    addressList($memNum);

    // FIRE ALL UPDATE
    include('custUpdates.php');
    updateCustomerAllLanes($memNum);

}
else if (validateUserQuiet('editmembers_csc') && isset($_GET['fixedaddress'])){
    $curQ = "select reasoncode from suspensions where cardno=$memNum";
    $curR = $sql->query($curQ);
    $curCode = (int)(array_pop($sql->fetchRow($curR)));

    $newCode = $curCode & ~16;
    alterReason($memNum,$newCode);

    addressList($memNum);

    // FIRE ALL UPDATE
    include('custUpdates.php');
    updateCustomerAllLanes($memNum);
}

?>

<table>
<tr>
<td><a href="testEdit.php?memnum=<?php echo $memNum; ?> ">
Edit Info</a>
</td>
<td>
&nbsp;
</td>
</tr>
</table>
</body>
</html>
