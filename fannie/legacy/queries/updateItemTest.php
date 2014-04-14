<?php
include('../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
include('../db.php');
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

//$descr = $_POST['descript'] ;
//$price = $_POST['price'];


//$db3=$sql->connect('129.103.2.12','sa');
//$sql->select_db('POSBDAT',$db3);

/*$db1=$sql->connect('129.103.2.99','sa');
$sql->select_db('POSBDAT',$db1);
*/
extract($_POST);

//echo $today;

$Scale = (isset($_POST["Scale"])) ? 1 : 0;
if (isset($_POST['s_bycount']) && $_POST['s_bycount'] == 'On' && isset($_POST['s_type']) && $_POST['s_type'] == 'Fixed Weight') {
    $Scale = 0;
}
$FS = (isset($_POST["FS"])) ? 1 : 0;
$NoDisc = (isset($_POST["NoDisc"])) ? 0 : 1;
$inUse = (isset($_POST["inUse"])) ? 1 : 0;
$QtyFrc = (isset($_POST["QtyFrc"])) ? 1 : 0;
$local = (isset($_POST["local"])) ? (int)$_POST["local"] : 0;

/* if the user isn't validated but is logged in, then
   they don't have permission to change prices on all
   items.  So get the sub department and check that.
*/
$deptSubQ = $sql->prepare("select superID from MasterSuperDepts where dept_ID = ?");
$deptSubR = $sql->execute($deptSubQ, array($dept));
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
  include('../../item/audit.php');
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

$descript = str_replace("'","",$descript);
$descript = str_replace("\"","",$descript);
$descript = $sql->escape($descript);
if (empty($manufacturer))
	$manufacturer = '';
if (empty($distributor))
	$distributor = '';
$manufacturer = str_replace("'","",$manufacturer);
$distributor = str_replace("'","",$distributor);
// lookup vendorID by name
$vendorID = 0;
$vendor = new VendorsModel($sql);
$vendor->vendorName($distributor);
foreach($vendor->find('vendorID') as $obj) {
    $vendorID = $obj->vendorID();
    break;
}

$stamp = date("Y-m-d H:i:s");
$model = new ProductsModel($sql);
$model->upc($upc);
$model->description($descript);
$model->brand($manufacturer);
$model->normal_price($price);
$model->tax($tax);
$model->scale($Scale);
$model->foodstamp($FS);
$model->department($dept);
$model->inUse($inUse);
$model->modified($stamp);
$model->qttyEnforced($QtyFrc);
$model->discount($NoDisc);
$model->pricemethod($price_method);
$model->groupprice($vol_price);
$model->quantity($vol_qtty);
$model->local($local);
$model->default_vendor_id($vendorID);
$model->save();

$checkP = $sql->prepare("SELECT upc FROM prodExtra WHERE upc=?");
$checkR = $sql->execute($checkP, array($upc));
if ($sql->num_rows($checkR) == 0){
	$extraQ = $sql->prepare("insert into prodExtra values (?,?,?,0,0,0,'','',0,'')");
	$extraR = $sql->execute($extraQ, array($upc, $distributor, $manufacturer));
}
else {
	$extraQ = $sql->prepare("update prodExtra set manufacturer=?,distributor=? where upc=?");
	$extraR = $sql->execute($extraQ, array($manufacturer, $distributor, $upc));
}

$model->pushToLanes();

$query1 = $sql->prepare("SELECT * FROM products WHERE upc = ?");
$result1 = $sql->execute($query1, array($upc));
$row = $sql->fetch_array($result1);
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
	  $s_itemdesc = $s_longdesc;
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
    $netWeight = 0;
    if (isset($s_netwt)) {
        $netWeight = (int)$s_netwt;
    }

	$s_label = 63;
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
	$scaleQuery = $sql->prepare("select plu from scaleItems where plu=?");
	$scaleRes = $sql->execute($scaleQuery, array($s_plu));
	$nr = $sql->num_rows($scaleRes);

	/* apostrophe filter */
	$s_itemdesc = str_replace("'","",$s_itemdesc);
	$s_text = str_replace("'","",$s_text);
	$s_itemdesc = str_replace("\"","",$s_itemdesc);
	$s_text = str_replace("\"","",$s_text);

    $scaleItem = new ScaleItemsModel($dbc);
    $scaleItem->plu($s_plu);
    $scaleItem->price($s_price);
    $scaleItem->itemdesc($s_itemdesc);
    $scaleItem->weight($wt);
    $scaleItem->bycount($bc);
    $scaleItem->tare($tare);
    $scaleItem->shelflife($shelflife);
    $scaleItem->text($s_text);
    $scaleItem->label($s_label);
    $scaleItem->graphics($graphics);
    $scaleItem->netWeight($netWeight);
    $scaleItem->save();

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
    /*
	include('hobartcsv/parse.php');

	// generate csv files and place them in the
	// DGW import directory
    // @deprecated 29Mar14
	parseitem('ChangeOneItem',$s_plu,$s_itemdesc,$tare,$shelflife,$s_price,
		  $s_bycount,$s_type,$s_exception,$s_text,$s_label,$graphics);
    */

    $item_info = array(
        'RecordType' => 'ChangeOneItem',
        'PLU' => $s_plu,
        'Description' => $s_itemdesc,
        'Tare' => $tare,
        'ShelfLife' => $shelflife,
        'Price' =>$s_price,
        'Label' => $s_label,
        'ExpandedText' => $s_text,
        'ByCount' => ($s_bycount == 'on') ? 1 : 0,
    );
    if ($netWeight != 0) {
        $item_info['NetWeight'] = $netWeight;
    }
    if ($graphics) {
        $item_info['Graphics'] = $graphics;
    }
    // normalize type + bycount; they need to match
    if ($item_info['ByCount'] && $s_type == 'Random Weight') {
        $item_info['Type'] = 'By Count';
    } else if ($s_type == 'Fixed Weight') {
        $item_info['Type'] = 'Fixed Weight';
        $item_info['ByCount'] = 1;
    } else {
        $item_info['Type'] = 'Random Weight';
        $item_info['ByCount'] = 0;
    }

    HobartDgwLib::writeItemsToScales($item_info);
}

