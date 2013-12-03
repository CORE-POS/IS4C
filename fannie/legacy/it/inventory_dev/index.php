<?php

include('../../../config.php');
header('Location: '.$FANNIE_URL.'modules/plugins2.0/DeliInventory/DeliInventoryPage.php');
exit;

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

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
	case 'additem':
		$item = $_GET['item'];
		$orderno = $_GET['orderno'];
		$units = strim($_GET['units']);
		$price = $_GET['price'];
		$price = preg_replace("/,/",".",$price);
		$size = $_GET['size'];
		$cases = strim($_GET['cases']);
		$fraction = strim($_GET['fraction']);
		$category = strim($_GET['category']);
		$category = preg_replace("/_/"," ",$category);

		if (empty($price))
			$price = 0;
		if (empty($cases))
			$cases = 0;
		if (empty($fraction))
			$fraction = 0;
		
		$stocktotal = 0;
		if (!empty($units)){
			if ($units[strlen($units)-1] == '#' && 
				$fraction[strlen($fraction)-1] == '#' && $units != 0){
				$partial = substr($fraction,0,strlen($fraction)-1) / substr($units,0,strlen($units)-1);
				$stocktotal = $cases + $partial;		
			}
			else if ($units != 0){
				$partial = $fraction / $units;
				$stocktotal = $cases + $partial;
			}
		}
		$total = $stocktotal * $price;
			
		$idQ = "select max(id) from deliInventoryCat";
		$idR = $sql->query($idQ);
		$id = array_pop($sql->fetch_array($idR)) + 1;
		
		$insQ = "insert into deliInventoryCat values 
				($id,'$item','$orderno','$units',$cases,'$fraction',$stocktotal,$price,$total,'$size','$category')";
		$insR = $sql->query($insQ);
		
		$out .= gettable();
		break;
	case 'saveitem':
		$id = $_GET["id"];
		$item = strim($_GET['item']);
		$orderno = strim($_GET['orderno']);
		$units = strim($_GET['units']);
		$cases = strim($_GET['cases']);
		$fraction = strim($_GET['fraction']);
		$price = strim($_GET['price']);
		$size = strim($_GET['size']);

		if (empty($cases) || !is_numeric($cases))
			$cases = 0;
		if (empty($fraction))
			$fraction = 0;
		
		$stocktotal = 0;
		if (!empty($units)){
			if ($units[strlen($units)-1] == '#' && 
				$fraction[strlen($fraction)-1] == '#' && $units != 0){
				$partial = substr($fraction,0,strlen($fraction)-1) / substr($units,0,strlen($units)-1);
				$stocktotal = $cases + $partial;		
			}
			else if ($units != 0){
				$partial = $fraction / $units;
				$stocktotal = $cases + $partial;
			}
		}
		$total = $stocktotal * $price;
		
		$upQ = "update deliInventoryCat set item='$item',orderno='$orderno',units='$units',cases=$cases,
				fraction='$fraction',totalstock=$stocktotal,price=$price,total=$total,size='$size'
				where id=$id";
		$upR = $sql->query($upQ);
		
		break;
	case 'refresh':
		$out .= gettable();
		break;
	case 'deleteitem':
		$id = $_GET['id'];
		
		$delQ = "delete from deliInventoryCat where id=$id";
		$delR = $sql->query($delQ);
		
		$out .= gettable();
		break;
	case 'printview':
		$category = $_GET['category'];
			
		$out = "";

		if (isset($_GET["excel"])){
			header("Content-Disposition: inline; filename=deliInventoryCat.xls");
			header("Content-Description: PHP3 Generated Data");
			header("Content-type: application/vnd.ms-excel; name='excel'");
		}
		else {
			$out .= "<a href=index.php?action=printview&category=$category&excel=yes>Save to Excel</a><br />";	
		}
		$out .= gettable(true,$category);
		break;
	case 'saveCategory':
		$oldcat = preg_replace("/_/"," ",$_GET['oldcat']);
		$newcat = preg_replacE("/_/"," ",$_GET['newcat']);

		$updateQ = "update deliInventoryCat set category='$newcat' where category='$oldcat'";
		$updateR = $sql->query($updateQ);

		$out .= gettable();
		break;
	case 'moveUp':
		$id = $_GET['id'];
		$cat = preg_replace("/_/"," ",$_GET['category']);

		$locateQ = "select max(id) from deliInventoryCat where
				id < $id and category='$cat'";	
		$locateR = $sql->query($locateQ);
		$loc = array_pop($sql->fetch_array($locateR));
		if ($loc != "")
			swap($id,$loc);		
	
		$out .= gettable();
		break;
	case 'moveDown':
		$id = $_GET['id'];
		$cat = preg_replace("/_/"," ",$_GET['category']);

		$locateQ = "select min(id) from deliInventoryCat where
				id > $id and category='$cat'";	
		$locateR = $sql->query($locateQ);
		$loc = array_pop($sql->fetch_array($locateR));
		if ($loc != "")
			swap($id,$loc);		
	
		$out .= gettable();
		break;
	case 'catList':
		$id = $_GET['id'];
		$cat = preg_replace("/_/"," ",$_GET['category']);
		
		$fetchQ = "select category from deliInventoryCat
			   group by category order by category";
		$fetchR = $sql->query($fetchQ);
		
		$out .= "$id"."`";
		$out .= "<select onchange=\"saveCat($id);\" id=catSelect$id>";
		while ($fetchW = $sql->fetch_array($fetchR)){
			if ($fetchW[0] == $cat)
				$out .= "<option selected>$fetchW[0]</option>";
			else
				$out .= "<option>$fetchW[0]</option>";
		}
		$out .= "</select>";
		break;
	case 'changeCat':
		$id = $_GET['id'];
		$newcat = $_GET['newcat'];
		$upQ = "update deliInventoryCat set category='$newcat' where id=$id";
		$upR = $sql->query($upQ);

		$out .= gettable();
		break;
	case 'clearAll':
		$clearQ = "update deliInventoryCat set cases=0, fraction=0,
			totalstock=0, total=0";
		$clearR = $sql->query($clearQ);
		$out .= gettable();
		break;
	}
	
	echo $out;
	return;
}

