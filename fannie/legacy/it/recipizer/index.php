<?php

include('../../../config.php');

include_once('dbconnect.php');
include_once('products.php');

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
	case 'addCategory':
		$newCat = $_GET['newCategory'];
		
		$idQ = "select max(id) from categories";
		$idR = $sql->query($idQ);
		$idRow = $sql->fetch_array($idR);
		$id = $idRow[0]+1;
		
		$insQ = "insert into categories values ($id,'$newCat')";
		$insR = $sql->query($insQ);
		$out .= getCategories();
		break;
	case 'viewRecipes':
		$catID = $_GET['catID'];
		$out .= getRecipes($catID);
		break;
	case 'addRecipe':
		$name = $_GET['name'];
		$upc = $_GET['upc'];
		$margin = $_GET['margin'];
		$servings = 0;
		if (!empty($_GET['servings']))
			$servings = $_GET['servings'];
		$shelflife = 0;
		if (!empty($_GET['shelflife']))
			$shelflife = $_GET['shelflife'];
		$catID = $_GET['catID'];
		
		$idQ = "select max(id) from recipes";
		$idR = $sql->query($idQ);
		$idRow = $sql->fetch_array($idR);
		$id = $idRow[0]+1;
	
		$insQ = "insert into recipes values ($id,'$name','$upc',0,$margin,$catID,$servings,0,$shelflife)";
		$insR = $sql->query($insQ);
		
		$insQ = "insert into info values ($id,'')";
		$insR = $sql->query($insQ);
		
		$out .= displayRecipe($id);
		break;
	case 'displayRecipe':
		$id = $_GET['id'];
		$out .= displayRecipe($id);
		break;
	case 'writeField':
		$id = $_GET['id'];
		$field = $_GET['field'];
		$value = $_GET['value'];
		
		$upQ = "update recipes set $field='$value' where id=$id";
		$upR = $sql->query($upQ);
		break;
	case 'saveNewStep':
		$id = $_GET['id'];
		$ord = $_GET['ord'];
		$step = $_GET['step'];
		
		$insQ = "insert into steps values ($id,$ord,'$step')";
		$insR = $sql->query($insQ);
		
		$out .= getSteps($id);
		break;
	case 'saveStep':
		$id = $_GET['id'];
		$ord = $_GET['ord'];
		$step = $_GET['step'];
		
		$upQ = "update steps set step='$step' where ord=$ord and recipeID=$id";
		$upR = $sql->query($upQ);
		
		$out .= getSteps($id);
		break;
	case 'deleteStep':
		$id = $_GET['id'];
		$ord = $_GET['ord'];
		
		$delQ = "delete from steps where recipeID=$id and ord=$ord limit 1";
		$delR = $sql->query($delQ);
		
		fixOrder('steps',$ord);
		
		$out .= getSteps($id);
		break;
	case 'moveUp':
		$id = $_GET['id'];
		$table = $_GET['table'];
		$ord = $_GET['ord'];
		$swap = $ord-1;
		
		$openQ = "update $table set ord=-1*$swap where ord=$swap and recipeID=$id";
		$openR = $sql->query($openQ);
		
		$fillQ = "update $table set ord=$swap where ord=$ord and recipeID=$id";
		$fillR = $sql->query($fillQ);
		
		$finishQ = "update $table set ord=$ord where ord=-1*$swap and recipeID=$id";
		$finishR = $sql->query($finishQ);
		
		if ($table == 'steps'){
			$out .= "recipesteps`";
			$out .= getSteps($id);
		}
		else if ($table == 'ingredientlist'){
			$out .= "recipeingredients`";
			$out .= getIngredients($id);
		}
		break;
	case 'moveDown':
		$id = $_GET['id'];
		$table = $_GET['table'];
		$ord = $_GET['ord'];
		$swap = $ord+1;
		
		$openQ = "update $table set ord=-1*$swap where ord=$swap and recipeID=$id";
		$openR = $sql->query($openQ);
		
		$fillQ = "update $table set ord=$swap where ord=$ord and recipeID=$id";
		$fillR = $sql->query($fillQ);
		
		$finishQ = "update $table set ord=$ord where ord=-1*$swap and recipeID=$id";
		$finishR = $sql->query($finishQ);
		
		if ($table == 'steps'){
			$out .= "recipesteps`";
			$out .= getSteps($id);
		}
		else if ($table == 'ingredientlist'){
			$out .= "recipeingredients`";
			$out .= getIngredients($id);
		}
		break;
	case 'writeInfo':
		$id = $_GET['id'];
		$info = $_GET['info'];
		
		$upQ = "update info set info='$info' where recipeID = $id";
		$upR = $sql->query($upQ);
		break;
	case 'saveNewIngredient':
		$id = $_GET['id'];
		$ord = $_GET['ord'];
		$ingredientID = $_GET['ingredientID'];
		$measure = $_GET['measure'];
		$units = $_GET['units'];
		$prep = $_GET['prep'];
		
		$insQ = "insert into ingredientlist values ($id,$measure,'$units',$ingredientID,$ord,'$prep')";
		$insR = $sql->query($insQ);
		
		$out .= getIngredients($id);
		break;
	case 'saveIngredient':
		$id = $_GET['id'];
		$ord = $_GET['ord'];
		$ingredientID = $_GET['ingredientID'];
		$measure = $_GET['measure'];
		$units = $_GET['units'];
		$prep = $_GET['prep'];
		
		$upQ = "update ingredientlist set ingredientID=$ingredientID,measure=$measure,unit='$units',prep='$prep' where recipeID=$id and ord=$ord"; 
		$upR = $sql->query($upQ);
		
		$out .= getIngredients($id);
		break;
	case 'deleteIngredient':
		$id = $_GET['id'];
		$ord = $_GET['ord'];
		
		$delQ = "delete from ingredientlist where recipeID=$id and ord=$ord limit 1";
		$delR = $sql->query($delQ);
		
		fixOrder('ingredientlist',$ord);
		
		$out .= getIngredients($id);
		break;
	case 'savePrice':
		$id = $_GET['id'];
		$price = $_GET['price'];
		
		$upQ = "update recipes set price=$price where id=$id";
		$upR = $sql->query($upQ);
		
		$upcQ = "select upc from recipes where id=$id";
		$upcR = $sql->query($upcQ);
		$upcW = $sql->fetch_array($upcR);
		$upc = $upcW[0];
		
		setPrice($upc,$price);
		break;
	case 'remargin':
		$id = $_GET['id'];
		
		$current_margin = margin($id);
		
		$upQ = "update recipes set current_margin=$current_margin where id=$id";
		$upR = $sql->query($upQ);
		
		$fetchQ = "select margin from recipes where id=$id";
		$fetchR = $sql->query($fetchQ);
		$fetchW = $sql->fetch_array($fetchR);
		
		if ($current_margin < $fetchW[0])
			$out .= currentMarginDiv($current_margin,'#bb0000');
		else
			$out .= currentMarginDiv($current_margin,'#00bb00');
		break;
	case 'autoprice':
		$id = $_GET['id'];
		
		// get info
		$fetchQ = "select price,current_margin,margin,servings,upc from recipes where id=$id";
		$fetchR = $sql->query($fetchQ);
		$fetchW = $sql->fetch_array($fetchR);
		
		// calculate a price to meet desired margin
		$recipe_cost = $fetchW['price'] - $fetchW['current_margin'];
		$newprice = $recipe_cost + $fetchW['margin'];
		$newprice = (string)$newprice;
		$len = strlen($newprice);
		$newprice[$len-1] = '9';
		
		// update the price
		$upQ = "update recipes set price=$newprice where id=$id";
		$upR = $sql->query($upQ);
		setPrice($fetchW['upc'],$newprice);
		
		// re-do current margin
		$newcurrentmargin = margin($id);
		$upQ = "update recipes set current_margin=$newcurrentmargin where id=$id";
		$upR = $sql->query($upQ);
		
		// new price field
		$out .= "<b>Price</b>: $newprice [ ";
		$out .= "<a href=\"\" onclick=\"editPrice(); return false;\">";
		$out .= "<img src='images/b_edit.png'></a> ]";
		$out .= "<input type=hidden id=hrecipeprice value=\"$newprice\" />";

		// extra separator
		$out .= "`";

		// new current margin field
		if ($newcurrentmargin < $fetchW['margin'])
			$out .= currentMarginDiv($newcurrentmargin,'#bb0000');
		else
			$out .= currentMarginDiv($newcurrentmargin,'#00bb00');
		
		break;
	case 'restatus':
		$id = $_GET['id'];
		$out .= getStatus($id);
		break;
	case 'getcats':
		$current = $_GET['current'];
		$q = "select name from categories order by name";
		$r = $sql->query($q);

		$out .= "<select id=categoryselect>";
		while ($w = $sql->fetch_array($r)){
			if ($w[0] == $current)
				$out .= "<option selected>$w[0]</option>";
			else
				$out .= "<option>$w[0]</option>";
		}
		$out .= "</select>";
		$out .= "<input type=submit value=Save onclick=\"updateCategory(); return false;\" />";
		break;
	case 'changeCat':
		$id = $_GET['id'];
		$cat = $_GET['cat'];
	
		$q = "select id from categories where name='$cat'";
		$catID = array_pop($sql->fetch_array($sql->query($q)));

		$q = "update recipes set categoryID=$catID where id=$id";
		$r = $sql->query($q);
		break;
	case 'copyform':
		$catID = $_GET['id'];
		
		$q = "select name,id from recipes where categoryID=$catID order by name";
		$r = $sql->query($q);

		$out .= "<form onSubmit=\"addCopy(); return false;\">";
		$out .= "<table><tr>";
		$out .= "<td>Name</td><td><input type=text id=name /></td></tr>";
		$out .= "<tr><td>Recipe to copy</td><td><select id=tocopy>";
		while ($w = $sql->fetch_array($r))
			$out .= "<option value=$w[1]>$w[0]</option>";
		$out .= "</select></td></tr>";
		$out .= "<tr><td><input type=submit value=Copy /></td></tr></table>";
		$out .= "</form>";
		break;
	case 'copyRecipe':
		$id = $_GET['id'];
		$name = $_GET['name'];
		$newid = copyRecipe($id,$name);
		$out .= displayRecipe($newid);
		break;
	case 'deleteRecipe':
		$id = $_GET['id'];

		$delQ1 = "delete from recipes where id=$id";
		$delR1 = $sql->query($delQ1);
		$delQ2 = "delete from ingredientList where recipeID=$id";
		$delR2 = $sql->query($delQ2);
		$delQ3 = "delete from steps where recipeID=$id";
		$delR3 = $sql->query($delQ3);
		$delQ4 = "delete from info where recipeID=$id";
		$delR4 = $sql->query($delQ4);

		$out .= "The recipe has been deleted";
		break;
	}

	
	// send output and return (so ajax doesn't get all the html')
	print $out;
	return;
}

