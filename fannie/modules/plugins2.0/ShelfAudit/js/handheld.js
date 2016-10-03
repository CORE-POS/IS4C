var handheld = (function($) {
    var mod = {};

    mod.paintFocus = function(elem){
        if (elem == 'upc_in'){
            $('#upc_in').addClass('focused');
            $('#cur_qty').removeClass('focused');
        } else {
            $('#cur_qty').addClass('focused');
            $('#upc_in').removeClass('focused');
        }
    };

    mod.lookupItem = function(e) {
        e.preventDefault();
        $.ajax({
            method: "get",
            data: $('#upcScanForm').serialize()
        }).done(function(data) {
            $('#qtyArea').html(data);
            if ($('#cur_qty').length > 0) {
                $('#cur_qty').focus();
                mod.paintFocus('cur_qty');
            }
        }).fail(function(err,stat,xhr) {
            $('#qtyArea').html('Error: ' + err);
        }).always(function() {
            $('#upc_in').val('');
        });

        return false;
    };

    mod.updateQty = function(amt){
        var cur = Number($('#cur_qty').val());
        if (cur + amt < 0)
            cur = 0;
        else
            cur = cur+amt;
        $('#cur_qty').val(cur);

        cur += Number($('#old-qty').html());
        $('#live-qty').html(cur);

        // save new quantity, return cursor to upc input
        var args = 'id='+$('#cur_upc').val()+'&qty='+cur+'&store='+$('#store').val();
        $.ajax({
            data: args,
            method: 'post',
            cache: false,
            error: function(){
                $('#upc_in').focus();
                mod.paintFocus('upc_in');
            },
            success: function(){
                $('#upc_in').focus();
                mod.paintFocus('upc_in');
            }
        });
    };

    mod.qtyTyped = function(ev){
        var cur = Number($('#cur_qty').val()) + Number($('#old-qty').html());
        $('#live-qty').html(cur);
        // save new quantity, return cursor to upc input
        var args = 'id='+$('#cur_upc').val()+'&qty='+cur+'&store='+$('#store').val();
        $.ajax({
            data: args,
            method: 'post',
            cache: false,
            error: function(){
            },
            success: function(){
            }
        });
        if (ev.keyCode==13){
            ev.preventDefault();
            $('#upc_in').focus();
            mod.paintFocus('upc_in');
        }
        else if (ev.keyCode >= 37 && ev.keyCode <= 40){
            $('#upc_in').focus();
            mod.paintFocus('upc_in');
        }
    };

    mod.catchTab = function(e) {
        if (e.keyCode == 9) {
            e.preventDefault();
            $('#upc_in').focus();
        }
    };

    return mod;

}(jQuery));
