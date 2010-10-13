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
</script>
</head>

<?php
echo "<BODY onLoad='putFocus(0,0);'>";

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../db.php');
//$descr = $_POST['descript'] ;
//$price = $_POST['price'];

//$db2=$sql->connect('129.103.2.11','sa');
//$sql->select_db('POSBDAT',$db2);

//$db3=$sql->connect('129.103.2.12','sa');
//$sql->select_db('POSBDAT',$db3);

/*$db1=$sql->connect('129.103.2.99','sa');
$sql->select_db('POSBDAT',$db1);
*/
extract($_POST);

//echo $today;

$Scale = (isset($_POST["Scale"])) ? 1 : 0;
$FS = (isset($_POST["FS"])) ? 1 : 0;
$NoDisc = (isset($_POST["NoDisc"])) ? 0 : 1;
$inUse = (isset($_POST["inUse"])) ? 1 : 0;
$QtyFrc = (isset($_POST["QtyFrc"])) ? 1 : 0;
$local = (isset($_POST["local"])) ? 1 : 0;

/* if the user isn't validated but is logged in, then
   they don't have permission to change prices on all
   items.  So get the sub department and check that.
*/
$deptSubQ = "select superID from MasterSuperDepts where dept_ID = $dept";
$deptSubR = $sql->query($deptSubQ);
$deptSubW = $sql->fetch_array($deptSubR);
$deptSub = $deptSubW[0];
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
  include('audit.php');
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

$price_method=0;
$vol_price = 0;
$vol_qtty = 0;
if (isset($_POST['doVolume']) && isset($_POST['vol_price']) && isset($_POST['vol_qtty'])){
	$price_method=2;
	if (isset($_POST["pricemethod"]) && $_POST["pricemethod"] != 0)
		$price_method = $_POST["pricemethod"];
	$vol_price = $_POST['vol_price'];
	$vol_qtty = $_POST['vol_qtty'];
}
if (empty($vol_price) || $vol_price == 0) {
	$price_method = 0;
	$vol_price = 0;
}
if (empty($vol_qtty) || $vol_qtty == 0){
	$price_method = 0;
	$vol_qtty = 0;
}

$descript = $sql->escape($descript);

$query = "UPDATE Products 
	SET description = $descript, 
	normal_price=$price,
	tax='$tax',
	Scale='$Scale',
	foodstamp='$FS',
	department = '$dept',
	inUse = '$inUse',
	modified= getdate(),
        qttyEnforced = '$QtyFrc',
        discount='$NoDisc',
	pricemethod='$price_method',
	groupprice=$vol_price,
	quantity='$vol_qtty',
	local=$local
	where upc ='$upc'";
//echo $query;

$query1 = "INSERT INTO prodUpdate 
        VALUES('$upc',$descript, 
        $price,$dept,
        $tax,$FS,
        $Scale,
        $likeCode,
	getdate(),
	$uid,
	$QtyFrc,
        $NoDisc,
	$inUse)
	";
//////////echo $query1;
//$resultU = $sql->query($query1,$db);
$result1 = $sql->query($query1);

$result = $sql->query($query);

if (empty($manufacturer))
	$manufacturer = '';
if (empty($distributor))
	$distributor = '';
$manufacturer = str_replace("'","",$manufacturer);
$distributor = str_replace("'","",$distributor);
$extraQ = "update prodExtra set manufacturer='$manufacturer',distributor='$distributor' where upc='$upc'";
$extraR = $sql->query($extraQ);

require('laneUpdates.php');
updateProductAllLanes($upc);

$query1 = "SELECT * FROM Products WHERE upc = '$upc'";
$result1 = $sql->query($query1);
$row = $sql->fetch_array($result1);
//echo '<br>'.$query1;
//$modDateQ = "SELECT MAX(modified) FROM prodUpdate WHERE upc = '$upc'";
//echo $modDateQ;
//$modDateR = $sql->query($modDateQ);
//$modDateW = $sql->fetch_row($modDateR);
//$strMod = strtotime($modDateW[0]);
//echo $strMod;
//echo $row['modified'];
$strMod = strtotime($row['modified']);
//echo $strMod;
//$modDate = date('Y-m-j h:i:s',$strMod);
//echo $modDate;

$modDate = $row['modified'];

