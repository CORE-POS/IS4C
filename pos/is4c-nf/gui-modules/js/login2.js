
var login2 = (function ($) {
    var mod = {};

    var spinner = function() {
        return '<div class="lds-spinner"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>';
    };

    var showError = function(msg) {
        $('#loginBox').removeClass('coloredArea');
        $('#loginBox').addClass('errorColoredArea');
        $('#loginMsg').html(msg);
    };

    var enableForm = function() {
        $('#formlocal input').prop('disabled', false);
        $('#userPassword').focus();
    };

    mod.tryLogin = function() {
        $('#loginMsg').html(spinner());
        var dstr = $('#formlocal').serialize();
        $('#formlocal input').val('');
        $('#formlocal input').prop('disabled', true);
        $.ajax({
            url: '../ajax/AjaxLogin.php',
            data: dstr,
            method: 'get',
            dataType: 'json'
        }).done(function (resp) {
            if (resp.success && resp.url) {
                location = resp.url;
            } else {
                showError(resp.msg);
                enableForm();
            }
        }).fail(function () {
            showError('Error occurred trying to sign in');
            enableForm();
        });
    };

    return mod;

}(jQuery));