function gettable($limit=false,$limitCat="ALL"){
	global $sql;
	$ret = "";
	$colors = array('#ffffcc','#ffffff');
	$c = 0;

	$fetchQ = "select item,size,orderno,units,
		   case when cases='0' then NULL else cases end as cases,
		   case when fraction='0' then NULL else fraction end as fraction,
		   case when totalstock='0' then NULL else totalstock end as totalstock,
		   price,total,category,id
	           from deliInventoryCat
		   order by category, item";
	if ($limit){
		$fetchQ = "select item,size,orderno,units,
			   case when cases='0' then NULL else cases end as cases,
			   case when fraction='0' then NULL else fraction end as fraction,
			   case when totalstock='0' then NULL else totalstock end as totalstock,
			   price,total,category,id
			   from deliInventoryCat
			   where category='$limitCat'
			   order by category, item";
	}
	$fetchR = $sql->query($fetchQ);

	$ret = "<a href=\"\" onclick=\"saveAll();return false;\">Save all changes</a> | <a href=\"\" onclick=\"clearAll();return false;\">Clear all totals</a><br /><br />";

	$currentCat = "";
	$sum = 0.0;
	while ($fetchW = $sql->fetch_array($fetchR)){
		$catfixed = $currentCat;
		if ($fetchW['category'] != $currentCat){
			if ($currentCat != ""){
				$ret .= "<tr><th bgcolor=$colors[$c]>Grand Total</th>";
				for ($i = 0; $i < 7; $i++)
					$ret .= "<td bgcolor=$colors[$c]>&nbsp;</td>";	
				$ret .= "<td>$sum</td></tr>";
				$ret .= "</table>";
				if (!$limit)
					$ret .= inputBox($currentCat);
				$ret .= "<hr />";
			}
			$currentCat = $fetchW['category'];
			$catfixed = preg_replace("/ /","_",$currentCat);
			$ret .= "<b><span id=category$catfixed>$currentCat</span></b>";	
			$ret .= "<span id=renameTrigger$catfixed>";
			if (!$limit){
				$ret .= " [<a href=\"\" onclick=\"renameCategory('$catfixed'); return false;\">Rename This Category</a>]";
				$ret .= " [<a href=\"index.php?action=printview&category=$currentCat\">Print this Category</a>]";
			}
			$ret .= "</span>";
			$ret .= "<table cellspacing=0 cellpadding=3 border=1>";
			$ret .= "<tr><th>Item</th><th>Size</th><th>Order #</th><th>Units/Case</th>";
			$ret .= "<th>Cases</th><th>#/Each</th><th>Total cases</th>";
			$ret .= "<th>Price/case</th><th>Total</th></tr>";
			$c = 0;
			$sum = 0.0;
		}
		$ret .= "<tr>";
		$ret .= "<td onclick=\"edititem(".$fetchW['id'].",'$catfixed');\" id=item".$fetchW['id']."col0 bgcolor=$colors[$c]>".$fetchW['item']."&nbsp;</td>";
		$ret .= "<td onclick=\"edititem(".$fetchW['id'].",'$catfixed');\" id=item".$fetchW['id']."col1 bgcolor=$colors[$c]>".$fetchW['size']."&nbsp;</td>";
		$ret .= "<td onclick=\"edititem(".$fetchW['id'].",'$catfixed');\" id=item".$fetchW['id']."col2 bgcolor=$colors[$c]>".$fetchW['orderno']."&nbsp;</td>";
		$ret .= "<td onclick=\"edititem(".$fetchW['id'].",'$catfixed');\" id=item".$fetchW['id']."col3 bgcolor=$colors[$c]>".$fetchW['units']."&nbsp;</td>";
		$ret .= "<td onclick=\"edititem(".$fetchW['id'].",'$catfixed');\" id=item".$fetchW['id']."col4 bgcolor=$colors[$c]>".$fetchW['cases']."&nbsp;</td>";
		$ret .= "<td onclick=\"edititem(".$fetchW['id'].",'$catfixed');\" id=item".$fetchW['id']."col5 bgcolor=$colors[$c]>".$fetchW['fraction']."&nbsp;</td>";
		$ret .= "<td onclick=\"edititem(".$fetchW['id'].",'$catfixed');\" id=item".$fetchW['id']."col6 bgcolor=$colors[$c]>".$fetchW['totalstock']."&nbsp;</td>";
		$ret .= "<td onclick=\"edititem(".$fetchW['id'].",'$catfixed');\" id=item".$fetchW['id']."col7 bgcolor=$colors[$c]>".$fetchW['price']."&nbsp;</td>";
		$ret .= "<td onclick=\"edititem(".$fetchW['id'].",'$catfixed');\" id=item".$fetchW['id']."col8 bgcolor=$colors[$c]>".$fetchW['total']."&nbsp;</td>";

		$sum += $fetchW['total'];		

		if (!$limit){
			$ret .= "<td id=edit".$fetchW['id']." bgcolor=$colors[$c]><a href=\"\" onclick=\"edititem(".$fetchW['id']."); return false;\" title=Edit><img src=images/b_edit.png border=0 /></a></td>";
			$ret .= "<td id=changecat".$fetchW['id']." bgcolor=$colors[$c]><a href=\"\" onclick=\"catList(".$fetchW['id'].",'$catfixed'); return false;\">Category</a></td>";
			$ret .= "<td bgcolor=$colors[$c]><a href=\"\" onclick=\"deleteitem(".$fetchW['id']."); return false;\" title=Delete><img src=images/b_drop.png border=0 /></a></td>";
		}

		$ret .= "</tr>";
		$c = ($c+1)%2;
		
	}
	$ret .= "<tr><th bgcolor=$colors[$c]>Grand Total</th>";
	for ($i = 0; $i < 7; $i++)
		$ret .= "<td bgcolor=$colors[$c]>&nbsp;</td>";	
	$ret .= "<td>$sum</td></tr>";
	$ret .= "</table>";
	if (!$limit)
		$ret .= inputBox($currentCat);
	
	
	return $ret;
}

