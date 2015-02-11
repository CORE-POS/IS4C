function addInvoiceLine()
{
    var vendor_id = $('#vendor-id').val();
    var row = $('<tr>');

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
        });
    row.append($('<td>').append(upc));

    var qty = $('<input type="text" name="cases[]" required />')
        .val(1)
        .addClass('input-sm')
        .addClass('form-control');
    row.append($('<td>').addClass('col-sm-1').append(qty));

    var caseSize = $('<input type="text" name="case-size[]" required />')
        .val(1)
        .addClass('item-units')
        .addClass('input-sm')
        .addClass('form-control');
    row.append($('<td>').addClass('col-sm-1').append(caseSize));

    var total = $('<input type="text" name="total[]" required />')
        .addClass('price-field')
        .addClass('input-sm')
        .addClass('form-control');
    row.append($('<td>').append(total));

    var brand = $('<input type="text" name="brand[]" required />')
        .val($('#vendor-name strong').html())
        .addClass('item-brand')
        .addClass('input-sm')
        .addClass('form-control');
    row.append($('<td>').append(brand));

    var description = $('<input type="text" name="description[]" required />')
        .hover(function() {
            $(this).prop('title', $(this).val());
        })
        .addClass('item-description')
        .addClass('input-sm')
        .addClass('form-control');
    row.append($('<td>').append(description));

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

function doLookup(mode, term, elem)
{
    var vendor_id = $('#vendor-id').val();
    p = { type: 'vendor', vendor_id: vendor_id };
    if (mode == 'sku') {
        p.sku = term;
    } else if (mode == 'upc') {
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
        contentType: 'application/json',
        success: function(data) {
            if (data.result) {
                if (mode == 'sku') {
                    elem.closest('tr').find('.item-upc').val(data.result.upc);
                } else if (mode == 'upc') {
                    elem.closest('tr').find('.item-sku').val(data.result.sku);
                }
                if (data.result.units != '') {
                    elem.closest('tr').find('.item-units').val(data.result.units);
                }
                if (data.result.brand != '') {
                    elem.closest('tr').find('.item-brand').val(data.result.brand);
                }
                if (data.result.description != '') {
                    elem.closest('tr').find('.item-description').val(data.result.description);
                }
            }
        },
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
        contentType: 'application/json',
        success: function(data) {
            if (data.result) {
                callback(data.result);
            }
        },
        error: function() {
            callback([]);
        }
    });
}

function saveOrder()
{
    $('#save-btn').prop('disabled', true);
    var dataStr = $('#order-form').serialize();
    $.ajax({
        type: 'post',
        data: dataStr,
        dataType: 'json',
        success: function(resp) {
            if (resp.error) {
                showBootstrapAlert('#alert-area', 'danger', resp.message);
                $('#save-btn').prop('disabled', false);
            } else {
                location = 'ViewPurchaseOrders.php?id=' + resp.order_id;
            }
        }
    });
}
