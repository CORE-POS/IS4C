var genericBilling = (function($) {
    var mod = {};

    mod.getMemInfo = function(){
        $.ajax({
            url: 'GenericBillingPage.php?id='+$('#memnum').val(),
            type: 'get'
        }).done(function(resp){
            $('#contentArea').html(resp);
            $('#resultArea').html('');
        });
    };

    mod.postBilling = function() {
        var data = 'id='+$('#form_memnum').val();
        data += '&amount='+$('#amount').val();
        data += '&desc='+$('#desc').val();
        $.ajax({
            url: 'GenericBillingPage.php',
            type: 'post',
            data: data,
            dataType: 'json'
        }).done(function(resp){
            if (resp.billed) {
                $('#contentArea').html('');
                showBootstrapAlert('#resultArea', 'success', resp.msg);
            } else {
                showBootstrapAlert('#resultArea', 'danger', resp.msg);
            }
        });
    };

    return mod;
}(jQuery));
