<?php
include('../../config.php');

include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');

include($FANNIE_ROOT.'auth/login.php');
if (!validateUserQuiet('editmembers') && !validateUserQuiet('editmembers_csc') && !validateUserQuiet('viewmembers')){
    $url = $FANNIE_URL.'auth/ui/loginform.php?redirect='.$_SERVER['PHP_SELF'];
    header('Location: '.$url);
    return;
}

include('memAddress.php');
include('header.html');

$memID = -1; // better failure
if(isset($_REQUEST['memNum'])){
    $memID = $_REQUEST['memNum'];
}

if(isset($_REQUEST['memID'])){
    $memID = $_REQUEST['memID'];
}

if(isset($_REQUEST['submit2'])){
   //echo $_GET['memName'];
   $memID = $_REQUEST['memName'];
   //echo "I am here: $memID";
}
$lName = "";
if (isset($_REQUEST['lastName']))
    $lName = $_REQUEST['lastName'];
$fName = "";
if (isset($_REQUEST['firstName']))
    $fName = $_REQUEST['firstName'];

/********************************************************************
 * prefetch result: 
 * why?  this is done to address a bug where if a search was performed
 * by name and exactly one result was found the top menu items
 * (details, AR, etc) would not work because the links wouldn't have
 * a member ID set properly.  Getting the result earlier like this
 * lets the member ID get filled in before that menu is printed.
********************************************************************/

$row = "";
$query_drop = "";
$result = prefetch_result($memID,$lName,$fName,$query_drop);
if ($sql->num_rows($result) > 0){
  $row = $sql->fetchRow($result);
  $memID = $row[0];
}

?>
<body 
    bgcolor="#66CC99" 
    leftmargin="0" topmargin="0" 
    marginwidth="0" marginheight="0" 
>
<table width="660" height="111" border="0" cellpadding="0" cellspacing="0" bgcolor="#66cc99">
  <tr>
    <td colspan="3"><img src="../images/newLogo_small1.gif"  /></td>
    <!-- <td colspan="9" valign="middle"><font size="+3" face="Papyrus, Verdana, Arial, Helvetica, sans-serif">PI Killer</font></td>
  --> </tr>
  <tr>
    <td colspan="11" bgcolor="#006633"><!--<a href="memGen.php">-->
    <img src="../images/general.gif" width="72" height="16" border="0" />
        <a href="<?php echo $FANNIE_URL; ?>modules/plugins2.0/PIKiller/PIEquityPage.php?id=<? echo $memID; ?>">
    <img src="../images/equity.gif" width="72" height="16" border="0" /></a>
        <a href="<?php echo $FANNIE_URL; ?>modules/plugins2.0/PIKiller/PIArPage.php?id=<? echo $memID; ?>">
    <img src="../images/AR.gif" width="72" height="16" border="0" /></a>
        <a href="memControl.php?memID=<?php echo $memID ?>">
    <img src="../images/control.gif" width="72" height="16" border="0" /></a>
        <a href="<?php echo $FANNIE_URL; ?>modules/plugins2.0/PIKiller/PIPurchasesPage.php?id=<? echo $memID; ?>">
    <img src="../images/detail.gif" width="72" height="16" border="0" /></a>
        <a href="<?php echo $FANNIE_URL; ?>modules/plugins2.0/PIKiller/PIPatronagePage.php?id=<? echo $memID; ?>">
    <img src="../images/patronage.gif" /></a>
    </td>
  </tr>
  <tr>
    <td colspan="9">
    <a href="mainMenu.php" target="_top" 
        onclick="MM_nbGroup('down','group1','Members','../images/memDown.gif',1)" 
        onmouseover="MM_nbGroup('over','Members','../images/memOver.gif','../images/memUp.gif',1)" 
        onmouseout="MM_nbGroup('out')"><img src="../images/memDown.gif" alt="" name="Members" border="0" id="Members" 
        onload="MM_nbGroup('init','group1','Members','../images/memUp.gif',1)" /></a>
    <a href="javascript:;" target="_top" 
        onclick="MM_nbGroup('down','group1','Reports','../images/repDown.gif',1)" 
        onmouseover="MM_nbGroup('over','Reports','../images/repOver.gif','../images/repUp.gif',1)" 
        onmouseout="MM_nbGroup('out')"><img src="../images/repUp.gif" alt="" name="Reports" width="81" height="62" border="0" id="Reports" 
        onload="" /></a>
    <a href="javascript:;" target="_top" 
        onClick="MM_nbGroup('down','group1','Items','../images/itemsDown.gif',1)" 
        onMouseOver="MM_nbGroup('over','Items','../images/itemsOver.gif','../images/itemsUp.gif',1)" 
        onMouseOut="MM_nbGroup('out')"><img name="Items" src="../images/itemsUp.gif" border="0" alt="Items" 
        onLoad="" /></a>
    <a href="memDocs.php?memID=<?php echo $memID; ?>" target="_top" 
        onClick="MM_nbGroup('down','group1','Reference','../images/refDown.gif',1)" 
        onMouseOver="MM_nbGroup('over','Reference','../images/refOver.gif','../images/refUp.gif',1)" 
        onMouseOut="MM_nbGroup('out')"><img name="Reference" src="../images/refUp.gif" border="0" alt="Reference" 
        onLoad="" /></a>
    </td>
