<?php
include('../../config.php');

require($FANNIE_ROOT.'auth/login.php');
$validatedUser = validateUserQuiet('pricechange');
$auditedUser = validateUserQuiet('audited_pricechange');
$logged_in = checkLogin();
refreshSession();
?>
<html>
<head>
<SCRIPT LANGUAGE="JavaScript">

<!-- This script and many more are available free online at -->
<!-- The JavaScript Source!! http://javascript.internet.com -->
<!-- John Munn  (jrmunn@home.com) -->

<!-- Begin
 function putFocus(formInst, elementInst) {
  if (document.forms.length > 0) {
   document.forms[formInst].elements[elementInst].focus();
  }
 }
// The second number in the "onLoad" command in the body
// tag determines the form's focus. Counting starts with '0'
//  End -->
//function shelftag(){
//    testwindow= window.open ("addShelfTag.php?upc=", "Add Shelf Tag","location=0,status=1,scrollbars=1,width=300,height=220");
//    testwindow.moveTo(50,50);
//}
function shelftag(upc){
testwindow= window.open ("addShelfTag.php?upc="+upc, "New Shelftag","location=0,status=1,scrollbars=1,width=300,height=220");
testwindow.moveTo(50,50);
}

</script>

</head>
<BODY onLoad='putFocus(0,0);'>

