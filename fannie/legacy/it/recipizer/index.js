/* ajax request */
function createRequestObject() {
    var ro;
    var browser = navigator.appName;
    if(browser == "Microsoft Internet Explorer"){
        ro = new ActiveXObject("Microsoft.XMLHTTP");
    }else{
        ro = new XMLHttpRequest();
    }
    return ro;
}

/* global request object */
var http = createRequestObject();

/* send action to this page 
   tack on more arguments as needed with '&' and '='
*/
function phpSend(action) {
    http.open('get', 'index.php?action='+action);
    http.onreadystatechange = handleResponse;
    http.send(null);
}

/* ajax callback function 
   by convention, results return [actionname]`[data]
   splitting on backtick separates, then switch on action name
   allows different actions to be handled differently
*/
function handleResponse() {
    if(http.readyState == 4){
        var response = http.responseText;
        var array = response.split('`');
        switch(array[0]){
        	case 'addCategory':
        		document.getElementById('left').innerHTML = array[1];
        		document.getElementById('right').innerHTML = "Category added";
        		break;
        	case 'viewRecipes':
        	case 'addRecipe':
		case 'copyRecipe':
        	case 'displayRecipe':
		case 'deleteRecipe':
        		document.getElementById('right').innerHTML = array[1];
        		break;
        	case 'writeField':
        	case 'writeInfo':
        	case 'savePrice':
		case 'changeCat':
        		break;
        	case 'saveNewStep':
        	case 'saveStep':
        	case 'deleteStep':
        		document.getElementById('recipesteps').innerHTML = array[1];
        		break;
        	case 'saveNewIngredient':
        	case 'saveIngredient':
        	case 'deleteIngredient':
        		document.getElementById('recipeingredients').innerHTML = array[1];
        		break;
        	case 'moveUp':
        	case 'moveDown':
        		document.getElementById(array[1]).innerHTML = array[2];
        		break;
        	case 'remargin':
        		document.getElementById('recipecurrentmargin').innerHTML = array[1];
        		break;
        	case 'autoprice':
        		document.getElementById('recipeprice').innerHTML = array[1];
        		document.getElementById('recipecurrentmargin').innerHTML = array[2];
        		break;
        	case 'restatus':
        		document.getElementById('recipestatus').innerHTML = array[1];
        		break;
		case 'getcats':
			document.getElementById('recipecategory').innerHTML = array[1];
			break;
		case 'copyform':
			document.getElementById('right').innerHTML = array[1];
			document.getElementById('name').focus();
			break;
		default:
			alert(response);
        }
	}
}

/* display a form to input a new category */
function newCategory(){
	var content = "<form onSubmit=\"addCategory(); return false\">";
	content += "New category name: <input type=text id=name /><p />";
	content += "<input type=submit value=Create />"
	content += "</form>";
	document.getElementById('right').innerHTML = content;
	document.getElementById('name').focus();
}

/* check if the category is blank, then submit the request */
function addCategory(){
	var category = document.getElementById('name').value;
	if (category == ''){
		alert('Category name cannot be blank');
	}
	else {
		phpSend('addCategory&newCategory='+category)
	}
}

/* wrapper for sending view request */
function viewRecipes(id){
	phpSend('viewRecipes&catID='+id);
}

/* global definition of units select box @ loadtime */
var unitSelect = '<?php getUnitsSelector(); ?>';

/* form for a new recipe */
function newRecipe(id){
	var content = "<form onSubmit=\"addRecipe(); return false;\">";
	content += "<table><tr>";
	content += "<td>Name</td><td><input type=text id=name /></td></tr>";
	content += "<tr><td>UPC</td><td><input type=text id=upc /></td></tr>";
	content += "<tr><td>Desired Margin</td><td><input type=text id=margin /></td></tr>";
	content += "<tr><td>Servings</td><td><input type=text id=servings /></td></tr>";
	content += "<tr><td>Shelf Life</td><td><input type=text id=shelflife /></td></tr>";
	content += "<input type=hidden id=catID value=\""+id+"\" />";
	content += "<tr><td><input type=submit value=Create /></td></tr></table>";
	content += "</form>";
	document.getElementById('right').innerHTML = content;
	document.getElementById('name').focus();
}

function copyRecipe(id){
	phpSend("copyform&id="+id);
}

