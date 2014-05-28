function genericAutoComplete(ws_url, field_name, search_term, callback)
{
    var req = {
        service: 'FannieAutoComplete',
        field: field_name,
        search: search_term
    };

    $.ajax({
        url: ws_url,
        type: 'post',
        data: JSON.stringify(req),
        dataType: 'json',
        contentType: 'application/json',
        success: function(data) {
            callback(data);
        },
        error: function() {
            callback([]);
        }
    });
}

function bindAutoComplete(identifier, ws_url, field_name)
{
    $(identifier).autocomplete({
        source: function(request, callback) {
            genericAutoComplete(ws_url, field_name, request.term, callback);            
        },
        minLength: 2
    });
}
