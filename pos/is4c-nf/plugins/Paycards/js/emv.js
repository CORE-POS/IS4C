var emv = (function($){
    var mod = {};

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
            url: 'http://localhost:8999',
            type: 'POST',
            data: xmlData,
            dataType: 'text'
        }).done(finishTrans).fail(errorTrans);
    };

    return mod;
}(jQuery));
