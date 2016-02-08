var wayback = (function($) {
    var mod = {};

    mod.submitWrapper = function(e)
    {
        $('#wayback-form').prop('disabled', true);
        var dataStr = $('#wayback-form').serialize();
        $('#wayback-form').prop('disabled', false);
        e.preventDefault();

        var currentDate = new Date();
        var endDate = new Date($('input[name=date]').val());
        $('#wayback-table').html('');
        $('#progress-bar').show();
        while (currentDate >= endDate) {
            $.ajax({
                url: 'WaybackMachine.php',
                data: dataStr + '&current='+currentDate.toISOString(),
                dataType: 'json',
                async: false
            }).done(function(resp) {
                resp.forEach(function(row) {
                    var tRow = $('<tr>');   
                    row.forEach(function(item) {
                        var td = $('<td>').html(item);
                        tRow.append(td);
                    });
                    $('#wayback-table').append(tRow);
                });
            });
            currentDate.setMonth(currentDate.getMonth()-1);
        }
        $('#progress-bar').hide();

        return false;
    }

    return mod;
}(jQuery));

$(document).ready(function() {
    $('#wayback-form').submit(wayback.submitWrapper);
});
