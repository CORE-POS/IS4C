var cablist = (function($, errorLog) {
    var mod = {};

    mod.submitWrapper = function(urlStem) {
        var ref = $('#selectlist').val();
        if (ref != ""){
            $.ajax({
                url: urlStem + 'ajax/AjaxCabReceipt.php',
                type: 'get',
                cache: false,
                data: 'input='+ref
            }).fail(function(xhr, statusText, err) {
                errorLog.show(xhr, statusText, err);
            }).done(function(data){
                window.location = urlStem + 'gui-modules/pos2.php';
            });
        } else {
            window.location = urlStem+'gui-modules/pos2.php';
        }

        return false;
    };

    return mod;
}(jQuery, errorLog));