$udesc = isset($_REQUEST['u_desc'])?$_REQUEST['u_desc']:'';
$ubrand = isset($_REQUEST['u_brand'])?$_REQUEST['u_brand']:'';
$usize = isset($_REQUEST['u_size'])?$_REQUEST['u_size']:'';
$utext = isset($_REQUEST['u_long_text'])?$_REQUEST['u_long_text']:'';
$utext = str_replace("\r","",$utext);
$utext = str_replace("\n","<br />",$utext);
$utext = preg_replace("/[^\x01-\x7F]/","", $utext); // strip non-ASCII (word copy/paste artifacts)
$uonline = isset($_REQUEST['u_enableOnline'])?1:0;
$uexpires = isset($_REQUEST['u_expires'])?$_REQUEST['u_expires']:'';
if (!empty($udesc) || !empty($ubrand) || !empty($usize)){
	include($FANNIE_ROOT.'src/Credentials/OutsideDB.is4c.php');
	$dbs = array($sql);
	$q = $sql->prepare("SELECT special_price,discounttype FROM products WHERE upc=?");
	$r = $sql->execute($q, array($upc));
	$w = $sql->fetch_row($r);
	if ($uonline == 0){
		$del = $dbc->prepare("DELETE FROM productUser WHERE upc=?");
        $dbc->execute($del, array($upc));
		$del = $dbc->prepare("DELETE FROM productExpires WHERE upc=?");
        $dbc->execute($del, array($upc));
	}
	else{
		$dbs[] = $dbc;
		$del = $dbc->prepare("DELETE FROM products WHERE upc=?");
        $dbc->execute($del, array($upc));
		$query99 = $dbc->prepare("INSERT INTO products (upc,description,normal_price,pricemethod,groupprice,quantity,special_price,specialpricemethod,
				specialgroupprice,specialquantity,start_date,end_date,department,size,tax,foodstamp,scale,scaleprice,mixmatchcode,
				modified,advertised,tareweight,discount,discounttype,unitofmeasure,wicable,qttyEnforced,idEnforced,cost,inUse,numflag,
				subdept,deposit,local)
				VALUES(?,?,?,0,0.00,0,?,0,0.00,0,'','',?,0,?,?,?,0,0,now(),0,0,?,
				?,0,0,0,0,0.00,1,
				0,0,0.00,?)");
		$dbc->execute($query99, array($upc, $descript, $price, $w['special_price'], $dept, $tax, $FS, $Scale, $NoDisc, $w['discounttype'], $local));
	}

	foreach($dbs as $con){
		$char = strstr($utext,"start");
        $pu_model = new ProductUserModel($con);
        $pu_model->upc($upc);
        $pu_model->brand($ubrand);
        $pu_model->description($udesc);
        $pu_model->sizing($usize);
        $pu_model->long_text($utext);
        $pu_model->enableOnline($uonline);
        $pu_model->save();

		$prep = $con->prepare("SELECT * FROM productExpires WHERE upc=?");
        $chk = $con->execute($prep, array($upc));
		if ($con->num_rows($chk) == 0){
			$ins = $con->prepare("INSERT INTO productExpires (upc,expires) VALUES (?, ?)");
            $con->execute($ins, array($upc, $uexpires));
		}
		else {
			$up = $con->prepare("UPDATE productExpires SET expires=? WHERE upc=?");
            $con->execute($up, array($uexpires, $upc));
		}
	}
}


