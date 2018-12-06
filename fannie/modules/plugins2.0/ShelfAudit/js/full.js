var full = (function ($) {
    var mod = {};

    var lastSearch = 0;

    mod.setFilter = function(f) {
        if (f == "") {
            EXTRA_AUTO_COMPLETE_PARAMS = {};
        } else {
            EXTRA_AUTO_COMPLETE_PARAMS = { superID: f };
        }
    };

    mod.search = function() {
        if ($('#upc').val().length > 0) {
            transferring = true;
            $.ajax({
                type: 'post',
                data: $('#searchform').serialize()
            }).fail(function (xhr, st, err) {
                $('#results').html('<div class="alert alert-danger">Error (' + st + ',' + err + ')</div>');
                transferring = false;
            }).done(function (resp) {
                lastSearch = new Date().getTime();
                console.log(lastSearch);
                $('#results').html(resp); 
                $('#newQty').focus();
                $('#upc').val('');
            });
        }
    };

    mod.keybind = function(ev) {
        if (new Date().getTime() > (100 + lastSearch)) {
            if (ev.which == 13) {
                $('#upc').focus();
            }
            var cur = $('#lastQty').val();
            var qty = $('#newQty').val();
            var next = (cur*1) + (qty*1);
            $('#curQty').html(next);
            $.ajax({
                type: 'post', 
                data: $('#results input').serialize()+"&real="+next
            }).done(function (resp) {
                $('#recent').html(resp);
                console.log(resp);
            });
        }
    };

    return mod;

})(jQuery);
