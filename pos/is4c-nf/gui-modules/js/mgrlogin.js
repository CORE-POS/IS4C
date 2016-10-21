var mgrlogin = (function($, errorLog) {
    var mod = {};

    var cancelOrder = function(data, urlStem) {
        $.ajax({
            url: urlStem + 'ajax/AjaxEnd.php',
            type: 'get',
            data: 'receiptType=cancelled&ref='+data.trans_num,
            cache: false
        }).fail(function(xhr, statusText, err) {
            errorLog.show(xhr, statusText, err);
        }).done(function(data) {
            window.location = urlStem + 'gui-modules/pos2.php';
        });
    };

    var showError = function(data) {
        $('div#cancelLoginBox').removeClass('coloredArea');
        $('div#cancelLoginBox').addClass('errorColoredArea');
        $('span.larger').html(data.heading);
        $('span#localmsg').html(data.msg);
        $('#userPassword').val('');
        $('#userPassword').focus();
    };

    mod.submitWrapper = function(urlStem) {
        var passwd = $('#reginput').val();
        if (passwd == ''){
            passwd = $('#userPassword').val();
        }
        $.ajax({
            data: 'input='+passwd,
            type: 'get',
            cache: false,
            dataType: 'json'
        }).fail(function(xhr, statusText, err) {
            errorLog.show(xhr, statusText, err);
        }).done(function(data) {
            if (data.cancelOrder){
                cancelOrder(data, urlStem);
            } else if (data.giveUp){
                window.location = urlStem + 'gui-modules/pos2.php';
            } else {
                showError(data);
            }
        });

        return false;
    };

    return mod;
}(jQuery, errorLog));
