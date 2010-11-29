<?php
include('../../config.php');

include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');

include('memAddress.php');
include('header.html');

$memID = -1; // better failure
if(isset($_GET['memNum'])){
	$memID = $_GET['memNum'];
}elseif(isset($_POST['memNum'])){
	$memID = $_POST['memNum'];
}

if(isset($_GET['memID'])){
	$memID = $_GET['memID'];
}

if(isset($_GET['submit2'])){
   //echo $_GET['memName'];
   $memID = $_GET['memName'];
   //echo "I am here: $memID";
}
$lName = "";
if (isset($_POST['lastName']))
	$lName = $_POST['lastName'];
$fName = "";
if (isset($_POST['firstName']))
	$fName = $_POST['firstName'];

/********************************************************************
 * prefetch result: 
 * why?  this is done to address a bug where if a search was performed
 * by name and exactly one result was found the top menu items
 * (details, AR, etc) would not work because the links wouldn't have
 * a member ID set properly.  Getting the result earlier like this
 * lets the member ID get filled in before that menu is printed.
********************************************************************/

$row = "";
$result = prefetch_result($memID,$lName,$fName);
if ($sql->num_rows($result) > 0){
  $row = $sql->fetch_array($result);
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
		<a href="testDetails.php?memID=<? echo $memID; ?>">
	<img src="../images/equity.gif" width="72" height="16" border="0" /></a>
		<a href="memARTrans.php?memID=<? echo $memID; ?>">
	<img src="../images/AR.gif" width="72" height="16" border="0" /></a>
		<a href="memControl.php">
	<img src="../images/control.gif" width="72" height="16" border="0" /></a>
		<a href="memTrans.php?memID=<? echo $memID; ?>">
	<img src="../images/detail.gif" width="72" height="16" border="0" /></a>
		<a href="patronage.php?memID=<? echo $memID; ?>">
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
    $query_drop = "SELECT * FROM custdata where LastName like '$cliplName' AND FirstName LIKE '$clipfName' order by FirstName,CardNo";
    //echo $query_drop;
    $value="CardNo";
    $label="FirstName";
    $mem="memName";
    echo "<form action=memGen.php method=GET>";
    echo "<table>";
    echo "<tr><td>";
    query_to_drop($query_drop,$value,$label,$mem,$cliplName);
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
include_once($FANNIE_ROOT.'auth/login.php');
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
function prefetch_result($memID,$lName,$fName){
  global $sql;
  if(empty($memID)){
    if(empty($lName)){
      return false;
    }
    else{

      $lName = str_replace("'","",$lName);
      $fName = str_replace("'","",$fName);
      
      $query = "SELECT CardNo 
                FROM custdata 
                WHERE 
                LastName LIKE '$lName%'
                AND FirstName LIKE '$fName%'
                ORDER BY LastName,FirstName,CardNo";
      $result = $sql->query($query);
      if ($sql->num_rows($result) > 0)
	return $result;

      $query = "SELECT CardNo
                FROM custdata
                WHERE 
                LastName LIKE '%$lName%'
                AND FirstName LIKE '%$fName%'
                ORDER BY LastName,FirstName,CardNo";
      $result = $sql->query($query);
      if ($sql->num_rows($result) > 0)
	return $result;

      $cliplName = substr($lName,0,6) . '%';
      //echo $cliplName . "<br>";                                                                                                                      
      $clipfName = substr($fName,0,6) . '%';
      //echo $clipfName . "<br>";                                                                                                                    
      
      $query = "SELECT CardNo
                FROM custdata
                WHERE 
                LastName LIKE '$cliplName'
                AND FirstName LIKE '$clipfName'
                ORDER BY LastName,FirstName,CardNo";
      $result = $sql->query($query);
      if ($sql->num_rows($result) > 0)
	return $result;

      $query = "SELECT CardNo
                FROM custdata
                WHERE 
                LastName LIKE '%$cliplName'
                AND FirstName LIKE '%$clipfName'
                ORDER BY LastName,FirstName,CardNo";
      $result = $sql->query($query);
      return $result;
    }
  }
  else{
    $query = sprintf("SELECT CardNo
               FROM custdata
               WHERE 
               CardNo = %d 
               AND PersonNum= 1",$memID);

    $result = $sql->query($query);
    return $result;
  }
}
