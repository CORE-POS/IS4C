function saveStudent(form, first_name, last_name, card_no, phone, payment, opt_student, opt_phone)
{
    $.ajax({
        type: 'get',
        data: 'first_name='+first_name+
            '&last_name='+last_name+
            '&card_no='+card_no+
            '&phone='+phone+
            '&payment='+payment+
            '&opt_student='+opt_student+
            '&opt_phone='+opt_phone,
        success: function(resp) {
            $('#line-div').html(resp);
            showBootstrapAlert('#instructions-p', 'success', 'Saved changes');
        }
    });
}