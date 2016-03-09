var tenderEditor = (function($) {
    var mod = {};
    var ajax_url = 'TenderEditor.php';

    var _popover = function(data, elem, orig) {
        var timeout=1500;
        if (data === "") {
            data = 'Saved!';
        } else {
            elem.val(orig);
            timeout = 3000;
        }
        elem.popover({
            html: true,
            content: data,
            placement: 'auto bottom'
        });
        elem.popover('show');
        setTimeout(function(){elem.popover('destroy'); }, timeout);
    };

    mod.saveCode = function(val,t_id){
        var elem = $(this);
        var orig = this.defaultValue;
        $.ajax({
            type:'post',
            url: ajax_url,
            data: 'saveCode='+val+'&id='+t_id
        }).done(function(data){
            _popover(data, elem, orig);
        });
    };

    mod.saveName = function(val,t_id){
        var elem = $(this);
        var orig = this.defaultValue;
        $.ajax({
            type:'post',
            url: ajax_url,
            data: 'saveName='+val+'&id='+t_id
        }).done(function(data){
            _popover(data, elem, orig);
        });
    };

    mod.saveType = function(val,t_id){
        var elem = $(this);
        var orig = this.defaultValue;
        $.ajax({
            type:'post',
            url: ajax_url,
            data: 'saveType='+val+'&id='+t_id
        }).done(function(data){
            _popover(data, elem, orig);
        });
    };

    mod.saveCMsg = function(val,t_id){
        var elem = $(this);
        var orig = this.defaultValue;
        $.ajax({
            type:'post',
            url: ajax_url,
            data: 'saveCMsg='+val+'&id='+t_id
        }).done(function(data){
            _popover(data, elem, orig);
        });
    };

    mod.saveMin = function(val,t_id){
        var elem = $(this);
        var orig = this.defaultValue;
        $.ajax({
            type:'post',
            url: ajax_url,
            data: 'saveMin='+val+'&id='+t_id
        }).done(function(data){
            _popover(data, elem, orig);
        });
    };

    mod.saveMax = function(val,t_id){
        var elem = $(this);
        var orig = this.defaultValue;
        $.ajax({
            type:'post',
            url: ajax_url,
            data: 'saveMax='+val+'&id='+t_id
        }).done(function(data){
            _popover(data, elem, orig);
        });
    };

    mod.saveRLimit = function(val,t_id){
        var elem = $(this);
        var orig = this.defaultValue;
        $.ajax({
            type:'post',
            url: ajax_url,
            data: 'saveRLimit='+val+'&id='+t_id
        }).done(function(data){
            _popover(data, elem, orig);
        });
    };

    mod.saveSalesCode = function(val, t_id){
        var elem = $(this);
        var orig = this.defaultValue;
        $.ajax({
            type:'post',
            url: ajax_url,
            data: 'saveSalesCode='+val+'&id='+t_id
        }).done(function(data){
            _popover(data, elem, orig);
        });
    };

    mod.addTender = function(){
        $.ajax({
            type:'post',
            url: ajax_url,
            data:'newTender=yes'
        }).done(function(data){
            $('#mainDisplay').html(data);
        });
    };

    return mod;
}(jQuery));
