
var priceCheck = (function ($) {

    var mod = {};

    mod.showDefault = function() {
        $('#pc-results').html('<div class="alert alert-info h2">Price Check</div>');
    };

    var timeout;

    mod.search = function() {
        $.ajax({
            url: 'PriceCheckPage.php',
            data: $('#pc-upc').serialize()
        }).done(function (resp) {
            $('#pc-results').html(resp);
        }).always(function() {
            $('#pc-upc').val('')
            $('#pc-upc').focus();
            if (timeout) {
                clearTimeout(timeout);
            }
            timeout = setTimeout(mod.showDefault, 15000);
        });
    };

    return mod;

}(jQuery));
