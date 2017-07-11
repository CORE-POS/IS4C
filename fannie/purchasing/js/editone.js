function itemSearch(){
	if ($('#searchField').val()=='') return false;

	var dstr = 'id='+$('#vendor-id').val();
	dstr += '&search='+$('#searchField').val();
    dstr += '&orderID='+$('#order-id').val();
	$('#searchField').val('');

	$.ajax({
		url: 'EditOnePurchaseOrder.php?'+dstr,
		method: 'get',
		dataType: 'json'
    }).done(function(data){
        if (!data.items || data.items.length == 0){
            $('#SearchResults').html('No item found');
            $('#searchField').focus();
        } else if (data.items.length == 1){
            $('#SearchResults').html(oneResultForm(data.items[0], 0));
        } else {
            $('#SearchResults').html(manyResultForm(data.items));
        }

        if (data.table) {
            $('#list-wrapper').html(data.table);
        }
	});
}

function markInCurrentOrder(data)
{
	for(var i=0; i<data.length;i++){
        $.ajax({
            url: 'EditOnePurchaseOrder.php',
            data: 'id='+$('#order-id').val()+'&sku='+data[i].sku+'&index='+i,
            dataType: 'json'
        }).done(function(result) {
            if (result.qty != 0) {
                $('#qtyRow'+result.index).append(' <span style="color:green;">IN CURRENT ORDER</span>');
                $('#srQty'+result.index).val(result.qty);
            }
        });
    }
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

function showResultForm(num){
	$('.srDiv').hide();
	$('#sr'+num).show();
}

function oneResultForm(obj, resultNum){
	var output = '<div class="srDiv col-sm-6" id="sr'+resultNum+'" ';
	if (resultNum > 0)
		output += ' style="display:none;"';
	output += '>';
    output += '<form onsubmit="saveItem('+resultNum+');return false;">';
	output += '<input type="hidden" id="srSKU'+resultNum+'" value="'+obj.sku+'" />';
	output += '<table class="table table-bordered small">';
	output += '<tr>';
	output += '<td colspan="3">'+obj.sku + " " + obj.title+'</td></tr>';
	output += '<tr><td>Unit Size: '+obj.unitSize+'</td>';
    if (obj.history[0]) {
        output += '<td>' + obj.history[0].date + '</td><td>' + obj.history[0].cases + '</td></tr>';
    } else {
        output += '<td></td><td></td>';
    }
	output += '</tr><tr><td>Units/Case: '+obj.caseSize+'</td>';
    if (obj.history[1]) {
        output += '<td>' + obj.history[1].date + '</td><td>' + obj.history[1].cases + '</td></tr>';
    } else {
        output += '<td></td><td></td>';
    }
	output += '</tr><tr><td>Case Cost: '+obj.caseCost+'</td>';
    if (obj.history[2]) {
        output += '<td>' + obj.history[2].date + '</td><td>' + obj.history[2].cases + '</td></tr>';
    } else {
        output += '<td></td><td></td>';
    }
	output += '</tr><tr>';
	output += '<td id="qtyRow'+resultNum+'" colspan="3"><input type="number" size="3" value="'+obj.cases+'" ';
    output += ' onchange="saveItem('+resultNum+')" onfocus="this.select();" id="srQty'+resultNum+'" />';
	output += ' Cases</td></tr>';	
	output += '</table>';
	output += '</form><br />';

	output += '</div>';
	return output;
}

function saveItem(resultNum){
	var dstr = 'id='+$('#order-id').val();
	dstr += '&sku='+$('#srSKU'+resultNum).val();
	dstr += '&qty='+$('#srQty'+resultNum).val();
    saveQty = $('#srQty'+resultNum).val();
	$.ajax({
		url: 'EditOnePurchaseOrder.php?'+dstr,
		method: 'get',
		dataType: 'json'
    }).done(function(data){
        if (data.error){
            $('#SearchResults').html(data.error);
        }
        if (data.table) {
            $('#list-wrapper').html(data.table);
        }
        $('#searchField').focus();
	});
}

function updateList()
{
    var dstr = 'id=' + $('#order-id').val();
    dstr += '&' + $('#list-wrapper input').serialize();
    $.ajax({
        url: 'EditOnePurchaseOrder.php',
        dataType: 'json',
        type: 'post',
        data: dstr
    }).done(function(resp) {
        if (resp.table) {
            $('#list-wrapper').html(resp.table);
            showBootstrapAlert('#list-wrapper', 'success', 'Updated Order');
        }
    });
}

function addManualRow() {
    var newID = 'inp' + Date.now();
    var newRow = '<tr>';
    newRow += '<td><input type="text" name="sku[]" class="form-control" id="' + newID + '" /></td>';
    newRow += '<td><input type="text" name="upc[]" class="form-control" /></td>';
    newRow += '<td><input type="text" name="brand[]" class="form-control" /></td>';
    newRow += '<td><input type="text" name="description[]" class="form-control" /></td>';
    newRow += '<td><input type="text" name="unitSize[]" class="form-control" /></td>';
    newRow += '<td><input type="text" name="case[]" class="form-control" /></td>';
    newRow += '<td><input type="text" name="qty[]" class="form-control" /></td>';
    newRow += '<td><input type="text" name="totalCost[]" class="form-control" /></td>';
    $('#list-wrapper table').append(newRow);
    $('#'+newID).focus();
}

