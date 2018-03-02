$('#classSelector').on('change', function(){
    document.forms['classSelector'].submit();
});
function itemEditing(size)
{
    $('.editable').change(function(){
        var current_seat = $(this).closest('tr').find('.id').html();
        $(this).prev('span.collapse').html($(this).val());
        $('.tablesorter').trigger('update');
        var elem = $(this);
        var orig = this.defaultValue;
        $.ajax({
            type: 'post',
            url: 'registryUpdate.php',
            dataType: 'json',
            data: 'upc='+$('#upc').val()+'&seat='+current_seat+'&field='+$(this).attr('name')+'&value='+$(this).val()+'&size='+size,
            success: function(resp) {
                if (resp.error) {
                    showBootstrapAlert('#alert-area', 'danger', resp.error_msg);
                } else {
                    showBootstrapPopover(elem, orig, '');
                    getNumSeats();
                }
            }
        });
    });
}
function getNumSeats()
{
    var upc = $('#curUpc').val();
    var notified = $('#notified').val();
    var soldOut = $('#soldOut').val();
    var n = 0;
    var seats = 0;
    var className = $('#className').val();
    var expires = $('#classExpires').val();
    var y = 20+upc.substring(11,13);
    var m = upc.substring(7,9);
    var d = upc.substring(9,11);
    var classDate = y+'-'+m+'-'+d+' 00:00:00';
    var newDate = $('#newDate').val();
    $('input').each(function(){
        var tablename = $(this).closest('table').attr('name');
        var name = $(this).attr('name');
        if (name == 'editFirst' && tablename == 'ClassRegistry') {
            seats++;
            var v = $(this).val();
            if (v) {
                n++;
            }
        }
    });
    if (n > 4 && classDate == expires) {
        //modify expiration date 
        $.ajax({ 
            type: 'post',
            data:
                'upc='+upc+
                '&newDate='+newDate,
            success: function(resp) {
                $('#classExpires').val(newDate);
            }
        });
    }
    if (n < seats && (n+3) >= seats && notified == 0) {
        //send notification that class is almost full.
        $.ajax({ 
            type: 'post',
            data:
                'upc='+upc+
                '&notify=1'+
                '&seats='+seats+
                '&n='+n+
                '&className='+className+
                '&soldOut='+soldOut,
            success: function(resp) {
                $('#notified').val(1);
            }
        });
    } else if (n >= seats && soldOut == 0) {
        // remove class from website and send sold-out notification.
        $.ajax({
            type: 'post',
            data:
                'upc='+upc+
                '&sellOut=1'+
                '&seats='+seats+
                '&n='+n+
                '&className='+className+
                '&notified='+notified,
            success: function(resp) {
                if (resp.error) {
                    showBootstrapAlert('#alert-area', 'danger', resp.error_msg);
                }
                $('#soldOut').val(1);
            }
        });
    }
}
function withdraw()
{
    $('.withdraw').change(function(){
        var current_seat = $(this).closest('tr').find('.seat').html();
        $.ajax({
            type: 'post',
            url: 'registryUpdate.php',
            dataType: 'json',
            data: 'upc='+$('#upc').val()+'&seat='+current_seat+'&field='+$(this).attr('name')+'&value='+$(this).val(),
            success: function(resp) {
                    if (resp.error) {
                        showBootstrapAlert('#alert-area', 'danger', resp.error_msg);
                    } else {
                        showBootstrapPopover(elem, orig, '');
                    }
                }
        });
    });
}
function checkSoldOut()
{
    $('#first_name').change(function(){
        $.ajax({
            type: 'post',
            url: 'noauto/alertRegFull.php',
            dataType: 'json',
            data: 'upc='+$('#upc').val(),
            success: function(resp) {}
        });
    });
}
