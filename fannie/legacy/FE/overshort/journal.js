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
var busy = 0;

/* send action to this page 
   tack on more arguments as needed with '&' and '='
*/
function phpSend(action) {
    if (busy == 0){
	busy = 1;
	http.open('get', 'journal.php?action='+action);
	http.onreadystatechange = handleResponse;
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
		busy = 0;
		var response = http.responseText;
		var array = response.split('`');
		switch(array[0]){
		case 'repull':
			document.getElementById('display').innerHTML += "<br />Done clearing. Reloading from POS (this'll take a minute)";
			phpSend('dateinput&startDate='+array[1]+'&endDate='+array[2]);
			break;
		case 'dateinput':
		case 'dateinput2':
			document.getElementById('display').innerHTML = array[1];
			break;
		case 'save2':
		case 'save3':
		case 'save4':
		case 'saveMisc':
			break;
		default:
			alert(response);
		}
	}
}

function loader(){
	var d1 = document.getElementById('startDate').value;
	var d2 = document.getElementById('endDate').value;
	phpSend('dateinput&startDate='+d1+'&endDate='+d2);
}

function loader2(){
	var val = document.getElementById('selectDate').value;
	phpSend('dateinput2&dateStr='+val);
}

function repull(d1,d2){
	document.getElementById('display').innerHTML = 'Clearing current data';
	phpSend('repull&startDate='+d1+'&endDate='+d2);
}

function save2(newval,key1,key2){
	var datestr = document.getElementById('datestr').value;
	phpSend('save2&datestr='+datestr+'&val='+newval+'&key1='+key1+'&key2='+key2);
}

function save3(newval,key1,key2,key3){
	var datestr = document.getElementById('datestr').value;
	phpSend('save3&datestr='+datestr+'&val='+newval+'&key1='+key1+'&key2='+key2+'&key3='+key3);
}

function save4(newval,key1,key2,key3,key4){
	var datestr = document.getElementById('datestr').value;
	phpSend('save4&datestr='+datestr+'&val='+newval+'&key1='+key1+'&key2='+key2+'&key3='+key3+'&key4='+key4);
}

function saveMisc(newval,misckey,timestamp,savetype){
	var datestr = document.getElementById('datestr').value;
	phpSend('saveMisc&datestr='+datestr+'&val='+newval+'&misc='+misckey+'&ts='+timestamp+'&type='+savetype);
}

// rebalance( timestamp )
function rb(ts){
	var os = 0;

	var debits = document.getElementsByName('debit'+ts);
	for(var i=0;i<debits.length;i++){
		os += Number(debits[i].value);
	}
	var credits = document.getElementsByName('credit'+ts);
	for(var i=0;i<credits.length;i++)
		os -= Number(credits[i].value);

	if (os < 0){
		document.getElementById('overshortCredit'+ts).innerHTML='&nbsp;';
		document.getElementById('overshortDebit'+ts).innerHTML = Math.round(-1*os*100)/100;
	}
	else {
		document.getElementById('overshortDebit'+ts).innerHTML='&nbsp;';
		document.getElementById('overshortCredit'+ts).innerHTML = Math.round(os*100)/100;
	}
}
