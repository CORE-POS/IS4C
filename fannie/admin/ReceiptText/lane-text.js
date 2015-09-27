function loadStrings(type)
{
    $.ajax({
        type: 'get',
        data: 'type='+type,
        success: function(resp) {
            $('#line-div').html(resp);
        }
    });
}

function saveString(form)
{
    if ($('input[name="newLine"]').length == 0) {
        return false;
    }

    var dstr = $(form).serialize();
    $.ajax({
        type: 'post',
        data: dstr,
        success: function(resp) {
            $('#line-div').html(resp);
            showBootstrapAlert('#instructions-p', 'success', 'Saved changes');
        }
    });
}
