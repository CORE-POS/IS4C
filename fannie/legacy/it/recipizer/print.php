<?php

include('../../../config.php');
include_once('dbconnect.php');

$sql = dbconnect();

$id = $_GET['id'];

echo displayRecipe($id);

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
    
    $ret .= "<span style=\"font-size: 150%\"><b>$name</b></span><br />";
    $ret .= "\n<b>UPC</b>: $upc<br />";
    $ret .= "\n<b>Servings</b>: $servings<br />";
    
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
    $ret = "<table cellspacing=0 cellpadding=3 border=1>";
    $q = $sql->prepare("select step from steps where recipeID = ? order by ord");
    $r = $sql->execute($q, array($id));
    $i = 1;
    while ($w = $sql->fetchRow($r)){
        $ret .= "<tr>";
        $ret .= "<td>$i.</td>";
        $ret .= "<td id=steplist$i>";
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
    $ret = "<table cellspacing=0 cellpadding=3 border=1>";
    $q = $sql->prepare("select i.name,l.measure,l.unit,l.prep from ingredientlist as l
          left join ingredients as i on i.id = l.ingredientID
          where recipeID = ? order by ord");
    $r = $sql->execute($q, array($id));
    $i = 1;
    $numbering = 1;
    while ($w = $sql->fetchRow($r)){
        $ret .= "<tr>";
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

