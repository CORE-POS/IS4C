<?php

include('../../../config.php');
include_once('dbconnect.php');

$sql = dbconnect();

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
    case 'viewRecipes':
        $catID = $_GET['catID'];
        $out .= getRecipes($catID);
        break;
    case 'displayRecipe':
        $id = $_GET['id'];
        $out .= displayRecipe($id);
        break;
    case 'multiply':
        $id = $_GET['id'];
        $mult = $_GET['multiplier'];
        $out .= getIngredients($id,$mult);
    }

    echo $out;
    return;
}

// returns a list of categories as a string
function getCategories(){
    global $sql;
    $ret = "<b>Categories</b>:";
    $ret .= "<ul>";
    $q = "select name,id from categories order by name";
    $r = $sql->query($q);
    if ($sql->num_rows($r) != 0){
        while ($w = $sql->fetchRow($r)){
            $ret .= "<li><a href=\"\" onClick=\"viewRecipes(".$w[1]."); return false;\">";
            $ret .= $w[0]."</a></li>";
        }
    }
    $ret .= "</ul>";
    
    return $ret;
}

// returns a list of recipes in category with id $id (as a string again)
function getRecipes($id){
    global $sql;
    $q = $sql->prepare("select name from categories where id=?");
    $r = $sql->execute($q, array($id));
    $w = $sql->fetchRow($r);
    $catName = $w[0];    

    $ret = "<b>Category</b>: ".$catName."<br />";
    $ret .= "<b>Recipes</b>:";
    $ret .= "<ul>";
    $q = $sql->prepare("select name,id from recipes where categoryID = ? order by name");
    $r = $sql->execute($q, array($id));
    if ($sql->num_rows($r) != 0){
        while ($w = $sql->fetchRow($r)){
            $ret .= "<li><a href=\"\" onClick=\"displayRecipe(".$w[1]."); return false;\">";
            $ret .= $w[0]."</a></li>";
        }
    }
    $ret .= "</ul>";
    
    return $ret;
}

// displays a recipe.  this may become 'ze doozy'
function displayRecipe($id){
    global $sql;
    $ret = "";
    $q = $sql->prepare("select r.name,r.upc,r.margin,r.price,r.servings,r.current_margin,i.info
          from recipes as r, info as i where r.id=i.recipeID and r.id=?");
    $r = $sql->execute($q, array($id));
    $w = $sql->fetchRow($r);
    
    $name = $w['name'];
    $upc = $w['upc'];
    $margin = $w['margin'];
    $price = $w['price'];
    $servings = $w['servings'];
    $current_margin = $w['current_margin'];
    $info = $w['info'];
    
    $ret .= "<b>Name</b>: $name<br />";
    $ret .= "\n<b>UPC</b>: $upc<br />";
    $ret .= "<div id=recipeprice><b>Price</b>: $price</div>";
    $ret .= "\n<b>Servings</b>: $servings<br />";
    $ret .= "<b>Multiplier</b>: <input type=text value=1 id=multiplier size=3 /> ";
    $ret .= "<a href=\"\" onclick=\"mult($id); return false;\">Change</a> | ";
    $ret .= "<a href=\"print.php?id=$id\" target=\"print_window\">Print</a><br />";
    
    $ret .= "<br /><b>Ingredients</b>";
    $ret .= "<div id=recipeingredients>";
    $ret .= getIngredients($id);
    $ret .= "</div>";
    
    $ret .= "<br /><br /><b>Steps</b>:";
    $ret .= "<div id=recipesteps>";
    $ret .= getSteps($id);
    $ret .= "</div>";
    
    $ret .= "<br /><br /><b>Info</b>:";
    $ret .= "<div id=infovalue>".$info."</div>";
    
    return $ret;
}

// prints the list of steps
function getSteps($id){
    global $sql;
    $ret = "<table>";
    $q = $sql->prepare("select step from steps where recipeID = ? order by ord");
    $r = $sql->execute($q, array($id));
    $i = 1;
    while ($w = $sql->fetchRow($r)){
        if ($i % 2 != 0)
            $ret .= "<tr style=\"background: #cccccc;\">";
        else
            $ret .= "<tr style=\"background: #aaaaaa;\">";
        $ret .= "<td>$i.</td>";
        $ret .= "<td id=steplist$i style=\"width: 20em;\">";
        $ret .= $w[0]."</td>";
        $ret .= "</tr>";
        $i++;
    }
    $ret .= "</table>";
    
    return $ret;
}

// get the list of ingredients as a string
function getIngredients($id,$mult=1){
    global $sql;
    $ret = "<table>";
    $q = $sql->prepare("select i.name,l.measure,l.unit,l.prep from ingredientlist as l
          left join ingredients as i on i.id = l.ingredientID
          where recipeID = ? order by ord");
    $r = $sql->execute($q, array($id));
    $i = 1;
    $numbering = 1;
    while ($w = $sql->fetchRow($r)){
        if ($i % 2 != 0)
            $ret .= "<tr style=\"background: #cccccc;\">";
        else
            $ret .= "<tr style=\"background: #aaaaaa;\">";
        if ($w[0] == 'LABEL'){
            $numbering = 0;
            $ret .= "<td>&nbsp;</td>";
            $ret .= "<td id=ingredientlist$i><b>$w[3]</b></td>";
        }
        else {
            $ret .= "<td>$numbering.</td>";
            $ret .= "<td id=ingredientlist$i style=\"width: 20em;\">";
            $ret .= "<span id=ingredientname$i>$w[0]</span> - ";
            $w[1] *= $mult;
            $ret .= "<span id=ingredientmeasure$i>$w[1]</span> ";
            $ret .= "<span id=ingredientunits$i>$w[2]</span> - ";
            $ret .= "<span id=ingredientprep$i>$w[3]</span>";
            $ret .= "</td>";
        }
        $ret .= "</tr>";
        $i++;
        $numbering++;
    }
    $ret .= "</table>";
    
    return $ret;
}

?>

<html>
<head><title>Recipizer::Index</title>
<script type="text/javascript" src="viewer.js"></script>
<link rel="stylesheet" type="text/css" href="index.css">
</head>

<body>
<div id="top">
<a href="index.php">Recipes</a> :: 
<a href="ingredients.php">Ingredients</a> :: 
<a href="viewer.php">Viewer</a>
</div>
<div id="left">

<?php echo getCategories() ?>

</div>
<div id="right">

</div>
</body>
</html>
