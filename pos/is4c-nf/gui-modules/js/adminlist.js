var adminlist = (function($) {
    var mod = {};

    mod.suspendOrder = function(ref) {
        $.ajax({
            type: 'post',
            url: '../ajax-callbacks/AjaxEnd.php',
            cache: false,
            data: 'receiptType=suspended&ref=' + ref,
            dataType: 'json'
        }).always(function() {
            window.location = '../gui-modules/pos2.php';
        });

    };

    return mod;
}(jQuery));
