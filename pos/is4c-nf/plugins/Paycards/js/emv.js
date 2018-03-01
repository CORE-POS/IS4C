var emv = (function($){
    var mod = {};

    var serverURL = 'http://localhost:8999';
    mod.setURL = function(url) {
        serverURL = url;
    };

    var finishTrans = function(resp) {
        // POST result to PHP page in POS to
        // process the result.
        $('div.baseHeight').html('Finishing transaction');
        var f = $('<form id="js-form"></form>');
        f.append($('<input type="hidden" name="xml-resp" />').val(resp));
        $('body').append(f);
        $('#js-form').submit();
    };

    var errorTrans = function(xhr, stat, err) {
        // display error to user?
        // go to dedicated error page?
        $('div.baseHeight').html('Finishing transaction');
        var f = $('<form id="js-form"></form>');
        var resp = 'error';
        if (xhr.responseXml !== null && xhr.responseXml !== '') {
            resp = xhr.responseXml;
        } else if (xhr.responseText !== null && xhr.responseText !== '') {
            resp = xhr.responseText;
        }
        f.append($('<input type="hidden" name="xml-resp" />').val(resp));
        f.append($('<input type="hidden" name="err-info" />').val(JSON.stringify(xhr)+'-'+stat+'-'+err));
        $('body').append(f);
        $('#js-form').submit();
    };

    mod.submit = function(xmlData) {
        $.ajax({
            url: serverURL,
            type: 'POST',
            data: xmlData,
            dataType: 'text'
        }).done(finishTrans).fail(errorTrans);
    };

    var updateProcessing = function() {
        var content = $('div#emvProcText').html() + '.';
        if (content.length >= 23) {
            content = 'Waiting for response.';
        }
        $('div#emvProcText').html(content);
        setTimeout(updateProcessing, 1000);
    };

    mod.showProcessing = function(elem) {
        var wrapper = '<div class="coloredArea centerOffset centeredDisplay rounded">';
        var spinner = '<div class="lds-spinner"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>';
        var testDiv = '<div id="emvProcText">Waiting for response</div>';
        var all = wrapper + testDiv + spinner + '</div>';
        $(elem).html(all);
        updateProcessing();
    };

    return mod;
}(jQuery));
