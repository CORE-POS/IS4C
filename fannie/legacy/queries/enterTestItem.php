<?php
include('../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
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
testwindow= window.open ("../../item/addShelfTag.php?upc="+upc, "New Shelftag","location=0,status=1,scrollbars=1,width=300,height=220");
testwindow.moveTo(50,50);
}

</script>

</head>
<BODY onLoad='putFocus(0,0);'>

<?php
include('../db.php');

extract($_POST);

$upc = str_pad($upc, 13, '0', STR_PAD_LEFT);

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

$del99Q = $sql->prepare("DELETE FROM products where upc = ?");
$delISR = $sql->execute($del99Q, array($upc));

//echo '<br>' .$upc;
//echo $descript;
//echo $price;

// set the tax and foodstamp according to values in departments
$taxfsQ = $sql->prepare("select dept_tax,dept_fs,superID,dept_discount 
from departments as d left join
MasterSuperDepts AS s ON d.dept_no=s.dept_ID
where dept_no = ?");
$taxfsR = $sql->execute($taxfsQ, array($dept));
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
  require('../../item/audit.php');
  if (!empty($likeCode))
    audit($deptSub,$auditedUser,$upc,$descript,$price,$tax,$FS,$Scale,$NoDisc,$likeCode);
  else
    audit($deptSub,$auditedUser,$upc,$descript,$price,$tax,$FS,$Scale,$NoDisc);
}
if (!$validatedUser && !$auditedUser && substr($upc,0,3) != "002"){
	echo "Please ";
	echo "<a href=/auth/ui/loginform.php?redirect=/queries/productTest.php?upc=$upc>";
	echo "login</a> to add new items";
	return;
}

$descript = str_replace("'","",$descript);
$descript = str_replace("\"","",$descript);
$descript = $sql->escape($descript);
if (empty($manufacturer))
	$manufacturer = '';
if (empty($distributor))
	$distributor = '';
$manufacturer = preg_replace("/\\\'/","",$manufacturer);
$distributor = preg_replace("/\\\'/","",$distributor);
// lookup vendorID by name
$vendorID = 0;
$vendor = new VendorsModel($sql);
$vendor->vendorName($distributor);
foreach($vendor->find('vendorID') as $obj) {
    $vendorID = $obj->vendorID();
    break;
}

$stamp = date("Y-m-d H:i:s");

// use model instead of raw INSERT query
$model = new ProductsModel($sql);
$model->upc($upc);
$model->description($descript);
$model->brand($manufacturer);
$model->normal_price($price);
$model->pricemethod(0);
$model->groupprice(0.00);
$model->quantity(0);
$model->special_price(0.00);
$model->specialpricemethod(0);
$model->specialgroupprice(0.00);
$model->specialquantity(0);
$model->start_date('');
$model->end_date('');
$model->department($dept);
$model->size(0);
$model->tax($tax);
$model->foodstamp($FS);
$model->scale($Scale);
$model->scaleprice(0);
$model->mixmatchcode(0);
$model->modified($stamp);
$model->advertised(0);
$model->tareweight(0);
$model->discount($NoDisc);
$model->discounttype(0);
$model->unitofmeasure(0);
$model->wicable(0);
$model->qttyEnforced(0);
$model->idEnforced(0);
$model->cost(0.00);
$model->inUse(1);
$model->numflag(0);
$model->subdept(0);
$model->deposit(0.00);
$model->local($local);
$model->default_vendor_id($vendorID);
$model->save();

$model->upc($upc);
$model->pushToLanes();

$checkQ = $sql->prepare("select * from prodExtra where upc=?");
$checkR = $sql->execute($checkQ, array($upc));
if ($sql->num_rows($checkR) == 0){
	$extraQ = $sql->prepare("insert into prodExtra values (?,?,?,0,0,0,'','',0,'')");
	$extraR = $sql->execute($extraQ, array($upc, $distributor, $manufacturer));
}

if(isset($likeCode) && $likeCode > 0){
	$delLikeCode = $sql->prepare("DELETE FROM upcLike WHERE upc = ?");
	$insLikeCode = $sql->prepare("INSERT INTO upcLike VALUES(?,?)");
	$delLikeCodeR = $sql->execute($delLikeCode, array($upc));
	$insLikeCodeR = $sql->execute($insLikeCode, array($upc, $likeCode));
}

$query1 = $sql->prepare("SELECT * FROM products WHERE upc = ?");
$result1 = $sql->execute($query1, array($upc));
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
        $query2 = $sql->prepare("SELECT * FROM departments where dept_no = ?");
        $result2 = $sql->execute($query2, array($dept));
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
        echo "<input name=upc type=text id=upc> Enter <select name=ntype>
		<option>UPC</option>
		<option>SKU</option>
		<option>Brand Prefix</option>
		</select> here<br>";
        echo "<input name=submit type=submit value=submit>";
        echo "</form>";
?>

