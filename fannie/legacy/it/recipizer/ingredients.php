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
        $idW = $sql->fetchRow($idR);
        $id = $idW[0]+1;
        
        $insQ = $sql->prepare("insert into ingredients values (?, ?, ?, ?, ?, ?, ?)");
        //echo $insQ;
        $insR = $sql->execute($insQ, array($id, $name, $size, $sizeUnit, $volume, $volUnit, $cost));
    
        $out .= getIngredients();
        break;
    case 'viewIngredient':
        $id = $_GET['id'];
        $out .= viewIngredient($id);
        break;
    case 'editName':
        $id = $_GET['id'];
        $nameQ = $sql->prepare("select name from ingredients where id=?");
        $nameR = $sql->execute($nameQ, array($id));
        $nameW = $sql->fetchRow($nameR);
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
        
        $upQ = $sql->prepare("update ingredients set name=? where id=?");
        $upR = $sql->execute($upQ, array($name, $id));
        break;
    case 'editSize':
        $id = $_GET['id'];
        $sizeQ = $sql->prepare("select size,sizeUnit from ingredients where id=?");
        $sizeR = $sql->execute($sizeQ, array($id));
        $sizeW = $sql->fetchRow($sizeR);
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
        $upQ = $sql->prepare("update ingredients set size=?,sizeUnit=? where id=?");
        $upR = $sql->execute($upQ, array($size, $sizeUnit, $id));
        break;
    case 'editVolume':
        $id = $_GET['id'];
        $volumeQ = $sql->prepare("select volume,volumeUnit from ingredients where id=?");
        $volumeR = $sql->execute($volumeQ, array($id));
        $volumeW = $sql->fetchRow($volumeR);
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
        
        $upQ = $sql->prepare("update ingredients set volume=?,volumeUnit=? where id=?");
        $upR = $sql->execute($upQ, array($volume, $volumeUnit, $id));
        break;
    case 'editCost':
        $id = $_GET['id'];
        $costQ = $sql->prepare("select cost from ingredients where id=?");
        $costR = $sql->execute($costQ, array($id));
        $costW = $sql->fetchRow($costR);
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
        
        $upQ = $sql->prepare("update ingredients set cost=? where id=?");
        $upR = $sql->execute($upQ, array($cost, $id));
        break;
    case 'flipOn':
        $id = $_GET['id'];
        $class = $_GET['class'];
        
        $inQ = $sql->prepare("insert into ingredientstatus values (?,?)");
        $inR = $sql->execute($inQ, array($id, $class));
        break;
    case 'flipOff':
        $id = $_GET['id'];
        $class = $_GET['class'];
        
        $delQ = $sql->prepare("delete from ingredientstatus where ingredientID=? and classID=?");
        $delR = $sql->execute($delQ, array($id, $class));
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
        while ($w = $sql->fetchRow($r)){
            $ret .= "<li><a href=\"\" onClick=\"viewIngredient($w[1]); return false;\">";
            $ret .= $w[0]."</a></li>";
        }
    }
    $ret .= "</ul>";
    
    return $ret;
}

function viewIngredient($id){
    global $sql;
    $fetchQ = $sql->prepare("select name,size,sizeUnit,volume,volumeUnit,cost from ingredients where id=?");
    $fetchR = $sql->execute($fetchQ, array($id));
    $fetchW = $sql->fetchRow($fetchR);
    
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
    
    $statusQ = $sql->prepare("select c.id,c.name,case when s.classID is NULL then 0 else 1 end as flag 
               from ingredientclasses as c left outer join ingredientstatus as s
               on c.id = s.classID and s.ingredientID=?");
    $statusR = $sql->execute($statusQ, array($id));
    while ($statusW = $sql->fetchRow($statusR)){
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
