<?php
include('../../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

include('audit.php');
include('../../queries/barcode.php');

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

$batchtypes = array();
$typesQ = "select batchTypeID,typeDesc from batchType order by batchTypeID";
$typesR = $sql->query($typesQ);
while ($typesW = $sql->fetch_array($typesR))
	$batchtypes[$typesW[0]] = $typesW[1];
	
$owners = array('','Cool','Deli','Meat','HBC','Bulk','Grocery','Produce','Gen Merch','IT');

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
		$name = str_replace("'","''",$_GET['name']);
		$startdate = $_GET['startdate']." 00:00:00.00";
		$enddate = $_GET['enddate']." 00:00:00.00";
		$owner = $_GET['owner'];
		
		$infoQ = "select discType from batchType where batchTypeID=$type";
		$infoR = $sql->query($infoQ);
		$discounttype = array_pop($sql->fetch_array($infoR));
		
		$insQ = "insert into batches (startDate, endDate, batchName, batchType, discountType,
			priority) values ('$startdate','$enddate','$name',$type,$discounttype,0)";
		$insR = $sql->query($insQ);
		$id = $sql->insert_id();
		
		/*
		$idQ = "select max(batchID) from batches";
		$idR = $sql->query($idQ);
		$id = array_pop($sql->fetch_array($idR));
		*/
		
		$insQ = "insert batchowner values ($id,'$owner')";
		$insR = $sql->query($insQ);
		
		$out .= batchListDisplay();
		break;
	case 'deleteBatch':
		$id = $_GET['id'];

		$unsaleQ = "UPDATE products AS p LEFT JOIN batchList as b
			ON p.upc=b.upc
			SET special_price=0,
			specialpricemethod=0,specialquantity=0,
			specialgroupprice=0,p.discounttype=0,
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
		$unsaleR = $sql->query($unsaleQ);

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
				batchList AS l ON l.upc='LC'+convert(varchar,v.likeCode)
				WHERE l.upc LIKE '%LC%'
				AND l.batchID=$id";
		}
		$unsaleLCR = $sql->query($unsaleLCQ);
		
		$delQ = "delete from batches where batchID=$id";
		$delR = $sql->query($delQ);
		
		$delQ = "delete from batchList where batchID=$id";
		$delR = $sql->query($delQ);

		exec("touch /pos/sync/scheduled/products");

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
		$infoR = $sql->query($infoQ);
		$discounttype = array_pop($sql->fetch_array($infoR));
		
		$upQ = "update batches set batchname='$name',batchtype=$type,discounttype=$discounttype,startdate='$startdate',enddate='$enddate' where batchID=$id";
		$upR = $sql->query($upQ);
		
		$checkQ = "select batchID from batchowner where batchID=$id";
		$checkR = $sql->query($checkQ);
		if($sql->num_rows($checkR) == 0){
			$insQ = "insert batchowner values ($id,'$owner')";
			$insR = $sql->query($insQ);
		}
		else{
			$upQ = "update batchowner set owner='$owner' where batchID=$id";
			$upR = $sql->query($upQ);
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
		$upc = str_pad($_GET['upc'],13,'0',STR_PAD_LEFT);
		$newupc = fixBarcode($upc);
		$tag = false;
		if ($_GET['tag'] == 'true')
			$tag = true;
		$testQ = "select * from products where upc='$newupc'";
		$testR = $sql->query($testQ);
		if ($sql->num_rows($testR) > 0) $upc = $newupc;
		
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
		
		if ($price != ""){
			$checkQ = "select upc from batchList where upc='$upc' and batchID=$id";
			$checkR = $sql->query($checkQ);
			if ($sql->num_rows($checkR) == 0){
				$insQ = "insert into batchList (upc,batchID,salePrice,active,pricemethod,quantity)
					values ('$upc',$id,$price,1,0,0)";
				$insR = $sql->query($insQ);
			}
			else {
				$upQ = "update batchList set saleprice=$price where upc='$upc' and batchID=$id";
				$upR = $sql->query($upQ);
			}
			$audited = $_GET['audited'];
			if ($audited == 1)
				auditPriceChange($sql,$_GET['uid'],$upc,$price,$id);
		}
		
		$out .= addItemUPCInput();
		$out .= '`';
		$out .= showBatchDisplay($id);
		break;
	case 'addItemLCPrice':
		$id = $_GET['id'];
		$lc = $_GET['lc'];
		$price = $_GET['price'];
		
		if ($price != ""){
			$checkQ = "select upc from batchList where upc='LC$lc' and batchID='$id'";
			$checkR = $sql->query($checkQ);
			if ($sql->num_rows($checkR) == 0){
				$insQ = "insert into batchList (upc,batchID,salePrice,active,pricemethod,quantity)
					values ('LC$lc',$id,$price,1,0,0)";
				$insR = $sql->query($insQ);
			}
			else {
				$upQ = "update batchList set saleprice=$price where upc='LC$lc' and batchID=$id";
				$upR = $sql->query($upQ);
			}
			$audited = $_GET['audited'];
			if ($audited == 1)
				auditPriceChangeLC($sql,$_GET['uid'],$upc,$price,$id);
		}
		
		$out .= addItemLCInput();
		$out .= '`';
		$out .= showBatchDisplay($id);
		break;
	case 'deleteItem':
		$id = $_GET['id'];
		$upc = $_GET['upc'];
		
		$delQ = "delete from batchList where batchID=$id and upc='$upc'";
		$delR = $sql->query($delQ);
		
		$delQ = "delete from batchBarcodes where upc='$upc' and batchID='$id'";
		$delR = $sql->query($delQ);

		if (substr($upc,0,2) != 'LC'){
			// take the item off sale if this batch is currently on sale
			$unsaleQ = "UPDATE products AS p LEFT JOIN batchList as b on p.upc=b.upc
					set p.discounttype=0,special_price=0,start_date=0,end_date=0 
				    WHERE p.upc='$upc' and b.batchID=$id";
			if ($FANNIE_SERVER_DBMS == "MSSQL"){
				$unsaleQ = "update products set discounttype=0,special_price=0,start_date=0,end_date=0 
					    from products as p, batches as b where
					    p.upc='$upc' and b.batchID=$id and b.startdate=p.start_date and b.enddate=p.end_date";
			}
			$unsaleR = $sql->query($unsaleQ);
			
            $model = new ProductsModel($sql);
            $model->upc($upc);
            $model->pushToLanes();
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
					where u.likeCode=$lc and u.upc=p.upc and b.startdate=p.start_date and b.enddate=p.end_date
					and b.batchID=$id";
			}
			$unsaleR = $sql->query($unsaleQ);

            $prep = $sql->prepare('SELECT upc FROM upcLike WHERE likeCode=?');
            $all = $sql->execute($prep, array($lc));
            while($row = $sql->fetch_row($all)) {
                $model = new ProductsModel($sql);
                $model->upc($row['upc']);
                $model->pushToLanes();
            }
		}
		$audited = $_GET['audited'];
		if ($audited == "1")
			auditDelete($sql,$_GET['uid'],$upc,$id);	
		
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
		$method = 0;
		$qty = $_GET['saleqty'];
		if (is_numeric($qty)) $method=2;
		else $qty=0;
		
		$upQ = "update batchList set saleprice=$saleprice,pricemethod=$method,quantity=$qty where batchID=$id and upc='$upc'";
		$upR = $sql->query($upQ);
		
		$upQ = "update batchBarcodes set normal_price=$saleprice where upc='$upc' and batchID=$id";
		$upR = $sql->query($upQ);

		$audited = $_GET["audited"];
		if ($audited == "1")
			auditSavePrice($sql,$_GET['uid'],$upc,$saleprice,$id);
			
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
		$checkR = $sql->query($checkQ);
		if ($sql->num_rows($checkR) == 0){
			$insQ = "insert into batchBarcodes values ('$upc','$desc',$price,'$brand','$sku','$size','$units','$vendor',$id)";
			$insR = $sql->query($insQ);
		}
		else {
			$upQ = "update batchBarcodes set normal_price=$price where upc='$upc'";
			$upR = $sql->query($upQ);
		}
		
		$insQ = "insert into batchList (upc,batchID,salePrice,active,pricemethod,quantity) 
			values ('$upc',$id,$price,1,0,0)";
		$insR = $sql->query($insQ);
		
		$out .= addItemUPCInput('true');
		$out .= '`';
		$out .= showBatchDisplay($id);
		break;
	case 'redisplay':
		$mode = $_GET['mode'];
		$out .= batchListDisplay('',$mode);
		break;
	case 'forceBatch':
		$id = $_GET['id'];
		require('forceBatch.php');
		forceBatch($id);	
		break;
	case 'unsale':
		$id = $_GET['id'];
		require('unsale.php');
		unsale($id);	
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
			where u.likeCode = $likecode order by p.upc desc";
		$likeR = $sql->query($likeQ);
		while ($likeW = $sql->fetch_row($likeR)){
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
	}
	
	print $out;
	return;
}

