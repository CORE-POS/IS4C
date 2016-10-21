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
        case 'newBatch':
        case 'deleteBatch':
        case 'deleteItem':
        case 'refilter':
        case 'redisplay':
	case 'redisplayWithOrder':
        		document.getElementById('displayarea').innerHTML = array[1];
        		break;
        case 'newTag':
        		document.getElementById('inputarea').innerHTML = array[1];
        		break;
        case 'saveBatch':
        case 'savePrice':
        case 'forceBatch':
	case 'unsale':
        		break;
        case 'showBatch':
        		document.getElementById('inputarea').innerHTML = array[1];
        		document.getElementById('displayarea').innerHTML = array[2];
        		document.getElementById('addItemUPC').focus();	
        		break;
        case 'backToList':
        		document.getElementById('inputarea').innerHTML = array[1];
        		document.getElementById('displayarea').innerHTML = array[2];
        		document.getElementById('newBatchName').focus();	
                setupDatePickers();
        		break;
        case 'addItemUPC':
        case 'addItemLC':
        		document.getElementById('inputarea').innerHTML = array[1];
                if (document.getElementById('addItemPrice')) {
                    document.getElementById('addItemPrice').focus();
                } else if (document.getElementById('addItemUPC')) {
                    document.getElementById('addItemUPC').focus();
                }
        		break;
	case 'switchToLC':
	case 'switchFromLC':
			document.getElementById('inputarea').innerHTML = array[1];
			document.getElementById('addItemUPC').focus();
			document.getElementById('addItemUPC').select();
			break;
        case 'addItemPrice':
        case 'addItemLCPrice':
        case 'addTag':
        		document.getElementById('inputarea').innerHTML = array[1];
        		document.getElementById('displayarea').innerHTML = array[2];
        		document.getElementById('addItemUPC').focus();
			document.getElementById('addItemUPC').select();
        		break;
	case 'expand':
		doExpand(array[1],array[2],array);
		break;
	default:
		alert(response);
        }
	}
}

/********************* END AJAX BASICS ******************/

var batchtypes = new Array('Co-op Deals','Cha-Ching Web','Cha-Ching NonWeb','Price Change','delete','Owner Extras','Volume','Floating MOS','% MOS');
var owners = new Array('','Cool','Deli','Meat','HBC','Bulk','Grocery','Produce','Gen Merch','IT');

function newBatch(){
	var type = document.getElementById('newBatchType').value;
	var name = document.getElementById('newBatchName').value;
	var startdate = document.getElementById('newBatchStartDate').value;
	var enddate = document.getElementById('newBatchEndDate').value;
	var owner = document.getElementById('newBatchOwner').value;
	
	phpSend('newBatch&type='+type+'&name='+name+'&startdate='+startdate+'&enddate='+enddate+'&owner='+owner);
	
	document.getElementById('newBatchType').value = 0;
	document.getElementById('newBatchName').value = '';
	document.getElementById('newBatchStartDate').value = '';
	document.getElementById('newBatchEndDate').value = '';
	
	document.getElementById('newBatchName').focus();
}

function deleteBatch(id,name){
	var audited = document.getElementById('isAudited').value;
	if (audited == "1"){
		alert("You're not allowed to delete batches");
		return;
	}

	if (confirm('Delete this batch ('+name+')?'))
		phpSend('deleteBatch&id='+id);
}

function editBatch(id){
	var name = document.getElementById('namelink'+id).innerHTML;
	var type = document.getElementById('type'+id).innerHTML;
	var startdate = document.getElementById('startdate'+id).innerHTML;
	var enddate = document.getElementById('enddate'+id).innerHTML;
	var owner = document.getElementById('owner'+id).innerHTML;
	
	var typeselect = "<select id=type"+id+"i>";
	for (var i = 1; i <= batchtypes.length; i++){
		if (i==7) i++;
		typeselect += "<option value="+i;
		if (batchtypes[i-1] == type)
			typeselect += " selected";
		typeselect += ">"+batchtypes[i-1]+"</option>";
	}
	typeselect += "</select>";
	
	var ownerselect = "<select id=owner"+id+"i>";
	for (var i = 0; i < owners.length; i++){
		ownerselect += "<option";
		if (owners[i] == owner)
			ownerselect += " selected";
		ownerselect += ">"+owners[i]+"</option>";
	}
	ownerselect += "</select>";
	
	document.getElementById('name'+id).innerHTML = "<input type=text id=name"+id+"i value=\""+name+"\" />";
	document.getElementById('type'+id).innerHTML = typeselect;
	document.getElementById('startdate'+id).innerHTML = "<input type=text id=startdate"+id+"i value=\""+startdate+"\" />";
	document.getElementById('enddate'+id).innerHTML = "<input type=text id=enddate"+id+"i value=\""+enddate+"\" />";
	document.getElementById('owner'+id).innerHTML = ownerselect;
	document.getElementById('edit'+id).innerHTML = "<a href=\"\" onclick=\"saveBatch("+id+"); return false;\">Save</a>";
	
	document.getElementById('name'+id+'i').focus();
}

