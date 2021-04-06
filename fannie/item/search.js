function getResults() {
    var dstr = $('#searchform').serialize();
    document.querySelectorAll('.upcCheckBox:checked').forEach(function(el){
        dstr += '&u[]='+el.value;
    });

    document.querySelector('.progress').style.display = 'block';
    document.querySelector('#resultArea').innerHTML = '';
    fetch('AdvancedItemSearch.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'search=1&' + dstr
    }).then(response => {
        if (!response.ok) {
            return;
        }
        response.text().then(data => {
            document.querySelector('.progress').style.display = 'none';
            document.querySelector('#resultArea').innerHTML = data;
            // don't run sorting JS on very large result sets
            if ($('.upcCheckBox').length < 2500) {
                $('.search-table').tablesorter({headers: { 0: { sorter:false } } });
            }
        });
    });
}
function toggleAll(elem, selector) {
    if (elem.checked) {
        document.querySelectorAll(selector).forEach(el => el.checked = true);
    } else {
        document.querySelectorAll(selector).forEach(el => el.checked = false);
    }
    checkedCount('#selection-counter', selector);
}
function checkedCount(output_selector, checked_selector)
{
    var count = document.querySelectorAll(checked_selector + ':checked').length;
    if (count == 0) {
        document.querySelector(output_selector).innerHTML = '';
    } else {
        document.querySelector(output_selector).innerHTML = count + ' items selected. These items will be retained in the next search.';
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
function goToList() {
    if (getItems()) {
        $('#actionForm').attr('action', 'ProductListPage.php');
        $('#actionForm').append('<input type="hidden" name="supertype" id="supertype-field" value="upc" />');
        $('#actionForm').submit();
    }
}
function goToReport() {
    goToPage($('#reportURL').val());
}
function goToPage(url) {
    if (getItems()) {
        $('#actionForm').attr('action', url);
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