/* check blank fields and add the new recipe */
function addRecipe(){
	var name = document.getElementById('name').value;
	var upc = document.getElementById('upc').value;
	var margin = document.getElementById('margin').value;
	var servings = document.getElementById('servings').value;
	var shelflife = document.getElementById('shelflife').value;
	var catID = document.getElementById('catID').value;
	if (name == '' || margin == ''){
		alert("Name and margin must not be blank");
	}
	else {
		phpSend('addRecipe&name='+name+'&upc='+upc+'&margin='+margin+'&servings='+servings+'&shelflife='+shelflife+'&catID='+catID);
	}
}

function addCopy(){
	var newname = document.getElementById('name').value;
	var id = document.getElementById('tocopy').value;
	phpSend('copyRecipe&id='+id+'&name='+newname);
}

/* wrapper for displaying recipe */
function displayRecipe(id){
	phpSend('displayRecipe&id='+id);
}

/* change one of the main recipe fields (margin,name,upc)
   to an edit dialog willed with current value */
function editRecipeField(field){
	var value = document.getElementById('hrecipe'+field).value;
	var content = "<form onSubmit=\"setRecipeField('"+field+"'); return false;\">";
	content += "<input type=text id="+field+" value=\""+value+"\" /> ";
	content += "<input type=submit value=Save />";
	content += "</form>";
	document.getElementById('recipe'+field).innerHTML = content;
	document.getElementById(field).focus();
}

/* saves on of the main recipe fields and reverts it to the
   normal display style */
function setRecipeField(field){
	var newvalue = document.getElementById(field).value;
	var content = " "+newvalue+" ";
	content += "<a href=\"\" onClick=\"editRecipeField('"+field+"'); return false;\">";
	content += "<img src=images/b_edit.png /></a>";
	content += "<input type=hidden id=hrecipe"+field+" value=\""+newvalue+"\" />";
	document.getElementById('recipe'+field).innerHTML = content;
	
	var id = document.getElementById('recipeID').value;
	phpSend('writeField&id='+id+'&field='+field+'&value='+newvalue);
}

/* append a form to the end of the step list for adding a new one */
function addStep(){
	var content = document.getElementById('recipesteps').innerHTML;
	var stepscount = document.getElementById('stepscount').value;
	stepscount++;
	content += "<form onSubmit=\"saveNewStep("+stepscount+"); return false;\">";
	content += "<input type=hidden id=ord"+stepscount+" value="+stepscount+" />";
	content += "<textarea id=step"+stepscount+"></textarea><br />";
	content += "<input type=submit value=Save />";
	content += "</form><br />";
	
	document.getElementById('stepscount').value = stepscount;
	document.getElementById('recipesteps').innerHTML = content;
	document.getElementById('step'+stepscount).focus();
}

/* save the new step */
function saveNewStep(num){
	var id = document.getElementById('recipeID').value;
	var ord = document.getElementById('ord'+num).value;
	var step = document.getElementById('step'+num).value;
	
	phpSend('saveNewStep&id='+id+'&ord='+ord+'&step='+step);
}

/* change a step in the list to an edit box */
function editStep(num){
	var value = document.getElementById('steplist'+num).innerHTML;
	var content = "<form onSubmit=\"saveStep("+num+"); return false;\">";
	content += "<input type=hidden id=ord"+num+" value="+num+" />";
	content += "<textarea id=step"+num+">"+value+"</textarea><br />";
	content += "<input type=submit value=Save />";
	content += "</form>";
	document.getElementById('steplist'+num).innerHTML = content;
	document.getElementById('step'+num).focus();
}

/* save an edited step */
function saveStep(num){
	var id = document.getElementById('recipeID').value;
	var ord = document.getElementById('ord'+num).value;
	var step = document.getElementById('step'+num).value;
	
	phpSend('saveStep&id='+id+'&ord='+ord+'&step='+step);
}

/* delete a step */
function deleteStep(num){
	if (confirm("Delete this step?")){
		var id = document.getElementById('recipeID').value;
		phpSend('deleteStep&id='+id+'&ord='+num);
	}
}

/* move an item in the list up, if possible */
function moveUp(table,num){
	if (num <= 1){
		return false;
	}
	var id = document.getElementById('recipeID').value;
	phpSend('moveUp&id='+id+'&table='+table+'&ord='+num);
	return true;
}

/* move an item in the list down, if possible */
function moveDown(table,num){
	var count = document.getElementById(table+'count').value;
	if (num == count){
		return false;
	}
	var id = document.getElementById('recipeID').value;
	phpSend('moveDown&id='+id+'&table='+table+'&ord='+num);
	return true;
}

/* toggle 'info' area to edit */
function editInfo(id){
	var value = document.getElementById('infovalue').innerHTML;
	var content = "<form onSubmit=\"writeInfo("+id+"); return false;\">";
	content += "<textarea id=editinfo>"+value+"</textarea><br />";
	content += "<input type=submit value=Save />";
	content += "</form>";
	document.getElementById('recipeinfo').innerHTML = content;
	document.getElementById('editinfo').focus();
}

