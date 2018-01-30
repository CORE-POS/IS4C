
var logging = (function ($) {

    var mod = {};

    mod.refreshLog = function() {
        $.ajax({
            url: 'noauto/LogRefresh.php'
        }).done(function (resp) {
            $('#log').html(resp);
        });
    }

    mod.runtimeError = function() {
        not_a_function();
    };

    mod.phpFatal = function() {
        $.ajax({
            url: 'noauto/PhpFatal.php'
        }).always(function() {
            mod.refreshLog();
        });
    };

    mod.phpSyntax = function() {
        $.ajax({
            url: 'noauto/PhpSyntaxWrapper.php'
        }).always(function() {
            mod.refreshLog();
        });
    };

    mod.sqlError = function() {
        $.ajax({
            url: 'noauto/SqlError.php'
        }).always(function() {
            mod.refreshLog();
        });
    };

    mod.phpNotice = function() {
        $.ajax({
            url: 'noauto/PhpNotice.php'
        }).always(function() {
            mod.refreshLog();
        });
    };

    return mod;

}(jQuery));

