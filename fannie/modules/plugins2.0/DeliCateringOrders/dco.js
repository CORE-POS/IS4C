
function autoFill()
{
    var x = document.getElementById("orderform");
    var card_no = x.elements[4].value;
    $.ajax({
        type: 'get',
        url: 'DeliCateringAjax.php',
        dataType: 'json',
        data: 'card_no='+card_no,
        error: function(xhr, status, error)
        { 
            alert('error:' + status + ':' + error + ':' + xhr.responseText) 
        },
        success: function(response)
        {
        }
    })
    .done(function(data){
        if (data.name) {
            $('#name').val(data.name);
        }
        if (data.phone) {
            $('#phone').val(data.phone);
        }
        if (data.altPhone) {
            $('#altPhone').val(data.altPhone);
        }
        if (data.email) {
            $('#email').val(data.email);
        }
    })
}