/* save changes to info */
function writeInfo(id){
	var newvalue = document.getElementById('editinfo').value;
	
	var content = "<div id=infovalue>"+newvalue+"</div>";
	content += "<br />( <a href=\"\" onClick=\"editInfo("+id+"); return false;\">Edit</a> )";
	
	document.getElementById('recipeinfo').innerHTML = content;
	
	phpSend('writeInfo&id='+id+'&info='+newvalue);
}

/* display interface for adding an ingredient */
function addIngredient(){
	var content = document.getElementById('recipeingredients').innerHTML;
	var ingredientcount = document.getElementById('ingredientlistcount').value;
	ingredientcount++;
	var selects = document.getElementById('ingredientSelect').innerHTML;
	content += "<form onSubmit=\"saveNewIngredient("+ingredientcount+"); return false;\">";
	content += "<input type=hidden id=ingredientord"+ingredientcount+" value="+ingredientcount+" />";
	content += "<select id=ingredientID"+ingredientcount+">";
	var split1 = selects.split('|');
	for (var i = 0; i < split1.length; i++){
		var temp = split1[i].split(":");
		content += "<option value="+temp[0]+">"+temp[1]+"</option>";
	}
	content += "</select>";
	content += "<input id=ingredientMeasure"+ingredientcount+" size=5 />";
	content += "<select id=ingredientUnits"+ingredientcount+">";
	var unitlist = new Array('tsp','T','fl oz','cup','pint','quart','gallon','oz','lbs','each');
	for (var i = 0; i < unitlist.length; i++){
		content += "<option>"+unitlist[i]+"</option>";
	}
	content += "</select>";
	content += " Prep: <input type=text id=ingredientPrep"+ingredientcount+" size=8 />";
	content += "<input type=submit value=Save />";
	content += "</form><br />";
	
	document.getElementById('ingredientlistcount').value = ingredientcount;
	document.getElementById('recipeingredients').innerHTML = content;
	document.getElementById('ingredientMeasure'+ingredientcount).focus();
}

/* insert a new ingredient into the ingredient list */
function saveNewIngredient(num){
	var id = document.getElementById('recipeID').value;
	var ord = document.getElementById('ingredientord'+num).value;
	var ingredientID = document.getElementById('ingredientID'+num).value;
	var measure = document.getElementById('ingredientMeasure'+num).value;
	var units = document.getElementById('ingredientUnits'+num).value;
	var prep = document.getElementById('ingredientPrep'+num).value;
	
	phpSend('saveNewIngredient&id='+id+'&ord='+ord+'&ingredientID='+ingredientID+'&measure='+measure+'&units='+units+'&prep='+prep);
}

/* bring up an edit form for an ingredient */
function editIngredient(num){
	var name = document.getElementById('ingredientname'+num).value;
	var measure = 0;
	var units = '0';
	if (name != "LABEL"){
		var measure = document.getElementById('ingredientmeasure'+num).innerHTML;
		var units = document.getElementById('ingredientunits'+num).innerHTML;
	}
	var prep = document.getElementById('ingredientprep'+num).innerHTML;
	var content = "<form onSubmit=\"saveIngredient("+num+"); return false;\">";
	content += "<input type=hidden id=ingredientord"+num+" value="+num+" />";
	if (name == "LABEL"){
		content += "<input type=hidden id=ingredientID"+num+" value=0 />";
		content += "<input type=hidden id=ingredientMeasure"+num+" value=0 />";
		content += "<input type=hidden id=ingredientUnits"+num+" value=0 />";
	}
	else {
		content += "<select id=ingredientID"+num+">";
		var selects = document.getElementById('ingredientSelect').innerHTML;
		var split1 = selects.split('|');
		for (var i = 0; i < split1.length; i++){
			var temp = split1[i].split(":");
			if (temp[1] == name)
				content += "<option value="+temp[0]+" selected>"+temp[1]+"</option>";
			else
				content += "<option value="+temp[0]+">"+temp[1]+"</option>";
		}
		content += "</select>";
		content += "<input id=ingredientMeasure"+num+" size=5 value="+measure+" />";
		content += "<select id=ingredientUnits"+num+">";
		var unitlist = new Array('tsp','T','fl oz','cup','pint','quart','gallon','oz','lbs','each');
		for (var i = 0; i < unitlist.length; i++){
			if (unitlist[i] == units)
				content += "<option selected>"+unitlist[i]+"</option>";		
			else
				content += "<option>"+unitlist[i]+"</option>";
		}
		content += "</select>";
	}
	content += "<input type=text id=ingredientPrep"+num+" size=6 value=\""+prep+"\" />";
	content += "<input type=submit value=Save />";
	content += "</form>";
	document.getElementById('ingredientlist'+num).innerHTML = content;
	document.getElementById('ingredientPrep'+num).focus();
}

