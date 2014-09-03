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
    in the file license.txt along with IT CORE; if not, write to the Free Software
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
    http.open('get', 'BatchManagementTool.php?action='+action);
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
        var array = response.split('`');
        switch(array[0]){
        case 'newBatch':
        case 'deleteBatch':
        case 'deleteItem':
        case 'refilter':
        case 'redisplay':
	case 'batchListPage':
	case 'redisplayWithOrder':
	case 'doPaste':
	case 'moveQual':
	case 'moveDisc':
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
	case 'PS_toggleDiscSplit':
	case 'PS_toggleMemberOnly':
	case 'saveLimit':
        		break;
	case 'PS_pricing':
			alert('Pricing Updated!');
			break;
	case 'autoTag':
			alert('New tags generated');
			break;
	case 'UnsaleBatch':
			alert('Sale stopped');
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
                $('#newBatchStartDate').datepicker();
                $('#newBatchEndDate').datepicker();
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
function getTypeIDs(){
	return document.getElementById('passtojstypeids').value.split("`");
}
function getOwners(){
	return document.getElementById('passtojsowners').value.split("`");
}

function newBatch()
{
    var data = 'action=newBatch&' + $('#newBatchForm :input').serialize();
    $.ajax({
        url: 'BatchManagementTool.php',
        method: 'get',
        data: data,
        success: function(resp) {
            $('#displayarea').html(resp);
        }
    });
    $('#newBatchForm').get(0).reset();
	
    $('#newBatchName').focus();
}

function deleteBatch(id, name)
{
	var audited = $('#isAudited').val();
	if (audited == "1") {
		alert("You're not allowed to delete batches");
		return;
	}

	if (confirm('Delete this batch ('+name+')?')) {
        $.ajax({
            url: 'BatchManagementTool.php',
            data: 'action=deleteBatch&id='+id,
            success: function(resp) {
                $('#displayarea').html(resp);
            }
        });
    }
}

function editBatch(id){
    var name = $('tr#batchRow' + id + ' a:first').html();
	var type = document.getElementById('type'+id).innerHTML;
	var startdate = document.getElementById('startdate'+id).innerHTML;
	var enddate = document.getElementById('enddate'+id).innerHTML;
	var owner = document.getElementById('owner'+id).innerHTML;

	var batchtypes = getTypes();
	var batchtypeids = getTypeIDs();
	var owners = getOwners();
	
	var typeselect = "<select id=type"+id+"i name=type>";
	for (var i = 0; i < batchtypes.length; i++){
		typeselect += "<option value="+batchtypeids[i];
		if (batchtypes[i] == type)
			typeselect += " selected";
		typeselect += ">"+batchtypes[i]+"</option>";
	}
	typeselect += "</select>";
	
	var ownerselect = "<select id=owner"+id+"i name=owner>";
	for (var i = 0; i < owners.length; i++){
		ownerselect += "<option";
		if (owners[i] == owner)
			ownerselect += " selected";
		ownerselect += ">"+owners[i]+"</option>";
	}
	ownerselect += "</select>";
	
	document.getElementById('name'+id).innerHTML = "<input type=text name=name id=name"+id+"i value=\""+name+"\" />";
	document.getElementById('type'+id).innerHTML = typeselect;
	document.getElementById('startdate'+id).innerHTML = "<input type=text name=startdate class=dateinput id=startdate"+id+"i value=\""+startdate+"\" />";
	document.getElementById('enddate'+id).innerHTML = "<input type=text name=enddate class=dateinput id=enddate"+id+"i value=\""+enddate+"\" />";
	document.getElementById('owner'+id).innerHTML = ownerselect;

    $('tr#batchRow' + id).find('a.batchEditLink').hide();
    $('tr#batchRow' + id).find('a.batchSaveLink').show();
	
    $('input.dateinput').datepicker();
	document.getElementById('name'+id+'i').focus();
}

function saveBatch(id)
{
    var data = $('tr#batchRow' + id + ' :input').serialize();
    $.ajax({
        url: 'BatchManagementTool.php',
        data: 'action=saveBatch&id=' + id + '&' + data,
        success: function(resp) {
        }
    });
    $('tr#batchRow' + id + ' td').each(function() {
        if ($(this).find('select').length > 0) {
            var val = $(this).find('option:selected').text();
            $(this).html(val);
        } else if ($(this).find('input').length > 0) {
            var val = $(this).find('input').val();
            $(this).html(val);
        }
    });

    var link = $('<a></a>');
    link.attr('href', '');
    link.html($('tr#batchRow' + id + ' td:first').html());
    link.click(function(e){ e.preventDefault(); showBatch(id); });
    $('tr#batchRow' + id + ' td:first').html('').append(link);
	
    $('tr#batchRow' + id).find('a.batchEditLink').show();
    $('tr#batchRow' + id).find('a.batchSaveLink').hide();
}

function showBatch(id, tag)
{
	if ($('#isAudited').val() == "1" && $('#type'+id).html() == 'Price Change') {
        alert("You can't edit price change batches");
        return;
	}

    $.ajax({
        url: 'BatchManagementTool.php',
        data: 'action=showBatch&id=' + id + '&tag=' + tag,
        dataType: 'json',
        success: function(resp) {
            $('#inputarea').html(resp.input);
            $('#displayarea').html(resp.display);
            $('#addItemUPC').focus();
        }
    });
}

function backToList()
{
    $.ajax({
        url: 'BatchManagementTool.php',
        data: 'action=backToList',
        dataType: 'json',
        success: function(resp) {
            $('#inputarea').html(resp.input);
            $('#displayarea').html(resp.display);
            $('#addItemUPC').focus();
        }
    });
}

function addItem()
{
    var data = 'id=' + $('#currentBatchID').val();
    data += '&' + $('.addItemForm :input').serialize();
    if ($('#addItemLikeCode').is(':checked')) {
        data += '&action=addItemLC';
    } else {
        data += '&action=addItemUPC';
    }
    $.ajax({
        url: 'BatchManagementTool.php',
        data: data,
        dataType: 'json',
        success: function(resp) {
            if (resp.error) {
                $('<div>'+resp.error+'</div>').dialog({
                    close: function() {
                        $(resp.field).focus();
                    },
                    modal: true
                });
            }
            $('#inputarea').html(resp.content);
            $(resp.field).focus();
        }
    });
}

function addItemFinish(upc)
{
	var data = 'id=' + $('#currentBatchID').val() + '&upc='+upc;
	data += '&price=' + $('#addItemPrice').val();

	data += '&uid=' + $('#uid').val();
	data += '&audited=' + $('#isAudited').val();

	var limit = $('#currentLimit').html();
	if (limit == '') {
        limit = 0;
    }
    data += '&limit=' + limit;

    if (!($('#addItemTag').is(':checked')) || $('#isAudited').val() == '1') {
        data += '&action=addItemPrice';
    } else {
        data += '&action=newTag';
    }

    $.ajax({
        url: 'BatchManagementTool.php',
        data: data,
        dataType: 'json',
        success: function(resp) {
            $('#inputarea').html(resp.input);
            if (resp.display) {
                $('#displayarea').html(resp.display);
                $('#addItemUPC').focus();
            }
        }
    });
}

function addItemLCFinish(lc)
{
	var data = 'id=' + $('#currentBatchID').val() + '&lc='+lc;
	data += '&price=' + $('#addItemPrice').val();

	data += '&uid=' + $('#uid').val();
	data += '&audited=' + $('#isAudited').val();

	var limit = $('#currentLimit').html();
	if (limit == '') {
        limit = 0;
    }
    data += '&limit=' + limit;
    data += '&action=addItemLCPrice';
    $.ajax({
        url: 'BatchManagementTool.php',
        data: data,
        dataType: 'json',
        success: function(resp) {
            $('#inputarea').html(resp.input);
            $('#displayarea').html(resp.display);
            $('#addItemUPC').focus();
        }
    });
}

function deleteItem(upc)
{
    var data = 'action=deleteItem';
    data += '&upc=' + upc;
    data += '&id=' + $('#currentBatchID').val();
    data += '&uid=' + $('#uid').val();
    data += '&audited=' + $('#isAudited').val();

    $.ajax({
        url: 'BatchManagementTool.php',
        data: data,
        success: function(resp) {
            $('#displayarea').html(resp);
        }
    });
}

function refilter(owner)
{
    $.ajax({
        url: 'BatchManagementTool.php',
        data: 'action=refilter&owner=' + encodeURIComponent(owner),
        success: function(resp) {
            $('#displayarea').html(resp);
        }
    });
}

function redisplay(mode)
{
    var data = 'action=redisplay';
    data += '&mode=' + encodeURIComponent(mode);
    data += '&owner=' + encodeURIComponent($('#filterOwner').val());
    $.ajax({
        url: 'BatchManagementTool.php',
        data: data,
        success: function(resp) {
            $('#displayarea').html(resp);
        }
    });
}

function batchListPage(filter,mode,batchID)
{
    var data = 'action=batchListPage';
    data += '&mode=' + encodeURIComponent(mode);
    data += '&filter=' + encodeURIComponent(filter);
    data += '&maxBatchID=' + encodeURIComponent(batchID);
    $.ajax({
        url: 'BatchManagementTool.php',
        data: data,
        success: function(resp) {
            $('#displayarea').html(resp);
        }
    });
}

function editPrice(upc){
	var saleprice = document.getElementById('salePrice'+upc).innerHTML;
	var saleqty = document.getElementById('saleQty'+upc).innerHTML;
	var numeric_qty = 1;
	if (saleqty != ''){
		numeric_qty = saleqty.split(' ')[0];
	}
	
	document.getElementById('salePrice'+upc).innerHTML = "<input type=text size=5 id=salePrice"+upc+"i value=\""+saleprice+"\" />";
	document.getElementById('saleQty'+upc).innerHTML = "<input type=text size=3 id=saleQty"+upc+"i value=\""+numeric_qty+"\" />@";

	var path = document.getElementById('buttonimgpath').value;
	var sv = "<a href=\"\" onclick=\"savePrice('"+upc+"'); return false;\"><img src=\""+path+"b_save.png\" alt=\"Save\" /></a>";
	document.getElementById('editLink'+upc).innerHTML = sv;
}

function savePrice(upc){
	var saleprice = document.getElementById('salePrice'+upc+'i').value;
	var saleqty = document.getElementById('saleQty'+upc+'i').value;

	saleqty = parseInt(saleqty,10);
	if (isNaN(saleqty))
		saleqty = 0;
	else if (saleqty <= 1)
		saleqty = 0;
	
	document.getElementById('salePrice'+upc).innerHTML = saleprice;
	if (saleqty == 0)
		document.getElementById('saleQty'+upc).innerHTML = '';
	else
		document.getElementById('saleQty'+upc).innerHTML = saleqty+' for ';

	var path = document.getElementById('buttonimgpath').value;
	var eb = "<a href=\"\" onclick=\"editPrice('"+upc+"'); return false;\"><img src=\""+path+"b_edit.png\" alt=\"Edit\" /></a>";
	document.getElementById('editLink'+upc).innerHTML = eb;
	
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

function moveDisc(upc){
	var id = document.getElementById('currentBatchID').value;
	phpSend('moveDisc&batchID='+id+'&upc='+upc);
}

function moveQual(upc){
	var id = document.getElementById('currentBatchID').value;
	phpSend('moveQual&batchID='+id+'&upc='+upc);
}

function PS_toggleDiscSplit(batchID){
	phpSend('PS_toggleDiscSplit&batchID='+batchID);
}

function PS_toggleMemberOnly(batchID){
	phpSend('PS_toggleMemberOnly&batchID='+batchID);
}

function PS_pricing(batchID){
	var qual = document.getElementById('PS_qualCount').value;	
	var disc = document.getElementById('PS_discount').value;	

	var saleType = 1;
	if (document.getElementById('PS_memCBX').checked)
		saleType = 2;

	var pricemethod = 4;
	if (document.getElementById('PS_splitCBX').checked)
		pricemethod = 3;

	var str = "quantity="+qual+"&discount="+disc+"&discounttype="+saleType+"&pricemethod="+pricemethod;
	phpSend('PS_pricing&batchID='+batchID+'&'+str);
}

function editLimit(batchID,limit){
	var saveLink = "<a href=\"\" onclick=\"saveLimit("+batchID+"); return false;\">Save Limit</a>";
	var limitIn = "<input type=\"text\" size=\"3\" value=\"";
	if (limit > 0) limitIn += limit;
	limitIn += "\" id=\"limitInputBox\" />";

	document.getElementById('limitLink').innerHTML = saveLink;
	document.getElementById('currentLimit').innerHTML = limitIn;
	document.getElementById('limitInputBox').focus();
}

function saveLimit(batchID){
	var newlimit = document.getElementById('limitInputBox').value;
	newlimit = parseInt(newlimit,10);
	if (isNaN(newlimit))
		newlimit = 0;
	else if (newlimit <= 0)
		newlimit = 0;
	
	var editLink = "<a href=\"\" onclick=\"editLimit("+batchID+","+newlimit+"); return false;\">";
	if (newlimit==0) editLink += "Add Limit</a>";
	else editLink += "Limit:</a>";

	document.getElementById('limitLink').innerHTML = editLink;
	if (newlimit == 0)
		document.getElementById('currentLimit').innerHTML = '';
	else
		document.getElementById('currentLimit').innerHTML = newlimit;

	phpSend('saveLimit&batchID='+batchID+'&limit='+newlimit);
}

function autoTag(bID){
	phpSend('autoTag&batchID='+bID);
}

function unsaleBatch(bID)
{
    phpSend('UnsaleBatch&batchID='+bID);
}

