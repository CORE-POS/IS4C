var PaycardTransLookupPage = (function($) {
    var mod = {};

    var gettingResult = 1;
    var enter_url = '';
    var clear_url = '';
    mod.performLookup = function() {
        $.ajax({
            type: 'get',  
            data: 'doLookup=1&id='+$('#refNum').val()+'&local='+$('#local').val()+'&mode='+$('#lookupMode').val(),
            dataType: 'json'
        }).done(function(resp) {
            $('.baseHeight').html(resp.output);
            enter_url = resp.confirm_dest;
            clear_url = resp.cancel_dest;
            gettingResult = 0;
        });
    };

    mod.formCallback = function() {
        if (gettingResult === 1) {
            return false;
        }

        var reginput = $('#reginput').val();

        if (reginput === '') {
            window.location = enter_url;
        } else if (reginput.toUpperCase() === 'CL') {
            window.location = clear_url;
        } else {
            $('#reginput').val('');
        }
    };

    return mod;
}(jQuery));
