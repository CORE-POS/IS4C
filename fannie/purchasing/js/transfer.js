xferPO = (function($) {
    var mod = {};

    mod.sameStore = function(e) {
        if ($('select[name=fromStore]').val() == $('select[name=toStore]').val()) {
            e.preventDefault();
            alert('Must choose two different stores');
            return false;
        }

        return true;
    };

    mod.checkAll = function(elem) {
        var checked = $(elem).prop('checked');
        console.log(checked);
        $('input.checkAll').each(function(){
            $(this).prop('checked', checked);
        });
    };

    return mod;
}(jQuery));
