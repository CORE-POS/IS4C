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
        if (xhr.responseXml !== null) {
            f.append($('<input type="hidden" name="xml-resp" />').val(xhr.responseXml));
        } else if (xhr.responseText !== null) {
            f.append($('<input type="hidden" name="xml-resp" />').val(xhr.responseText));
        } else {
            f.append($('<input type="hidden" name="xml-resp" />').val(''));
        }
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
