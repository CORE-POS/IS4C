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
 *    tack on more arguments as needed with '&' and '='
 *    */
function phpSend(action) {
    http.open('get', 'ajax.php?action='+action);
    http.onreadystatechange = handleResponse;
    http.send(null);
}

/* ajax callback function 
 *    by convention, results return [actionname]`[data]
 *       splitting on backtick separates, then switch on action name
 *          allows different actions to be handled differently
 *          */
function handleResponse() {
    if(http.readyState == 4){
        var response = http.responseText;
        var array = response.split('`');
        switch(array[0]){
	case 'monthview_save':
		break;
	case 'createCalendar':
		var cur = document.getElementById('yours').innerHTML;
		cur += array[1];
		document.getElementById('yours').innerHTML = cur;
		break;
	case 'savePrefs':
		alert('Settings have been saved');
		break;
	default:
		alert(response);
	}
    }
}
