var pos2 = (function($, errorLog) {
    var mod = {};

    var urlStem = '../';
    mod.setUrlStem = function(stem) {
        urlStem = stem;
    };

    var lines = 11;
    mod.setNumLines = function(num) {
        lines = Number(num);
    };

    mod.keydown = function(ev) {
        switch(ev.which) {
            case 33:
                parseWrapper('U'+lines);
                break;
            case 38:
                parseWrapper('U');
                break;
            case 34:
                parseWrapper('D'+lines);
                break;
            case 40:
                parseWrapper('D');
                break;
            case 9:
                parseWrapper('TFS');
                return false;
            case 69:
            case 101:
                return mod.getScaleWeight();
        }
    };

    /**
      Replace instances of 'SCAL' with the scale's weight. The command
      is triggered by the E keypress but that letter is never actually
      added to the input.
    */
    mod.getScaleWeight = function() {
        var current_input = $('#reginput').val().toUpperCase();
        if (current_input.indexOf('SCAL') !== -1) {
            var wgt = $.trim($('#scaleBottom').html());
            wgt = parseFloat(wgt);
            if (isNaN(wgt) || wgt == 0.00) {
                // weight not available
                return true;
            }
            var new_input = current_input.replace('SCAL', wgt);
            $('#reginput').val(new_input);
            
            return false;
        }

        return true;
    };

    mod.inputRetry = function(str) {
        parseWrapper(str);
    };

    var lockScreen = function() {
        window.location = urlStem + 'gui-modules/login3.php';
    };

    var screenLockVar;
    var saveTimeout;
    mod.enableScreenLock = function(timeout){
        if (typeof timeout === 'undefined') {
            timeout = saveTimeout;
        } else {
            saveTimeout = timeout;
        }
        if (timeout) {
            screenLockVar = setTimeout(lockScreen, Number(timeout));
        }
    };

    mod.receiptFetch = function(r_type, ref){
        $.ajax({
            url: urlStem + 'ajax/AjaxEnd.php',
            type: 'get',
            data: 'receiptType='+r_type+'&ref='+ref,
            dataType: 'json',
            cache: false
        }).done(function(data) {
            if (data.sync){
                ajaxTransactionSync(urlStem);
            }
            if (data.error) {
                var icon = $('#receipticon').attr('src');
                var newicon = icon.replace(/(.*graphics)\/.*/, "$1/deadreceipt.gif");
                $('#receipticon').attr('src', newicon);
            }
        }).fail(function(xhr, statusText, err) {
            var icon = $('#receipticon').attr('src');
            var newicon = icon.replace(/(.*graphics)\/.*/, "$1/deadreceipt.gif");
            $('#receipticon').attr('src', newicon);
            errorLog.show(xhr, statusText, err);
        });
    };

    mod.submitWrapper = function(){
        var str = $('#reginput').val();
        $('#reginput').val('');
        clearTimeout(screenLockVar);
        runParser(str, urlStem);
        mod.enableScreenLock();
        return false;
    };

    return mod;
}(jQuery, errorLog));
