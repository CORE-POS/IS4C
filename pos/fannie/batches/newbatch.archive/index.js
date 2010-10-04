/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

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
        var response = http.responseText.replace(/^\s*/,'');
        //alert(response);
        var array = response.split('`');
        switch(array[0]){
        case 'newBatch':
        case 'deleteBatch':
        case 'deleteItem':
        case 'refilter':
        case 'redisplay':
	case 'redisplayWithOrder':
	case 'doPaste':
        		document.getElementById('displayarea').innerHTML = array[1];
        		break;
        case 'newTag':
        		document.getElementById('inputarea').innerHTML = array[1];
        		break;
        case 'saveBatch':
        case 'savePrice':
        case 'forceBatch':
	case 'doCut':
	case 'unCut':
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
        		break;
        case 'addItemUPC':
        case 'addItemLC':
        		document.getElementById('inputarea').innerHTML = array[1];
        		document.getElementById('addItemPrice').focus();
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
		alert('&'+array[0]+'&');
        }
	}
}

/********************* END AJAX BASICS ******************/

function getTypes(){
	return document.getElementById('passtojstypes').value.split("`");
}
function getOwners(){
	return document.getElementById('passtojsowners').value.split("`");
}

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

	var batchtypes = getTypes();
	var owners = getOwners();
	
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
	document.getElementById('startdate'+id).innerHTML = "<input type=text id=startdate"+id+"i value=\""+startdate+"\" onclick=\"showCalendarControl(this);\" />";
	document.getElementById('enddate'+id).innerHTML = "<input type=text id=enddate"+id+"i value=\""+enddate+"\" onclick=\"showCalendarControl(this);\" />";
	document.getElementById('owner'+id).innerHTML = ownerselect;

	var path = document.getElementById('buttonimgpath').value;
	var svb = "<a href=\"\" onclick=\"saveBatch("+id+"); return false;\"><img src=\""+path+"b_save.png\" alt=\"Save\" /></a>";
	document.getElementById('edit'+id).innerHTML = svb;
	
	document.getElementById('name'+id+'i').focus();
}

function saveBatch(id){
	var name = document.getElementById('name'+id+'i').value;
	var type = document.getElementById('type'+id+'i').value;
	var startdate = document.getElementById('startdate'+id+'i').value;
	var enddate = document.getElementById('enddate'+id+'i').value;
	var owner = document.getElementById('owner'+id+'i').value;

	var batchtypes = getTypes();
	
	document.getElementById('name'+id).innerHTML = "<a id=namelink"+id+" href=\"\" onclick=\"showBatch("+id+"); return false;\">"+name+"</a>";
	document.getElementById('type'+id).innerHTML = batchtypes[type-1];
	document.getElementById('startdate'+id).innerHTML = startdate;
	document.getElementById('enddate'+id).innerHTML = enddate;
	document.getElementById('owner'+id).innerHTML = owner;

	var path = document.getElementById('buttonimgpath').value;
	var eb = "<a href=\"\" onclick=\"editBatch("+id+"); return false;\"><img src=\""+path+"b_edit.png\" alt=\"Edit\" /></a>";
	document.getElementById('edit'+id).innerHTML = eb;
	
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
	
	document.getElementById('salePrice'+upc).innerHTML = "<input type=text size=5 id=salePrice"+upc+"i value=\""+saleprice+"\" />";

	var path = document.getElementById('buttonimgpath').value;
	var sv = "<a href=\"\" onclick=\"savePrice('"+upc+"'); return false;\"><img src=\""+path+"b_save.png\" alt=\"Save\" /></a>";
	document.getElementById('editLink'+upc).innerHTML = sv;
}

function savePrice(upc){
	var saleprice = document.getElementById('salePrice'+upc+'i').value;
	
	document.getElementById('salePrice'+upc).innerHTML = saleprice;

	var path = document.getElementById('buttonimgpath').value;
	var eb = "<a href=\"\" onclick=\"editPrice('"+upc+"'); return false;\"><img src=\""+path+"b_edit.png\" alt=\"Edit\" /></a>";
	document.getElementById('editLink'+upc).innerHTML = eb;
	
	var id = document.getElementById('currentBatchID').value;
	var uid = document.getElementById('uid').value;
	var audited = document.getElementById('isAudited').value;
	
	phpSend('savePrice&id='+id+'&upc='+upc+'&saleprice='+saleprice+'&uid='+uid+'&audited='+audited);
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

function doCut(upc,batchID,uid){
	phpSend('doCut&upc='+upc+'&batchID='+batchID+'&uid='+uid);	
	var repl = "<a href=\"\" onclick=\"unCut('"+upc+"',"+batchID+","+uid+"); return false;\">Undo</a>";
	document.getElementById('cpLink'+upc).innerHTML = repl;
}

function unCut(upc,batchID,uid){
	phpSend('unCut&upc='+upc+'&batchID='+batchID+'&uid='+uid);	
	var repl = "<a href=\"\" onclick=\"doCut('"+upc+"',"+batchID+","+uid+"); return false;\">Cut</a>";
	document.getElementById('cpLink'+upc).innerHTML = repl;
}

function doPaste(uid,batchID){
	phpSend('doPaste&uid='+uid+'&batchID='+batchID);
}
