<?php

include('../../../config.php');
include_once('dbconnect.php');

$sql = dbconnect();

$out = '';
if (isset($_GET['action'])){
	// prepend request name & backtick
	$out = $_GET['action']."`";
	// switch on request name
	switch ($_GET['action']){
	case 'addIngredient':
		$name = $_GET['name'];
		$size = $_GET['size'];
		$sizeUnit = $_GET['sizeUnit'];
		$volume = $_GET['volume'];
		$volUnit = $_GET['volumeUnit'];
		$cost = $_GET['cost'];
		
		$idQ = "select max(id) from ingredients";
		$idR = $sql->query($idQ);
		$idW = $sql->fetch_array($idR);
		$id = $idW[0]+1;
		
		$insQ = "insert into ingredients values ($id,'$name',$size,'$sizeUnit',$volume,'$volUnit',$cost)";
		//echo $insQ;
		$insR = $sql->query($insQ);
	
		$out .= getIngredients();
		break;
	case 'viewIngredient':
		$id = $_GET['id'];
		$out .= viewIngredient($id);
		break;
	case 'editName':
		$id = $_GET['id'];
		$nameQ = "select name from ingredients where id=$id";
		$nameR = $sql->query($nameQ);
		$nameW = $sql->fetch_array($nameR);
		$name = $nameW[0];
		
		$out .= "<td>Name:</td><td>";
		$out .= "<form onSubmit=\"saveName($id); return false;\">";	
		$out .= "<input type=text id=name$id size=10 value=\"$name\" /> ";
		$out .= "<input type=submit value=Save />";
		$out .= "</form></td>";
		break;
	case 'saveName':
		$id = $_GET['id'];
		$name = $_GET['name'];
		
		$upQ = "update ingredients set name='$name' where id=$id";
		$upR = $sql->query($upQ);
		break;
	case 'editSize':
		$id = $_GET['id'];
		$sizeQ = "select size,sizeUnit from ingredients where id=$id";
		$sizeR = $sql->query($sizeQ);
		$sizeW = $sql->fetch_array($sizeR);
		$size = $sizeW[0];
		$sizeUnit = $sizeW[1];
		
		$out .= "<td>Weight:</td><td>";
		$out .= "<form onsubmit=\"saveSize($id)\"; return false;\">";
		$out .= "<input type=text id=size$id size=4 value=\"$size\" /> ";
		$out .= "<select id=sizeUnit$id>";
		$opts = array("lbs","oz","each");
		foreach ($opts as $x){
			$out .= "<option";
			if ($sizeUnit == $x)
				$out .= " selected";
			$out .= ">$x</option>";
		}
		$out .= "</select> ";
		$out .= "<input type=submit value=Save />";
		$out .= "</form></td>";
		break;
	case 'saveSize':
		$id = $_GET['id'];
		$size = $_GET['size'];
		$sizeUnit = $_GET['sizeUnit'];
		$upQ = "update ingredients set size=$size,sizeUnit='$sizeUnit' where id=$id";
		$upR = $sql->query($upQ);
		break;
	case 'editVolume':
		$id = $_GET['id'];
		$volumeQ = "select volume,volumeUnit from ingredients where id=$id";
		$volumeR = $sql->query($volumeQ);
		$volumeW = $sql->fetch_array($volumeR);
		$volume = $volumeW[0];
		$volumeUnit = $volumeW[1];
		
		$out .= "<td>Volume:</td><td>";
		$out .= "<form onsubmit=\"saveVolume($id)\"; return false;\">";
		$out .= "<input type=text id=volume$id size=4 value=\"$volume\" /> ";
		$out .= "<select id=volumeUnit$id>";
		$opts = array("tsp","T","fl oz","cup","pint","quart","gallon","each");
		foreach ($opts as $x){
			$out .= "<option";
			if ($volumeUnit == $x)
				$out .= " selected";
			$out .= ">$x</option>";
		}
		$out .= "</select> ";
		$out .= "<input type=submit value=Save />";
		$out .= "</form></td>";
		break;
	case 'saveVolume':
		$id = $_GET['id'];
		$volume = $_GET['volume'];
		$volumeUnit = $_GET['volumeUnit'];
		
		$upQ = "update ingredients set volume=$volume,volumeUnit='$volumeUnit' where id=$id";
		$upR = $sql->query($upQ);
		break;
	case 'editCost':
		$id = $_GET['id'];
		$costQ = "select cost from ingredients where id=$id";
		$costR = $sql->query($costQ);
		$costW = $sql->fetch_array($costR);
		$cost = $costW[0];
		
		$out .= "<td>Cost:</td><td>";
		$out .= "<form onSubmit=\"saveCost($id); return false;\">";	
		$out .= "<input type=text id=cost$id size=10 value=\"$cost\" /> ";
		$out .= "<input type=submit value=Save />";
		$out .= "</form></td>";
		break;
	case 'saveCost':
		$id = $_GET['id'];
		$cost = $_GET['cost'];
		
		$upQ = "update ingredients set cost=$cost where id=$id";
		$upR = $sql->query($upQ);
		break;
	case 'flipOn':
		$id = $_GET['id'];
		$class = $_GET['class'];
		
		$inQ = "insert into ingredientstatus values ($id,$class)";
		$inR = $sql->query($inQ);
		break;
	case 'flipOff':
		$id = $_GET['id'];
		$class = $_GET['class'];
		
		$delQ = "delete from ingredientstatus where ingredientID=$id and classID=$class";
		$delR = $sql->query($delQ);
		break;
	}
	echo $out;
	return;
}

