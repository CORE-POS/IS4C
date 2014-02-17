$(document).ready(function() {
    $('input.autodash').keypress(function(event){
        if (event.which >= 48 && event.which <= 57) {
            if ($(this).val().length > 10) {
                return false;
            } else {
                return true; 
            }
        } else if (event.charCode === 0) {
            return true;
        } else {
            return false;
        }
    });
    $('input.autodash').keyup(function(){
        var cur = $(this).val();
        var digits = cur.replace(/\D+/g, '');
        if (digits.length > 3 && digits.length < 6) {
            cur = digits.substring(0, 3) + '-' + digits.substring(3);    
        } else if (digits.length >= 6) {
            cur = digits.substring(0, 3) + '-' + digits.substring(3, 5) + '-' + digits.substring(5);    
        }
        $(this).val(cur);
    });
});

function doReplace()
{
    var one = $('#newVal1').val();
    var two = $('#newVal2').val();
    if (one !== two) {
        $('#replaceInfoLine').html('Error: Values Do Not Match');
    } else if (!one.match(/^\d\d\d-\d\d-\d\d\d\d$/)) {
        $('#replaceInfoLine').html('Error: Value Must Be XXX-XX-XXXX');
    } else {
        $.ajax({
            url: 'GumTaxIdPage.php',
            type: 'post',
            data: 'id='+$('#hidden_id').val()+'&new1='+one+'&new2='+two,
            dataType: 'json',
            success: function(resp) {
                if (resp.errors.length > 0) {
                    alert(resp.errors);
                } else {
                    location = 'GumTaxIdPage.php?id='+$('#hidden_id').val();
                }
            }
        });
    }
}

function viewInfo()
{
    $.ajax({
        url: 'GumTaxIdPage.php',
        type: 'post',
        data: 'id='+$('#hidden_id').val()+'&key='+encodeURIComponent($('#keyarea').val()),
        success: function(resp) {
            $('#tax_id_field').html(resp);
            $('#keyarea').val('');
        }
    });
}
