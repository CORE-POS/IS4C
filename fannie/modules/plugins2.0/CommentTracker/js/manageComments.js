var manageComments = (function($) {

    var mod = {};

    mod.sendMsg = function() {
        $('#resp-ta').keyup(function() {
            if ($('#resp-ta').val().length > 0) {
                $('#sending-msg').html('Response will be emailed to the customer')
                    .addClass('alert-warning')
                    .removeClass('alert-info');
                $('#send-btn').prop('disabled', false);
            } else {
                $('#sending-msg').html('Nothing will be emailed to the customer')
                    .addClass('alert-info')
                    .removeClass('alert-warning');
                $('#send-btn').prop('disabled', true);
            }
        });
    };

    mod.sendBtn = function() {
        $('#resp-ta').keyup(function() {
            if ($('#resp-ta').val().length > 0) {
                $('#send-btn').prop('disabled', false);
            } else {
                $('#send-btn').prop('disabled', true);
            }
        });
    };

    mod.saveCategory = function(commentID, catID) {
        $.ajax({
            url: 'ManageComments.php',
            method: 'post',
            data: 'id='+commentID+'&catID='+catID
        }).done(function (resp) {
            showBootstrapAlert('#alertArea', 'success', 'Saved category');
        }).fail(function () {
            showBootstrapAlert('#alertArea', 'danger', 'Error saving category');
        });
    };

    mod.saveAppropriate = function(commentID, appr) {
        $.ajax({
            url: 'ManageComments.php',
            method: 'post',
            data: 'id='+commentID+'&appropriate='+(appr ? '1' : '0')
        }).done(function (resp) {
            showBootstrapAlert('#alertArea', 'success', 'Saved appropriateness');
        }).fail(function () {
            showBootstrapAlert('#alertArea', 'danger', 'Error saving appriprateness');
        });
    };

    return mod;

}(jQuery));