/* input functions
 * functions for generating content that goes in the
 * inputarea div
 */
function newBatchInput(){
	global $batchtypes;	

	$ret = "<form onsubmit=\"newBatch(); return false;\">";
	$ret .= "<table>";
	$ret .= "<tr><th>Batch Type</th><th>Name</th><th>Start date</th><th>End date</th><th>Owner</th></tr>";
	$ret .= "<tr>";
	$ret .= "<td><select id=newBatchType>";
	foreach ($batchtypes as $id=>$desc){
		$ret .= "<option value=$id>$desc</option>";
	}
	$ret .= "</select></td>";
	$ret .= "<td><input type=text id=newBatchName /></td>";
	$ret .= "<td><input type=text id=newBatchStartDate onfocus=\"showCalendarControl(this);\" /></td>";
	$ret .= "<td><input type=text id=newBatchEndDate onfocus=\"showCalendarControl(this);\" /></td>";
	$ret .= "<td><select id=newBatchOwner />";
	global $owners;
	foreach ($owners as $o)
		$ret .= "<option>$o</option>";
	$ret .= "</select></td>";
	$ret .= "<td><input type=submit value=Add /></td>";
	$ret .= "</tr></table></form><br />";
	
	$ret .= "<b>Filter</b>: show batches owned by: ";
	$ret .= "<select id=filterOwner onchange=\"refilter();\">";
	foreach ($owners as $o)
		$ret .= "<option>$o</option>";
	$ret .= "</select>";
	
	$ret .= " <a href=barcodenew.php>Print shelf tags</a>";
	
	return $ret;
}

