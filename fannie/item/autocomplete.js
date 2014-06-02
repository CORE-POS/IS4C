function genericAutoComplete(ws_url, field_name, search_term, callback)
{
    var req = {
        jsonrpc: '2.0',
        method: 'FannieAutoComplete',
        id: new Date().getTime(),
        params: { field: field_name, search: search_term }
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

function bindAutoComplete(identifier, ws_url, field_name)
{
    $(identifier).autocomplete({
        source: function(request, callback) {
            genericAutoComplete(ws_url, field_name, request.term, callback);            
        },
        minLength: 2
    });
}