</tr>
</table>

<?

//echo $memID;
//echo $lName;
//echo $fName;

/*if(isset($_GET['submit2'])){
   //echo $_GET['memName'];
   $memID = $_GET['memName'];
   echo "I am here: $memID";
}*/

// the result couldn't be prefetched, implies no id or name
if($result == false){
  echo "Please enter a member number or name on the previous page.";
  echo " Click <a href = 'mainMenu.php'> here </a> to return.";
}
else{
  $numMemRows= $sql->num_rows($result);
  //echo $numMemRows;
  
  $cliplName = substr($lName,0,6) . '%';
  //echo $cliplName;                                                                                                                                         
  $clipfName = substr($fName,0,1) . '%';
  //echo $clipfName;  

  if($numMemRows < 1){
    echo "No member found <br>";
            
  }
  elseif($numMemRows == 1){
    addressList($row[0]);                        
  }
  // show multiple results
  else{
    echo "There is more than one result <br>";
    //echo $numMemRows;
    $value="CardNo";
    $label="FirstName";
    $mem="memName";
    echo "<form action=memGen.php method=GET>";
    echo "<table>";
    echo "<tr><td>";
    echo '<select name="memName">';
    while($row = $sql->fetch_row($query_drop)){
        var_dump($row);
        printf('<option value="%d">%d %s, %s</option>',
            $row['CardNo'], $row['CardNo'], $row['LastName'], $row['FirstName']);
    }
    echo '</select>';
    echo "</td><td><input type='submit' name='submit2' value='submit'></td>";
    echo "</tr></table>";
    echo "</form>";
    
  }
}

$memNext = $memID+1;
$memPrec = $memID-1;

?>
<table>
<tr>
<?php
if (!validateUserQuiet('editmembers') && !validateUserQuiet('editmembers_csc')){
  echo "<td><a href=\"{$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/members/memGen.php?memNum=$memID\">Login to edit</a> | </td>";
}
else {
  if (validateUserQuiet('editmembers'))
      echo "<td><a href=testEdit.php?memnum=$memID>[ Logged in ] Edit Info</a> | <a href=\"{$FANNIE_URL}auth/ui\">Logout</a></td>";
  else
      echo "<td><a href=limitedEdit.php?memnum=$memID>[ Logged in ] Edit Info</a> | <a href=\"{$FANNIE_URL}auth/ui\">Logout</a></td>";
}
?>
<td>
&nbsp;
</td>
<td>
<a href="memGen.php?memNum=<? echo $memPrec; ?> ">
Prev Mem</a>
</td>
<td>
<a href="memGen.php?memNum=<? echo $memNext; ?> ">
Next Mem
</a>
</td>
</tr>
</table>
</body>
</html>

