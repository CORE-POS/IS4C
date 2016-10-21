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
    http.open('get', 'price_compare.php?action='+action);
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
		case 'savePrice':
		case 'saveUnfiPrice':
		case 'toggleVariable':
			break;
		default:
			alert(response);
		}
	}
}

function editPrice(upc){
	var price = document.getElementById('pricefield'+upc).innerHTML.replace(/.+?>(.+)<\/a>/i,"$1")

	var content = "<input type=text size=4 id=priceedit"+upc+" value="+price+" />";
	content += "<input type=submit value=Save onclick=\"savePrice('"+upc+"'); return false;\" />";

	document.getElementById('pricefield'+upc).innerHTML = content;
}

function savePrice(upc){
	var newprice = document.getElementById('priceedit'+upc).value;

	var content = "<a href=\"\" onclick=\"editPrice('"+upc+"'); return false;\">";
	content += newprice+"</a>";

	document.getElementById('pricefield'+upc).innerHTML = content;

	phpSend('savePrice&upc='+upc+'&price='+newprice);
}

function editUnfiPrice(upc){
	var price = document.getElementById('unfiprice'+upc).innerHTML.replace(/.+?>(.+)<\/a>/i,"$1")

	var content = "<input type=text size=4 id=unfipriceedit"+upc+" value="+price+" />";
	content += "<input type=submit value=Save onclick=\"saveUnfiPrice('"+upc+"'); return false;\" />";

	document.getElementById('unfiprice'+upc).innerHTML = content;
}

function savePrice(upc){
	var newprice = document.getElementById('priceedit'+upc).value;

	var content = "<a href=\"\" onclick=\"editPrice('"+upc+"'); return false;\">";
	content += newprice+"</a>";

	document.getElementById('pricefield'+upc).innerHTML = content;

	phpSend('savePrice&upc='+upc+'&price='+newprice);
}

function saveUnfiPrice(upc){
	var newprice = document.getElementById('unfipriceedit'+upc).value;

	var content = "<a href=\"\" onclick=\"editUnfiPrice('"+upc+"'); return false;\">";
	content += newprice+"</a>";

	document.getElementById('unfiprice'+upc).innerHTML = content;

	phpSend('saveUnfiPrice&upc='+upc+'&price='+newprice);
}

function toggleVariable(upc){
	var toggle = document.getElementById('var'+upc).checked;
	phpSend('toggleVariable&upc='+upc+'&toggle='+toggle);
}
