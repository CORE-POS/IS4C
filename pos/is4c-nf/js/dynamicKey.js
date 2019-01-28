
var dynamicKey = (function ($) {
    var mod = {};

    var inputElement = null;
    var getInput = function() {
        if (inputElement === null) {
            inputElement = $(':input:focus');
        }
        return inputElement;
    };

    mod.setInput = function(elem) {
        inputElement = elem;
    };

    mod.submit = function() {
        if (typeof 'pos2.submitWrapper' == 'function') {
            pos2.submitWrapper();
        } else if (typeof 'submitWrapper' == 'function') {
            submitWrapper();
        } else {
            getInput().closest('form').submit();
        }
    };

    mod.keypress = function(json) {
        json = JSON.parse(json);
        var inp = getInput(); 
        if (!json.append) {
            inp.val('');
        }
        inp.val(inp.val() + '' + json.entry);
        if (inp.submit) {
            mod.submit();
        }
    };

    mod.render = function(json) {
        return '<div class="dynamicKey">'
            + '<button onclick="dynamicKey.keypress(' + JSON.stringify(json) + ');">'
            + json.label
            + '</button>'
            + '</div>';
    };

    return mod;
})(jQuery);