/* save changes to the ingredient */
function saveIngredient(num){
	var id = document.getElementById('recipeID').value;
	var ord = document.getElementById('ingredientord'+num).value;
	var ingredientID = document.getElementById('ingredientID'+num).value;
	var measure = document.getElementById('ingredientMeasure'+num).value;
	var units = document.getElementById('ingredientUnits'+num).value;
	var prep = document.getElementById('ingredientPrep'+num).value;
	
	phpSend('saveIngredient&id='+id+'&ord='+ord+'&ingredientID='+ingredientID+'&measure='+measure+'&units='+units+'&prep='+prep);
}

/* remove an ingredient from the list */
function deleteIngredient(num){
	if (confirm("Delete this ingredient from this recipe?")){
		var id = document.getElementById('recipeID').value;
		phpSend('deleteIngredient&id='+id+'&ord='+num);
	}
}

/* bring up edit interface for price */
function editPrice(){
	var id = document.getElementById('recipeID').value;
	var price = document.getElementById('hrecipeprice').value;
	var content = "<form onsubmit=\"savePrice("+id+"); return false;\">";
	content += "<b>Price</b>: ";
	content += "<input type=text id=price size=5 value=\""+price+"\" /> ";
	content += "<input type=submit value=Save />";
	document.getElementById('recipeprice').innerHTML = content;
}

/* save price changes */
function savePrice(id){
	var newprice = document.getElementById('price').value;
	var content = "<b>Price</b>: "+newprice+" [";
	content += "<a href=\"\" onclick=\"editPrice(); return false;\">Edit</a>";
	content += " ] <input type=hidden id=hrecipeprice value=\""+newprice+"\" />";
	
	phpSend('savePrice&id='+id+'&price='+newprice);
	
	document.getElementById('recipeprice').innerHTML = content;
	
}

/* re-calculate the current margin */
function remargin(){
	var id = document.getElementById('recipeID').value;
	phpSend('remargin&id='+id);
}

/* assign a price based on desired margin */
function autoprice(){
	var id = document.getElementById('recipeID').value;
	phpSend('autoprice&id='+id);
}

/*	create an input box to add a 'label' to the ingredient list
	a label is just an ingredient with id 0 and prep filled in with
	the label text (so it can be manipulated like a label within
	the list)	
*/
function addLabel(){
	var content = document.getElementById('recipeingredients').innerHTML;
	var ingredientcount = document.getElementById('ingredientlistcount').value;
	ingredientcount++;
	
	content += "<form onSubmit=\"saveNewIngredient("+ingredientcount+"); return false;\">";
	content += "<input type=hidden id=ingredientord"+ingredientcount+" value="+ingredientcount+" />";
	content += "<input type=hidden id=ingredientID"+ingredientcount+" value=0 />";
	content += "<input type=hidden id=ingredientMeasure"+ingredientcount+" value=0 />";
	content += "<input type=hidden id=ingredientUnits"+ingredientcount+" value=0 />";
	content += " Label: <input type=text id=ingredientPrep"+ingredientcount+" size=12 maxlength=50 />";
	content += "<input type=submit value=Save />";
	content += "</form><br />";
	
	document.getElementById('ingredientlistcount').value = ingredientcount;
	document.getElementById('recipeingredients').innerHTML = content;
	document.getElementById('ingredientMeasure'+ingredientcount).focus();
}

function restatus(id){
	phpSend('restatus&id='+id);
}

function changeCategory(current){
	phpSend('getcats&current='+current);
}

function updateCategory(){
	var id = document.getElementById('recipeID').value;
	var cat = document.getElementById('categoryselect').value;

	var content = cat+" <a href=\"\" onClick=\"changeCategory('"+cat+"'); return false;\">";
	content += "<img src='images/b_edit.png'></a>";

	document.getElementById('recipecategory').innerHTML = content;
	phpSend('changeCat&id='+id+'&cat='+cat);
}

function deleteRecipe(name){
	if (confirm("Are you sure you want to delete \""+name+"\"?")){
		var id = document.getElementById('recipeID').value;
		phpSend("deleteRecipe&id="+id);
	}
}
