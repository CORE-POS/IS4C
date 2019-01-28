function issueWarning() {
    return confirm('Confirm: issuing an check');
}
function issueCheck(id) {
    if ($('#issueCheckbox').prop('checked')) {
        location = 'GumLoanPayoffPage.php?id=' + id + '&pdf=1&issued=1';
        $('#issueCheckbox').attr('disabled', 'disabled');
        var d = new Date();
        $('#issueDate').html(d.toISOString());
    } 
}
