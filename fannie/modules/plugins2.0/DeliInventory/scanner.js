var scanner = (function ($) {
    var mod = {};

    var lastSearch = 0;

    var repaint = function(a, b, c) {
        $(a).addClass('focused');
        $(b).removeClass('focused');
        $(c).removeClass('focused');
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
                    repaint('#newQty', '#upc', '#newCases');
                }
            });
        } else {
            $('#results').html('');
        }
    };

    mod.tabQty = function(ev) {
        if (ev.which == 9) {
            ev.preventDefault();
            $('#newCases').focus();
            repaint('#newCases', '#newQty', '#upc');
        }
    };

    mod.tabCases = function(ev) {
        if (ev.which == 9) {
            ev.preventDefault();
            $('#upc').focus();
            repaint('#upc', '#newQty', '#newCases');
        }
    };


    mod.keybindQty = function(ev) {
        if (new Date().getTime() > (100 + lastSearch)) {
            if (ev.which == 13) {
                $('#upc').focus();
                repaint('#upc', '#newQty', '#newCases');
            }
            var cur = $('#lastQty').val();
            var qty = $('#newQty').val();
            var next = (cur*1) + (qty*1);
            $('#curQty').html(next);
            $.ajax({
                type: 'post', 
                data: $('#results input').serialize()+"&realQty="+next
            }).done(function (resp) {
                $('#recent').html(resp);
            });
        }
    };

    mod.keybindCases = function(ev) {
        if (new Date().getTime() > (100 + lastSearch)) {
            if (ev.which == 13) {
                $('#upc').focus();
                repaint('#upc', '#newQty', '#newCases');
            }
            var cur = $('#lastCases').val();
            var qty = $('#newCases').val();
            var next = (cur*1) + (qty*1);
            $('#curCases').html(next);
            $.ajax({
                type: 'post', 
                data: $('#results input').serialize()+"&realCases="+next
            }).done(function (resp) {
                $('#recent').html(resp);
            });
        }
    };

    mod.autosubmit = function(ev, ui) {
        $('#upc').val(ui.item.value);
        mod.search();
        console.log("Current: " + $('#upc').val());
    };

    mod.autocomplete = function(selector) {
        $(selector).autocomplete({
            source: function(request, callback) {
                $.ajax({
                    url: 'DIScanner.php',
                    type: 'get',
                    data: 'search=' + request.term,
                    dataType: 'json'
                }).done(function (data) {
                    callback(data);
                }).fail(function () {
                    callback([]);
                });
            },
            minLength: 2
        });
    };

    return mod;

})(jQuery);