function saveBatch(id){
	var name = document.getElementById('name'+id+'i').value;
	var type = document.getElementById('type'+id+'i').value;
	var startdate = document.getElementById('startdate'+id+'i').value;
	var enddate = document.getElementById('enddate'+id+'i').value;
	var owner = document.getElementById('owner'+id+'i').value;
	
	document.getElementById('name'+id).innerHTML = "<a id=namelink"+id+" href=\"\" onclick=\"showBatch("+id+"); return false;\">"+name+"</a>";
	document.getElementById('type'+id).innerHTML = batchtypes[type-1];
	document.getElementById('startdate'+id).innerHTML = startdate;
	document.getElementById('enddate'+id).innerHTML = enddate;
	document.getElementById('owner'+id).innerHTML = owner;
	document.getElementById('edit'+id).innerHTML = "<a href=\"\" onclick=\"editBatch("+id+"); return false;\">Edit</a>";
	
	phpSend('saveBatch&id='+id+'&name='+name+'&type='+type+'&startdate='+startdate+'&enddate='+enddate+'&owner='+owner);
}

function showBatch(id,tag){
	if (document.getElementById('isAudited').value == "1"){
		if (document.getElementById('type'+id).innerHTML == "Price Change"){
			alert("You can't edit price change batches");
			return;
		}
	}
	phpSend('showBatch&id='+id+'&tag='+tag);
}

function backToList(){
	phpSend('backToList');
}

function addItem(){
	var id = document.getElementById('currentBatchID').value;
	var upc = document.getElementById('addItemUPC').value;
	var tag = document.getElementById('addItemTag').checked;
	var lc = document.getElementById('addItemLikeCode').checked;

	if (!lc)
		phpSend('addItemUPC&id='+id+'&upc='+upc+'&tag='+tag);
	else
		phpSend('addItemLC&id='+id+'&lc='+upc);
}

function addItemFinish(upc){
	var id = document.getElementById('currentBatchID').value;
	var price = document.getElementById('addItemPrice').value;
	var tag = document.getElementById('addItemTag').checked;

	var uid = document.getElementById('uid').value;
	var audited = document.getElementById('isAudited').value;
	
	if (!tag || audited=="1")
		phpSend('addItemPrice&id='+id+'&upc='+upc+'&price='+price+'&uid='+uid+'&audited='+audited);
	else
		phpSend('newTag&id='+id+'&upc='+upc+'&price='+price);
}

function addItemLCFinish(lc){
	var id = document.getElementById('currentBatchID').value;
	var price = document.getElementById('addItemPrice').value;
	
	var uid = document.getElementById('uid').value;
	var audited = document.getElementById('isAudited').value;

	phpSend('addItemLCPrice&id='+id+'&lc='+lc+'&price='+price+'&uid='+uid+'&audited='+audited);
}

function deleteItem(upc){
	var id = document.getElementById('currentBatchID').value;
	var uid = document.getElementById('uid').value;
	var audited = document.getElementById('isAudited').value;
	
	phpSend('deleteItem&id='+id+'&upc='+upc+'&uid='+uid+'&audited='+audited);
}

function refilter(){
	var owner = document.getElementById('filterOwner').value;
	
	phpSend('refilter&owner='+owner);
}

function redisplay(mode){
	phpSend('redisplay&mode='+mode);
}

function editPrice(upc){
	var saleprice = document.getElementById('salePrice'+upc).innerHTML;
	var qty = "";
	if (saleprice.indexOf(" for ") != -1){
		var tmp = saleprice.split(" for ");
		qty = tmp[0];
		saleprice = tmp[1];	
	}

	var content = "(<input type=text size=3 id=saleQty"+upc+"i value=\""+qty+"\" /> for)";
	content += "<input type=text size=5 id=salePrice"+upc+"i value=\""+saleprice+"\" />";
	
	document.getElementById('salePrice'+upc).innerHTML = content;
	document.getElementById('editLink'+upc).innerHTML = "<a href=\"\" onclick=\"savePrice(\'"+upc+"\'); return false;\">Save</a>";
}

