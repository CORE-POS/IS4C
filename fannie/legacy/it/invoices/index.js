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
var busy = false;

/* send action to this page 
   tack on more arguments as needed with '&' and '='
*/
function phpSend(action) {
    if (!busy){
	http.open('get', 'index.php?action='+action);
	http.onreadystatechange = handleResponse;
	busy = true;
	http.send(null);
    }
    else
	setTimeout("phpSend('"+action+"')",50);
}

/* ajax callback function 
   by convention, results return [actionname]`[data]
   splitting on backtick separates, then switch on action name
   allows different actions to be handled differently
*/
function handleResponse() {
    if(http.readyState == 4){
	busy = false;
        var response = http.responseText;
        var array = response.split('`');
	switch(array[0]){
	case 'pickDate':
	case 'upcSearch':
		document.getElementById('invoicenumber').innerHTML = array[1];
		break;
	case 'pickNum':
		document.getElementById('invoice').innerHTML = array[1];
		break;
	default:
		alert(response);
	}
    }
}

function pickDate(){
	var date = document.getElementById('date_select').value;

	if (date != "")
		phpSend("pickDate&date="+date);
}	

function pickNum(){
	var date = document.getElementById('current_date').value;
	var num = document.getElementById('select_num').value;
	
	if (num != "")
		phpSend("pickNum&date="+date+"&num="+num);
}

function upcSearch(){
	var upc = document.getElementById('search_upc').value;

	if (upc != "")
		phpSend("upcSearch&upc="+upc);
}

function pickInv(){
	var invoice = document.getElementById('select_inv').value;

	if (invoice != ""){
		var temp = invoice.split(' ');
		phpSend('pickNum&date='+temp[0]+'&num='+temp[1]);	
	}
}