<?php
// function declaration(s)
// prefetch_result is here to find a result row earlier in
// execution.  This way member id can be pulled out of the
// result and filled into menu links as needed.
function prefetch_result($memID,$lName,$fName,&$qd){
  global $sql;
  if(empty($memID)){
    if(empty($lName)){
      return false;
    }
    else{

      $lName = str_replace("'","",$lName);
      $fName = str_replace("'","",$fName);
      
      $query = $sql->prepare("SELECT CardNo 
                FROM custdata 
                WHERE 
                LastName LIKE ?
                AND FirstName LIKE ?
                ORDER BY LastName,FirstName,CardNo");
      $qd = $sql->prepare("SELECT * 
                FROM custdata 
                WHERE 
                LastName LIKE ?
                AND FirstName LIKE ?
                ORDER BY LastName,FirstName,CardNo");

      $result = $sql->execute($query, array($lName.'%', $fName.'%'));
      if ($sql->num_rows($result) > 0){
        $qd = $sql->execute($qd, array($lName.'%', $fName.'%'));
        return $result;
      }

      $query = $sql->prepare("SELECT CardNo
                FROM custdata
                WHERE 
                LastName LIKE ?
                AND FirstName LIKE ?
                ORDER BY LastName,FirstName,CardNo");
      $qd = $sql->prepare("SELECT *
                FROM custdata
                WHERE 
                LastName LIKE ?
                AND FirstName LIKE ?
                ORDER BY LastName,FirstName,CardNo");
      $result = $sql->execute($query, array('%'.$lName.'%', '%'.$fName.'%'));
      if ($sql->num_rows($result) > 0) {
        $qd = $sql->execute($qd, array('%'.$lName.'%', '%'.$fName.'%'));
        return $result;
      }

      $cliplName = substr($lName,0,6) . '%';
      //echo $cliplName . "<br>";                                                                                                                      
      $clipfName = substr($fName,0,6) . '%';
      //echo $clipfName . "<br>";                                                                                                                    
      
      $query = $sql->prepare("SELECT CardNo
                FROM custdata
                WHERE 
                LastName LIKE ?
                AND FirstName LIKE ?
                ORDER BY LastName,FirstName,CardNo");
      $qd = $sql->prepare("SELECT *
                FROM custdata
                WHERE 
                LastName LIKE ?
                AND FirstName LIKE ?
                ORDER BY LastName,FirstName,CardNo");
      $result = $sql->execute($query, array($cliplName, $clipfName));
      if ($sql->num_rows($result) > 0) {
        $qd = $sql->execute($qd, array($cliplName, $clipfName));
        return $result;
      }

      $query = $sql->prepare("SELECT CardNo
                FROM custdata
                WHERE 
                LastName LIKE ?
                AND FirstName LIKE ?
                ORDER BY LastName,FirstName,CardNo");
      $qd = $sql->prepare("SELECT *
                FROM custdata
                WHERE 
                LastName LIKE ?
                AND FirstName LIKE ?
                ORDER BY LastName,FirstName,CardNo");
      $result = $sql->execute($query, array('%'.$cliplName, '%'.$clipfName));
      $qd = $sql->execute($qd, array('%'.$cliplName, '%'.$clipfName));
      return $result;
    }
  } else{
    $query = $sql->prepare("SELECT CardNo
       FROM custdata
       WHERE 
       CardNo = ?
       AND PersonNum= 1");

    $result = $sql->execute($query, array($memID));
    if ($sql->num_rows($result) == 0){
        // alternative: try number as ID card UPC
        $query = $sql->prepare("SELECT card_no AS CardNo FROM
            memberCards WHERE upc=?");
        $result = $sql->execute($query, array(str_pad($memID,13,'0',STR_PAD_LEFT)));
        if ($sql->num_rows($result)==0){
            // alt alt: try removing check digit
            $query = $sql->prepare("SELECT card_no AS CardNo FROM
                memberCards WHERE upc=?");
            $result = $sql->execute($query, array(str_pad(substr($memID,0,strlen($memID)-1),13,'0',STR_PAD_LEFT)));
        }
    }
    return $result;
  }
}

