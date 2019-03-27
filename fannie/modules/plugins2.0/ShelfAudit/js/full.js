var full = (function ($) {
    var mod = {};

    var lastSearch = 0;

    var repaint = function(a, b) {
        $(a).addClass('focused');
        $(b).removeClass('focused');
    };

    mod.setFilter = function(f) {
        if (f == "") {
            EXTRA_AUTO_COMPLETE_PARAMS = { lc: true };
        } else {
            EXTRA_AUTO_COMPLETE_PARAMS = { lc: true, superID: f };
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
                $('#results').html(resp); 
                $('#upc').val('');
                if ($('#newQty').length > 0) {
                    $('#newQty').focus();
                    repaint('#newQty', '#upc');
                }
            });
        } else {
            $('#results').html('');
        }
    };

    mod.tab = function(ev) {
        if (ev.which == 9) {
            ev.preventDefault();
            $('#upc').focus();
            repaint('#upc', '#newQty');
        }
    };

    var saveDelay = 0;

    mod.keybind = function(ev) {
        repaint('#newQty', '#upc');
        if (new Date().getTime() > (100 + lastSearch)) {
            if (ev.which == 13) {
                $('#upc').focus();
                repaint('#upc', '#newQty');
            }
            var cur = $('#lastQty').val();
            var qty = $('#newQty').val();
            var next = (cur*1) + (qty*1);
            $('#curQty').html(next);
            if (saveDelay) {
                clearTimeout(saveDelay);
            }
            saveDelay = setTimeout(function() {
                $.ajax({
                    type: 'post', 
                    data: $('#results input').serialize()+"&real="+next
                }).done(function (resp) {
                    $('#recent').html(resp);
                });
            }, 50);
        }
    };

    mod.autosubmit = function(ev, ui) {
        $('#upc').val(ui.item.value);
        mod.search();
        console.log("Current: " + $('#upc').val());
    };

    mod.incDec = function(amt) {
        var cur = $('#newQty').val();
        var next = (cur*1) + amt;
        $('#newQty').val(next);
        $('#newQty').trigger('keyup');
    };

    mod.resetQty = function() {
        $('#newQty').val('');
        $('#newQty').trigger('keyup');
    };

    return mod;

})(jQuery);
