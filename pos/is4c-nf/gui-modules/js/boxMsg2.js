var boxMsg2 = (function($, errorLog) {
    var mod = {};

    var changePage = function(data, cmd) {
        var changeTo = data.dest_page;
        if (!data.cleared) {
            changeTo += "?reginput=" + encodeURIComponent(cmd);
            changeTo += "&repeat=1";
        }
        window.location = changeTo;
    };

    var endorse = function(endorseType, endorseAmt, callback) {
        $.ajax({
            url: '../ajax/AjaxEndorse.php',
            type: 'get',
            data: 'type='+endorseType+'&amount='+endorseAmt,
            cache: false
        }).done(callback);
    };

    mod.submitWrapper = function(urlStem) {
        var str = $('#reginput').val();
        var endorseType = $('#endorseType').val();
        var endorseAmt = $('#endorseAmt').val();
        var cmd = $('#repeat-cmd').val();
        $.ajax({
            url: urlStem + 'ajax/AjaxDecision.php',
            type: 'get',
            data: 'input='+str,
            dataType: 'json',
            cache: false
        }).done(function(data) {
            if (!data.cleared && endorseType != ''){
                endorse(endorseType, endorseAmt, function() {
                    changePage(data, cmd);
                });
            } else {
                changePage(data, cmd);
            }
        }).fail(function(xhr, statusText, err) {
            errorLog.show(xhr, statusText, err);
        });
        return false;
    };

    return mod;

}(jQuery, errorLog));
