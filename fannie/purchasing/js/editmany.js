function itemSearch(){
	if ($('#searchField').val() == '') return false;

	var dataStr = 'search='+$('#searchField').val();
	$.ajax({
		url: 'EditManyPurchaseOrders.php?'+dataStr,
		type: 'get',
		dataType: 'json'
    }).done(function(data){
        if (data.length == 0){
            $('#SearchResults').html('No item found');
            $('#searchField').focus();
        }
        else if (data.length == 1){
            $('#SearchResults').html(oneResultForm(data[0], 0));
            $('#srQty0').focus();	
        }
        else {
            $('#SearchResults').html(manyResultForm(data));
            $('#srQty0').focus();	
        }
	});
}

function oneResultForm(obj, resultNum){
	var output = '<div class="srDiv" id="sr'+resultNum+'" ';
	if (resultNum > 0)
		output += ' style="display:none;"';
	output += '>';
	output += '<table class="table">';
	output += '<tr><td align="right">SKU</td>';
	output += '<td id="srSKU'+resultNum+'">'+obj.sku+'</td></tr>';
	output += '<tr>';
	output += '<td colspan="2">'+obj.title+'</td></tr>';
	output += '<tr><td>Unit Size: '+obj.unitSize+'</td>';
	output += '<td>Units/Case: '+obj.caseSize+'</td></tr>';
	output += '<tr><td>Unit Cost: '+obj.unitCost+'</td>';
	output += '<td>Case Cost: '+obj.caseCost+'</td></tr>';
	output += '<tr>';
	output += '<td colspan="2">Order <input type="number" size="3" value="1" onfocus="this.select();" id="srQty'+resultNum+'" />';
	output += ' Cases</td></tr>';	
	output += '</table>';
	output += '<input type="hidden" id="srVendorID'+resultNum+'" value="'+obj.vendorID+'" />';
	output += '<button type="submit" class="btn btn-default" onclick="saveItem('+resultNum+');return false;">Confirm</button>';

	output += '</div>';
	return output;
}

function showResultForm(num){
	$('.srDiv').hide();
	$('#sr'+num).show();
}

function manyResultForm(data){
	var selectText = '<select onchange="showResultForm(this.value);">';
	var divs = '';
	for(var i=0; i<data.length;i++){
		selectText += '<option value="'+i+'">';
		selectText += data[i].sku+' '+data[i].title;
		selectText += '</option>';
		divs += oneResultForm(data[i], i);
	}
	selectText += '</select>';
	return selectText+divs;
}

function saveItem(resultNum){
	var dstr = 'id='+$('#srVendorID'+resultNum).val();
	dstr += '&sku='+$('#srSKU'+resultNum).html();
	dstr += '&qty='+$('#srQty'+resultNum).val();
	$.ajax({
		url: 'EditManyPurchaseOrders.php?'+dstr,
		method: 'get',
		dataType: 'json'
    }).done(function(data){
        if (data.error){
            $('#SearchResults').html(data.error);
        }
        else if (data.sidebar){
            $('#orderInfo').html(data.sidebar);
            $('#SearchResults').html('Item added to order');
        }
        $('#searchField').focus();
	});
}
