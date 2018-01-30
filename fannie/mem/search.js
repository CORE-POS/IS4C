
function runSearch() {
    var dstr = $('#memSearchForm').serialize();
    $('#resultsArea').html('');
    $('#progressBar').show();
    $.ajax({
        data: dstr,
        method: 'post',
    }).error(function (e1, e2, e3) {
        $('#progressBar').hide();
        $('#resultsArea').html(JSON.stringify(e1) + ", " + e2 + ", " + e3);
    }).done(function (resp) {
        $('#progressBar').hide();
        $('#resultsArea').html(resp);   
    });
}
function checkedCount(output_selector, checked_selector) {
    var count = $(checked_selector + ':checked').length;
    if (count == 0) {
        $(output_selector).html('');
    } else {
        $(output_selector).html(count + ' items selected. These items will be retained in the next search.');
    }
}
function toggleAll(elem, selector) {
    if (elem.checked) {
        $(selector).prop('checked', true);
    } else {
        $(selector).prop('checked', false);
    }
    checkedCount('#selection-counter', selector);
}
function sendTo(url) {
    $('#sendForm').html('');
    $('#sendForm').attr('action', url);
    var checks = $('.savedCB:checked');
    if (checks.length > 0) {
        $.each(checks, function (i, o) {
            $('#sendForm').append('<input type="hidden" name="id[]" value="' + o.value + '" />');
        });
        $('#sendForm').submit();
    }
}

