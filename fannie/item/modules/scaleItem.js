var scaleItem = (function() {
    var mod = {};
    
    mod.countField = function(field, output) {
        var txt = $('.' + field + ':visible').val();
        var countable = txt.replace(/\r/g, "");
        countable = countable.replace(/\n/g, "");

        if ($('#si_sync').prop('checked')) {
            $('.' + field + ':hidden').val(txt);
        }

        document.getElementById(output).innerHTML = countable.length;
    };

    mod.appendScaleTag = function(tag) {
        var current = $('.s_text:visible').val();
        current += "{" + tag + "}";
        $('.s_text:visible').val(current);
        mod.countField('s_text', 'expLength');
    };

    return mod;
}());
