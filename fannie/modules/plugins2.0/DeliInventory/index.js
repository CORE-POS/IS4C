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
    if (busy)
	setTimeout("phpSend('"+action+"')",10);
    else {
	http.open('get', 'DeliInventoryPage.php?action='+action);
	http.onreadystatechange = handleResponse;
	http.send(null);
	busy = true;
    }
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
        //alert(response);
        var array = response.split('`');
        switch(array[0]){
		case 'additem':
			document.getElementById('tablearea').innerHTML = array[1];
			break;
		case 'saveCategory':
		case 'clearAll':
		case 'refresh':
			document.getElementById('tablearea').innerHTML = array[1];
			break;
		case 'catList':
			document.getElementById('changecat'+array[1]).innerHTML = array[2];
			break;
		case 'saveitem':
			saveCount--;
			if (saveCount <= 0)
				phpSend('refresh');
			break;
		default:
			alert(response);
			alert("There was an error processing your request");
        }
    }
}

/********************* END AJAX BASICS ******************/

function additem(category){
	var item = escape(document.getElementById('newitem'+category).value);
	var orderno = document.getElementById('neworderno'+category).value;
	var units = document.getElementById('newunits'+category).value;
	var price = document.getElementById('newprice'+category).value;
	var size = escape(document.getElementById('newsize'+category).value);
	var cases = document.getElementById('newcases'+category).value;
	var fraction = document.getElementById('newfraction'+category).value;
	
	units = units.replace(/\#/g,'%23');
	fraction = fraction.replace(/\#/g,'%23');

	var sendcat = category+"";
	if (category == "__new__")
		sendcat = document.getElementById('category__new__').value;
	
	phpSend('additem&item='+item+'&orderno='+orderno+'&units='+units+'&cases='+'&price='+price+'&size='+size+'&cases='+cases+'&fraction='+fraction+'&category='+sendcat);
	
	document.getElementById('newform'+category).reset();
	document.getElementById('newitem'+category).focus();
}

function edititem(id){
	if (in_queue(id)) return;
	else queue_add(id);

	var item = document.getElementById('item'+id+'col0').innerHTML;
	var size = document.getElementById('item'+id+'col1').innerHTML;
	var orderno = document.getElementById('item'+id+'col2').innerHTML;
	var units = document.getElementById('item'+id+'col3').innerHTML;
	var cases = document.getElementById('item'+id+'col4').innerHTML;
	var fraction = document.getElementById('item'+id+'col5').innerHTML;
	var price = document.getElementById('item'+id+'col7').innerHTML;
	
	document.getElementById('item'+id+'col0').innerHTML = "<input type=text id=itemname"+id+" value=\""+item+"\" maxlength=50 />";
	document.getElementById('item'+id+'col1').innerHTML = "<input type=text id=size"+id+" value=\""+size+"\" size=8 maxlength=20 />";
	document.getElementById('item'+id+'col2').innerHTML = "<input type=text id=orderno"+id+" value=\""+orderno+"\" size=6 maxlength=15 />";
	document.getElementById('item'+id+'col3').innerHTML = "<input type=text id=units"+id+" value=\""+units+"\" size=7 maxlength=10 />";
	document.getElementById('item'+id+'col4').innerHTML = "<input type=text id=cases"+id+" value=\""+cases+"\" size=4 />";
	document.getElementById('item'+id+'col5').innerHTML = "<input type=text id=fraction"+id+" value=\""+fraction+"\" size=4 maxlength=10 />";
	document.getElementById('item'+id+'col7').innerHTML = "<input type=text id=price"+id+" value=\""+price+"\" size=7 />";
	
	document.getElementById('edit'+id).innerHTML = "<a href=\"\" onclick=\"saveAll(); return false;\">Save</a>";

    $('#itemRow'+id+' input:text').keyup(key_callback);
}

function key_callback(e){
	var keycode = e.keyCode;
	if (e.keyCode == 13) saveAll();
}

var saveCount = 0;
function saveAll(){
	saveCount = 0;
	while (editQueue.length > 0){
		saveitem(editQueue.pop());
		saveCount++;
	}
}

function saveitem(id){
	var item = escape(document.getElementById('itemname'+id).value);
	var orderno = document.getElementById('orderno'+id).value;
	var units = document.getElementById('units'+id).value;
	var cases = document.getElementById('cases'+id).value;
	var fraction = document.getElementById('fraction'+id).value;
	var price = document.getElementById('price'+id).value;
	var size = escape(document.getElementById('size'+id).value);
	
	units = units.replace(/\#/g,'%23');
	fraction = fraction.replace(/\#/g,'%23');
	item = item.replace(/\+/g,'%2B');
	
	var dstr = 'action=saveitem&id='+id+'&item='+item+'&orderno='+orderno+'&units='+units+'&cases='+cases+'&fraction='+fraction+'&price='+price+'&size='+size;
	$.ajax({
		url: 'DeliInventoryPage.php',
		data: dstr,
		type: 'get',
		dataType: 'json',
		success: function(resp){
			$('#item'+id+'col0').html(unescape(item));
			$('#item'+id+'col1').html(unescape(size));
			$('#item'+id+'col2').html(orderno);
			$('#item'+id+'col3').html(units);
			$('#item'+id+'col4').html(cases);
			$('#item'+id+'col5').html(fraction);
			$('#item'+id+'col6').html(resp.stock);
			$('#item'+id+'col7').html(price);
			$('#item'+id+'col8').html(resp.total);
			$('#edit'+id).html('<a href="" onclick="edititem('+id+'); return false;" title="Edit"><img src="'+$('#editbtn').val()+'" /></a>');
			$('#ttl'+resp.cat).html(resp.grandTotal);
		}
	});
}

function deleteitem(id){
	if (confirm("Delete this item?")){
		saveAll();

		$.ajax({
			url: 'DeliInventoryPage.php',
			data: 'action=deleteitem&id='+id,
			type: 'get',
			dataType: 'json',
			success: function(resp){
				if (resp.delete_category){
					$('#wholeCategory'+resp.delete_category).remove();
				}
				else if (resp.delete_row){
					$('#itemRow'+id).remove();	
				}
			}
		});
	}
}

function renameCategory(category){
	var curName = document.getElementById('category'+category).innerHTML;
	var clearName =  curName.replace(/_/,' ');
	var form = "<input type=text id=renameCategory"+category+" value=\""+clearName+"\" />";
	var link = " (<a href=\"\" onclick=\"saveCategory('"+category+"'); return false;\">Save</a>)";
	document.getElementById('category'+category).innerHTML = form;
	document.getElementById('renameTrigger'+category).innerHTML = link;
}

function saveCategory(category){
	var newcat = document.getElementById('renameCategory'+category).value;
	phpSend('saveCategory&oldcat='+category+'&newcat='+newcat);
}

function catList(id,category){
	phpSend('catList&id='+id+'&category='+category);
}

function saveCat(id){
	var newcat = document.getElementById('catSelect'+id).value;
	var clearName =  newcat.replace(/_/,' ');
	$.ajax({
		url: 'DeliInventoryPage.php',
		type: 'get',
		data: 'action=changeCat&id='+id+'&newcat='+newcat,
		dataType: 'json',
		success: function(resp){
			var elem = $('#itemRow'+id).detach();
			$('#catTable'+clearName+' tbody').append(elem);
			$('#changecat'+id).html('<a href="" onclick="catList('+id+',\''+clearName+'\');return false;">Category</a>');
		}
	});
}

function clearAll(){
	if (confirm("Are you sure you want to clear all totals?"))
		phpSend('clearAll');
}

// UTILITY
var editQueue = new Array();

function in_queue(val){
	for (var i = 0; i < editQueue.length; i++){
		if (editQueue[i] == val) return true;
	}	
	return false;
}

function queue_add(val){
	editQueue.push(val);
}

function queue_rm(val){
	var temp = new Array();
	for (var i = 0; i < editQueue.length; i++){
		if (editQueue[i] != val) temp.push(editQueue[i]);
	}
	editQueue = temp;
}
