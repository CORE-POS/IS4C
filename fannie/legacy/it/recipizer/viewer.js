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
    http.open('get', 'viewer.php?action='+action);
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
        case 'viewRecipes':
        case 'displayRecipe':
        		document.getElementById('right').innerHTML = array[1];
        		break;
        	case 'multiply':
        		document.getElementById('recipeingredients').innerHTML = array[1];
        		break;
        }
	}
}

/* wrapper for sending view request */
function viewRecipes(id){
	phpSend('viewRecipes&catID='+id);
}

/* wrapper for displaying recipe */
function displayRecipe(id){
	phpSend('displayRecipe&id='+id);
}

function mult(id){
	var multiplier = document.getElementById('multiplier').value;
	phpSend('multiply&id='+id+'&multiplier='+multiplier);
}