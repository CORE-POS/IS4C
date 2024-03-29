function skuField(row, vendor_id)
{
    var sku = $('<input type="text" name="sku[]" required />')
        .addClass('form-control')
        .addClass('item-sku')
        .addClass('upc-field')
        .addClass('input-sm')
        .autocomplete({
            source: function(request, callback) {
                vendorAutoComplete('../ws/', 'sku', request.term, vendor_id, callback);
            },
            minLength: 2
        })
        .on( "autocompletechange", function(event,ui) {
            // post value to console for validation
            skuLookup($(this).val(), $(this));
        });
    row.append($('<td>').append(sku));
}

function upcField(row, vendor_id)
{
    var upc = $('<input type="text" name="upc[]" required />')
        .addClass('form-control')
        .addClass('item-upc')
        .addClass('input-sm')
        .addClass('upc-field')
        .autocomplete({
            source: function(request, callback) {
                vendorAutoComplete('../ws/', 'item', request.term, vendor_id, callback);
            },
            minLength: 2
        })
        .on( "autocompletechange", function(event,ui) {
            // post value to console for validation
            upcLookup($(this).val(), $(this));
        })
        .on("keyup", function(event,ui) {
            if (event.keyCode == 13) {
                addInvoiceLine();
            }
        });
    row.append($('<td>').append(upc));
}

function qtyField(row)
{
    var qty = $('<input type="text" name="cases[]" required />')
        .val(1)
        .addClass('input-sm')
        .addClass('item-cases')
        .addClass('form-control');
    row.append($('<td>').addClass('col-sm-1').append(qty));
}

function caseSizeField(row)
{
    var caseSize = $('<input type="text" name="case-size[]" required />')
        .val(1)
        .addClass('item-units')
        .addClass('input-sm')
        .addClass('form-control');
    row.append($('<td>').addClass('col-sm-1').append(caseSize));
}

function totalField(row)
{
    var total = $('<input type="text" name="total[]" required />')
        .addClass('price-field')
        .addClass('input-sm')
        .addClass('form-control');
    row.append($('<td>').append(total));
}

function brandField(row)
{
    var brand = $('<input type="text" name="brand[]" />')
        .val($('#vendor-name strong').html())
        .addClass('item-brand')
        .addClass('input-sm')
        .addClass('form-control');
    row.append($('<td>').append(brand));
}

function descriptionField(row)
{
    var description = $('<input type="text" name="description[]" required />')
        .hover(function() {
            $(this).prop('title', $(this).val());
        })
        .addClass('item-description')
        .addClass('input-sm')
        .addClass('form-control');
    row.append($('<td>').append(description));
}

function removeButton(row)
{
    if ($('input[name=order-id]').length > 0) {
        return;
    }
    var remove = $('<button type="button">')
        .addClass('btn')
        .addClass('btn-default')
        .addClass('alert-danger')
        .addClass('btn-sm')
        .html($('#delete-html').html())
        .click(function() {
            $(this).closest('tr').remove();
        });
    row.append($('<td>').append(remove));
}

function receiveLine()
{
    var row = $('<tr class="small">');
    row.append('<th class="text-right">Recv\'d Qty</th>');
    row.append('<td><input type="text" name="recv-cases[]" class="input-sm recv-cases form-control" /></td>'); 
    row.append('<th class="text-right" colspan="2">Recv\'d Cost</th>');
    row.append('<td><input type="text" name="recv-cost[]" class="input-sm recv-cost form-control" /></td>'); 
    row.append('<th class="text-right">Recv\'d Date</th>');
    row.append('<td><input type="text" name="recv-date[]" class="input-sm recv-date form-control" /></td>'); 

    row.prependTo('#invoice-table tbody');

    $('input.recv-date').datepicker({
        dateFormat: 'yy-mm-dd',    
        changeYear: true,
        yearRange: "c-10:c+10",
    });
    $('input.recv-date').attr('autocomplete', 'off');
}

function addInvoiceLine()
{
    if ($('input[name=order-id]').length > 0) {
        receiveLine();
    }

    var vendor_id = $('#vendor-id').val();
    var row = $('<tr>');

    upcField(row, vendor_id);
    skuField(row, vendor_id);
    qtyField(row);
    caseSizeField(row);
    totalField(row);
    brandField(row);
    descriptionField(row);
    removeButton(row);

    row.prependTo('#invoice-table tbody');

    row.find('input:first').focus();
}

