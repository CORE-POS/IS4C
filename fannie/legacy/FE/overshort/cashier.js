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
    http.open('get', 'cashier.php?action='+action);
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
	case 'loadCashier':
		document.getElementById('display').innerHTML=array[1];
		document.getElementById('countSCA').focus();
		break;	
	case 'save':
		alert(array[1]);
		break;
	default:
		alert("ERROR:"+response);
	}
    }
}

function loadCashier(){
	var empno = document.getElementById('empno').value;
	var tdate = document.getElementById('date').value;

	phpSend('loadCashier&empno='+empno+'&date='+tdate);

	document.getElementById('date').value='';
	document.getElementById('empno').value='';
}

var tenders = Array('CA','CK','CC','MI','EF','EC','GD','TC','CP','IC','SC');

function resumSheet(){
	var countTotal = 0;
	var osTotal = 0;
	var posTotal = 0;
	
	for (var i = 0; i < tenders.length; i++){
		var c = 0;
		if (tenders[i] != 'CK')
			c = Number(document.getElementById('count'+tenders[i]).value);
		else
			c = Number(document.getElementById('countCK').innerHTML);

		var p = Number(document.getElementById('pos'+tenders[i]).innerHTML);
		
		var os = Math.round( (c - p)*100 ) / 100;	
		if (tenders[i] == 'CA'){
			var sca = Number(document.getElementById('countSCA').value);
			posTotal += sca;
			os = Math.round( (c - p - sca)*100 ) / 100;
		}

		osTotal += os;
		countTotal += c;
		posTotal += p;
		
		document.getElementById('os'+tenders[i]).innerHTML = os;
	}	
	
	document.getElementById('posT').innerHTML = Math.round(posTotal*100)/100;
	document.getElementById('countT').innerHTML = Math.round(countTotal*100)/100;
	document.getElementById('osT').innerHTML = Math.round(osTotal*100)/100;
}

function resumChecks(){
	var checks = document.getElementById('checklisting').value;
	var tmp = checks.split("\n");
	var sum = 0;
	for (var i = 0; i < tmp.length; i++){
		sum += Number(tmp[i]);		
	}

	document.getElementById('countCK').innerHTML = Math.round(sum*100)/100;
	resumSheet();
}

function save(){
	var tenders = saveTenders();
	var checks = saveChecks();
	var notes = escape(document.getElementById('notes').value);
	var empno = document.getElementById('current_empno').value;
	var tdate = document.getElementById('current_date').value;

	phpSend('save&empno='+empno+'&date='+tdate+'&tenders='+tenders+'&checks='+checks+'&notes='+notes);
}

function saveTenders(){
	var ret = '';
	var sca = document.getElementById('countSCA').value;
	ret += "SCA:"+sca;
	for (var i = 0; i < tenders.length; i++){
		var t = 0;
		if (tenders[i] != 'CK')
			t = document.getElementById('count'+tenders[i]).value;
		else
			t = document.getElementById('count'+tenders[i]).innerHTML;

		ret += "|"+tenders[i]+":"+t;
	}

	return ret;
}

function saveChecks(){
	var ret = '';
	var checks = document.getElementById('checklisting').value;
	var tmp = checks.split("\n")

	for (var i=0; i<tmp.length;i++){
		ret += tmp[i]+",";
	}

	ret = ret.substring(0,ret.length - 1);
	return ret;
}