// returns a list of ingredients as a string
function getIngredients(){
	global $sql;
	$ret = "<b>Ingredients</b>: ( <a href=\"\" onClick=\"newIngredient(); return false;\">New</a> )";
	$ret .= "<ul>";
	$q = "select name,id from ingredients where id > 0 order by name";
	$r = $sql->query($q);
	if ($sql->num_rows($r) != 0){
		while ($w = $sql->fetch_array($r)){
			$ret .= "<li><a href=\"\" onClick=\"viewIngredient($w[1]); return false;\">";
			$ret .= $w[0]."</a></li>";
		}
	}
	$ret .= "</ul>";
	
	return $ret;
}

function viewIngredient($id){
	global $sql;
	$fetchQ = "select name,size,sizeUnit,volume,volumeUnit,cost from ingredients where id=$id";
	$fetchR = $sql->query($fetchQ);
	$fetchW = $sql->fetch_array($fetchR);
	
	$ret = "<input type=hidden id=ingredientID value=$id />";
	$ret .= "<table>";
	
	$ret .= "<tr id=ingredientName><td>Name:</td><td>".$fetchW['name']."</td>";
	$ret .= "<td><a href=\"\" onClick=\"editName($id); return false;\">Edit</a></td></tr>";
	
	$ret .= "<tr id=ingredientSize><td>Weight:</td><td>".$fetchW['size']." ".$fetchW['sizeUnit']."</td>";
	$ret .= "<td><a href=\"\" onClick=\"editSize($id); return false;\">Edit</a></td></tr>";
	
	$ret .= "<tr id=ingredientVolume><td>Volume:</td><td>".$fetchW['volume']." ".$fetchW['volumeUnit']."</td>";
	$ret .= "<td><a href=\"\" onClick=\"editVolume($id); return false;\">Edit</a></td></tr>";
	
	$ret .= "<tr id=ingredientCost><td>Cost:</td><td>".$fetchW['cost']."</td>";
	$ret .= "<td><a href=\"\" onClick=\"editCost($id); return false;\">Edit</a></td></tr>";
	
	$ret .= "</table>";
	
	$statusQ = "select c.id,c.name,case when s.classID is NULL then 0 else 1 end as flag 
			   from ingredientclasses as c left outer join ingredientstatus as s
			   on c.id = s.classID and s.ingredientID=$id";
	$statusR = $sql->query($statusQ);
	while ($statusW = $sql->fetch_array($statusR)){
		$ret .= "<b>".$statusW['name']."</b>: ";
		$ret .= "<input type=checkbox id=\"".$statusW['name'].$statusW['id']."\" ";
		if ($statusW['flag'] == 1)
			$ret .= "checked ";
		$ret .= "onclick=\"flipStatus($id,'{$statusW['name']}',{$statusW['id']});\" /><br />";
	}
	
	return $ret;
}
?>

<html>
<head><title>Recipizer::Ingredients</title>
<script type="text/javascript" src="ingredients.js"></script>
<link rel="stylesheet" type="text/css" href="index.css">
</head>
<div id="top">
<a href="index.php">Recipes</a> :: 
<a href="ingredients.php">Ingredients</a> :: 
<a href="viewer.php">Viewer</a>
</div>
<div id="left">
<?php echo getIngredients() ?>
</div>
<div id="right">

</div>
</body>
</html>
