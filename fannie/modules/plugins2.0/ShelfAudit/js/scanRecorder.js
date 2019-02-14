var scanRecorder = (function ($) {

    var prefix = "SaScan_";
    var mod = {};

    var getInput = function() {
        var ret = '';
        if ($('#upc_in').val() != '') {
            ret = $('#upc_in').val();
            $('#upc_in').val('');
        } else if ($('#socketm').val() != '') {
            ret = $('#socketm').val();
            $('#socketm').val('');
            ret = ret.substring(0, ret.length - 1);
        }

        return ret;
    };

    var strip = function(str) {
        return str.substring(prefix.length);
    };

    var prefixed = function(str) {
        return (str.substring(0, prefix.length) == prefix);
    };

    mod.redisplay = function() {
        var items = mod.getItems();
        var html = '';
        for (var i=0; i<items.length; i++) {
            html += items[i].count;
            html += '&nbsp;&nbsp;&nbsp;&nbsp;';
            html += items[i].upc;
            html += '<br />';
        }
        $('#scan-data').html(html);
    };

    mod.scan = function() {
        console.log('here');
        var upc = getInput();
        if (upc) {
            if (!localStorage.getItem(prefix + upc)) {
                localStorage.setItem(prefix + upc, 1);
            } else {
                var cur = localStorage.getItem(prefix + upc);
                localStorage.setItem(prefix + upc, (cur*1) + 1);
            }
            mod.redisplay();
        }
    };

    mod.getItems = function() {
        var ret = [];
        for (var i=0; i<localStorage.length; i++) {
            var key = localStorage.key(i);
            if (!prefixed(key)) {
                continue;
            }
            var count = localStorage.getItem(key);
            ret.push({ upc: strip(key), count: count });
        }

        return ret;
    };

    mod.clear = function() {
        var clearKeys = [];
        for (var i=0; i<localStorage.length; i++) {
            var key = localStorage.key(i);
            if (prefixed(key)) {
                clearKeys.push(key);
            }
        }
        for (var i=0; i<clearKeys.length; i++) {
            localStorage.removeItem(clearKeys[i]);
        }
        mod.redisplay();
    }

    mod.emit = function() {
        var items = JSON.stringify(mod.getItems());
        $.ajax({
            type: 'post',
            data: 'json='+encodeURI(items)
        }).done(function (resp) {
            $('#scan-data').html('<h1>Email Sent</h1>');
            setTimeout(function () { mod.redisplay(); }, 3000);
        });
    };

    return mod;

})(jQuery);