function addItemUPCInput($newtags=false){
	$ret = "<form onsubmit=\"addItem(); return false;\">";
	$ret .= "<b>UPC</b>: <input type=text id=addItemUPC maxlength=13 /> ";
	$ret .= "<input type=submit value=Add />";
	$ret .= "<input type=checkbox id=addItemTag";
	if ($newtags)
		$ret .= " checked";
	$ret .= " /> New shelf tag";
	$ret .= " <input type=checkbox id=addItemLikeCode onclick=\"switchToLC();\" /> Likecode";
	$ret .= "</form>";
	
	return $ret;
}

function addItemLCInput($newtags=false){
	global $sql;
	$ret = "<form onsubmit=\"addItem(); return false;\">";
	$ret .= "<b>Like code</b>: <input type=text id=addItemUPC size=4 value=1 /> ";
	$ret .= "<select id=lcselect onchange=lcselect_util();>";
	$lcQ = "select likeCode,likeCodeDesc from likeCodes order by likeCode";
	$lcR = $sql->query($lcQ);
	while ($lcW = $sql->fetch_array($lcR))
		$ret .= "<option value=$lcW[0]>$lcW[0] $lcW[1]</option>";
	$ret .= "</select>";
	$ret .= "<input type=submit value=Add />";
	$ret .= "<input type=checkbox id=addItemTag";
	if ($newtags)
		$ret .= " checked";
	$ret .= " /> New shelf tag";
	$ret .= " <input type=checkbox id=addItemLikeCode checked onclick=\"switchFromLC();\" /> Likecode";
	$ret .= "</form>";
	
	return $ret;
}