// returns a list of categories as a string
function getCategories(){
	global $sql;
	$ret = "<b>Categories</b>: ( <a href=\"\" onClick=\"newCategory(); return false;\">New</a> )";
	$ret .= "<ul>";
	$q = "select name,id from categories order by name";
	$r = $sql->query($q);
	if ($sql->num_rows($r) != 0){
		while ($w = $sql->fetch_array($r)){
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
	$q = "select name from categories where id=$id";
	$r = $sql->query($q);
	$w = $sql->fetch_array($r);
	$catName = $w[0];	

	$ret = "<b>Category</b>: ".$catName."<br />";
	$ret .= "<b>Recipes</b>: ( <a href=\"\" onClick=\"newRecipe(".$id."); return false;\">New</a> ";
	$ret .= ":: <a href=\"\" onClick=\"copyRecipe(".$id."); return false;\">Copy</a>";
	$ret .= ")";
	$ret .= "<ul>";
	$q = "select name,id from recipes where categoryID = $id order by name";
	$r = $sql->query($q);
	if ($sql->num_rows($r) != 0){
		while ($w = $sql->fetch_array($r)){
			$ret .= "<li><a href=\"\" onClick=\"displayRecipe(".$w[1]."); return false;\">";
			$ret .= $w[0]."</a></li>";
		}
	}
	$ret .= "</ul>";
	
	return $ret;
}

// gives an html <select> of all available measures
function getUnitsSelector(){
	global $sql;
	$ret = "<select name=units id=units>";
	$q = "select distinct output from convertor";
	$r = $sql->query($q);
	while ($w = $sql->fetch_array($r)){
		$ret .= "<option>".$w[0]."</option>";
	}
	$ret .= "</select>";
	
	return $ret;
}

// displays a recipe.  this may become 'ze doozy'
function displayRecipe($id){
	global $sql;
	$ret = "";
	$q = "select r.name,r.upc,r.margin,r.price,r.servings,r.shelflife,r.current_margin,i.info,
		  c.name as category
		  from recipes as r, info as i, categories as c
		   where r.id=i.recipeID and c.id=r.categoryID and r.id=$id";
	$r = $sql->query($q);
	$w = $sql->fetch_array($r);
	
	$name = $w['name'];
	$upc = $w['upc'];
	$margin = $w['margin'];
	$price = $w['price'];
	$servings = $w['servings'];
	$shelflife = $w['shelflife'];
	$current_margin = $w['current_margin'];
	$info = $w['info'];
	$category = $w['category'];
	
	$ret .= "<input type=hidden id=recipeID value=\"$id\" />";
	
	$ret .= "<b>Name</b>: <span id=recipename> $name ";
	$ret .= "<a href=\"\" onClick=\"editRecipeField('name'); return false;\">";
	$ret .= "<img src='images/b_edit.png'></a> ";
	$ret .= "<input type=hidden id=hrecipename value=\"$name\" /></span>";
	$ret .= "<a href=\"\" onClick=\"deleteRecipe('$name'); return false;\">";
	$ret .= "<img src='images/b_drop.png'></a><br />";
	
	$ret .= "<b>Category</b>: <span id=recipecategory> $category ";
	$ret .= "<a href=\"\" onClick=\"changeCategory('$category'); return false;\">";
	$ret .= "<img src='images/b_edit.png'></a> ";
	$ret .= "</span><br />";

	$ret .= "\n<b>UPC</b>: <span id=recipeupc> $upc ";
	$ret .= "<a href=\"\" onClick=\"editRecipeField('upc'); return false;\">";
	$ret .= "<img src='images/b_edit.png'></a> ";
	$ret .= "<input type=hidden id=hrecipeupc value=\"$upc\" /></span><br />";
	
	$ret .= "<div id=recipeprice><b>Price</b>: $price ";
	$ret .= "<a href=\"\" onclick=\"editPrice(); return false;\">";
	$ret .= "<img src='images/b_edit.png'></a> ";
	$ret .= "<input type=hidden id=hrecipeprice value=\"$price\" /></div>";
	
	$ret .= "\n<b>Servings</b>: <span id=recipeservings> $servings ";
	$ret .= "<a href=\"\" onClick=\"editRecipeField('servings'); return false;\">";
	$ret .= "<img src='images/b_edit.png'></a> ";
	$ret .= "<input type=hidden id=hrecipeservings value=\"$servings\" /></span><br />";
	
	$ret .= "\n<b>Shelf Life</b>: <span id=recipeshelflife> $shelflife ";
	$ret .= "<a href=\"\" onClick=\"editRecipeField('shelflife'); return false;\">";
	$ret .= "<img src='images/b_edit.png'></a> ";
	$ret .= "<input type=hidden id=hrecipeshelflife value=\"$shelflife\" /></span><br />";
	
	$ret .= "\n<b>Desired Margin</b>: <span id=recipemargin> $margin  ";
	$ret .= "<a href=\"\" onClick=\"editRecipeField('margin'); return false;\">";
	$ret .= "<img src='images/b_edit.png'></a> ";
	$ret .= "<input type=hidden id=hrecipemargin value=\"$margin\" /></span><br />";
	
	$ret .= "<div id=recipecurrentmargin>";
	if ($current_margin < $margin)
		$ret .= currentMarginDiv($current_margin,'#bb0000');
	else
		$ret .= currentMarginDiv($current_margin,'#00bb00');
	$ret .= "</div>";
	
	$ret .= "<br /><b>Ingredients</b>";
	$ret .= "<div id=recipeingredients>";
	$ret .= getIngredients($id);
	$ret .= "</div>";
	$ret .= "( <a href=\"\" onClick=\"addIngredient(); return false;\">New ingredient</a> ) ";
	$ret .= "( <a href=\"\" onclick=\"addLabel(); return false;\">New label</a> )";
	
	$ret .= "<br /><br /><b>Steps</b>:";
	$ret .= "<div id=recipesteps>";
	$ret .= getSteps($id);
	$ret .= "</div>";
	$ret .= "( <a href=\"\" onClick=\"addStep(); return false;\">New step</a> )";
	
	$ret .= "<br /><br /><b>Info</b>:";
	$ret .= "<div id=recipeinfo>";
	$ret .= "<div id=infovalue>".$info."</div>";
	$ret .= "<br />( <a href=\"\" onClick=\"editInfo($id); return false;\"><img src='images/b_edit.png'></a> )";
	$ret .= "</div>";
	$ret .= "<br /><b>Status</b>: ";
	$ret .= "<span id=recipestatus>";
	$ret .= getStatus($id);
	$ret .= "</span> ";
	$ret .= "[ <a href=\"\" onclick=\"restatus($id); return false;\">Refresh</a> ]";
	
	return $ret;
}

// prints the list of steps
function getSteps($id){
	global $sql;
	$ret = "<table>";
	$q = "select step from steps where recipeID = $id order by ord";
	$r = $sql->query($q);
	$i = 1;
	while ($w = $sql->fetch_array($r)){
		if ($i % 2 != 0)
			$ret .= "<tr style=\"background: #cccccc;\">";
		else
			$ret .= "<tr style=\"background: #aaaaaa;\">";
		$ret .= "<td>$i.</td>";
		$ret .= "<td id=steplist$i style=\"width: 20em;\">";
		$ret .= $w[0]."</td>";
		$ret .= "<td><a href=\"\" onClick=\"editStep($i); return false;\"><img src='images/b_edit.png'></a></td>";
		$ret .= "<td><a href=\"\" onClick=\"deleteStep($i); return false; \"><img src='images/b_drop.png'></a></td>";
		$ret .= "<td><a href=\"\" onClick=\"moveUp('steps',$i); return false;\"><img src='images/b_up.png'></a></td>";
		$ret .= "<td><a href=\"\" onClick=\"moveDown('steps',$i); return false;\"><img src='images/b_down.png'></a></td>";
		$ret .= "</td>";
		$i++;
	}
	$i--;
	$ret .= "</table>";
	$ret .= "<input type=hidden id=stepscount value=$i />";
	
	return $ret;
}

// get the list of ingredients as a string
function getIngredients($id){
	global $sql;
	$ret = "<table>";
	$q = "select i.name,l.measure,l.unit,l.prep from ingredientlist as l
		  left join ingredients as i on i.id = l.ingredientID
		  where recipeID = $id order by ord";
	$r = $sql->query($q);
	$i = 1;
	$numbering = 1;
	while ($w = $sql->fetch_array($r)){
		if ($i % 2 != 0)
			$ret .= "<tr style=\"background: #cccccc;\">";
		else
			$ret .= "<tr style=\"background: #aaaaaa;\">";
		$ret .= "<input type=hidden id=ingredientname$i value=\"$w[0]\" />";
		if ($w[0] == 'LABEL'){
			$numbering = 0;
			$ret .= "<td>&nbsp;</td>";
			$ret .= "<td id=ingredientlist$i><b><span id=ingredientprep$i>$w[3]</span></b></td>";
		}
		else {
			$ret .= "<td>$numbering.</td>";
			$ret .= "<td id=ingredientlist$i style=\"width: 20em;\">";
			$ret .= "<span id=ingredientname$i>$w[0]</span> - ";
			$ret .= "<span id=ingredientmeasure$i>$w[1]</span> ";
			$ret .= "<span id=ingredientunits$i>$w[2]</span> - ";
			$ret .= "<span id=ingredientprep$i>$w[3]</span>";
			$ret .= "</td>";
		}
		$ret .= "<td><a href=\"\" onClick=\"editIngredient($i); return false;\"><img src='images/b_edit.png'></a></td>";
		$ret .= "<td><a href=\"\" onClick=\"deleteIngredient($i); return false; \"><img src='images/b_drop.png'></a></td>";
		$ret .= "<td><a href=\"\" onClick=\"moveUp('ingredientlist',$i); return false;\"><img src='images/b_up.png'></a></td>";
		$ret .= "<td><a href=\"\" onClick=\"moveDown('ingredientlist',$i); return false;\"><img src='images/b_down.png'></a></td>";
		$ret .= "</td>";
		$i++;
		$numbering++;
	}
	$i--;
	$ret .= "</table>";
	$ret .= "<input type=hidden id=ingredientlistcount value=$i />";
	
	return $ret;
}

// fixes the 'ord' column values in the given $table after
// the row with ord $ord was deleted.
// uni-directional; not peak efficient.
function fixOrder($table,$ord){
	global $sql;
	$cur = $ord;
	$next = $ord+1;
	
	$maxQ = "select max(ord) from $table";
	$maxR = $sql->query($maxQ);
	$maxW = $sql->fetch_array($maxR);
	$max = $maxW[0];
	
	while ($next <= $max){
		$upQ = "update $table set ord=$cur where ord=$next";
		$upR = $sql->query($upQ);
		$cur++;
		$next++;
	}
}

// get a select box of available ingredients
function ingredientSelect(){
	global $sql;
	$ret = "";
	$fetchQ = "select name,id from ingredients where id > 0 order by name";
	$fetchR = $sql->query($fetchQ);
	while ($row = $sql->fetch_array($fetchR))
		$ret .= $row[1].":".$row[0]."|";
	return substr($ret,0,strlen($ret)-1);
}

// calculate the current margin
function margin($id){
	global $sql;
	// get all measurement and cost information
	$fetchQ = "select l.measure,l.unit,i.cost,i.volume,i.volumeunit,
			   i.size,i.sizeunit
			   from ingredientlist as l left join ingredients as i
			   on l.ingredientID = i.id where l.recipeID = $id and i.id > 0";
	$fetchR = $sql->query($fetchQ);
	
	// accumulate cost
	$cost = 0.0;
	while ($row = $sql->fetch_array($fetchR)){
		$insize = $row['measure'];
		$inunit = $row['unit'];
		$outsize = $row['volume'];
		$outunit = $row['volumeunit'];
		$outsize2 = $row['size'];
		$outunit2 = $row['sizeunit'];
		$item_cost = $row['cost'];
		
		// if the unit matches the volume size, do nothing
		// if it matches the weight size, switch outsize
		// to from volume to weight for later calculation
		if ($inunit == $outunit2)
			$outsize = $outsize2;
		elseif ($inunit != $outunit){
			// try as a volume conversion first
			$q = "select multiplier from convertor where input = '$outunit'
				  and output='$inunit'";
			$r = $sql->query($q);
			$w = $sql->fetch_array($r);
			// if that fails, try a weight conversion
			// (one of these 2 is assumed to work)
			if ($w[0] == ''){
				$q = "select multiplier from convertor where input = '$outunit2'
					  and output = '$inunit'";
				$r = $sql->query($q);
				$w = $sql->fetch_array($r);
				//echo "IN: $outunit2 OUT: $inunit MULT: $w[0]<br />";
				$outsize = $outsize2 * $w[0];
				$item_cost *= $w[0];
			}
			else {
				//echo "IN: $outunit OUT: $inunit MULT: $w[0]<br />";
				$outsize *= $w[0];
				$item_cost *= $w[0];
			}
		}
		$cost += ($insize / $outsize) * $item_cost;
	}
	
	$priceQ = "select price,servings from recipes where id=$id";
	$priceR = $sql->query($priceQ);
	$priceW = $sql->fetch_array($priceR);
	
	$current_margin = $priceW[0] - ($cost / $priceW[1]);
	$current_margin = round($current_margin,2);
	
	return $current_margin;
}

// create the current margin div with $color text color
function currentMarginDiv($current_margin,$color){
	$ret = "<b>Current Margin</b>: ";
	$ret .= "<span style=\"{color: $color;}\">$current_margin</span>";
	$ret .= " [ <a href=\"\" onclick=\"remargin(); return false;\"> Refresh</a> ]";
	$ret .= " [ <a href=\"\" onclick=\"autoprice(); return false;\"> Autoprice</a> ]";
	
	return $ret;
}

// determine allergen status
function getStatus($id){
	global $sql;
	$q = "select name from ingredientclasses where id in (
		  	select s.classID from ingredientlist as l 
		  	left join ingredientstatus as s 
		  	on s.ingredientID = l.ingredientID
		  	where l.recipeID = $id
		  	group by s.classID having count(*) >= (
		  		select count(*) from ingredientlist
		  		where recipeID = $id
		  	)
		  ) order by name";
	$r = $sql->query($q);
	$ret = '';
	while ($w = $sql->fetch_array($r)){
		$ret .= $w[0].", ";
	}
	
	return substr($ret,0,strlen($ret)-2);
}

function copyRecipe($id,$name){
	global $sql;
	$idQ = "select max(id) from recipes";
	$idR = $sql->query($idQ);
	$idRow = $sql->fetch_array($idR);
	$newid = $idRow[0]+1;

	$recipesQ = "insert into recipes
			select $newid,'$name',upc,price,margin,categoryID,servings,current_margin,shelflife
			from recipes where id=$id";
	$recipesR = $sql->query($recipesQ);

	$ingredientsQ = "insert into ingredientlist
			select $newid,measure,unit,ingredientID,ord,prep
			from ingredientlist where recipeID=$id";	
	$ingredientsR = $sql->query($ingredientsQ);

	$stepsQ = "insert into steps
			select $newid,ord,step from steps where recipeID=$id";
	$stepsR = $sql->query($stepsQ);

	$infoQ = "insert into info
			select $newid,info from info where recipeID=$id";
	$infoR = $sql->query($infoQ);

	return $newid;
}

?>

<html>
<head><title>Recipizer::Index</title>
<script type="text/javascript" src="index.js"></script>
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
<!-- hidden div to store ingredient select list -->
<div id="ingredientSelect">
<?php echo ingredientSelect() ?>
</div>
</body>
</html>