<?php
if (!class_exists("SQLManager")) require_once($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');

extract($_POST);

$price = trim($price);
if (!is_numeric($price))
	$price = 0;

if(!isset($tax)){
	$tax = 0;
}	
if(!isset($FS)){
	$FS = 0;
}
if(!isset($Scale)){
	$Scale = 0;
}

if(empty($likeCode)){
  $likeCode = 0;
}

if(!isset($QtyFrc)){
   $QtyFrc = 0;
}

$NoDisc = 1;

$inUse= 1;

if (!isset($local)){
   $local = 0;
}

$del99Q = "DELETE FROM products where upc = '$upc'";
//$del99R = $sql->query($del99Q,$db1);
$delISR = $sql->query($del99Q);

//echo '<br>' .$upc;
//echo $descript;
//echo $price;

// set the tax and foodstamp according to values in departments
$taxfsQ = "select dept_tax,dept_fs,superID,dept_discount 
from departments as d left join
MasterSuperDepts AS s ON d.dept_no=s.dept_ID
where dept_no = $dept";
$taxfsR = $sql->query($taxfsQ);
if ($sql->num_rows($taxfsR) == 1){
  $taxfsRow = $sql->fetch_array($taxfsR);
  $tax = $taxfsRow[0];
  $FS = $taxfsRow[1];
  if ($taxfsRow[3] == 0)
	$NoDisc = 0;
}

/* if the user isn't validated but is logged in, then
   they don't have permission to change prices on all
   items.  So get the sub department and check that.
*/
$deptSub = $taxfsRow[2];
if (!$validatedUser && !$auditedUser && $logged_in){
  $validatedUser = validateUserQuiet('pricechange',$deptSub);
}

$uid = 1005;
if ($validatedUser){
  $validatedUID = getUID($validatedUser);
  $uid = $validatedUID;
}
elseif ($auditedUser){
  $auditedUID = getUID($auditedUser);
  $uid = $auditedUID;
  require('audit.php');
  if (!empty($likeCode))
    audit($deptSub,$auditedUser,$upc,$descript,$price,$tax,$FS,$Scale,$NoDisc,$likeCode);
  else
    audit($deptSub,$auditedUser,$upc,$descript,$price,$tax,$FS,$Scale,$NoDisc);
}
if (!$validatedUser && !$auditedUser && substr($upc,0,3) != "002"){
	echo "Please ";
	echo "<a href={$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/queries/productTest.php?upc=$upc>";
	echo "login</a> to add new items";
	return;
}

$descript = str_replace("'","",$descript);
$descript = $sql->escape($descript);

$query1 = "INSERT INTO prodUpdate 
        VALUES('$upc',$descript, 
        $price,$dept,
        $tax,$FS,        $Scale,
        $likeCode,
        getdate(),
        $uid,
        $QtyFrc,
        $NoDisc,
        $inUse)
        ";
//echo $query1;
$result1 = $sql->query($query1);


$query99 = "INSERT INTO products
		VALUES('$upc',$descript,$price,0,0.00,0,0.00,0,0.00,0,'','',$dept,0,$tax,$FS,$Scale,0,0,getdate(),0,0,$NoDisc,0,0,0,0,0,0.00,1,
		0,0,0.00,$local)";
//echo $query99;
//$result = $sql->query($query99,$db1);
$resultI = $sql->query($query99);

//$insertR = $sql->query('EXEC insertItemProc',$db);
require('laneUpdates.php');
addProductAllLanes($upc);

if (empty($manufacturer))
	$manufacturer = '';
if (empty($distributor))
	$distributor = '';
$manufacturer = preg_replace("/\\\'/","",$manufacturer);
$distributor = preg_replace("/\\\'/","",$distributor);
$checkQ = "select * from prodExtra where upc='$upc'";
$checkR = $sql->query($checkQ);
if ($sql->num_rows($checkR) == 0){
	$extraQ = "insert into prodExtra values ('$upc','$distributor','$manufacturer',0,0,0,'','',0,'')";
	$extraR = $sql->query($extraQ);
}

//$result99 = $sql->query($query99,$db1);
if(isset($likeCode) && $likeCode > 0){
	$delLikeCode = "DELETE FROM upcLike WHERE upc = '$upc'";
	$insLikeCode = "INSERT INTO upcLike VALUES('$upc','$likeCode')";
	$delLikeCodeR = $sql->query($delLikeCode);
	$insLikeCodeR = $sql->query($insLikeCode);
}

$query1 = "SELECT * FROM products WHERE upc = '$upc'";
$result1 = $sql->query($query1);
$row = $sql->fetch_array($result1);
//echo '<br>'.$query1;

if (isset($_REQUEST['shelftag'])){
	printf("<script type=\"text/javascript\">
		shelftag('%s');
		</script>",$upc);
}

echo "<table>";
        echo "<tr><td align=right><b>UPC</b></td><td><font color='red'>".$row[0]."</font><input type=hidden value='$row[0]' name=upc></td>";
        echo "</tr><tr><td><b>Description</b></td><td>$row[1]</td>";
        echo "<td><b>Price</b></td><td>$$row[2]</td></tr></table>";
        echo "<table border=0><tr>";
        echo "<th>Dept<th>Tax<th>FS<th>Scale</b>";
        echo "</tr>";
        echo "<tr>";
        //$dept=$row[12];
        $query2 = "SELECT * FROM departments where dept_no = $dept";
        $result2 = $sql->query($query2);
	$num = $sql->num_rows($result2);
	$row2 = $sql->fetch_array($result2);
	echo "<td>";
        echo $dept.' ' . $row2['dept_name'];
        echo " </td>";  
        echo "<td align=center>";
        echo "Reg <input type=radio name=tax";
        if($row[14]==1){
                echo " checked";
        }
        echo " value=1><br>Deli<input type=radio name = tax value=2";
		if($row[14]==2){
			echo " checked";
		}
	echo "><br>";
	echo "No Tax<input type=radio name=tax value=0";
		if($row[14]==0){
			echo " checked";
		}
        echo "></td><td align=center><input type=checkbox name=FS";     
        if($row[15]==1){
                echo " checked";
        }
        echo "></td><td align=center><input type=checkbox name=scale";
        if($row[16]==1){
                echo " checked";
        }
        echo "></td></tr>";
        
        echo "</table>";
        echo "<hr>";
        //echo "I am here.";
        echo "<form action=productTest.php method=post>";
        echo "<input name=upc type=text id=upc> Enter UPC/PLU here<br>";
        echo "<input name=submit type=submit value=submit>";
	echo " <input type=checkbox name=prefix> Manufacturer Prefix";
        echo "</form>";
?>

