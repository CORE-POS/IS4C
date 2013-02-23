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
        //alert(response);
        var array = response.split('`');
        switch(array[0]){
	case 'redisplay':
		document.getElementById('contents').innerHTML = array[1];
		break;
	default:
		alert(response);
	}
    }
}

function newType(mytype){
	switch(mytype){
	case 'welcome':
		showWelcome();
		break;
	case 'due':
		showDue();
		break;
	case 'pastdue':
		showPastDue();
		break;
	case 'ar':
		showAR();
		break;
	case 'upgrade':
		showUpgrade();
		break;
	case 'term':
		showTerm();
		break;
	case 'paidinfull':
		showPaidInFull();
		break;
	}
}

function selectall(elem){
	var e = document.getElementById(elem);
	for (var i=0; i<e.options.length; i++){
		e.options[i].selected = true;
	}
}

function showWelcome(){
	var b = "<b>Show</b>: ";
	b += "<input type=radio name=buttons onchange=\"redisplay('welcome','0month');\" checked /> This month";
	b += "<input type=radio name=buttons onchange=\"redisplay('welcome','1month');\" /> Last month";
	b += "<input type=radio name=buttons onchange=\"redisplay('welcome','2month');\" /> Two months ago";
	b += "<input type=radio name=buttons onchange=\"redisplay('welcome','all');\" /> All members";
	document.getElementById('buttons').innerHTML = b;

	redisplay('welcome','0month');
}

function showUpgrade(){
	var b = "<b>Show</b>: ";
	b += "<input type=radio name=buttons onchange=\"redisplay('upgrade','0month');\" checked /> This month";
	b += "<input type=radio name=buttons onchange=\"redisplay('upgrade','1month');\" /> Last month";
	b += "<input type=radio name=buttons onchange=\"redisplay('upgrade','2month');\" /> Two months ago";
	b += "<input type=radio name=buttons onchange=\"redisplay('upgrade','all');\" /> All members";
	document.getElementById('buttons').innerHTML = b;

	redisplay('upgrade','0month');
}

function showPaidInFull(){
	var b = "<b>Show</b>: ";
	b += "<input type=radio name=buttons onchange=\"redisplay('paidinfull','0month');\" checked /> This month";
	b += "<input type=radio name=buttons onchange=\"redisplay('paidinfull','1month');\" /> Last month";
	b += "<input type=radio name=buttons onchange=\"redisplay('paidinfull','2month');\" /> Two months ago";
	b += "<input type=radio name=buttons onchange=\"redisplay('paidinfull','all');\" /> All members";
	document.getElementById('buttons').innerHTML = b;

	redisplay('paidinfull','0month');
}

function showTerm(){
	var b = "<b>Show</b>: ";
	b += "<input type=radio name=buttons onchange=\"redisplay('upgrade','0month');\" checked /> This month";
	b += "<input type=radio name=buttons onchange=\"redisplay('upgrade','1month');\" /> Last month";
	b += "<input type=radio name=buttons onchange=\"redisplay('upgrade','2month');\" /> Two months ago";
	b += "<input type=radio name=buttons onchange=\"redisplay('upgrade','all');\" /> All members";
	document.getElementById('buttons').innerHTML = '';

	redisplay('term','');
}

function showDue(){
	var b = "<b>Show</b>: ";
	b += "<input type=radio name=buttons onchange=\"redisplay('due','0month');\" checked /> This month";
	b += "<input type=radio name=buttons onchange=\"redisplay('due','1month');\" /> 30 days";
	b += "<input type=radio name=buttons onchange=\"redisplay('due','2month');\" /> 60 days";
	b += "<input type=radio name=buttons onchange=\"redisplay('due','all');\" /> All members";
	document.getElementById('buttons').innerHTML = b;

	redisplay('due','0month');
}

function showPastDue(){
	var b = "<b>Show</b>: ";
	b += "<input type=radio name=buttons onchange=\"redisplay('pastdue','0month');\" checked /> This month";
	b += "<input type=radio name=buttons onchange=\"redisplay('pastdue','1month');\" /> Last month";
	b += "<input type=radio name=buttons onchange=\"redisplay('pastdue','2month');\" /> Two months ago";
	b += "<input type=radio name=buttons onchange=\"redisplay('pastdue','all');\" /> All members";
	document.getElementById('buttons').innerHTML = b;

	redisplay('pastdue','0month');
}

function showAR(){
	var b = "<b>Show</b>: ";
	b += "<input type=radio name=buttons onchange=\"redisplay('ar','reg');\" checked /> Regular";
	b += "<input type=radio name=buttons onchange=\"redisplay('ar','business');\" /> Business (EOM)";
	b += "<input type=radio name=buttons onchange=\"redisplay('ar','allbusiness');\" /> Business (Any balance)";
	document.getElementById('buttons').innerHTML = b;

	redisplay('ar','reg');
}

var e1;
var e2;

function redisplay(type,subtype){
	e1 = type;
	e2 = subtype;
	document.getElementById('contents').innerHTML = "";
	phpSend('redisplay&type='+type+'&subtype='+subtype);
}

function doExcel(){
	top.location='index.php?excel=yes&type='+e1+'&subtype='+e2;
}
