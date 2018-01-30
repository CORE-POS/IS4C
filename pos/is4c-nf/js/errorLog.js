var errorLog = (function ($) {

    var mod = {};

    /**
      Log a message via AJAX handler
      @param str [string] log message
      @param urlStem [string] path to pos/is4c-nf/
    */
    mod.log = function(str, urlStem) {
        $.ajax({
            url: urlStem+'/ajax/AjaxJsError.php',
            data: 'data='+str,
            async: false
        });
    };

    /**
      Display an AJAX error on screen
    */
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

    /**
      Attach logging to the window.onerror event.
      @param urlStem [string] path to pos/is4c-nf/
    */
    mod.register = function(urlStem) {
        window.onerror = function(msg, pageURL, lineNo, colNo, error) {
            var logEntry = { 
                message: msg,
                url: pageURL,
                line: lineNo,
                col: colNo,
                detail: error
            };
            mod.log(JSON.stringify(logEntry), urlStem);
        };
    };

    return mod;

}(jQuery));

