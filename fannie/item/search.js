function getResults() {
    var dstr = $('#searchform').serialize();
    $('.upcCheckBox:checked').each(function(){
        dstr += '&u[]='+$(this).val();
    });

    $('.progress').show();
    $('#resultArea').html('');
    $.ajax({
        url: 'AdvancedItemSearch.php',
        type: 'post',
        data: 'search=1&' + dstr,
    }).done(function(data) {
        $('.progress').hide();
        $('#resultArea').html(data);
        // don't run sorting JS on very large result sets
        if ($('.upcCheckBox').length < 2500) {
            $('.search-table').tablesorter({headers: { 0: { sorter:false } } });
        }
    });
}
function toggleAll(elem, selector) {
    if (elem.checked) {
        $(selector).prop('checked', true);
    } else {
        $(selector).prop('checked', false);
    }
    checkedCount('#selection-counter', selector);
}
function checkedCount(output_selector, checked_selector)
{
    var count = $(checked_selector + ':checked').length;
    if (count == 0) {
        $(output_selector).html('');
    } else {
        $(output_selector).html(count + ' items selected. These items will be retained in the next search.');
    }
}
// helper: add all selected upc values to hidden form
// as hidden input tags. the idea is to submit UPCs
// to the handling page via POST because the amount of
// data might not fit in the query string. the hidden 
// form also opens in a new tab/window so search
// results are not lost
function getItems() {
    $('#actionForm').empty();
    var ret = false;
    $('.upcCheckBox:checked').each(function(){
        $('#actionForm').append('<input type="hidden" name="u[]" value="' + $(this).val() + '" />');
        ret = true;
    });
    return ret;
}
function goToBatch() {
    if (getItems()) {
        $('#actionForm').attr('action', '../batches/BatchFromSearch.php');
        $('#actionForm').submit();
    }
}
function goToEdit() {
    if (getItems()) {
        $('#actionForm').attr('action', 'EditItemsFromSearch.php');
        $('#actionForm').submit();
    }
}
function goToList() {
    if (getItems()) {
        $('#actionForm').attr('action', 'ProductListPage.php');
        $('#actionForm').append('<input type="hidden" name="supertype" id="supertype-field" value="upc" />');
        $('#actionForm').submit();
    }
}
function goToSigns() {
    if (getItems()) {
        $('#actionForm').attr('action', '../admin/labels/SignFromSearch.php');
        $('#actionForm').submit();
    }
}
function goToManualSigns() {
    if (getItems()) {
        $('#actionForm').attr('action', '../admin/labels/ManualSignsPage.php');
        $('#actionForm').submit();
    }
}
function goToMargins() {
    if (getItems()) {
        $('#actionForm').attr('action', 'MarginToolFromSearch.php');
        $('#actionForm').submit();
    }
}
function goToCoupons() {
    if (getItems()) {
        $('#actionForm').attr('action', '../modules/plugins2.0/HouseCoupon/HouseCouponEditor.php');
        $('#actionForm').submit();
    }
}
function goToSync() {
    if (getItems()) {
        $('#actionForm').attr('action', 'hobartcsv/SyncFromSearch.php');
        $('#actionForm').submit();
    }
}
function goToReport() {
    if (getItems()) {
        $('#actionForm').attr('action', $('#reportURL').val());
        $('#actionForm').submit();
    }
}
function formReset()
{
    $('#vendorSale').attr('disabled', 'disabled');
    $('.saleField').attr('disabled', 'disabled');
}
function chainSuper(superID)
{
    if (superID === '') {
        superID = -1;
    }
    var req = {
        jsonrpc: '2.0',
        method: '\\COREPOS\\Fannie\\API\\webservices\\FannieDeptLookup',
        id: new Date().getTime(),
        params: {
            'type' : 'children',
            'superID' : superID
        }
    };
    $.ajax({
        url: '../ws/',
        type: 'post',
        data: JSON.stringify(req),
        dataType: 'json',
        contentType: 'application/json'
    }).done(function(resp) {
        if (resp.result) {
            $('#dept-start').empty().append('<option value="">Select Start...</option>');
            $('#dept-end').empty().append('<option value="">Select End...</option>');
            for (var i=0; i<resp.result.length; i++) {
                var opt = $('<option>').val(resp.result[i]['id'])
                    .html(resp.result[i]['id'] + ' ' + resp.result[i]['name']);
                $('#dept-start').append(opt.clone());
                $('#dept-end').append(opt);
            }
        }
    });
}