function skuLookup(sku, elem)
{
    doLookup('sku', sku, elem);
}

function upcLookup(upc, elem)
{
    doLookup('upc', upc, elem);
}

var loading = false;
function stopLoading()
{
    loading=false;
}

function doLookup(mode, term, elem)
{
    if (loading) {
        return;
    }
    var vendor_id = $('#vendor-id').val();
    var p = { type: 'vendor', vendor_id: vendor_id };
    if (mode === 'sku') {
        p.sku = term;
    } else if (mode === 'upc') {
        p.upc = term;
    }

    var req = {
        jsonrpc: '2.0',
        method: '\\COREPOS\\Fannie\\API\\webservices\\FannieItemInfo',
        id: new Date().getTime(),
        params: p
    };

    $.ajax({
        url: '../ws/',
        type: 'post',
        data: JSON.stringify(req),
        dataType: 'json',
        contentType: 'application/json'
    }).done(function(data) {
        if (data.result && (data.result.sku || data.result.upc)) {
            if (mode === 'sku' && data.result.upc !== '0000000000000') {
                elem.closest('tr').find('.item-upc').val(data.result.upc);
            } else if (mode === 'sku') {
                elem.closest('tr').find('.item-upc').val('');
            } else if (mode === 'upc') {
                elem.closest('tr').find('.item-sku').val(data.result.sku);
            }
            if (data.result.units !== '') {
                elem.closest('tr').find('.item-units').val(data.result.units);
            }
            if (data.result.brand !== '') {
                elem.closest('tr').find('.item-brand').val(data.result.brand);
            }
            if (data.result.description !== '') {
                elem.closest('tr').find('.item-description').val(data.result.description);
            }
        }
    });
}

function vendorAutoComplete(ws_url, field_name, search_term, vendor_id, callback)
{
    var req = {
        jsonrpc: '2.0',
        method: '\\COREPOS\\Fannie\\API\\webservices\\FannieAutoComplete',
        id: new Date().getTime(),
        params: { field: field_name, search: search_term, vendor_id: vendor_id }
    };

    $.ajax({
        url: ws_url,
        type: 'post',
        data: JSON.stringify(req),
        dataType: 'json',
        contentType: 'application/json'
    }).done(function(data) {
        if (data.result) {
            callback(data.result);
        }
    }).fail(function() {
        callback([]);
    });
}

function saveOrder()
{
    $('#save-btn').prop('disabled', true);
    var dataStr = $('#order-form').serialize();
    $.ajax({
        type: 'post',
        data: dataStr,
        dataType: 'json'
    }).done(function(resp) {
        if (resp.error) {
            showBootstrapAlert('#alert-area', 'danger', resp.message);
            $('#save-btn').prop('disabled', false);
        } else {
            window.location = 'ViewPurchaseOrders.php?id=' + resp.order_id;
        }
    });
}

function existingOrder(orderJSON, itemsJSON)
{
    var order = JSON.parse(orderJSON);
    var items = JSON.parse(itemsJSON);

    if (order.creationDate) {
        $('input[name=order-date]').val(order.creationDate);
    }
    if (order.vendorOrderID) {
        $('input[name=po-number]').val(order.vendorOrderID);
    }
    if (order.vendorInvoiceID) {
        $('input[name=inv-number]').val(order.vendorInvoiceID);
    }

    var name = $('#vendor-name').html();
    name = name.replace('New <', 'Existing <');
    name += ' #' + order.orderID;
    $('#vendor-name').html(name);

    var idField = $('<input type="hidden" name="order-id" />').val(order.orderID);
    $('#order-form').append(idField);

    loading = true;
    items.forEach(function(item) {
        var total = Number(item.receivedTotalCost);
        var unit = Number(item.unitCost);
        var cases = Number(item.quantity);
        var caseSize = Number(item.caseSize);

        if (isNaN(total)) {
            total = unit * cases * caseSize;
        }

        addInvoiceLine();
        $('input.item-sku:first').val(item.sku);
        $('input.item-upc:first').val(item.internalUPC);
        $('input.item-cases:first').val(cases);
        $('input.item-units:first').val(caseSize);
        $('input.price-field:first').val(total);
        $('input.item-brand:first').val(item.brand);
        $('input.item-description:first').val(item.description);
        $('input.recv-cases:first').val(item.receivedQty);
        $('input.recv-cost:first').val(item.receivedTotalCost);
        $('input.recv-date:first').val(item.receivedDate);
    });

    setTimeout(stopLoading, 250);
}
