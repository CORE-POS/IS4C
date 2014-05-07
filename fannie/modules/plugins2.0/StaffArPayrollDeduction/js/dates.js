function addDate()
{
    var newdate = $('#newDate').val();
    $.ajax({
        url: 'StaffArDatesPage.php',
        type: 'get',
        data: 'add='+newdate,
        success: function(resp) {
            $('#mainDisplayDiv').html(resp);
        }
    });
}

function removeDate(id)
{
    $.ajax({
        url: 'StaffArDatesPage.php',
        type: 'get',
        data: 'delete='+id,
        success: function(resp) {
            $('#mainDisplayDiv').html(resp);
        }
    });
}

