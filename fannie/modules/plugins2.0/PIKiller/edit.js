var piJS = (function($) {
    var mod = {};

    var extractFields = function(data) {
        var pieces = data.split("^");
        var ret = {};

        ret.state = pieces[0].substring(1,3);
        ret.city = pieces[0].substring(3);
        ret.addr = pieces[2];
        var names = pieces[1].split(" ");
        ret.fname = names[0];
        ret.lname = names[names.length-1];

        return ret;
    };

    var updateFields = function(info) {
        console.log(info);
        if (info.fname) {
            $('input[name=FirstName]').val(info.fname);
        }
        if (info.lname) {
            $('input[name=LastName]').val(info.lname);
        }
        if (info.addr) {
            $('input[name=address1]').val(info.addr);
        }
        if (info.city) {
            $('input[name=city]').val(info.city);
        }
        if (info.state) {
            $('input[name=state]').val(info.state);
        }
    };

    var reg = /%.+\?/;

    mod.nosubmit = function(e) {
        if (e.which === 13 && reg.test($(this).val())) {
            e.preventDefault();
            var newval = mod.changeFunc($(this).val());
            if (reg.test($(this).val())) {
                $(this).val(newval);
            }
            return false;
        }

        return true;
    };

    mod.changeFunc = function(val) {
        if (reg.test(val)) {
            var arr = reg.exec(val);
            var info = extractFields(arr[0]);
            updateFields(info); 
            var newval = val.replace(arr[0], '');
            return newval;
        }
        return val;
    };

    return mod;
})(jQuery);