if(!empty($likeCode)){
   if ($likeCode == -1){
     $updateLikeQ = $sql->prepare("delete from upcLike where upc=?");
     $updateLikeR = $sql->execute($updateLikeQ, array($upc));
   }
   else if(!isset($update)){
	//Update all like coded items to $upc
	$likeQuery = $sql->prepare("UPDATE products SET normal_price = ?,department = ?,tax = ?,scale=?,foodstamp=?,inUse=?, modified = ?,
			pricemethod=?,groupprice=?,quantity=?,local=?
                  WHERE upc IN (SELECT u.upc FROM upcLike AS u WHERE u.likeCode = ?)");
	
	$likeResult = $sql->execute($likeQuery, array($price, $dept, $tax, $Scale, $FS, $inUse, $modDate, $price_method, $vol_price, $vol_qtty, $local, $likeCode));

    
	    //INSERTED HERE TO INSERT UPDATE INTO prodUpdate for likecoded items. 
	    $selectQ = $sql->prepare("SELECT * FROM upcLike WHERE likecode = ?");
	    //echo $selectQ;
	    $selectR = $sql->execute($selectQ, array($likeCode));
        $prodUpdate = new ProdUpdateModel($sql);
	    while($selectW = $sql->fetch_array($selectR)){
	       $upcL = $selectW['upc'];
	       if($upcL != $upc){
                $prodUpdate->reset();
                $prodUpdate->upc($upcL);
                $prodUpdate->logUpdate(ProdUpdateModel::UPDATE_EDIT);
                $p_model = new ProductsModel($sql);
                $p_model->upc($upcL);
                $p_model->pushToLanes();
	      }   
	    }
    	
	$delQ = $sql->prepare("DELETE FROM upcLike WHERE upc = ?");
	$delR = $sql->execute($delQ, array($upc));
	$updateLikeQ = $sql->prepare("INSERT INTO upcLike VALUES(?,?)");
	$updateLikeR = $sql->execute($updateLikeQ, array($upc, $likeCode));

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
        $query2 = $sql->prepare("SELECT * FROM departments where dept_no = ?");
        $result2 = $sql->execute($query2, array($dept));
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