function inputBox($category){
	global $sql;
	$category = preg_replace("/ /","_",$category);
	$ret = "<form onsubmit=\"additem('$category'); return false;\" id=newform$category>";
	$ret .= "<table cellspacing=0 cellpadding=3 border=1>";
	$ret .= "<tr>";
	$ret .= "<th>Item</th><th>Size</th><th>Order #</th><th>Units/Case</th>";
	$ret .= "<th>Cases</t><th>#/Each</th><th>Price/case</th>";
	$ret .= "</tr>";
	$ret .= "<tr>";
	$ret .= "<td bgcolor=#cccccc><input type=text id=newitem$category maxlength=50 /></td>";
	$ret .= "<td bgcolor=#cccccc><input type=text id=newsize$category size=8 maxlength=20 /></td>";
	$ret .= "<td bgcolor=#cccccc><input type=text id=neworderno$category size=6 maxlength=15 /></td>";
	$ret .= "<td bgcolor=#cccccc><input type=text id=newunits$category size=7 maxlength=10 /></td>";
	$ret .= "<td bgcolor=#cccccc><input type=text id=newcases$category size=7 maxlength=10 /></td>";
	$ret .= "<td bgcolor=#cccccc><input type=text id=newfraction$category size=7 maxlength=10 /></td>";
	$ret .= "<td bgcolor=#cccccc><input type=text id=newprice$category size=7 /></td>";
	$ret .= "<td><input type=submit value=Add /></td>";
	$ret .= "</tr>";
	$ret .= "</table>";
	$ret .= "</form>";

	return $ret;
}

