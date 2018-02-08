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

    mod.canned = function(cannedID) {
        if (cannedID == 0) {
            $('#resp-ta').val('');
            $('#resp-ta').trigger('keyup');
            return;
        }
        $.ajax({
            url: 'ManageComments.php',
            method: 'get',
            data: 'canned=' + cannedID
        }).done(function (resp) {
            $('#resp-ta').val(resp);
            $('#resp-ta').trigger('keyup');
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
     
    mod.savePNN = function(commentID, pnn) {
        $.ajax({
            url: 'ManageComments.php',
            method: 'post',
            data: 'id='+commentID+'&pnn='+pnn
        }).done(function (resp) {
            showBootstrapAlert('#alertArea', 'success', 'Saved type rating');
        }).fail(function () {
            showBootstrapAlert('#alertArea', 'danger', 'Error saving type rating');
        });
    };

    mod.saveTags = function(commentID, tags) {
        $.ajax({
            url: 'ManageComments.php',
            method: 'post',
            data: 'id='+commentID+'&tags='+encodeURIComponent(tags)
        }).done(function (resp) {
            showBootstrapAlert('#alertArea', 'success', 'Saved tags');
            $('#tagLinks').html(resp);
        }).fail(function () {
            showBootstrapAlert('#alertArea', 'danger', 'Error tags');
        });
    };

    mod.autoTag = function(tags) {
        $('#myTags').autocomplete({
            source: function(req, callback) {
                if (req.term.indexOf(',') != -1) {
                    var tmp = req.term.split(',');
                    req.term = tmp[tmp.length - 1].trim();
                }
                if (req.term.length >= 2) {
                    callback(tags.filter(t => t.indexOf(req.term) != -1));
                } else {
                    callback([]);
                }
            },
            select: function(ev, ui) {
                var newVal = ui.item.value;
                var current = $('#myTags').val().toLowerCase();
                if (current.indexOf(newVal) == -1) {
                    var tmp = current.split(',');
                    tmp = tmp.map(t => t.trim());
                    var combine = '';
                    for (var i=0; i<tmp.length - 1; i++) {
                        combine += tmp[i] + ', ';
                    }
                    combine += newVal;
                    $('#myTags').val(combine);
                }
                ev.preventDefault();
                return false;
            }
        });
    };

    return mod;

}(jQuery));
