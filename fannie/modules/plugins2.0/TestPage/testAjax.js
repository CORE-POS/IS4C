function test(first_name, last_name)
{
    $.ajax({
        type: 'get',
        data: 'first_name='+first_name+
            '&last_name='+last_name,
        success: function(resp) {
            $('#ajax-resp').html('AJAX CALL RETURNED: ' + resp);
        }
    });
}