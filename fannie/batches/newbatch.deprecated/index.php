<?php
/*******************************************************************************

    Copyright 2009,2010 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include('../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
$dbc = FannieDB::get($FANNIE_OP_DB);
include('audit.php');
include('../../auth/login.php');

$user = checkLogin();
$uid = 0;
if ($user) {
	$uid = getUID($user);
	refreshSession();
}

if (!function_exists("updateProductAllLanes")) include($FANNIE_ROOT.'item/laneUpdates.php');

$batchtypes = array();
$typesQ = "select batchTypeID,typeDesc from batchType order by batchTypeID";
$typesR = $dbc->query($typesQ);
while ($typesW = $dbc->fetch_array($typesR))
	$batchtypes[$typesW[0]] = $typesW[1];
	
$ownersQ = "SELECT super_name FROM MasterSuperDepts GROUP BY super_name ORDER BY super_name";
$ownersR = $dbc->query($ownersQ);
$owners = array('');
while($ownersW = $dbc->fetch_row($ownersR))
	array_push($owners,$ownersW[0]);
array_push($owners,'IT');

/* ajax responses 
 * $out is the output sent back
 * by convention, the request name ($_GET['action'])
 * is prepended to all output so the javascript receiver
 * can handle responses differently as needed.
 * a backtick separates request name from data
 */