function addItemPriceInput($upc,$newtags=false){
	global $sql;
	$fetchQ = "select description,normal_price from products where upc='$upc'";
	$fetchR = $sql->query($fetchQ);
	$fetchW = $sql->fetch_array($fetchR);
	
	$ret = "<form onsubmit=\"addItemFinish('$upc'); return false;\">";
	$ret .= "<b>UPC</b>: $upc <b>Description</b>: $fetchW[0] <b>Normal price</b>: $fetchW[1] ";
	$ret .= "<b>Sale price</b>: <input type=text id=addItemPrice size=5 /> ";
	$ret .= "<input type=submit value=Add />";
	$ret .= "<input type=checkbox id=addItemTag";
	if ($newtags)
		$ret .= " checked";
	$ret .= " /> New shelf tag";
	$ret .= "</form>";
	
	return $ret;
}

function addItemPriceLCInput($lc){
	global $sql;
	$fetchQ = "select likeCodeDesc from likeCodes where likeCode=$lc";
	$fetchR = $sql->query($fetchQ);
	$desc = array_pop($sql->fetch_array($fetchR));
	
	/* get the most common price for items in a given
	 * like code
	 */
	$fetchQ = "select p.normal_price from products as p
			left join upcLike as u on p.upc=u.upc and u.likeCode=$lc
			where u.upc is not null
			group by p.normal_price
			order by count(*) desc";
	$fetchQ = $sql->add_select_limit($fetchQ,1);
	$fetchR = $sql->query($fetchQ);
	$normal_price = array_pop($sql->fetch_array($fetchR));
	
	$ret = "<form onsubmit=\"addItemLCFinish('$lc'); return false;\">";
	$ret .= "<b>Like code</b>: $lc <b>Description</b>: $desc <b>Normal price</b>: $normal_price ";
	$ret .= "<b>Sale price</b>: <input type=text id=addItemPrice size=5 /> ";
	$ret .= "<input type=submit value=Add />";
	$ret .= "</form>";
	
	return $ret;
}