// Hobart Scale stuff
// send data to scales if appropriate fields
// are present
if (!empty($s_plu)){
	// grab price, item description from the main input
	// fields.  This eliminates the need for separate
	// inputs for these values on the scale input form
	// (reducing necessary input AND chances of mismatches)
	$s_itemdesc = $descript;
	if ($s_longdesc != ""){
	  $s_itemdesc = $dbc->escape($s_longdesc);
	}
	$s_price = trim($price," ");

	// bycount is a simple boolean in the db
	// set $bc 0 or 1 accordingly
	$s_bycount = "";
	if (isset($_POST["s_bycount"])) $s_bycount = $_POST["s_bycount"];
	$bc = 0;
	if ($s_bycount == "on"){
	   $bc = 1;
	}
	// weight is also a simple boolean in the db
	$wt = 0;
	if ($s_type == "Fixed Weight"){
	   $wt = 1;
	}
	$tare = 0;
	if (!empty($s_tare)){
	  $tare = $s_tare;
	}
	if (!isset($s_text)){
	  $s_text = " ";
	}
	$shelflife = 0;
	if (!empty($s_shelflife)){
	  $shelflife = $s_shelflife;
	}
	if (!is_numeric($shelflife)){
	  if (preg_match("/(\d*).*/",$shelflife,$matches) == 1)
	    $shelflife = $matches[1];
	  else
	    $shelflife = 0;
	}

	$s_label = 103;
	if ($s_label == "horizontal" && $s_type == "Random Weight")
		$s_label = 133;
	elseif ($s_label == "horizontal" && $s_type == "Fixed Weight")
		$s_label = 63;
	elseif ($s_label == "vertical" && $s_type == "Random Weight")
		$s_label = 103;
	elseif ($s_label == "vertical" && $s_type == "Fixed Weight")
		$s_label = 23;

	$graphics = 0;
	$s_graphics = "";
	if (isset($_POST["s_graphics"])) $s_graphics = $_POST["s_graphics"];
	if ($s_graphics == "on"){
	  $graphics = 121;
	  $s_label = 53;
	}

	// for right now, check if the item is in the scaleItems
	// table and add it if it isn't
	// otherwise, update the row
	$scaleQuery = "Select plu from scaleItems where plu='{$s_plu}'";
	$scaleRes = $sql->query($scaleQuery);
	$nr = $sql->num_rows($scaleRes);

	/* apostrophe filter */
	$s_itemdesc = str_replace("'","",$s_itemdesc);
	$s_text = str_replace("'","",$s_text);
	$s_itemdesc = str_replace("\"","",$s_itemdesc);
	$s_text = str_replace("\"","",$s_text);

	if ($nr == 0){
	   $scaleQuery = "insert into scaleItems (plu,price,itemdesc,exceptionprice,
						 weight,bycount,tare,shelflife,text,label,graphics) 
						 values
						 ('$s_plu',$s_price,'$s_itemdesc',
						 $s_exception,$wt,$bc,$tare,
						 $shelflife,'$s_text',$s_label,$graphics)";
	   //echo $scaleQuery;
	   $scaleRes = $sql->query($scaleQuery);
	}
	else {
	   $scaleQuery = "update scaleItems set
			 price = $s_price,
			 itemdesc = '$s_itemdesc',
			 exceptionprice = $s_exception,
			 weight = $wt,
			 bycount = $bc,
			 tare = $tare,
			 shelflife = $shelflife,
			 text = '$s_text',
			 label = $s_label,
			 graphics = $graphics
			 where plu = '$s_plu'";
	  //echo $scaleQuery;
	  $scareRes = $sql->query($scaleQuery);
	}

	$datetime = date("m/d/Y h:i:s a");
	$fp = fopen('hobartcsv/query.log','a');
	fwrite($fp,"$datetime\n$scaleQuery\n\n");
	fclose($fp);

	// trim the upc down to size
	// grabbing the 4 or 5 non-zero digits after the 2
	// might just need 4, ask chris
	preg_match("/002(\d\d\d\d)0/",$s_plu,$matches);
	$s_plu = $matches[1];

	//echo "<br />plu ".$s_plu;

	// hobart csv functionality
	include('hobartcsv/parse.php');

	// generate csv files and place them in the
	// DGW import directory
	parseitem('ChangeOneItem',$s_plu,$s_itemdesc,$tare,$shelflife,$s_price,
		  $s_bycount,$s_type,$s_exception,$s_text,$s_label,$graphics);
}


