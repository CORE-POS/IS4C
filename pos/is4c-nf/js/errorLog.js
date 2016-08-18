var errorLog = (function ($) {

    var mod = {};

    mod.log = function(str, urlStem) {
        $.ajax({
            url: urlStem+'/ajax/AjaxJsError.php',
            data: 'data='+str,
            async: false
        });
    };

    mod.show = function(xhr, statusText, err) {
        try {
            var resp = JSON.parse(xhr.responseText);
            if (resp.error) {
                $('#jsErrorLog').append(resp.error+"<br />");
            } else {
                throw "No error in response body";
            }
        } catch (ex) {
            $('#jsErrorLog').append(statusText+" - "+err+"<br />");
        }
    };

    return mod;

}(jQuery));