function swap($id1,$id2){
	global $sql;
	$q1 = "update deliInventoryCat set id=-1*$id2 where id=$id2";
	$sql->query($q1);
	
	$q2 = "update deliInventoryCat set id=$id2 where id=$id1";
	$sql->query($q2);

	$q3 = "update deliInventoryCat set id=$id1 where id=-1*$id2";
	$sql->query($q3);
}

// safari trim
// also takes off ascii 160 chars
function strim($str){
	return trim($str,chr(32).chr(9).chr(10).chr(11).chr(13).chr(0).chr(160).chr(194));
}
?>

<html>
<head><title>Inventory</title>
<script type="text/javascript" src="index.js"></script>
<link rel="stylesheet" type="text/css" href="index.css">
</head>
<body>
<div id=tablearea><?php echo gettable() ?></div>
<div id=inputarea>
<hr />
<b>Add an item to a new category</b><br />
<form onsubmit="additem('__new__'); return false;" id=newform__new__>
	<table cellspacing=0 cellpadding=3 border=1>
	<tr>
	<th>Item</th><th>Size</th><th>Order #</th><th>Units/Case</th>
	<th>Cases</t><th>#/Each</th><th>Price/case</th><th>Category Name</th>
	</tr>
	<tr>
	<td bgcolor=#cccccc><input type=text id=newitem__new__ maxlength=50 /></td>
	<td bgcolor=#cccccc><input type=text id=newsize__new__ size=8 maxlength=20 /></td>
	<td bgcolor=#cccccc><input type=text id=neworderno__new__ size=6 maxlength=15 /></td>
	<td bgcolor=#cccccc><input type=text id=newunits__new__ size=7 maxlength=10 /></td>
	<td bgcolor=#cccccc><input type=text id=newcases__new__ size=7 maxlength=10 /></td>
	<td bgcolor=#cccccc><input type=text id=newfraction__new__ size=7 maxlength=10 /></td>
	<td bgcolor=#cccccc><input type=text id=newprice__new__ size=7 /></td>
	<td bgcolor=#cccccc><input type=text id=category__new__ maxlength=50 /></td>
	<td><input type=submit value=Add /></td>
	</tr>
	</table>
</form>
<br />
</div>
</body>
</html>