function newTagInput($upc,$price,$id){
	global $sql;
	$unfiQ = "select distinct * from UNFI where upc = '$upc'";
	$unfiR = $sql->query($unfiQ);
	$unfiN = $sql->num_rows($unfiR);
	
	$size = '';
	$brand = '';
	$units = '';
	$sku = '';
	$desc = '';
	$vendor = '';
	// grab info from the UNFI table if possible.
	if ($unfiN == 1){
		$unfiW = $sql->fetch_array($unfiR);
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
		$descR = $sql->query($descQ);
		$desc = strtoupper(array_pop($sql->fetch_array($descR)));
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
function batchListDisplay($filter='',$mode='all'){
	global $batchtypes, $sql;
	
	$colors = array('#ffffff','#ffffcc');
	$c = 0;
	$ret = "<b>Display</b>: ";
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
	$ret .= "<table border=1 cellspacing=0 cellpadding=3>";
	$ret .= "<tr><th bgcolor=$colors[$c]>Batch Name</th>";
	$ret .= "<th bgcolor=$colors[$c]>Type</th>";
	$ret .= "<th bgcolor=$colors[$c]>Start date</th>";
	$ret .= "<th bgcolor=$colors[$c]>End date</th>";
	$ret .= "<th bgcolor=$colors[$c]>Owner</th></tr>";
	
	// the 'all' query
	$fetchQ = "select b.batchName,b.batchType,b.startDate,b.endDate,b.batchID,
			   o.owner from batches as b left outer join batchowner as o
			   on b.batchID = o.batchID order by b.batchID desc";
	switch($mode){
	case 'pending':
		$fetchQ = "select b.batchName,b.batchType,b.startDate,b.endDate,b.batchID,
			   o.owner from batches as b left outer join batchowner as o
			   on b.batchID = o.batchID
			   where ".$sql->datediff('b.startDate',$sql->now())." > 0
			   order by b.batchID desc";
		break;
	case 'current':
		$fetchQ = "select b.batchName,b.batchType,b.startDate,b.endDate,b.batchID,
			   o.owner from batches as b left outer join batchowner as o
			   on b.batchID = o.batchID
			   where ".$sql->datediff('b.startDate',$sql->now())." < 1
			   and ".$sql->datediff('b.endDate',$sql->now())." > 0
			   order by b.batchID desc";
		break;
	case 'historical':
		$fetchQ = "select b.batchName,b.batchType,b.startDate,b.endDate,b.batchID,
			   o.owner from batches as b left outer join batchowner as o
			   on b.batchID = o.batchID
			   where ".$sql->datediff('b.endDate',$sql->now())." <= 0
			   order by b.batchID desc";
		break;	
	}
	// use a filter - only works in 'all' mode
	if ($filter != ''){
		$fetchQ = "select b.batchName,b.batchType,b.startDate,b.endDate,b.batchID,
			   o.owner from batches as b left outer join batchowner as o
			   on b.batchID = o.batchID where o.owner='$filter' order by b.batchID desc";
	}
	$fetchR = $sql->query($fetchQ);
	
	$count = 0;
	while($fetchW = $sql->fetch_array($fetchR)){
		$c = ($c + 1) % 2;
		$count += 1;
		//if ($count > 100) break;
		$ret .= "<tr>";
		$ret .= "<td bgcolor=$colors[$c] id=name$fetchW[4]><a id=namelink$fetchW[4] href=\"\" onclick=\"showBatch($fetchW[4]";
		if ($fetchW[1] == 4) // batchtype 4
			$ret .= ",'true'";
		else
			$ret .= ",'false'";
		$ret .= "); return false;\">$fetchW[0]</a></td>";
		$ret .= "<td bgcolor=$colors[$c] id=type$fetchW[4]>".$batchtypes[$fetchW[1]]."</td>";
		$ret .= "<td bgcolor=$colors[$c] id=startdate$fetchW[4]>$fetchW[2]</td>";
		$ret .= "<td bgcolor=$colors[$c] id=enddate$fetchW[4]>$fetchW[3]</td>";
		$ret .= "<td bgcolor=$colors[$c] id=owner$fetchW[4]>$fetchW[5]</td>";
		$ret .= "<td bgcolor=$colors[$c] id=edit$fetchW[4]><a href=\"\" onclick=\"editBatch($fetchW[4]); return false;\">Edit</a></td>";
		$ret .= "<td bgcolor=$colors[$c]><a href=\"\" onclick=\"deleteBatch($fetchW[4],'$fetchW[0]'); return false;\">Delete</a></td>";
		$ret .= "<td bgcolor=$colors[$c]><a href=\"/queries/batches/forLisa.php?batchID=$fetchW[4]\">Report</a></td>";
		$ret .= "</tr>";
	}
	
	$ret .= "</table>";
	return $ret;
}

function showBatchDisplay($id,$orderby=' ORDER BY b.listID DESC'){
	global $sql;
	$nameQ = "select batchName,batchType from batches where batchID=$id";
	$nameR = $sql->query($nameQ);
	$nameW = $sql->fetch_row($nameR);
	$name = $nameW[0];
	$type = $nameW[1];
	$saleHeader = "Sale Price";
	if ($type == 8){
		$saleHeader = "$ Discount";
	}
	elseif ($type == 9){
		$saleHeader = "% Discount";
	}
	else if ($type == 4){
		$saleHeader = "New Price";
	}
	
	$fetchQ = "select b.upc,
			case when l.likeCode is null then p.description
			else l.likeCodeDesc end as description,
			p.normal_price,b.salePrice,
			b.quantity
			from batchList as b left outer join products as p on
			b.upc = p.upc left outer join likeCodes as l on
			b.upc = concat('LC',convert(l.likeCode,char))
			where b.batchID = $id $orderby";
	$fetchR = $sql->query($fetchQ);
	
	$ret = "<b>Batch name</b>: $name<br />";
	$ret .= "<a href=\"\" onclick=\"backToList(); return false;\">Back to batch list</a> | ";
	$ret .= "<a href=barcodenew.php?batchID%5B%5D=$id>Print shelf tags</a> | ";
	$ret .= "<a href=\"\" onclick=\"forceBatch($id); return false;\">Force batch</a> | ";
	$ret .= "<a href=\"\" onclick=\"unsale($id); return false;\">Take Batch Off Sale</a><br />";
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
	if ($orderby != "ORDER BY b.saleprice DESC")
		$ret .= "<th><a href=\"\" onclick=\"redisplayWithOrder($id,'ORDER BY b.saleprice DESC'); return false;\">$saleHeader</a></th>";
	else
		$ret .= "<th><a href=\"\" onclick=\"redisplayWithOrder($id,'ORDER BY b.saleprice ASC'); return false;\">$saleHeader</a></th>";
	$ret .= "</tr>";
	
	$colors = array('#ffffff','#ffffcc');
	$c = 0;
	$row = 1;
	while($fetchW = $sql->fetch_array($fetchR)){
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
		if ($fetchW[4] != 0)
			$ret .= "<td bgcolor=$colors[$c] id=salePrice$fetchW[0]>$fetchW[4] for $fetchW[3]</td>";
		else
			$ret .= "<td bgcolor=$colors[$c] id=salePrice$fetchW[0]>$fetchW[3]</td>";
		$ret .= "<td bgcolor=$colors[$c] id=editLink$fetchW[0]><a href=\"\" onclick=\"editPrice('$fetchW[0]'); return false;\">Edit</a></td>";
		$ret .= "<td bgcolor=$colors[$c]><a href=\"\" onclick=\"deleteItem('$fetchW[0]'); return false;\">Delete</a></td>";
		$ret .= "</tr>";
		$row++;
	}
	$ret .= "</table>";
	$ret .= "<input type=hidden id=currentBatchID value=$id />";
	
	return $ret;
}

include($FANNIE_ROOT.'auth/login.php');
$user = validateUserQuiet('batches');
$audited=0;
if (!$user){
	$audited=1;
	$user = validateUserQuiet('batches_audited');
}
if (!$user){
	header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/it/newbatch");
	return;
}

?>

<html>
<head><title>Batch controller</title>
<script type="text/javascript" src="index.js"></script>
<script src="<?php echo $FANNIE_URL; ?>src/CalendarControl.js"
        language="javascript"></script>
<link href="<?php echo $FANNIE_URL; ?>src/style.css"
      rel="stylesheet" type="text/css">
<link rel="stylesheet" type="text/css" href="index.css">
</head>
<body onload="document.getElementById('newBatchName').focus();">
<div style="text-align:center;" id="batchmobile">
<a href="batchmobile-large.png"><img src="batchmobile-small.png" border=0 /></a>
<br />
<a href="" onclick="document.getElementById('batchmobile').style.display='none'; return false;">Hide</a>
</div>
<div id="inputarea">
<?php echo newBatchInput(); ?>
</div>
<div id="displayarea">
<?php echo batchListDisplay(); ?>
</div>
<input type=hidden id=uid value="<?php echo $user; ?>" />
<input type=hidden id=isAudited value="<?php echo $audited; ?>" />
</body>
</html>