function savePrice(upc){
	var saleprice = document.getElementById('salePrice'+upc+'i').value;
	var saleqty = document.getElementById('saleQty'+upc+'i').value;
	var content = saleprice;
	if (!/\D/.test(saleqty))
		content = saleqty + ' for ' + saleprice;
	if (saleqty == "")
		content = saleprice;
	
	document.getElementById('salePrice'+upc).innerHTML = content;
	document.getElementById('editLink'+upc).innerHTML = "<a href=\"\" onclick=\"editPrice(\'"+upc+"\'); return false;\">Edit</a>";
	
	var id = document.getElementById('currentBatchID').value;
	var uid = document.getElementById('uid').value;
	var audited = document.getElementById('isAudited').value;
	
	phpSend('savePrice&id='+id+'&upc='+upc+'&saleprice='+saleprice+'&uid='+uid+'&audited='+audited+'&saleqty='+saleqty);
}

function newTag(){
	var id = document.getElementById('newTagID').value;
	var upc = document.getElementById('newTagUPC').value;
	var price = document.getElementById('newTagPrice').value;
	var desc = document.getElementById('newTagDesc').value;
	var brand = document.getElementById('newTagBrand').value;
	var units = document.getElementById('newTagUnits').value;
	var size = document.getElementById('newTagSize').value;
	var sku = document.getElementById('newTagSKU').value;
	var vendor = document.getElementById('newTagVendor').value;
	
	phpSend('addTag&id='+id+'&upc='+upc+'&price='+price+'&desc='+desc+'&brand='+brand+'&units='+units+'&size='+size+'&sku='+sku+'&vendor='+vendor);
}

function forceBatch(id){
	phpSend('forceBatch&id='+id);
	alert('Batch '+id+' has been forced');
}

function unsale(id){
	phpSend('unsale&id='+id);
	alert('Batch '+id+' has been taken off sale');
}

function lcselect_util(){
	var lc = document.getElementById('lcselect').value;
	document.getElementById('addItemUPC').value = lc;
}

function switchToLC(){
	phpSend('switchToLC');
}

function switchFromLC(){
	phpSend('switchFromLC');
}

function redisplayWithOrder(id,neworder){
	phpSend('redisplayWithOrder&id='+id+'&order='+neworder);
}

function expand(likecode,saleprice){
	phpSend('expand&likecode='+likecode+'&saleprice='+saleprice);
}

function doExpand(likecode,saleprice,data){
	var table = document.getElementById('yeoldetable');
	var num = 0;
	var row = document.getElementById('expandId'+likecode).value;
	for (var i = 3; i < data.length; i++){
		var newrow = table.insertRow(Number(row)+1);
		newrow.innerHTML = data[i];
		num++;
	}

	var inputs = document.getElementsByName('expandId');
	for (var i = 0; i < inputs.length; i++){
		var theInput = inputs.item(i);
		if (Number(theInput.value) > Number(row))
			theInput.value = Number(theInput.value)+num;	
	}
	
	var newSpan = " <a href=\"\" onclick=\"doCollapse("+likecode+","+saleprice+","+num+"); return false;\">[-]</a>";
	document.getElementById("LCToggle"+likecode).innerHTML = newSpan;
}

function doCollapse(likecode,saleprice,num){
	var table = document.getElementById('yeoldetable');
	var row = document.getElementById('expandId'+likecode).value;
	for (var i = 0; i < num; i++)
		table.deleteRow(Number(row)+1);

	var inputs = document.getElementsByName('expandId');
	for (var i = 0; i < inputs.length; i++){
		var theInput = inputs.item(i);
		if (Number(theInput.value) > Number(row))
			theInput.value = Number(theInput.value)-num;	
	}

	var newSpan = " <a href=\"\" onclick=\"expand("+likecode+","+saleprice+"); return false;\">[+]</a>";
	document.getElementById("LCToggle"+likecode).innerHTML = newSpan;
}

function setupDatePickers() {
    $('#newBatchStartDate').datepicker();
    $('#newBatchEndDate').datepicker();
}
