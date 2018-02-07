var qlImg = (function ($) {
    
    var mod = {};

    mod.showForm = function(id) {
        if (id == '') {
            $('#entryArea').html('');
            return;
        }
        $.ajax({
            url: 'QuickLookupsImages.php',
            data: 'id='+id+'&form=1',
        }).success(function (resp) {
            $('#entryArea').html(resp);
        });
    };

    return mod;

}(jQuery));
