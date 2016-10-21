var laneText = (function($) {
    var mod = {};
    
    mod.loadStrings = function(type) {
        $.ajax({
            type: 'get',
            data: 'type='+type
        }).done(function(resp) {
            $('#line-div').html(resp);
        });
    }

    mod.saveString = function(form) {
        if ($('input[name="newLine"]').length == 0) {
            return false;
        }

        var dstr = $(form).serialize();
        $.ajax({
            type: 'post',
            data: dstr
        }).done(function(resp) {
            $('#line-div').html(resp);
            showBootstrapAlert('#instructions-p', 'success', 'Saved changes');
        });
    }

    return mod;
}(jQuery));