$out = '';
if (isset($_GET['action'])){
	// prepend request name & backtick
	$out = $_GET['action']."`";
	// switch on request name
	switch ($_GET['action']){
	case 'newBatch':
		$type = $_GET['type'];
		$name = $_GET['name'];
		$startdate = $_GET['startdate']." 00:00:00.00";
		$enddate = $_GET['enddate']." 00:00:00.00";
		$owner = $_GET['owner'];
		$priority = isset($_REQUEST['priority']) ? $_REQUEST['priority'] : 0;
		
		$infoQ = "select discType from batchType where batchTypeID=$type";
		$infoR = $dbc->query($infoQ);
		$discounttype = array_pop($dbc->fetch_array($infoR));
		
		$insQ = sprintf("INSERT INTO batches (startDate,endDate,batchName,batchType,
				discounttype,priority) VALUES (%s,%s,%s,%d,%d,%d)",
				$dbc->escape($startdate),$dbc->escape($enddate),
				$dbc->escape($name),$type,$discounttype,$priority);
		$insR = $dbc->query($insQ);
		$id = $dbc->insert_id();
		
		$insQ = "insert batchowner values ($id,'$owner')";
		$insR = $dbc->query($insQ);
		
		$out .= batchListDisplay();
		break;
	case 'deleteBatch':
		$id = $_GET['id'];

		$unsaleQ = "UPDATE products AS p LEFT JOIN batchList as b
			ON p.upc=b.upc
			SET special_price=0,
			specialpricemethod=0,specialquantity=0,
			specialgroupprice=0,discounttype=0,
			start_date='1900-01-01',end_date='1900-01-01'
			WHERE b.upc NOT LIKE '%LC%'
			AND b.batchID=$id";
		if ($FANNIE_SERVER_DBMS=="MSSQL"){
			$unsaleQ = "UPDATE products SET special_price=0,
				specialpricemethod=0,specialquantity=0,
				specialgroupprice=0,discounttype=0,
				start_date='1900-01-01',end_date='1900-01-01'
				FROM products AS p, batchList as b
				WHERE p.upc=b.upc AND b.upc NOT LIKE '%LC%'
				AND b.batchID=$id";
		}
		$unsaleR = $dbc->query($unsaleQ);

		$unsaleLCQ = "UPDATE products AS p LEFT JOIN
			likeCodeView AS v ON v.upc=p.upc LEFT JOIN
			batchList AS l ON l.upc=concat('LC',convert(v.likeCode,char))
			SET special_price=0,
			specialpricemethod=0,specialquantity=0,
			specialgroupprice=0,p.discounttype=0,
			start_date='1900-01-01',end_date='1900-01-01'
			WHERE l.upc LIKE '%LC%'
			AND l.batchID=$id";
		if ($FANNIE_SERVER_DBMS=="MSSQL"){
			$unsaleLCQ = "UPDATE products
				SET special_price=0,
				specialpricemethod=0,specialquantity=0,
				specialgroupprice=0,discounttype=0,
				start_date='1900-01-01',end_date='1900-01-01'
				FROM products AS p LEFT JOIN
				likeCodeView AS v ON v.upc=p.upc LEFT JOIN
				batchList AS l ON l.upc=concat('LC',convert(v.likeCode,char))
				WHERE l.upc LIKE '%LC%'
				AND l.batchID=$id";
		}
		$unsaleLCR = $dbc->query($unsaleLCQ);
		
		$delQ = "delete from batches where batchID=$id";
		$delR = $dbc->query($delQ);
		
		$delQ = "delete from batchList where batchID=$id";
		$delR = $dbc->query($delQ);

		$out .= batchListDisplay();
		break;
	case 'saveBatch':
		$id = $_GET['id'];
		$name = $_GET['name'];
		$type = $_GET['type'];
		$startdate = $_GET['startdate'];
		$enddate = $_GET['enddate'];
		$owner = $_GET['owner'];
		
		$infoQ = "select discType from batchType where batchTypeID=$type";
		$infoR = $dbc->query($infoQ);
		$discounttype = array_pop($dbc->fetch_array($infoR));
		
		$upQ = "update batches set batchName='$name',batchType=$type,discounttype=$discounttype,startDate='$startdate',endDate='$enddate' where batchID=$id";
		$upR = $dbc->query($upQ);
		
		$checkQ = "select batchID from batchowner where batchID=$id";
		$checkR = $dbc->query($checkQ);
		if($dbc->num_rows($checkR) == 0){
			$insQ = "insert batchowner values ($id,'$owner')";
			$insR = $dbc->query($insQ);
		}
		else{
			$upQ = "update batchowner set owner='$owner' where batchID=$id";
			$upR = $dbc->query($upQ);
		}
		
		break;
	case 'showBatch':
		$id = $_GET['id'];
		$tag = false;
		if ($_GET['tag'] == 'true')
			$tag = true;
		
		$out .= addItemUPCInput($tag);
		$out .= "`";
		$out .= showBatchDisplay($id);
		
		break;
	case 'backToList':
		$out .= newBatchInput();
		$out .= "`";
		$out .= batchListDisplay();
		
		break;
	case 'addItemUPC':
		$id = $_GET['id'];
		$upc = str_pad(trim($_GET['upc']),13,'0',STR_PAD_LEFT);
		$tag = false;
		if ($_GET['tag'] == 'true')
			$tag = true;
		
		$out .= addItemPriceInput($upc,$tag);
		break;
	case 'addItemLC':
		$id = $_GET['id'];
		$lc = $_GET['lc'];
		$out .= addItemPriceLCInput($lc);
		break;
	case 'addItemPrice':
		$id = $_GET['id'];
		$upc = $_GET['upc'];
		$price = $_GET['price'];
		$qty = isset($_REQUEST['limit'])?$_REQUEST['limit']:0;
		
		if ($price != ""){
			$checkQ = "select upc from batchList where upc='$upc' and batchID=$id";
			$checkR = $dbc->query($checkQ);
			if ($dbc->num_rows($checkR) == 0){
				$insQ = "INSERT INTO batchList (upc,batchID,salePrice,active,pricemethod,quantity) 
					VALUES ('$upc',$id,$price,1,0,$qty)";
				$insR = $dbc->query($insQ);
			}
			else {
				$upQ = "update batchList set salePrice=$price,quantity=$qty 
					where upc='$upc' and batchID=$id";
				$upR = $dbc->query($upQ);
			}
			$audited = $_GET['audited'];
			if ($audited == 1)
				auditPriceChange($dbc,$_GET['uid'],$upc,$price,$id);
		}
		
		$out .= addItemUPCInput();
		$out .= '`';
		$out .= showBatchDisplay($id);
		break;
	case 'addItemLCPrice':
		$id = $_GET['id'];
		$lc = $_GET['lc'];
		$price = $_GET['price'];
		$qty = isset($_REQUEST['limit'])?$_REQUEST['limit']:0;
		
		if ($price != ""){
			$checkQ = "select upc from batchList where upc='LC$lc' and batchID='$id'";
			$checkR = $dbc->query($checkQ);
			if ($dbc->num_rows($checkR) == 0){
				$insQ = "insert into batchList (upc,batchID,salePrice,active,pricemethod,quantity) 
					values ('LC$lc',$id,$price,1,0,$qty)";
				$insR = $dbc->query($insQ);
			}
			else {
				$upQ = "update batchList set salePrice=$price,quantity=$qty 
					where upc='LC$lc' and batchID=$id";
				$upR = $dbc->query($upQ);
			}
			$audited = $_GET['audited'];
			if ($audited == 1)
				auditPriceChangeLC($dbc,$_GET['uid'],$upc,$price,$id);
		}
		
		$out .= addItemLCInput();
		$out .= '`';
		$out .= showBatchDisplay($id);
		break;
	case 'deleteItem':
		$id = $_GET['id'];
		$upc = $_GET['upc'];
		
		$delQ = "delete from batchList where batchID=$id and upc='$upc'";
		$delR = $dbc->query($delQ);
		
		$delQ = "delete from batchBarcodes where upc='$upc' and batchID='$batchID'";
		$delR = $dbc->query($delQ);

		if (substr($upc,0,2) != 'LC'){
			// take the item off sale if this batch is currently on sale
			$unsaleQ = "UPDATE products AS p LEFT JOIN batchList as b on p.upc=b.upc
					set p.discounttype=0,special_price=0,start_date=0,end_date=0 
				    WHERE p.upc='$upc' and b.batchID=$id";
			if ($FANNIE_SERVER_DBMS == "MSSQL"){
				$unsaleQ = "update products set discounttype=0,special_price=0,start_date=0,end_date=0 
					    from products as p, batches as b where
					    p.upc='$upc' and b.batchID=$id and b.startDate=p.start_date and b.endDate=p.end_date";
			}
			$unsaleR = $dbc->query($unsaleQ);
			
			updateProductAllLanes($upc);
		}
		else {
			$lc = substr($upc,2);
			$unsaleQ = "UPDATE products AS p LEFT JOIN upcLike as u on p.upc=u.upc
					LEFT JOIN batchList as b ON b.upc=concat('LC',convert(u.likeCode,char))
					set p.discounttype=0,special_price=0,start_date=0,end_date=0 
				    WHERE u.likeCode='$lc' and b.batchID=$id";
			if ($FANNIE_SERVER_DBMS == "MSSQL"){
				$unsaleQ = "update products set discounttype=0,special_price=0,start_date=0,end_date=0
					from products as p, batches as b, upcLike as u
					where u.likecode=$lc and u.upc=p.upc and b.startDate=p.start_date and b.endDate=p.end_date
					and b.batchID=$id";
			}
			$unsaleR = $dbc->query($unsaleQ);

			//syncProductsAllLanes();
		}
		$audited = $_GET['audited'];
		if ($audited == "1")
			auditDelete($dbc,$_GET['uid'],$upc,$id);	
		
		$out .= showBatchDisplay($id);
		break;
	case 'refilter':
		$owner = $_GET['owner'];
		
		$out .= batchListDisplay($owner);
		break;
	case 'savePrice':
		$id = $_GET['id'];
		$upc = $_GET['upc'];
		$saleprice = $_GET['saleprice'];
		$saleqty = $_REQUEST['saleqty'];
		$pm = ($saleqty >= 2)?2:0;	
		
		$upQ = "update batchList set salePrice=$saleprice,quantity=$saleqty,
			pricemethod=$pm where batchID=$id and upc='$upc'";
		$upR = $dbc->query($upQ);
		
		$upQ = "update batchBarcodes set normal_price=$saleprice where upc='$upc' and batchID=$id";
		$upR = $dbc->query($upQ);

		$audited = $_GET["audited"];
		if ($audited == "1")
			auditSavePrice($dbc,$_GET['uid'],$upc,$saleprice,$id);
			
		break;
	case 'newTag':
		$id = $_GET['id'];
		$upc = $_GET['upc'];
		$price = $_GET['price'];
		
		$out .= newTagInput($upc,$price,$id);
		
		break;
	case 'addTag':
		$id = $_GET['id'];
		$upc = $_GET['upc'];
		$price = $_GET['price'];
		$desc = $_GET['desc'];
		$brand = $_GET['brand'];
		$units = $_GET['units'];
		$size = $_GET['size'];
		$sku = $_GET['sku'];
		$vendor = $_GET['vendor'];
		
		$checkQ = "select upc from batchBarcodes where upc='$upc' and batchID = $id";
		$checkR = $dbc->query($checkQ);
		if ($dbc->num_rows($checkR) == 0){
			$insQ = "insert into batchBarcodes (upc,description,normal_price,brand,sku,size,units,vendor,batchID)
				values ('$upc','$desc',$price,'$brand','$sku','$size','$units','$vendor',$id)";
			$insR = $dbc->query($insQ);
		}
		else {
			$upQ = "update batchBarcodes set normal_price=$price where upc='$upc'";
			$upR = $dbc->query($upQ);
		}
		
		$insQ = "insert into batchList (upc,batchID,salePrice,active,pricemethod,quantity) 
			values ('$upc',$id,$price,1,0,0)";
		$insR = $dbc->query($insQ);
		
		$out .= addItemUPCInput('true');
		$out .= '`';
		$out .= showBatchDisplay($id);
		break;
	case 'redisplay':
		$mode = $_GET['mode'];
		$out .= batchListDisplay('',$mode);
		break;
	case 'batchListPage':
		$filter = $_REQUEST['filter'];
		$mode = $_REQUEST['mode'];
		$max = $_REQUEST['maxBatchID'];
		$out .= batchListDisplay($filter,$mode,$max);
		break;
	case 'forceBatch':
		$id = $_GET['id'];
		require('forceBatch.php');
		forceBatch($id);	
		break;
	case 'switchToLC':
		$out .= addItemLCInput();
		break;
	case 'switchFromLC':
		$out .= addItemUPCInput();
		break;
	case 'redisplayWithOrder':
		$id = $_GET['id'];
		$order = $_GET['order'];
		$out .= showBatchDisplay($id,$order);
		break;
	case 'expand':
		$likecode = $_GET['likecode'];
		$saleprice = $_GET['saleprice'];
		$out .= $likecode."`";
		$out .= $saleprice."`";
		for ($i = 0; $i < 6; $i++) $out .= "<td>&nbsp;</td>";
		$out .= "`";
		
		$likeQ = "select p.upc,p.description,p.normal_price,$saleprice
			from products as p left join upcLike as u on p.upc=u.upc
			where u.likecode = $likecode order by p.upc desc";
		$likeR = $dbc->query($likeQ);
		while ($likeW = $dbc->fetch_row($likeR)){
			$out .= "<td><a href=/queries/productTest.php?upc=$likeW[0] target=_new$likeW[0]>$likeW[0]</a></td>";
			$out .= "<td>$likeW[1]</td>";
			$out .= "<td>$likeW[2]</td>";
			$out .= "<td>$likeW[3]</td>";
			$out .= "<td>&nbsp;</td>";
			$out .= "<td>&nbsp;</td>";
			$out .= "`";
		}
		$out = substr($out,0,strlen($out)-1);
		break;

	case 'doCut':
		$upc = $_REQUEST['upc'];
		$bid = $_REQUEST['batchID'];
		$uid = $_REQUEST['uid'];
		$q = sprintf("INSERT INTO batchCutPaste VALUES (%d,%s,%d)",
				$bid,$dbc->escape($upc),$uid);
		$dbc->query($q);
		break;

	case 'unCut':
		$upc = $_REQUEST['upc'];
		$bid = $_REQUEST['batchID'];
		$uid = $_REQUEST['uid'];
		$q = sprintf("DELETE FROM batchCutPaste WHERE upc=%s
				AND batchID=%d AND uid=%d",
				$dbc->escape($upc),$bid,$uid);
		$dbc->query($q);
		break;

	case 'doPaste':
		$uid = $_REQUEST['uid'];
		$bid = $_REQUEST['batchID'];
		$q = sprintf("SELECT listID FROM batchList as l INNER JOIN 
			batchCutPaste as b ON b.upc=l.upc AND b.batchID=l.batchID
			WHERE b.uid=%d",$uid);
		$r = $dbc->query($q);
		while($w = $dbc->fetch_row($r)){
			$upQ = sprintf("UPDATE batchList SET batchID=%d WHERE listID=%d",
				$bid,$w['listID']);
			$dbc->query($upQ);
		}
		$dbc->query(sprintf("DELETE FROM batchCutPaste WHERE uid=%d",$uid));
		$out .= showBatchDisplay($bid);
		break;
	case 'moveQual':
	case 'moveDisc':
		$batchID = $_REQUEST['batchID'];
		$upc = $_REQUEST['upc'];
		$q = sprintf("UPDATE batchList SET salePrice = -1*salePrice
			WHERE batchID=%d AND upc=%s",$batchID,
			$dbc->escape($upc));
		$r = $dbc->query($q);
		$out .= showBatchDisplay($batchID);
		break;
	case 'PS_toggleDiscSplit':
		$bid = $_REQUEST['batchID'];	
		$q = sprintf("SELECT pricemethod FROM batchList WHERE
			batchID=%d ORDER BY pricemethod",$bid);	
		$r = $dbc->query($q);
		$currMethod = 4;
		if ($dbc->num_rows($r) > 0){
			$currMethod = array_pop($dbc->fetch_row($r));
			if (empty($currMethod)) $currMethod = 4;
		}
		$newMethod = ($currMethod==4) ? 3 : 4;
		
		$q = sprintf("UPDATE batchList SET pricemethod=%d
			WHERE batchID=%d",$newMethod,$bid);
		$r = $dbc->query($q);
		break;
	case 'PS_toggleMemberOnly':
		$bid = $_REQUEST['batchID'];
		$q = sprintf("SELECT discounttype FROM batches 
			WHERE batchID=%d",$bid);
		$r = $dbc->query($q);
		$cur = array_pop($dbc->fetch_row($r));
		$new = ($cur==1) ? 2 : 1;
		$q = sprintf("UPDATE batches SET discounttype=%d
			WHERE batchID=%d",$new,$bid);
		$r = $dbc->query($q);
		break;
	case 'PS_pricing':
		$qty = $_REQUEST['quantity'];
		$disc = $_REQUEST['discount'];
		if ($disc < 0) $disc = abs($disc);
		$dtype = $_REQUEST['discounttype'];
		$pmethod = $_REQUEST['pricemethod'];
		$bid = $_REQUEST['batchID'];

		$upQ1 = sprintf("UPDATE batches SET discounttype=%d
			WHERE batchID=%d",$dtype,$bid);
		$upQ2 = sprintf("UPDATE batchList SET
				quantity=%d,pricemethod=%d,
				salePrice=%f WHERE batchID=%d
				AND salePrice >= 0",
				$qty+1,$pmethod,$disc,$bid);
		$upQ3 = sprintf("UPDATE batchList SET
				quantity=%d,pricemethod=%d,
				salePrice=%f WHERE batchID=%d
				AND salePrice < 0",
				$qty+1,$pmethod,-1*$disc,$bid);
		$dbc->query($upQ1);
		$dbc->query($upQ2);
		$dbc->query($upQ3);
		break;
	case 'saveLimit':
		$limitQ = sprintf("UPDATE batchList SET quantity=%d WHERE batchID=%d",
				$_REQUEST['limit'],$_REQUEST['batchID']);
		$dbc->query($limitQ);
		break;
	case 'autoTag':
		$delQ = sprintf("DELETE FROM batchBarcodes where batchID=%d",$_REQUEST['batchID']);
		$dbc->query($delQ);
		
		$selQ = sprintf("
			select l.upc,p.description,l.salePrice, 
			case when x.manufacturer is null then v.brand
			else x.manufacturer end as brand,
			case when v.sku is null then '' else v.sku end as sku,
			case when v.size is null then '' else v.size end as size,
			case when v.units is null then 1 else v.units end as units,
			case when x.distributor is null then z.vendorName
			else x.distributor end as vendor,
			l.batchID
			from batchList as l
			inner join products as p on
			l.upc=p.upc
			left join prodExtra as x on
			l.upc=x.upc
			left join vendorItems as v on
			l.upc=v.upc
			left join vendors as z on
			v.vendorID=z.vendorID
			where batchID=%d ORDER BY l.upc",$_REQUEST['batchID']);
		$selR = $dbc->query($selQ);
		$upc = "";
		while($selW = $dbc->fetch_row($selR)){
			if ($upc != $selW['upc']){
				$insQ = sprintf("INSERT INTO batchBarcodes
					(upc,description,normal_price,brand,sku,size,units,vendor,batchID)
					VALUES (%s,%s,%.2f,%s,%s,%s,%d,%s,%d)",
					$dbc->escape($selW['upc']),
					$dbc->escape($selW['description']),
					$selW['salePrice'],
					$dbc->escape($selW['brand']),
					$dbc->escape($selW['sku']),
					$dbc->escape($selW['size']),
					$selW['units'],
					$dbc->escape($selW['vendor']),
					$selW['batchID']
				);
				$dbc->query($insQ);
			}
			$upc = $selW['upc'];
		}
		break;
	}
	
	print $out;
	return;
}

/* input functions
 * functions for generating content that goes in the
 * inputarea div
 */
function newBatchInput(){
	global $batchtypes, $FANNIE_URL, $FANNIE_STORE_ID;

	$ret = "<form onsubmit=\"newBatch(); return false;\">";
	$ret .= "<table>";
	$ret .= "<tr><th>Batch Type</th><th>Name</th><th>Start date</th><th>End date</th><th>Owner</th><th>Priority</tr>";
	$ret .= "<tr>";
	$ret .= "<td><select id=newBatchType>";
	foreach ($batchtypes as $id=>$desc){
		$ret .= "<option value=$id>$desc</option>";
	}
	$ret .= "</select></td>";
	$ret .= "<td><input type=text id=newBatchName /></td>";
	$ret .= "<td><input type=text size=10 id=newBatchStartDate onfocus=\"showCalendarControl(this);\" /></td>";
	$ret .= "<td><input type=text size=10 id=newBatchEndDate onfocus=\"showCalendarControl(this);\" /></td>";
	$ret .= "<td><select id=newBatchOwner />";
	global $owners;
	foreach ($owners as $o)
		$ret .= "<option>$o</option>";
	$ret .= "</select></td>";
	$ret .= "<td><select id=\"newBatchPriority\">";
	$ret .= sprintf('<option value="%d">Default</option>',($FANNIE_STORE_ID!=0?10:0));
	$ret .= sprintf('<option value="%d">Override</option>',($FANNIE_STORE_ID==0?15:5));
	$ret .= "</select></td>";
	$ret .= "<td><input type=submit value=Add /></td>";
	$ret .= "</tr></table></form><br />";
	
	$ret .= "<span class=\"newBatchBlack\">";
	$ret .= "<b>Filter</b>: show batches owned by: ";
	$ret .= "</span>";
	$ret .= "<select id=filterOwner onchange=\"refilter();\">";
	foreach ($owners as $o)
		$ret .= "<option>$o</option>";
	$ret .= "</select>";
	
	$ret .= " <a href=\"{$FANNIE_URL}admin/labels/BatchShelfTags.php\">Print shelf tags</a>";
	
	return $ret;
}

function addItemUPCInput($newtags=false){
	$ret = "<form onsubmit=\"addItem(); return false;\">";
	$ret .= "<b style=\"color:#000;\">UPC</b>: <input type=text maxlength=13 id=addItemUPC /> ";
	$ret .= "<input type=submit value=Add />";
	$ret .= "<input type=checkbox id=addItemTag";
	if ($newtags)
		$ret .= " checked";
	$ret .= " /> <span class=\"newBatchBlack\">New shelf tag</span>";
	$ret .= " <input type=checkbox id=addItemLikeCode onclick=\"switchToLC();\" /> 
		<span class=\"newBatchBlack\">Likecode</span>";
	$ret .= "</form>";
	
	return $ret;
}

function addItemLCInput($newtags=false){
	global $FANNIE_OP_DB;
    $dbc = FannieDB::get($FANNIE_OP_DB);
	$ret = "<form onsubmit=\"addItem(); return false;\">";
	$ret .= "<span class=\"newBatchBlack\">";
	$ret .= "<b>Like code</b>: <input type=text id=addItemUPC size=4 value=1 /> ";
	$ret .= "<select id=lcselect onchange=lcselect_util();>";
	$lcQ = "select likecode,likecodeDesc from likeCodes order by likecode";
	$lcR = $dbc->query($lcQ);
	while ($lcW = $dbc->fetch_array($lcR))
		$ret .= "<option value=$lcW[0]>$lcW[0] $lcW[1]</option>";
	$ret .= "</select>";
	$ret .= "<input type=submit value=Add />";
	$ret .= "<input type=checkbox id=addItemTag";
	if ($newtags)
		$ret .= " checked";
	$ret .= " /> New shelf tag";
	$ret .= " <input type=checkbox id=addItemLikeCode checked onclick=\"switchFromLC();\" /> Likecode";
	$ret .= "</span>";
	$ret .= "</form>";
	
	return $ret;
}

function addItemPriceInput($upc,$newtags=false){
	global $FANNIE_OP_DB;
    $dbc = FannieDB::get($FANNIE_OP_DB);
	$fetchQ = "select description,normal_price from products where upc='$upc'";
	$fetchR = $dbc->query($fetchQ);
	$fetchW = $dbc->fetch_array($fetchR);
	
	$ret = "<form onsubmit=\"addItemFinish('$upc'); return false;\">";
	$ret .= "<span class=\"newBatchBlack\">";
	$ret .= "<b>UPC</b>: $upc <b>Description</b>: $fetchW[0] <b>Normal price</b>: $fetchW[1] ";
	$ret .= "<b>Sale price</b>: <input type=text id=addItemPrice size=5 /> ";
	$ret .= "<input type=submit value=Add />";
	$ret .= "<input type=checkbox id=addItemTag";
	if ($newtags)
		$ret .= " checked";
	$ret .= " /> New shelf tag";
	$ret .= "</span>";
	$ret .= "</form>";
	
	return $ret;
}

function addItemPriceLCInput($lc){
	global $FANNIE_OP_DB;
    $dbc = FannieDB::get($FANNIE_OP_DB);
	$fetchQ = "select likecodedesc from likeCodes where likecode=$lc";
	$fetchR = $dbc->query($fetchQ);
	$desc = array_pop($dbc->fetch_array($fetchR));
	
	/* get the most common price for items in a given
	 * like code
	 */
	$fetchQ = "select p.normal_price from products as p
			left join upcLike as u on p.upc=u.upc and u.likecode=$lc
			where u.upc is not null
			group by p.normal_price
			order by count(*) desc";
	$fetchQ = $dbc->add_select_limit($fetchQ,1);
	$fetchR = $dbc->query($fetchQ);
	$normal_price = array_pop($dbc->fetch_array($fetchR));
	
	$ret = "<form onsubmit=\"addItemLCFinish('$lc'); return false;\">";
	$ret .= "<span class=\"newBatchBlack\">";
	$ret .= "<b>Like code</b>: $lc <b>Description</b>: $desc <b>Normal price</b>: $normal_price ";
	$ret .= "<b>Sale price</b>: <input type=text id=addItemPrice size=5 /> ";
	$ret .= "</span>";
	$ret .= "<input type=submit value=Add />";
	$ret .= "</form>";
	
	return $ret;
}

function newTagInput($upc,$price,$id){
	global $FANNIE_OP_DB;
    $dbc = FannieDB::get($FANNIE_OP_DB);
	$unfiQ = "select brand,sku,size,upc,units,cost,description,depart from unfi where upc = '$upc'";
	$unfiR = $dbc->query($unfiQ);
	$unfiN = $dbc->num_rows($unfiR);
	
	$size = '';
	$brand = '';
	$units = '';
	$sku = '';
	$desc = '';
	$vendor = '';
	// grab info from the UNFI table if possible.
	if ($unfiN == 1){
		$unfiW = $dbc->fetch_array($unfiR);
		$size = $unfiW['size'];
		$brand = strtoupper($unfiW['brand']);
		$brand = preg_replace("/\'/","",$brand);
		$units = $unfiW['units'];
		$sku = $unfiW['sku'];
		$desc = strtoupper($unfiW['description']);
		$desc = preg_replace("/\'/","",$desc);
		$vendor = 'UNFI';
	}
	// otherwise, snag at least the description from products
	else {
		$descQ = "select description from products where upc='$upc'";
		$descR = $dbc->query($descQ);
		$desc = strtoupper(array_pop($dbc->fetch_array($descR)));
	}
	
	$ret = "<form onsubmit=\"newTag(); return false;\">";
	$ret .= "<table>";
	$ret .= "<tr><th>UPC</th><td>$upc <input type=hidden id=newTagUPC value=$upc /></td></tr>";
	$ret .= "<tr><th>Description</th><td><input type=text id=newTagDesc value=\"$desc\" /></td></tr>";
	$ret .= "<tr><th>Brand</th><td><input type=text id=newTagBrand value=\"$brand\" /></td></tr>";
	$ret .= "<tr><th>Units</th><td><input type=text size=8 id=newTagUnits value=\"$units\" /></td></tr>";
	$ret .= "<tr><th>Size</th><td><input type=text size=7 id=newTagSize value=\"$size\" /></td></tr>";
	$ret .= "<tr><th>Vendor</th><td><input type=text id=newTagVendor value=\"$vendor\" /></td></tr>";
	$ret .= "<tr><th>SKU</th><td><input type=text id=newTagSKU value=\"$sku\" /></td></tr>";
	$ret .= "<tr><th>Price</th><td><span style=\"{color: #00bb00;}\">$price</span>";
	$ret .= "<input type=hidden id=newTagPrice value=\"$price\" /></td></tr>";
	$ret .= "<tr><td><input type=submit value=Add /></td>";
	$ret .= "<td><a href=\"\" onclick=\"showBatch($id,'true'); return false;\">Cancel</a></td></tr>";
	$ret .= "<input type=hidden id=newTagID value=$id />";
	$ret .= "</table></form>";
	
	return $ret;
}

/* display functions
 * functions for generating content that goes in the
 * displayarea div
 */
function batchListDisplay($filter='',$mode='all',$maxBatchID=''){
	global $batchtypes, $FANNIE_OP_DB, $FANNIE_URL;
    $dbc = FannieDB::get($FANNIE_OP_DB);
	
	$colors = array('#ffffff','#ffffcc');
	$c = 0;
	$ret = "<span class=\"newBatchBlack\">";
	$ret .= "<b>Display</b>: ";
	if ($mode != 'pending')
		$ret .= "<a href=\"\" onclick=\"redisplay('pending'); return false;\">Pending</a> | ";
	else
		$ret .= "Pending | ";
	if ($mode != 'current')
		$ret .= "<a href=\"\" onclick=\"redisplay('current'); return false;\">Current</a> | ";
	else
		$ret .= "Current | ";
	if ($mode != 'historical')
		$ret .= "<a href=\"\" onclick=\"redisplay('historical'); return false;\">Historical</a> | ";
	else
		$ret .= "Historical | ";
	if ($mode != 'all')
		$ret .= "<a href=\"\" onclick=\"redisplay('all'); return false;\">All</a>";
	else
		$ret .= "All<br />";
	$ret .= "</span>";
	$ret .= "<table border=1 cellspacing=0 cellpadding=3>";
	$ret .= "<tr><th bgcolor=$colors[$c]>Batch Name</th>";
	$ret .= "<th bgcolor=$colors[$c]>Type</th>";
	$ret .= "<th bgcolor=$colors[$c]>Start date</th>";
	$ret .= "<th bgcolor=$colors[$c]>End date</th>";
	$ret .= "<th bgcolor=$colors[$c]>Owner</th>";
	$ret .= "<th colspan=\"3\">&nbsp;</th></tr>";
	
	// the 'all' query
	// where clause is for str_ireplace below
	$fetchQ = "select b.batchName,b.batchType,b.startDate,b.endDate,b.batchID,
			   o.owner from batches as b left outer join batchowner as o
			   on b.batchID = o.batchID where 1=1
			   order by b.batchID desc";
	switch($mode){
	case 'pending':
		$fetchQ = "select b.batchName,b.batchType,b.startDate,b.endDate,b.batchID,
			   o.owner from batches as b left outer join batchowner as o
			   on b.batchID = o.batchID
			   where ".$dbc->datediff("b.startDate",$dbc->now())." > 0
			   order by b.batchID desc";
		break;
	case 'current':
		$fetchQ = "select b.batchName,b.batchType,b.startDate,b.endDate,b.batchID,
			   o.owner from batches as b left outer join batchowner as o
			   on b.batchID = o.batchID
			   where ".$dbc->datediff("b.startDate",$dbc->now())." <= 0
			   and ".$dbc->datediff("b.endDate",$dbc->now())." >= 0
			   order by b.batchID desc";
		break;
	case 'historical':
		$fetchQ = "select b.batchName,b.batchType,b.startDate,b.endDate,b.batchID,
			   o.owner from batches as b left outer join batchowner as o
			   on b.batchID = o.batchID
			   where ".$dbc->datediff("b.endDate",$dbc->now())." <= 0
			   order by b.batchID desc";
		break;	
	}
	// use a filter - only works in 'all' mode
	if ($filter != ''){
		$fetchQ = "select b.batchName,b.batchType,b.startDate,b.endDate,b.batchID,
			   o.owner from batches as b left outer join batchowner as o
			   on b.batchID = o.batchID where o.owner='$filter' order by b.batchID desc";
	}
	$fetchQ = $dbc->add_select_limit($fetchQ,50);
	if (is_numeric($maxBatchID))
		$fetchQ = str_ireplace("where ","WHERE b.batchID < $maxBatchID AND ",$fetchQ);
	$fetchR = $dbc->query($fetchQ);
	
	$count = 0;
	$lastBatchID = 0;
	while($fetchW = $dbc->fetch_array($fetchR)){
		$c = ($c + 1) % 2;
		$ret .= "<tr>";
		$ret .= "<td bgcolor=$colors[$c] id=name$fetchW[4]><a id=namelink$fetchW[4] href=\"\" onclick=\"showBatch($fetchW[4]";
		if ($fetchW[1] == 4) // batchtype 4
			$ret .= ",'true'";
		else
			$ret .= ",'false'";
		$ret .= "); return false;\">$fetchW[0]</a></td>";
		$ret .= "<td bgcolor=$colors[$c] id=type$fetchW[4]>".$batchtypes[$fetchW[1]]."</td>";
		$fetchW[2] = array_shift(explode(" ",$fetchW[2]));
		$ret .= "<td bgcolor=$colors[$c] id=startdate$fetchW[4]>$fetchW[2]</td>";
		$fetchW[3] = array_shift(explode(" ",$fetchW[3]));
		$ret .= "<td bgcolor=$colors[$c] id=enddate$fetchW[4]>$fetchW[3]</td>";
		$ret .= "<td bgcolor=$colors[$c] id=owner$fetchW[4]>$fetchW[5]</td>";
		$ret .= "<td bgcolor=$colors[$c] id=edit$fetchW[4]><a href=\"\" onclick=\"editBatch($fetchW[4]); return false;\"><img src=\"{$FANNIE_URL}src/img/buttons/b_edit.png\" alt=\"Edit\" /></a></td>";
		$ret .= "<td bgcolor=$colors[$c]><a href=\"\" onclick=\"deleteBatch($fetchW[4],'$fetchW[0]'); return false;\"><img src=\"{$FANNIE_URL}src/img/buttons/b_drop.png\" alt=\"Delete\" /></a></td>";
		$ret .= "<td bgcolor=$colors[$c]><a href=\"batchReport.php?batchID=$fetchW[4]\">Report</a></td>";
		$ret .= "</tr>";
		$count++;
		$lastBatchID = $fetchW[4];
	}
	
	$ret .= "</table>";

	if (is_numeric($maxBatchID)){
		$ret .= sprintf("<a href=\"\" 
				onclick=\"scroll(0,0); batchListPage('%s','%s',''); return false;\">First Page</a>
				 | ",
				$filter,$mode);
	}
	if ($count >= 50){
		$ret .= sprintf("<a href=\"\" 
				onclick=\"scroll(0,0); batchListPage('%s','%s',%d); return false;\">Next page</a>",
				$filter,$mode,$lastBatchID);				
	}
	else {
		$ret .= "<span class=\"newBatchBlack\">Next page</span>";
	}

	return $ret;
}

function showBatchDisplay($id,$orderby=' ORDER BY b.listID DESC'){
	global $FANNIE_OP_DB,$FANNIE_SERVER_DBMS,$FANNIE_URL,$uid;
    $dbc = FannieDB::get($FANNIE_OP_DB);
	$uid = ltrim($uid,'0');
	$nameQ = "select batchName,batchType from batches where batchID=$id";
	$nameR = $dbc->query($nameQ);
	$nameW = $dbc->fetch_row($nameR);
	$name = $nameW[0];
	$type = $nameW[1];

	if ($type == 10){
		return showPairedBatchDisplay($id,$name);
	}

	$limitQ = "select max(quantity),max(pricemethod) from batchList WHERE batchID=$id";
	$limitR = $dbc->query($limitQ);
	$hasLimit = False;
	$canHaveLimit = False;
	$limit = 0;
	if ($dbc->num_rows($limitR) > 0){
		$limitW = $dbc->fetch_row($limitR);
		$limit = $limitW[0];
		$pm = $limitW[1];
		if ($pm > 0){
			// no limits with grouped sales
			$canHaveLimit = False;
			$dbc->query("UPDATE batchList SET quantity=0 WHERE pricemethod=0
				AND batchID=$id");
		}
		else {
			$canHaveLimit = True;
			if ($limit > 0){
				$hasLimit = True;
			}
		}
	}

	$saleHeader = "Sale Price";
	if ($type == 8){
		$saleHeader = "$ Discount";
	}
	elseif ($type == 9){
		$saleHeader = "% Discount";
	}
	elseif ($type == 4){
		$saleHeader = "New price";
	}
	
	$fetchQ = "select b.upc,
			case when l.likeCode is null then p.description
			else l.likeCodeDesc end as description,
			p.normal_price,b.salePrice,
			CASE WHEN c.upc IS NULL then 0 ELSE 1 END as isCut,
			b.quantity,b.pricemethod
			from batchList as b left join products as p on
			b.upc = p.upc left join likeCodes as l on
			b.upc = concat('LC',convert(l.likeCode,char))
			left join batchCutPaste as c ON
			b.upc=c.upc AND b.batchID=c.batchID
			where b.batchID = $id $orderby";
	if ($FANNIE_SERVER_DBMS == "MSSQL"){
		$fetchQ = "select b.upc,
				case when l.likecode is null then p.description
				else l.likecodedesc end as description,
				p.normal_price,b.salePrice,
				CASE WHEN c.upc IS NULL then 0 ELSE 1 END as isCut,
				b.quantity,b.pricemethod
				from batchList as b left join products as p on
				b.upc = p.upc left join likeCodes as l on
				b.upc = 'LC'+convert(varchar,l.likecode)
				left join batchCutPaste as c ON
				b.upc=c.upc AND b.batchID=c.batchID
				where b.batchID = $id $orderby";
	}
	$fetchR = $dbc->query($fetchQ);

	$cpCount = sprintf("SELECT count(*) FROM batchCutPaste WHERE uid=%d",$uid);
	$res = $dbc->query($cpCount);
	$cp = array_pop($dbc->fetch_row($res));
	
	$ret = "<span class=\"newBatchBlack\"><b>Batch name</b>: $name</span><br />";
	$ret .= "<a href=\"\" onclick=\"backToList(); return false;\">Back to batch list</a> | ";
	$ret .= "<a href=\"{$FANNIE_URL}admin/labels/BatchShelfTags.php?batchID%5B%5D=$id\">Print shelf tags</a> | ";
	$ret .= "<a href=\"\" onclick=\"autoTag($id); return false;\">Auto-tag</a> | ";
	if ($cp > 0)
		$ret .= "<a href=\"\" onclick=\"doPaste($uid,$id); return false;\">Paste Items ($cp)</a> | ";
	$ret .= "<a href=\"\" onclick=\"forceBatch($id); return false;\">Force batch</a> | ";
	if (!$canHaveLimit){
		$ret .= "No limit";
		$ret .= " <span id=\"currentLimit\" style=\"color:#000;\"></span>";
	}
	else if (!$hasLimit){
		$ret .= "<span id=\"limitLink\"><a href=\"\" onclick=\"editLimit($id,0); return false;\">Add Limit</a></span>";
		$ret .= " <span id=\"currentLimit\" style=\"color:#000;\"></span>";
	}
	else if ($hasLimit){
		$ret .= "<span id=\"limitLink\"><a href=\"\" onclick=\"editLimit($id,$limit); return false;\">Limit:</a></span>";
		$ret .= " <span id=\"currentLimit\" style=\"color:#000;\">$limit</span>";
	}
	$ret .= "<br />";
	$ret .= "<table id=yeoldetable cellspacing=0 cellpadding=3 border=1>";
	$ret .= "<tr>";
	if ($orderby != "ORDER BY b.upc ASC")
		$ret .= "<th><a href=\"\" onclick=\"redisplayWithOrder($id,'ORDER BY b.upc ASC'); return false;\">UPC</a></th>";
	else
		$ret .= "<th><a href=\"\" onclick=\"redisplayWithOrder($id,'ORDER BY b.upc DESC'); return false;\">UPC</a></th>";
	if ($orderby != "ORDER BY description ASC")
		$ret .= "<th><a href=\"\" onclick=\"redisplayWithOrder($id,'ORDER BY description ASC'); return false;\">Description</a></th>";
	else
		$ret .= "<th><a href=\"\" onclick=\"redisplayWithOrder($id,'ORDER BY description DESC'); return false;\">Description</a></th>";
	if ($orderby != "ORDER BY p.normal_price DESC")
		$ret .= "<th><a href=\"\" onclick=\"redisplayWithOrder($id,'ORDER BY p.normal_price DESC'); return false;\">Normal price</a></th>";
	else
		$ret .= "<th><a href=\"\" onclick=\"redisplayWithOrder($id,'ORDER BY p.normal_price ASC'); return false;\">Normal price</a></th>";
	if ($orderby != "ORDER BY b.salePrice DESC")
		$ret .= "<th><a href=\"\" onclick=\"redisplayWithOrder($id,'ORDER BY b.salePrice DESC'); return false;\">$saleHeader</a></th>";
	else
		$ret .= "<th><a href=\"\" onclick=\"redisplayWithOrder($id,'ORDER BY b.salePrice ASC'); return false;\">$saleHeader</a></th>";
	$ret .= "<th colspan=\"3\">&nbsp;</th>";
	$ret .= "</tr>";
	
	$colors = array('#ffffff','#ffffcc');
	$c = 0;
	$row = 1;
	while($fetchW = $dbc->fetch_array($fetchR)){
		$c = ($c + 1) % 2;
		$ret .= "<tr>";
		$fetchW[0] = rtrim($fetchW[0]);
		if (substr($fetchW[0],0,2) == "LC"){
			$likecode = rtrim(substr($fetchW[0],2));
			$ret .= "<td bgcolor=$colors[$c]>$fetchW[0]";
			$ret .= "<span id=LCToggle$likecode>";
			$ret .= " <a href=\"\" onclick=\"expand($likecode,$fetchW[3]); return false;\">[+]</a>";
			$ret .= "</span></td>";
			$ret .= "<input type=hidden value=$row id=expandId$likecode name=expandId />";
		}
		else {
			$ret .= "<td bgcolor=$colors[$c]><a href=/queries/productTest.php?upc=$fetchW[0] target=_new$fetchW[0]>$fetchW[0]</a></td>";
		}
		$ret .= "<td bgcolor=$colors[$c]>$fetchW[1]</td>";
		$ret .= "<td bgcolor=$colors[$c]>$fetchW[2]</td>";
		$qtystr = ($fetchW['pricemethod']>0 && is_numeric($fetchW['quantity']) && $fetchW['quantity'] > 0)?$fetchW['quantity']." for ":"";
		$ret .= "<td bgcolor=$colors[$c]><span id=saleQty$fetchW[0]>$qtystr</span><span id=salePrice$fetchW[0]>";
		$ret .= sprintf("%.2f</span></td>",$fetchW[3]);
		$ret .= "<td bgcolor=$colors[$c] id=editLink$fetchW[0]><a href=\"\" onclick=\"editPrice('$fetchW[0]'); return false;\"><img src=\"{$FANNIE_URL}src/img/buttons/b_edit.png\" alt=\"Edit\" /></a></td>";
		$ret .= "<td bgcolor=$colors[$c]><a href=\"\" onclick=\"deleteItem('$fetchW[0]'); return false;\"><img src=\"{$FANNIE_URL}src/img/buttons/b_drop.png\" alt=\"Delete\" /></a></td>";
		if ($fetchW[4] == 1)
			$ret .= "<td bgcolor=$colors[$c] id=cpLink$fetchW[0]><a href=\"\" onclick=\"unCut('$fetchW[0]',$id,$uid); return false;\">Undo</a></td>";
		else
			$ret .= "<td bgcolor=$colors[$c] id=cpLink$fetchW[0]><a href=\"\" onclick=\"doCut('$fetchW[0]',$id,$uid); return false;\">Cut</a></td>";
		$ret .= "</tr>";
		$row++;
	}
	$ret .= "</table>";
	$ret .= "<input type=hidden id=currentBatchID value=$id />";
	
	return $ret;
}

function showPairedBatchDisplay($id,$name){
	global $FANNIE_OP_DB,$FANNIE_SERVER_DBMS,$FANNIE_URL,$uid;
    $dbc = FannieDB::get($FANNIE_OP_DB);
	$ret = "";
	$ret .= sprintf('<input type="hidden" id="currentBatchID" value="%d" />',$id);
	$ret .= "<b>Batch name</b>: $name<br />";
	$ret .= "<a href=\"\" onclick=\"backToList(); return false;\">Back to batch list</a> | ";
	$ret .= "<a href=\"\" onclick=\"forceBatch($id); return false;\">Force batch</a>";
	$ret .= "No limit";
	$ret .= " <span id=\"currentLimit\" style=\"color:#000;\"></span>";

	$q = "SELECT b.discounttype,salePrice,
		CASE WHEN l.pricemethod IS NULL THEN 4 ELSE l.pricemethod END as pricemethod,
		CASE WHEN l.quantity IS NULL THEN 1 ELSE l.quantity END as quantity
		FROM batches AS b LEFT JOIN batchList AS l 
		ON b.batchID=l.batchID WHERE b.batchID=$id ORDER BY l.pricemethod";
	$r = $dbc->query($q);
	$w = $dbc->fetch_row($r);

	if (!empty($w['salePrice'])){
		$ret .= "<i>Add all items before fiddling with these settings
			or they'll tend to go haywire</i>";
		$ret .= '<table cellspacing=0 cellpadding=4 border=1>';
		$ret .= '<tr><th>Member only sale</th><td colspan="3"><input type="checkbox"
			onclick="PS_toggleMemberOnly('.$id.');" id="PS_memCBX" '
			.($w['discounttype']==2?'checked':'').' /></td>';	
		$ret .= '<th>Split discount</th><td colspan="1"><input type="checkbox"
			onclick="PS_toggleDiscSplit('.$id.');" id="PS_splitCBX" '
			.($w['pricemethod']==4?'':'checked').' /></td></tr>';
		$ret .= '<tr><th>Qualifiers Required</th>';
		$ret .= sprintf('<td><input type="text" size="4" value="%d"
				id="PS_qualCount" /></td>',
				$w['quantity']-1);
		$ret .= '<th>Discount</th>';
		$ret .= sprintf('<td><input type="text" size="5" value="%.2f"
				id="PS_discount" /></td>',
				(empty($w['salePrice'])?'':abs($w['salePrice'])));
		$ret .= sprintf('<td colspan="2"><input type="submit" value="Update Pricing"
				onclick="PS_pricing(%d); return false;" /></td></tr>',$id);
		$ret .= '</table>';
	}
	else {
		$ret .= "<i>Add items first</i>";
	}

	$fetchQ = "select b.upc,
			case when l.likeCode is null then p.description
			else l.likeCodeDesc end as description,
			p.normal_price,b.salePrice
			from batchList as b left join products as p on
			b.upc = p.upc left join likeCodes as l on
			b.upc = concat('LC'+convert(l.likeCode,char))
			where b.batchID = $id AND b.salePrice >= 0";
	if ($FANNIE_SERVER_DBMS == "MSSQL"){
		$fetchQ = "select b.upc,
				case when l.likecode is null then p.description
				else l.likecodedesc end as description,
				p.normal_price,b.salePrice
				from batchList as b left join products as p on
				b.upc = p.upc left join likeCodes as l on
				b.upc = 'LC'+convert(varchar,l.likecode)
				where b.batchID = $id AND b.salePrice >= 0";
	}
	$fetchR = $dbc->query($fetchQ);

	$colors = array('#ffffff','#ffffcc');
	$c = 0;
	$row = 1;
	$ret .= '<p /><table cellspacing="0" cellpadding="4" border="1">';
	$ret .= '<tr><th colspan="4">Qualifying Item(s)</th></tr>';
	while($fetchW = $dbc->fetch_array($fetchR)){
		$c = ($c + 1) % 2;
		$ret .= "<tr>";
		$fetchW[0] = rtrim($fetchW[0]);
		if (substr($fetchW[0],0,2) == "LC"){
			$likecode = rtrim(substr($fetchW[0],2));
			$ret .= "<td bgcolor=$colors[$c]>$fetchW[0]";
			/*
			$ret .= "<span id=LCToggle$likecode>";
			$ret .= " <a href=\"\" onclick=\"expand($likecode,$fetchW[3]); return false;\">[+]</a>";
			$ret .= "</span>";
			*/
			$ret .= "</td>";
			$ret .= "<input type=hidden value=$row id=expandId$likecode name=expandId />";
		}
		else {
			$ret .= "<td bgcolor=$colors[$c]><a href=/queries/productTest.php?upc=$fetchW[0] target=_new$fetchW[0]>$fetchW[0]</a></td>";
		}
		$ret .= "<td bgcolor=$colors[$c]>$fetchW[1]</td>";
		$ret .= "<td bgcolor=$colors[$c]><a href=\"\" onclick=\"moveDisc('$fetchW[0]'); return false;\"><img src=\"{$FANNIE_URL}src/img/buttons/arrow_down.gif\" alt=\"Make Discount Item\" /></a></td>";
		$ret .= "<td bgcolor=$colors[$c]><a href=\"\" onclick=\"deleteItem('$fetchW[0]'); return false;\"><img src=\"{$FANNIE_URL}src/img/buttons/b_drop.png\" alt=\"Delete\" /></a></td>";
		$ret .= "</tr>";
		$row++;
	}
	$ret .= "</table>";

	$fetchQ = "select b.upc,
			case when l.likecode is null then p.description
			else l.likecodedesc end as description,
			p.normal_price,b.salePrice
			from batchList as b left join products as p on
			b.upc = p.upc left join likeCodes as l on
			b.upc = concat('LC',convert(l.likecode,char))
			where b.batchID = $id AND b.salePrice < 0";
	if ($FANNIE_SERVER_DBMS == "MSSQL"){
		$fetchQ = "select b.upc,
				case when l.likecode is null then p.description
				else l.likecodedesc end as description,
				p.normal_price,b.salePrice
				from batchList as b left join products as p on
				b.upc = p.upc left join likeCodes as l on
				b.upc = 'LC'+convert(varchar,l.likecode)
				where b.batchID = $id AND b.salePrice < 0";
	}
	$fetchR = $dbc->query($fetchQ);

	$colors = array('#ffffff','#ffffcc');
	$c = 0;
	$row = 1;
	$ret .= '<p /><table cellspacing="0" cellpadding="4" border="1">';
	$ret .= '<tr><th colspan="4">Discount Item(s)</th></tr>';
	while($fetchW = $dbc->fetch_array($fetchR)){
		$c = ($c + 1) % 2;
		$ret .= "<tr>";
		$fetchW[0] = rtrim($fetchW[0]);
		if (substr($fetchW[0],0,2) == "LC"){
			$likecode = rtrim(substr($fetchW[0],2));
			$ret .= "<td bgcolor=$colors[$c]>$fetchW[0]";
			/*
			$ret .= "<span id=LCToggle$likecode>";
			$ret .= " <a href=\"\" onclick=\"expand($likecode,$fetchW[3]); return false;\">[+]</a>";
			$ret .= "</span>";
			*/
			$ret .= "</td>";
			$ret .= "<input type=hidden value=$row id=expandId$likecode name=expandId />";
		}
		else {
			$ret .= "<td bgcolor=$colors[$c]><a href=/queries/productTest.php?upc=$fetchW[0] target=_new$fetchW[0]>$fetchW[0]</a></td>";
		}
		$ret .= "<td bgcolor=$colors[$c]>$fetchW[1]</td>";
		$ret .= "<td bgcolor=$colors[$c]><a href=\"\" onclick=\"moveQual('$fetchW[0]'); return false;\"><img src=\"{$FANNIE_URL}src/img/buttons/arrow_up.gif\" alt=\"Make Qualifying Item\" /></a></td>";
		$ret .= "<td bgcolor=$colors[$c]><a href=\"\" onclick=\"deleteItem('$fetchW[0]'); return false;\"><img src=\"{$FANNIE_URL}src/img/buttons/b_drop.png\" alt=\"Delete\" /></a></td>";
		$ret .= "</tr>";
		$row++;
	}
	$ret .= "</table>";

	return $ret;
}

$user = validateUserQuiet('batches');
$audited=0;
if (!$user){
	$audited=1;
	$user = validateUserQuiet('batches_audited');
}
if (!$user){
	$url = $FANNIE_URL."auth/ui/loginform.php";
	$redirect = $FANNIE_URL."batches/newbatch/";
	header("Location:".$url."?redirect=".$redirect);
	return;
}

/*
$page_title = 'Fannie - New Batch Module';
$header = 'Item Batcher';
include('../../src/header.html');
*/

?>

<html>
<head><title>Batch Management</title>
<script type="text/javascript" src="index.js"></script>
<script src="../../src/CalendarControl.js"
        language="javascript"></script>
<link rel="stylesheet" type="text/css" href="index.css">
<link rel="stylesheet" type="text/css" href="../../src/style.css">
</head>
<body>

<div id="inputarea">
<?php echo newBatchInput(); ?>
</div>
<div id="displayarea">
<?php echo batchListDisplay(); ?>
</div>
<input type=hidden id=uid value="<?php echo $user; ?>" />
<input type=hidden id=isAudited value="<?php echo $audited; ?>" />
<?php
	$typestr = "";
	foreach($batchtypes as $b)
		$typestr .= $b."`";
	$typestr = substr($typestr,0,strlen($typestr)-1);

	$tidstr = "";
	foreach($batchtypes as $tid=>$b)
		$tidstr .= $tid."`";
	$tidstr = substr($tidstr,0,strlen($tidstr)-1);

	$ownerstr = "";
	foreach($owners as $o)
		$ownerstr .= $o."`";
	$ownerstr = substr($ownerstr,0,strlen($ownerstr)-1);	

	echo "<input type=hidden id=passtojstypes value=\"$typestr\" />";
	echo "<input type=hidden id=passtojstypeids value=\"$tidstr\" />";
	echo "<input type=hidden id=passtojsowners value=\"$ownerstr\" />";
	echo "<input type=hidden id=buttonimgpath value=\"{$FANNIE_URL}src/img/buttons/\" />";
?>

<?php
/* html footer */
//include('../../src/footer.html');
?>
</body>
</html>
