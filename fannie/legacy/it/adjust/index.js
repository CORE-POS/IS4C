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
	case 'refund':
		alert(array[2]); // fall through intentional!
	case 'loadReceipt':
		document.getElementById('contentarea').innerHTML = array[1];
		break;
	default:
		alert(response);
	}
    }
}

function loadReceipt(){
	var date = document.getElementById('rdate').value;
	var trans_num = document.getElementById('rtrans_num').value;
	phpSend('loadReceipt&date='+date+'&trans_num='+trans_num);
}

function refund(datestamp,trans_num){
	var changeDate = document.getElementById('newdate').value;
	if (changeDate == ''){
		alert("Please enter a date to file changes on");
		return;
	}
	phpSend('refund&date='+datestamp+'&trans_num='+trans_num+'&newdate='+changeDate);
}
