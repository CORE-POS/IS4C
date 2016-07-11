/* send action to this page 
   tack on more arguments as needed with '&' and '='
*/
function phpSend(action) {
    if (busy)
	setTimeout("phpSend('"+action+"')",10);
    else {
	http.open('get', 'DeliInventoryPage.php?action='+action);
	http.onreadystatechange = handleResponse;
	http.send(null);
	busy = true;
    }
}

