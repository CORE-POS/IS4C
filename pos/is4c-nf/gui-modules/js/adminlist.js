var adminlist = (function($, errorLog) {
    var mod = {};

    mod.suspendOrder = function(ref) {
        $.ajax({
            type: 'post',
            url: '../ajax/AjaxEnd.php',
            cache: false,
            data: 'receiptType=suspended&ref=' + ref,
            dataType: 'json'
        }).fail(function(xhr, statusText, err) {
            errorLog.show(xhr, statusText, err);
        }).always(function() {
            window.location = '../gui-modules/pos2.php';
        });

    };

    return mod;
}(jQuery, errorLog));
