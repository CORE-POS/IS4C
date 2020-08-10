var scaleItem = (function($) {
    var mod = {};
    
    mod.countField = function(field, output) {
        var txt = document.getElementById(field).value;
        txt = txt.replace(/\r/g, "");
        txt = txt.replace(/\n/g, "");

        document.getElementById(output).innerHTML = txt.length;
    };

    mod.appendScaleTag = function(tag) {
        var current = document.getElementById('s_text').value;
        current += "{" + tag + "}";
        document.getElementById('s_text').value = current;
    };

    return mod;
}());
