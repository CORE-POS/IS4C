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
    http.open('get', 'ingredients.php?action='+action);
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
        	case 'addIngredient':
        		document.getElementById('left').innerHTML = array[1];
        		break;
        	case 'viewIngredient':
        		document.getElementById('right').innerHTML = array[1];
        		break;
        	case 'editName':
        		document.getElementById('ingredientName').innerHTML = array[1];
        		break;
        	case 'saveName':
        	case 'saveSize':
        	case 'saveVolume':
        	case 'saveCost':
        	case 'flipOn':
        	case 'flipOff':
        		break;
        	case 'editSize':
        		document.getElementById('ingredientSize').innerHTML = array[1];
        		break;
        	case 'editVolume':
        		document.getElementById('ingredientVolume').innerHTML = array[1];
        		break;
        	case 'editCost':
        		document.getElementById('ingredientCost').innerHTML = array[1];
        		break;
		default:
			alert(response);
		}
	}
}

/* input form for a new ingredient */
function newIngredient(){
	var content = "<form onSubmit=\"addIngredient(); return false;\" >";
	content += "<table><tr>";
	content += "<td>Name:</td><td><input type=text id=name /></td></tr>";
	content += "<tr><td>Weight:</td><td><input type=text id=size size=5 /> ";
	content += "<select id=sizeUnit>";
	content += "<option>lbs</option><option>oz</option><option>each</option>";
	content += "</select></td></tr>";
	content += "<tr><td>Volume:</td><td><input type=text id=volume size=5 /> ";
	content += "<select id=volumeUnit>";
	content += "<option>tsp</option><option>T</option><option>fl oz</option>";
	content += "<option>cup</option><option>pint</option>";
	content += "<option>quart</option><option>gallon</option>";
	content += "<option>each</option>";
	content += "</select></td></tr>";
	content += "<tr><td>Cost:</td><td><input type=text id=cost size=5 /></td></tr>";
	content += "</table>";
	content += "<input type=submit value=Create />";
	content += "</form>"
	document.getElementById('right').innerHTML = content;
}

/* send form data to the backend for adding */
function addIngredient(){
	var name = document.getElementById('name').value;
	var size = document.getElementById('size').value;
	var sizeUnit = document.getElementById('sizeUnit').value;
	var volume = document.getElementById('volume').value;
	var volumeUnit = document.getElementById('volumeUnit').value;
	var cost = document.getElementById('cost').value;
	
	var str = 'addIngredient&name='+name+'&size='+size+'&sizeUnit='+sizeUnit;
	str += '&volume='+volume+'&volumeUnit='+volumeUnit+'&cost='+cost;
	phpSend(str);
	
	var content = 'Ingredient '+name+' added.';
	document.getElementById('right').innerHTML = content;
}

/* jscript wrapper */
function viewIngredient(id){
	phpSend('viewIngredient&id='+id);
}

function editName(id){
	phpSend('editName&id='+id);
}

function saveName(id){
	var newname = document.getElementById('name'+id).value;
	var content = "<td>Name:</td><td>"+newname+"</td>";
	content += "<td><a href=\"\" onClick=\"editName("+id+"); return false;\">Edit</a></td>";
	
	phpSend('saveName&id='+id+'&name='+newname);
	
	document.getElementById('ingredientName').innerHTML = content;
}

function editSize(id){
	phpSend('editSize&id='+id);
}

function saveSize(id){
	var newsize = document.getElementById('size'+id).value;
	var newsizeUnit = document.getElementById('sizeUnit'+id).value;
	var content = "<td>Weight:</td><td>"+newsize+" "+newsizeUnit+"</td>";
	content += "<td><a href=\"\" onclick=\"editSize("+id+"); return false;\">Edit</a></td>";
	
	phpSend('saveSize&id='+id+'&size='+newsize+'&sizeUnit='+newsizeUnit);
	
	document.getElementById('ingredientSize').innerHTML = content;
}

function editVolume(id){
	phpSend('editVolume&id='+id);
}

function saveVolume(id){
	var newvolume = document.getElementById('volume'+id).value;
	var newvolumeUnit = document.getElementById('volumeUnit'+id).value;
	var content = "<td>Volume:</td><td>"+newvolume+" "+newvolumeUnit+"</td>";
	content += "<td><a href=\"\" onclick=\"editVolume("+id+"); return false;\">Edit</a></td>";
	
	phpSend('saveVolume&id='+id+'&volume='+newvolume+'&volumeUnit='+newvolumeUnit);
	
	document.getElementById('ingredientVolume').innerHTML = content;
}

function editCost(id){
	phpSend('editCost&id='+id);
}

function saveCost(id){
	var newcost = document.getElementById('cost'+id).value;
	var content = "<td>Cost:</td><td>"+newcost+"</td>";
	content += "<td><a href=\"\" onClick=\"editCost("+id+"); return false;\">Edit</a></td>";
	
	phpSend('saveCost&id='+id+'&cost='+newcost);
	
	document.getElementById('ingredientCost').innerHTML = content;
}

function flipStatus(id,className,classID){
	var current = document.getElementById(className+classID).checked;
	if (current){
		phpSend('flipOn&id='+id+'&class='+classID);
	}
	else {
		phpSend('flipOff&id='+id+'&class='+classID);
	}
}