if(!empty($likeCode)){
   if ($likeCode == -1){
     $updateLikeQ = "delete from upcLike where upc='$upc'";
     $updateLikeR = $sql->query($updateLikeQ);
   }
   else if(!isset($update)){
	//Update all like coded items to $upc
	$likeQuery = "UPDATE Products SET normal_price = $price,department = '$dept',tax = '$tax',scale='$Scale',foodstamp='$FS',inUse='$inUse', modified = '$modDate' ,
			pricemethod='$price_method',groupprice=$vol_price,quantity='$vol_qtty',local=$local
                  FROM Products as p, upcLike as u WHERE u.upc = p.upc and u.likeCode = '$likeCode'";
	
	//echo $likeQuery;
	$likeResult = $sql->query($likeQuery);

    
	    //INSERTED HERE TO INSERT UPDATE INTO prodUpdate for likecoded items. 
	    $selectQ = "SELECT * FROM upcLike WHERE likecode = $likeCode";
	    //echo $selectQ;
	    $selectR = $sql->query($selectQ);
	    while($selectW = $sql->fetch_array($selectR)){
	       $upcL = $selectW['upc'];
	       if($upcL != $upc){
		  $insQ= "INSERT INTO prodUpdate SELECT upc, description,normal_price,department,tax,foodstamp,scale,$likeCode,getdate(),$uid,qttyEnforced,discount,1
			      FROM products where upc = '$upcL'";
		  $insR= $sql->query($insQ);
		  //echo $selectQ . "<br>";
	      }   
	    }
    	
	//check to see if $upc exists in upcLike, if not, then add it to upcLike
	$checkLikeQ = "SELECT * FROM upcLike WHERE upc = '$upc'";
	$checkLikeR = $sql->query($checkLikeQ);
	$checkLikeN = $sql->num_rows($checkLikeR); //returns 0 if not in upcLike

   	//echo $upc." ".$checkLikeN."<br>";
	if($checkLikeN == 0){
		$updateLikeQ = "INSERT INTO upcLike VALUES('$upc','$likeCode')";
		$updateLikeR = $sql->query($updateLikeQ);
	}
	else {
	  $updateLikeQ = "Update upcLike set likeCode=$likeCode where upc='$upc'";
	  //echo $updateLikeQ;
	  $updateLikeR = $sql->query($updateLikeQ);
	}
        // more than one item may have been changed. Push the whole table to the lanes
	exec("touch /pos/sync/scheduled/products");
    }
}

echo "<table>";
        echo "<tr><td align=right><b>UPC</b></td><td><font color='red'>".$row[0]."</font><input type=hidden value='$row[0]' name=upc></td>";
        echo "</tr><tr><td><b>Description</b></td><td>$row[1]</td>";
        echo "<td><b>Price</b></td><td>$$row[2]</td></tr></table>";
        echo "<table border=0><tr>";
        echo "<th>Dept<th>Tax<th>FS<th>Scale</b>";
        echo "</tr>";
        echo "<tr>";
        $dept=$row[12];
        $query2 = "SELECT * FROM Departments where dept_no = $dept";
        $result2 = $sql->query($query2);
	$row2 = $sql->fetch_array($result2);
	echo "<td>";
        echo $dept . ' ' . 
	$row2['dept_name'];
        echo " </td>";  
        echo "<td align=right>";
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
        //echo "<tr><td>" . $row[4] . "</td><td>" . $row[5]. "</td><td>" . $row[6] ."</td><td>" . $row[7] . "</td><td>" . $row[8] . "</td></tr>";
        //echo "<tr><td>" . $row[9] . "</td><td>" . $row[10] . "</td><td>" . $row[11] . "</td><td>" . $row[12] . "</td>";
        echo "<tr><td><font size=-1 color=purple><i>Last Modified</i></font></td>";
	echo "<td colspan=3><font size=-1 color=purple><i>$modDate</i></td></tr>"; 
        echo "</table>";
        //echo "I am here.";
	echo "<hr>"; 
       echo "<form action=productTest.php method=post>";
        echo "<input name=upc type=text id=upc> Enter <select name=ntype>
		<option>UPC</option>
		<option>SKU</option>
		<option>Brand Prefix</option>
		</select> here<br>";
        echo "<input name=submit type=submit value=submit>";
        echo "</form>";

?>
