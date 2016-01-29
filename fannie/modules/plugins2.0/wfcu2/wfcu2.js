function loadStrings(upc)
{
    $.ajax({
        type: 'get',
        data: 'upc='+upc,
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
            window.location.reload();
        }
    });
}
