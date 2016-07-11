var CoreAuth = (function($) {

    var mod = {};

    mod.linea = function() {
        var input = $('#linea-in').val();
        var parts = input.split('  ', 2);
        $('#authUserName').val(parts[0]);
        $('#authPassword').val(parts[1]);
        $('#authUserName').closest('form').submit();
    };

    return mod;

})(jQuery);
